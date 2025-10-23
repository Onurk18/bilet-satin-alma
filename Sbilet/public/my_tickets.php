<?php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers/ticket_helper.php';

boot_session();
if (!isset($_SESSION['user'])) redirect('/login.php?m=login_required');

expireOldTickets();

$pdo = db();
$uid = $_SESSION['user']['id'];

$sql = "
SELECT
  ti.id AS ticket_id,
  ti.status,
  ti.total_price,
  ti.created_at,
  t.id AS trip_id,
  t.departure_city, t.destination_city,
  t.departure_time, t.arrival_time,
  bc.name AS company_name,
  CASE 
    WHEN datetime(t.departure_time) <= datetime('now', '+1 hour') THEN 0
    ELSE 1
  END AS can_cancel
FROM Tickets ti
JOIN Trips t      ON t.id = ti.trip_id
JOIN Bus_Company bc ON bc.id = t.company_id
WHERE ti.user_id = :uid
ORDER BY ti.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $uid]);
$tickets = $stmt->fetchAll();

$fmtPrice = fn(int $tl) => number_format($tl, 0, ',', '.') . ' ₺';

$successMsg = $_SESSION['success_message'] ?? null;
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['success_message'], $_SESSION['form_errors']);

$statusColors = [
    'ACTIVE' => '#28a745',
    'CANCELLED' => '#dc3545',
    'EXPIRED' => '#6c757d'
];

$statusLabels = [
    'ACTIVE' => 'Aktif',
    'CANCELLED' => 'İptal Edildi',
    'EXPIRED' => 'Süresi Doldu'
];
?>
<!doctype html>
<html lang="tr">
<meta charset="utf-8">
<title>Biletlerim</title>
<style>
  .ticket-card {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: #f9f9f9;
  }
  .ticket-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
    font-size: 0.85em;
  }
  .cancel-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
  }
  .cancel-btn:hover {
    background: #c82333;
  }
  .cancel-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }
</style>
<body>
  <p><a href="/index.php">← Anasayfa</a></p>
  <h1>Biletlerim</h1>

  <?php if ($successMsg): ?>
    <div style="color:#155724; background-color:#d4edda; border:1px solid #c3e6cb; padding:12px; margin:10px 0; border-radius:4px;">
      ✓ <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div style="color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; padding:12px; margin:10px 0; border-radius:4px;">
      <?php foreach ($errors as $err): ?>
        ⚠ <?= e($err) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$tickets): ?>
    <p>Henüz biletin yok.</p>
  <?php else: ?>
    <?php foreach ($tickets as $tk): ?>
      <?php
        $s = $pdo->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id=:id ORDER BY seat_number");
        $s->execute([':id' => $tk['ticket_id']]);
        $seats = $s->fetchAll(PDO::FETCH_COLUMN);
        
        $status = $tk['status'];
        $canCancel = (int)$tk['can_cancel'] === 1 && $status === 'ACTIVE';
      ?>
      <div class="ticket-card">
        <div style="display: flex; justify-content: space-between; align-items: start;">
          <div>
            <h3 style="margin: 0 0 10px 0;"><?= e($tk['company_name']) ?></h3>
            <p style="margin: 5px 0;">
              <b><?= e($tk['departure_city']) ?></b> → <b><?= e($tk['destination_city']) ?></b>
            </p>
            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
              <?= e($tk['departure_time']) ?> → <?= e($tk['arrival_time']) ?>
            </p>
            <p style="margin: 5px 0;">
              <b>Koltuk(lar):</b> <?= e(implode(', ', $seats)) ?>
            </p>
            <p style="margin: 5px 0;">
              <b>Tutar:</b> <?= $fmtPrice((int)$tk['total_price']) ?>
            </p>
          </div>
          <div style="text-align: right;">
            <span class="ticket-status" style="background-color: <?= $statusColors[$status] ?? '#6c757d' ?>">
              <?= $statusLabels[$status] ?? $status ?>
            </span>
            <br><br>
            <?php if ($canCancel): ?>
              <form method="post" action="/cancel_ticket.php" style="display: inline;" 
                    onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?');">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ticket_id" value="<?= e($tk['ticket_id']) ?>">
                <button type="submit" class="cancel-btn">İptal Et</button>
              </form>
            <?php elseif ($status === 'ACTIVE'): ?>
              <small style="color: #999;">İptal süresi doldu</small>
            <?php endif; ?>
          </div>
        </div>
        <hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">
        <small style="color: #666;">
          PNR: <?= e($tk['ticket_id']) ?> · Satın alma: <?= e($tk['created_at']) ?>
        </small>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>