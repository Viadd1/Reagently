<?php
/**
 * Reagently: anonymising user deletion with ONE null placeholder per COMPANY.
 *
 * Preconditions: multi-company schema; InnoDB; company_id on users/projects/
 * reagents/locations; project_members and reservation composite foreign key.
 * The original user row is retained as a redacted, non-login tombstone.
 *
 * Important: ACTIVE reservations BLOCK deletion until completed/cancelled.
 * This prevents creating an orphaned active booking that no real user can use.
 */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
require_once BASE_PATH . '/includes/user_management_csrf.php';

$companyId = tenant_require_user($link);
reagently_require_user_post_csrf();

$viewerId = (int) $_SESSION['user_id'];
$isCompanyAdmin = ($_SESSION['role'] ?? '') === 'admin';
$targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!is_int($targetId)) {
    http_response_code(400);
    exit('Invalid user ID.');
}
if ($targetId !== $viewerId && !$isCompanyAdmin) {
    http_response_code(403);
    exit('You can delete your own account or, as a Company Admin, another ordinary user in your company.');
}

/** Check optional compatibility tables/columns without breaking older exports. */
function reagently_schema_has(mysqli $db, string $table, ?string $column = null): bool {
    if ($column === null) {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
        $stmt->bind_param('s', $table);
    } else {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        $stmt->bind_param('ss', $table, $column);
    }
    $stmt->execute();
    $found = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    return $found;
}

try {
    $link->begin_transaction();

    // Serialize deletion/placeholder creation across users at the same company.
    $stmt = $link->prepare("SELECT company_id FROM companies WHERE company_id = ? AND status = 'ACTIVE' FOR UPDATE");
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$company) {
        throw new DomainException('Your company is not active.');
    }

    // Never let a Company Admin delete another admin, a platform admin,
    // an already deleted account or a null placeholder.
    $stmt = $link->prepare(
        "SELECT user_id, username, role FROM users
         WHERE user_id = ? AND company_id = ? FOR UPDATE"
    );
    $stmt->bind_param('ii', $targetId, $companyId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target || $target['role'] !== 'user' || str_starts_with($target['username'], 'null_company_')) {
        throw new DomainException('Only ordinary user accounts in your company can be deleted.');
    }
    $oldUsername = (string) $target['username'];

    // Lock memberships first: a new reservation needs the (project_id,user_id)
    // foreign-key parent row, so it cannot race past the ACTIVE check.
    $stmt = $link->prepare('SELECT project_id FROM project_members WHERE user_id = ? FOR UPDATE');
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $membershipRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Defensive check: existing cross-tenant memberships must be repaired,
    // not silently modified during a company-scoped deletion.
    $stmt = $link->prepare(
        'SELECT COUNT(*) AS n FROM project_members pm
         JOIN projects p ON p.project_id = pm.project_id
         WHERE pm.user_id = ? AND p.company_id <> ?'
    );
    $stmt->bind_param('ii', $targetId, $companyId);
    $stmt->execute();
    $badMemberships = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    if ($badMemberships !== 0) {
        throw new RuntimeException('Cross-company project membership detected. Fix data before deletion.');
    }

    // Any reservation owned by the target must point to this company's
    // project, reagent AND storage location. Never move inconsistent data.
    $stmt = $link->prepare(
        'SELECT COUNT(*) AS n FROM reservations res
         JOIN projects p ON p.project_id = res.project_id
         JOIN reagent_lots l ON l.lot_id = res.lot_id
         JOIN reagents r ON r.reagent_id = l.reagent_id
         JOIN locations loc ON loc.location_id = l.location_id
         WHERE res.user_id = ? AND
           (p.company_id <> ? OR r.company_id <> ? OR loc.company_id <> ?)'
    );
    $stmt->bind_param('iiii', $targetId, $companyId, $companyId, $companyId);
    $stmt->execute();
    $invalidReservations = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    if ($invalidReservations !== 0) {
        throw new RuntimeException('Cross-company reservation detected. Fix data before deletion.');
    }

    // ACTIVE reservations must be handled by the user or a separately
    // authorised administrator. We do not transfer unusable ACTIVE bookings.
    $stmt = $link->prepare(
        "SELECT reservation_id FROM reservations WHERE user_id = ? AND status = 'ACTIVE' LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $hasActiveReservations = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($hasActiveReservations) {
        throw new DomainException('Please complete or cancel all ACTIVE reservations before deleting the account.');
    }

    // The users.username and users.email columns are globally UNIQUE, so
    // each company's reserved placeholder has a distinct technical identity.
    // Underscores are disallowed by the public alphanumeric username validator.
    $nullUsername = 'null_company_' . $companyId;
    $nullEmail = 'null_company_' . $companyId . '@example.invalid';
    $stmt = $link->prepare(
        'SELECT user_id, email, role, approved, is_verified, company_id
         FROM users WHERE username = ? FOR UPDATE'
    );
    $stmt->bind_param('s', $nullUsername);
    $stmt->execute();
    $placeholder = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$placeholder) {
        $blockedPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $stmt = $link->prepare(
            "INSERT INTO users
                (username, email, password, role, company_id, approved, is_verified)
             VALUES (?, ?, ?, 'deleted', ?, 0, 0)"
        );
        $stmt->bind_param('sssi', $nullUsername, $nullEmail, $blockedPassword, $companyId);
        $stmt->execute();
        $nullUserId = (int)$link->insert_id;
        $stmt->close();
    } else {
        if ((int)$placeholder['company_id'] !== $companyId ||
            $placeholder['email'] !== $nullEmail ||
            $placeholder['role'] !== 'deleted' ||
            (int)$placeholder['approved'] !== 0 ||
            (int)$placeholder['is_verified'] !== 0) {
            throw new RuntimeException('Existing company null user is not configured safely.');
        }
        $nullUserId = (int)$placeholder['user_id'];
    }
    if ($nullUserId === $targetId) {
        throw new RuntimeException('The null placeholder cannot be the target account.');
    }

    // 1. Create null membership in each of this user's company projects.
    //    Required BEFORE transferring reservations because reservations has
    //    a composite FK to project_members(project_id,user_id).
    $stmt = $link->prepare(
        'INSERT IGNORE INTO project_members (project_id, user_id)
         SELECT DISTINCT pm.project_id, ?
         FROM project_members pm
         JOIN projects p ON p.project_id = pm.project_id
         WHERE pm.user_id = ? AND p.company_id = ?'
    );
    $stmt->bind_param('iii', $nullUserId, $targetId, $companyId);
    $stmt->execute();
    $stmt->close();

    // 2. Transfer historical reservations. ACTIVE ones were blocked above.
    $stmt = $link->prepare(
        'UPDATE reservations res
         JOIN projects p ON p.project_id = res.project_id
         SET res.user_id = ?
         WHERE res.user_id = ? AND p.company_id = ?'
    );
    $stmt->bind_param('iii', $nullUserId, $targetId, $companyId);
    $stmt->execute();
    $stmt->close();

    // 3. Preserve inventory history, with its owner anonymised. Verify both
    //    reagent and physical storage location belong to the SAME company.
    $stmt = $link->prepare(
        'UPDATE inventory_transactions it
         JOIN reagent_lots l ON l.lot_id = it.lot_id
         JOIN reagents r ON r.reagent_id = l.reagent_id
         JOIN locations loc ON loc.location_id = l.location_id
         SET it.user_id = ?
         WHERE it.user_id = ? AND r.company_id = ? AND loc.company_id = ?'
    );
    $stmt->bind_param('iiii', $nullUserId, $targetId, $companyId, $companyId);
    $stmt->execute();
    $stmt->close();

    // 4. Original project's deletion policy: remove user-specific order
    //    requests and nicknames (these operations are destructive).
    $stmt = $link->prepare('DELETE FROM order_requests WHERE user_id = ?');
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $stmt->close();

    $stmt = $link->prepare(
        'DELETE rn FROM reagent_nicknames rn
         JOIN reagents r ON r.reagent_id = rn.reagent_id
         WHERE rn.created_by = ? AND r.company_id = ?'
    );
    $stmt->bind_param('ii', $targetId, $companyId);
    $stmt->execute();
    $stmt->close();

    // 5. Remove project membership only for this company.
    $stmt = $link->prepare(
        'DELETE pm FROM project_members pm
         JOIN projects p ON p.project_id = pm.project_id
         WHERE pm.user_id = ? AND p.company_id = ?'
    );
    $stmt->bind_param('ii', $targetId, $companyId);
    $stmt->execute();
    $stmt->close();

    // 6. Remove transient authentication/personal settings records if those
    //    tables exist in this project's database version.
    if (reagently_schema_has($link, 'password_resets')) {
        $stmt = $link->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $stmt->close();
    }
    if (reagently_schema_has($link, 'cookie_consent')) {
        $stmt = $link->prepare('DELETE FROM cookie_consent WHERE user_id = ?');
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $stmt->close();
    }
    if (reagently_schema_has($link, 'users', 'supervisor_id')) {
        $stmt = $link->prepare(
            'UPDATE users SET supervisor_id = NULL WHERE supervisor_id = ? AND company_id = ?'
        );
        $stmt->bind_param('ii', $targetId, $companyId);
        $stmt->execute();
        $stmt->close();
    }
    if (reagently_schema_has($link, 'announcements', 'company_id')) {
        $stmt = $link->prepare(
            "UPDATE announcements SET author_name = 'Deleted user'
             WHERE company_id = ? AND author_name = ?"
        );
        $stmt->bind_param('is', $companyId, $oldUsername);
        $stmt->execute();
        $stmt->close();
    }

    // 7. Fail safely if any old relationship remains, including a corrupted
    //    cross-tenant relationship which the above statements may not touch.
    foreach (['reservations', 'project_members', 'inventory_transactions',
              'order_requests', 'reagent_nicknames'] as $table) {
        $ownerColumn = ($table === 'reagent_nicknames') ? 'created_by' : 'user_id';
        // Fixed server-side names; never built from POST/GET input.
        $stmt = $link->prepare("SELECT COUNT(*) AS n FROM {$table} WHERE {$ownerColumn} = ?");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $remaining = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
        if ($remaining !== 0) {
            throw new RuntimeException('Account still has linked records. Deletion was rolled back.');
        }
    }

    // 8. Anonymising soft deletion: remove direct identifiers, retain user PK
    //    and company_id for referential integrity. The original user cannot
    //    log in, and admin/view_users.php excludes the role='deleted' row.
    $redactedUsername = 'deleted_user_' . $targetId;
    $redactedEmail = 'deleted_user_' . $targetId . '@example.invalid';
    $unusablePassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $stmt = $link->prepare(
        "UPDATE users SET
            username = ?, email = ?, password = ?,
            firstname = NULL, lastname = NULL, lab = NULL,
            role = 'deleted', approved = 0, is_verified = 0,
            verification_token = NULL, token_expires_at = NULL
         WHERE user_id = ? AND company_id = ? AND role = 'user'"
    );
    $stmt->bind_param('sssii', $redactedUsername, $redactedEmail, $unusablePassword, $targetId, $companyId);
    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();
    if ($updated !== 1) {
        throw new RuntimeException('Unable to anonymise account.');
    }

    // Legacy schemas may still store a supervisor for the deleted user.
    if (reagently_schema_has($link, 'users', 'supervisor_id')) {
        $stmt = $link->prepare('UPDATE users SET supervisor_id = NULL WHERE user_id = ? AND company_id = ?');
        $stmt->bind_param('ii', $targetId, $companyId);
        $stmt->execute();
        $stmt->close();
    }

    $link->commit();
} catch (Throwable $e) {
    $link->rollback();
    if ($e instanceof DomainException) {
        http_response_code(409);
        exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    error_log('Reagently delete user failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to delete account. No database changes were saved.');
} finally {
    $link->close();
}

if ($targetId === $viewerId) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}
header('Location: ' . BASE_URL . 'admin/view_users.php?msg=' . rawurlencode('Account deleted; historical records anonymised within this company.'));
exit;
