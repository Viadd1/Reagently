<?php
/**
 * Reagently Phase 3 STARTER — shared company identity and ownership guards.
 * THIS FILE DOES NOT isolate the application by itself. Every read/write
 * endpoint must use tenant-scoped SQL before deployment.
 *
 * Prerequisites: init.php, db.php, users/companies from Phase 1/2.
 */

declare(strict_types=1);

/**
 * Return the current database-verified company ID.
 * Never trust company_id, role or approved in client input or stale sessions.
 */
function tenant_require_user(mysqli $db): int
{
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        exit('Please log in to access this resource.');
    }

    $userId = (int) $_SESSION['user_id'];
    $stmt = $db->prepare(
        'SELECT u.role, u.company_id, u.approved, u.is_verified, c.status
         FROM users AS u
         LEFT JOIN companies AS c ON c.company_id = u.company_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account ||
        !in_array($account['role'], ['admin', 'user'], true) ||
        (int)$account['approved'] !== 1 ||
        (int)$account['is_verified'] !== 1 ||
        $account['company_id'] === null ||
        $account['status'] !== 'ACTIVE') {
        http_response_code(403);
        exit('Access denied: verified, approved user at an ACTIVE company required.');
    }

    // Session values are merely presentation hints; the database is authoritative.
    $_SESSION['role'] = $account['role'];
    $_SESSION['company_id'] = (int)$account['company_id'];
    return (int)$account['company_id'];
}

function tenant_require_admin(mysqli $db): int
{
    $companyId = tenant_require_user($db);
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Company admin role required.');
    }
    return $companyId;
}

/** Reagent lot must belong to the user's company through BOTH reagent and location. */
function tenant_lot_belongs_to_company(mysqli $db, int $lotId, int $companyId): bool
{
    $stmt = $db->prepare(
        'SELECT 1
         FROM reagent_lots AS l
         JOIN reagents AS r ON r.reagent_id = l.reagent_id
         JOIN locations AS loc ON loc.location_id = l.location_id
         WHERE l.lot_id = ?
           AND r.company_id = ?
           AND loc.company_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('iii', $lotId, $companyId, $companyId);
    $stmt->execute();
    $isOwned = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    return $isOwned;
}

function tenant_project_belongs_to_company(mysqli $db, int $projectId, int $companyId): bool
{
    $stmt = $db->prepare('SELECT 1 FROM projects WHERE project_id = ? AND company_id = ? LIMIT 1');
    $stmt->bind_param('ii', $projectId, $companyId);
    $stmt->execute();
    $isOwned = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    return $isOwned;
}

/**
 * Ownership checks above are helpers, NOT substitutes for tenant-scoped queries
 * inside each transaction. In particular, lock the lot row and revalidate
 * company, project membership and status INSIDE the transaction before writing.
 */

/** Shared CSRF token for company forms. */
function tenant_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
/** Validate a state-changing form, rejecting token arrays and missing tokens. */
function tenant_require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('POST required.');
    }
    $expected = $_SESSION['csrf_token'] ?? null;
    $supplied = $_POST['csrf_token'] ?? null;
    if (!is_string($expected) || $expected === '' || !is_string($supplied) ||
        !hash_equals($expected, $supplied)) {
        http_response_code(403);
        exit('Invalid CSRF token. Reload the form and try again.');
    }
}
