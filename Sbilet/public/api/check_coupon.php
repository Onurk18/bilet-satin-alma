<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/db.php';

header('Content-Type: application/json');
boot_session();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapmalısınız']);
    exit;
}

$couponCode = strtoupper(trim((string)($_GET['code'] ?? '')));
$tripId = trim((string)($_GET['trip_id'] ?? ''));
$userId = $_SESSION['user']['id'];

if ($couponCode === '' || $tripId === '') {
    echo json_encode(['success' => false, 'error' => 'Kupon kodu veya sefer ID eksik']);
    exit;
}

$pdo = db();

$tripStmt = $pdo->prepare("SELECT company_id FROM Trips WHERE id = :id LIMIT 1");
$tripStmt->execute([':id' => $tripId]);
$trip = $tripStmt->fetch();

if (!$trip) {
    echo json_encode(['success' => false, 'error' => 'Sefer bulunamadı']);
    exit;
}

$couponStmt = $pdo->prepare("
    SELECT id, code, discount, company_id, usage_limit, expire_date
    FROM Coupons
    WHERE code = :code
    LIMIT 1
");
$couponStmt->execute([':code' => $couponCode]);
$coupon = $couponStmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'error' => 'Kupon kodu geçersiz']);
    exit;
}

$errors = [];

if ($coupon['company_id'] !== null && $coupon['company_id'] !== $trip['company_id']) {
    $errors[] = 'Bu kupon bu firma için geçerli değil';
}

$today = date('Y-m-d');
if ($coupon['expire_date'] < $today) {
    $errors[] = 'Kuponun süresi dolmuş (' . $coupon['expire_date'] . ')';
}

$usageStmt = $pdo->prepare("
    SELECT COUNT(*) as used_count
    FROM User_Coupons
    WHERE coupon_id = :cid
");
$usageStmt->execute([':cid' => $coupon['id']]);
$usageData = $usageStmt->fetch();
$usedCount = (int)$usageData['used_count'];

if ($usedCount >= (int)$coupon['usage_limit']) {
    $errors[] = 'Kupon kullanım limitine ulaşmış';
}

$userUsageStmt = $pdo->prepare("
    SELECT COUNT(*) as user_used
    FROM User_Coupons
    WHERE coupon_id = :cid AND user_id = :uid
");
$userUsageStmt->execute([
    ':cid' => $coupon['id'],
    ':uid' => $userId
]);
$userUsageData = $userUsageStmt->fetch();
if ((int)$userUsageData['user_used'] > 0) {
    $errors[] = 'Bu kuponu daha önce kullanmışsınız';
}

if ($errors) {
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    exit;
}

echo json_encode([
    'success' => true,
    'discount' => (int)$coupon['discount'],
    'message' => '%' . $coupon['discount'] . ' indirim uygulanacak'
]);