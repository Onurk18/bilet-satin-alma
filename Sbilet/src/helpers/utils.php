<?php
// src/helpers/utils.php
declare(strict_types=1);

function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** Güvenli session başlat */
function boot_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $cookie = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookie['path'],
        'domain'   => $cookie['domain'],
        'secure'   => !empty($_SERVER['HTTPS']), // HTTPS varsa true
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SBILETSESS');
    session_start();
}

/** Basit XSS koruması için HTML escape */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect helper (ve çık) */
function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

/** CSRF token (session’da sakla) */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = uuidv4();
    }
    return $_SESSION['csrf_token'];
}

/** CSRF doğrulama (POST’ta çağır) */
function csrf_check(): void {
    $ok = isset($_POST['_token'], $_SESSION['csrf_token'])
       && hash_equals($_SESSION['csrf_token'], (string)$_POST['_token']);
    if (!$ok) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}
