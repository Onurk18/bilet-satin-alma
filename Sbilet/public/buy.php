<?php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/db.php';

boot_session();
if (!isset($_SESSION['user'])) {
    redirect('/login.php?m=login_required');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
csrf_check();

$tripId = trim((string)($_POST['trip_id'] ?? ''));
$seats  = $_POST['seats'] ?? [];
$couponCode = strtoupper(trim((string)($_POST['coupon_code'] ?? '')));

if ($tripId === '' || !is_array($seats) || count($seats) === 0) {
    $_SESSION['form_errors'] = ['Seçili koltuk yok.'];
    redirect('/trip.php?id=' . urlencode($tripId));
}


$seats = array_values(array_unique(array_map('intval', $seats)));

$pdo = db();


$trip = $pdo->prepare("SELECT id, company_id, price AS price_tl, capacity, departure_time FROM Trips WHERE id=:id LIMIT 1");
$trip->execute([':id' => $tripId]);
$t = $trip->fetch();
if (!$t) { http_response_code(404); exit('Sefer bulunamadı'); }


$capacity = (int)$t['capacity'];
foreach ($seats as $sn) {
    if ($sn < 1 || $sn > $capacity) {
        $_SESSION['form_errors'] = ['Geçersiz koltuk numarası: ' . $sn];
        redirect('/trip.php?id=' . urlencode($tripId));
    }
}


$unitPrice = (int)$t['price_tl'];
$totalPrice = $unitPrice * count($seats);
$discountAmount = 0;
$couponId = null;


if ($couponCode !== '') {
    $couponStmt = $pdo->prepare("
        SELECT id, discount, company_id, usage_limit, expire_date
        FROM Coupons
        WHERE code = :code
        LIMIT 1
    ");
    $couponStmt->execute([':code' => $couponCode]);
    $coupon = $couponStmt->fetch();
    
    if (!$coupon) {
        $_SESSION['form_errors'] = ['Kupon kodu geçersiz: ' . $couponCode];
        redirect('/trip.php?id=' . urlencode($tripId));
    }
    

    $errors = [];
    
    if ($coupon['company_id'] !== null && $coupon['company_id'] !== $t['company_id']) {
        $errors[] = 'Bu kupon bu firma için geçerli değil';
    }
    
    $today = date('Y-m-d');
    if ($coupon['expire_date'] < $today) {
        $errors[] = 'Kuponun süresi dolmuş';
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
        ':uid' => $_SESSION['user']['id']
    ]);
    $userUsageData = $userUsageStmt->fetch();
    if ((int)$userUsageData['user_used'] > 0) {
        $errors[] = 'Bu kuponu daha önce kullanmışsınız';
    }
    
    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        redirect('/trip.php?id=' . urlencode($tripId));
    }
    
    $discountPercent = (int)$coupon['discount'];
    $discountAmount = (int)($totalPrice * $discountPercent / 100);
    $totalPrice -= $discountAmount;
    $couponId = $coupon['id'];
}

$uid = $_SESSION['user']['id'];
$u = $pdo->prepare("SELECT id, balance FROM User WHERE id=:id LIMIT 1");
$u->execute([':id' => $uid]);
$user = $u->fetch();
if (!$user) { http_response_code(401); exit('Kullanıcı bulunamadı'); }
if ((int)$user['balance'] < $totalPrice) {
    $_SESSION['form_errors'] = [
        'Bakiyen yetersiz. Toplam: ' . number_format($totalPrice, 0, ',', '.') . ' ₺',
        'Mevcut bakiye: ' . number_format((int)$user['balance'], 0, ',', '.') . ' ₺'
    ];
    redirect('/trip.php?id=' . urlencode($tripId));
}

$in = implode(',', array_fill(0, count($seats), '?'));
$conf = $pdo->prepare("
    SELECT bs.seat_number
    FROM Booked_Seats bs
    JOIN Tickets ti ON ti.id = bs.ticket_id
    WHERE ti.trip_id = ? AND ti.status='ACTIVE' AND bs.seat_number IN ($in)
");
$conf->execute(array_merge([$tripId], $seats));
$already = $conf->fetchAll(PDO::FETCH_COLUMN);
if ($already) {
    $_SESSION['form_errors'] = ['Seçili koltuklardan bazıları dolu: ' . implode(', ', $already)];
    redirect('/trip.php?id=' . urlencode($tripId));
}

$pdo->beginTransaction();
try {
    $ticketId = uuidv4();
    $insT = $pdo->prepare("
        INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at)
        VALUES (:id, :trip, :user, 'ACTIVE', :price, datetime('now'))
    ");
    $insT->execute([
        ':id'    => $ticketId,
        ':trip'  => $tripId,
        ':user'  => $uid,
        ':price' => $totalPrice,
    ]);

    $insS = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at)
                           VALUES (:id, :tid, :sn, datetime('now'))");
    foreach ($seats as $sn) {
        $seatId = uuidv4();
        $insS->execute([
            ':id'  => $seatId,
            ':tid' => $ticketId,
            ':sn'  => $sn,
        ]);
    }

    
    $upd = $pdo->prepare("UPDATE User SET balance = balance - :amt WHERE id=:id");
    $upd->execute([':amt' => $totalPrice, ':id' => $uid]);


    if ($couponId !== null) {
        $insCoupon = $pdo->prepare("
            INSERT INTO User_Coupons (id, coupon_id, user_id, created_at)
            VALUES (:id, :cid, :uid, datetime('now'))
        ");
        $insCoupon->execute([
            ':id'  => uuidv4(),
            ':cid' => $couponId,
            ':uid' => $uid,
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit('Satın alma hatası: ' . e($e->getMessage()));
}


$_SESSION['success'] = $discountAmount > 0 
    ? 'Bilet satın alındı! ' . number_format($discountAmount, 0, ',', '.') . ' ₺ indirim uygulandı.'
    : 'Bilet başarıyla satın alındı!';
redirect('/my_tickets.php');