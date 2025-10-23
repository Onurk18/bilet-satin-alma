<?php

declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers/ticket_helper.php';

boot_session();


if (!isset($_SESSION['user'])) {
    redirect('/login.php?m=login_required');
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

csrf_check();

$ticketId = trim((string)($_POST['ticket_id'] ?? ''));
if ($ticketId === '') {
    http_response_code(400);
    exit('Geçersiz bilet ID');
}

$pdo = db();
$uid = $_SESSION['user']['id'];


$stmt = $pdo->prepare("SELECT user_id, status, total_price FROM Tickets WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(404);
    exit('Bilet bulunamadı');
}

if ($ticket['user_id'] !== $uid) {
    http_response_code(403);
    exit('Bu bilet size ait değil');
}

$cancelCheck = canCancelTicket($ticketId);

if (!$cancelCheck['can_cancel']) {
    $_SESSION['form_errors'] = [$cancelCheck['reason']];
    redirect('/my_tickets.php');
}

$pdo->beginTransaction();
try {
    $updateTicket = $pdo->prepare("UPDATE Tickets SET status = 'CANCELLED' WHERE id = :id");
    $updateTicket->execute([':id' => $ticketId]);
    
    $refundAmount = (int)$ticket['total_price'];
    $updateBalance = $pdo->prepare("UPDATE User SET balance = balance + :amount WHERE id = :uid");
    $updateBalance->execute([':amount' => $refundAmount, ':uid' => $uid]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Bilet başarıyla iptal edildi. ' . number_format($refundAmount, 0, ',', '.') . ' ₺ hesabınıza iade edildi.';
    
} catch (Throwable $e) {
    $pdo->rollBack();
    $_SESSION['form_errors'] = ['İptal işlemi başarısız: ' . $e->getMessage()];
}

redirect('/my_tickets.php');