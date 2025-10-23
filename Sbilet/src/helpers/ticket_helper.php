<?php
// src/helpers/ticket_helper.php
declare(strict_types=1);

// db.php burada require edilmeyecek, çağıran sayfa require etmeli

/**
 * Geçmiş seferlere ait ACTIVE biletleri EXPIRED yapar
 * Bu fonksiyon biletleri görüntülemeden önce çağrılmalı
 */
function expireOldTickets(): void {
    $pdo = db();
    
    // Kalkış zamanı geçmiş seferlere ait ACTIVE biletleri EXPIRED yap
    $sql = "
        UPDATE Tickets 
        SET status = 'EXPIRED'
        WHERE status = 'ACTIVE' 
        AND trip_id IN (
            SELECT id 
            FROM Trips 
            WHERE datetime(departure_time) <= datetime('now')
        )
    ";
    
    $pdo->exec($sql);
}

/**
 * Bir biletin iptal edilip edilemeyeceğini kontrol eder
 * Kalkışa 1 saatten az kaldıysa false döner
 */
function canCancelTicket(string $ticketId): array {
    $pdo = db();
    
    $stmt = $pdo->prepare("
        SELECT 
            ti.id,
            ti.status,
            t.departure_time,
            CASE 
                WHEN datetime(t.departure_time) <= datetime('now') THEN 'EXPIRED'
                WHEN datetime(t.departure_time) <= datetime('now', '+1 hour') THEN 'TOO_LATE'
                WHEN ti.status != 'ACTIVE' THEN 'NOT_ACTIVE'
                ELSE 'OK'
            END AS cancel_status
        FROM Tickets ti
        JOIN Trips t ON t.id = ti.trip_id
        WHERE ti.id = :id
        LIMIT 1
    ");
    
    $stmt->execute([':id' => $ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        return ['can_cancel' => false, 'reason' => 'Bilet bulunamadı.'];
    }
    
    $status = $ticket['cancel_status'];
    
    if ($status === 'EXPIRED') {
        return ['can_cancel' => false, 'reason' => 'Bu bilet süresi dolmuş.'];
    }
    
    if ($status === 'TOO_LATE') {
        return ['can_cancel' => false, 'reason' => 'Kalkışa 1 saatten az kaldı. İptal edilemez.'];
    }
    
    if ($status === 'NOT_ACTIVE') {
        return ['can_cancel' => false, 'reason' => 'Bu bilet zaten iptal edilmiş veya geçersiz.'];
    }
    
    return ['can_cancel' => true, 'ticket' => $ticket];
}