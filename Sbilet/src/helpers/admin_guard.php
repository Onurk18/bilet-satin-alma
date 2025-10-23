<?php
// src/helpers/admin_guard.php
declare(strict_types=1);
require_once __DIR__ . '/utils.php';

function require_admin(): void {
    boot_session();
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'ADMIN') {
        http_response_code(403);
        exit('Forbidden (ADMIN required)');
    }
}
