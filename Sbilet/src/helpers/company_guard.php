<?php
// src/helpers/company_guard.php
declare(strict_types=1);
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../db.php';


function require_company_admin(): array {
    boot_session();
    $u = $_SESSION['user'] ?? null;
    
    if (!$u || ($u['role'] ?? '') !== 'COMPANY_ADMIN') {
        http_response_code(403);
        exit('Forbidden (COMPANY_ADMIN required)');
    }
    
    $pdo = db();
    $stmt = $pdo->prepare("SELECT company_id FROM User WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $u['id']]);
    $row = $stmt->fetch();
    
    if (!$row || !$row['company_id']) {
        http_response_code(403);
        exit('Bu hesap herhangi bir firmaya atanmamış');
    }
    
    return [
        'user_id' => $u['id'],
        'company_id' => $row['company_id'],
        'full_name' => $u['full_name'],
        'email' => $u['email'],
    ];
}


function check_trip_ownership(string $tripId, string $companyId): bool {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT company_id FROM Trips WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $tripId]);
    $trip = $stmt->fetch();
    
    return $trip && $trip['company_id'] === $companyId;
}