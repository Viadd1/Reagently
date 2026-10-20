<?php
// Legacy registration URL: use the company-aware registration flow.
require_once __DIR__ . '/../init.php';
header('Location: ' . BASE_URL . 'authentication/register_company.php', true, 302);
exit;
