<?php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/db.php';

boot_session();

if (!isset($_SESSION['user'])) {
    redirect('/login.php?m=login_required');
}

$ticketId = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($ticketId === '') {
    http_response_code(400);
    exit('Bilet ID gerekli');
}

$pdo = db();
$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT 
        ti.id, ti.user_id, ti.status, ti.total_price, ti.created_at,
        t.departure_city, t.destination_city, t.departure_time, t.arrival_time,
        bc.name AS company_name,
        u.full_name, u.email
    FROM Tickets ti
    JOIN Trips t ON t.id = ti.trip_id
    JOIN Bus_Company bc ON bc.id = t.company_id
    JOIN User u ON u.id = ti.user_id
    WHERE ti.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(404);
    exit('Bilet bulunamadı');
}

if ($ticket['user_id'] !== $userId) {
    http_response_code(403);
    exit('Bu bilete erişim yetkiniz yok');
}

$seatsStmt = $pdo->prepare("
    SELECT seat_number 
    FROM Booked_Seats 
    WHERE ticket_id = :id 
    ORDER BY seat_number
");
$seatsStmt->execute([':id' => $ticketId]);
$seats = $seatsStmt->fetchAll(PDO::FETCH_COLUMN);

$fmtPrice = fn(int $tl) => number_format($tl, 0, ',', '.') . ' ₺';

?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bilet - <?= e($ticketId) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
  font-family: Arial, sans-serif;
  background: #f5f5f5;
  padding: 2rem;
}

.ticket-container {
  max-width: 800px;
  margin: 0 auto;
  background: white;
  border: 3px solid #667eea;
  border-radius: 10px;
  overflow: hidden;
}

.ticket-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  text-align: center;
}

.ticket-header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.ticket-body {
  padding: 2rem;
}

.section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px dashed #ddd;
}

.section:last-child {
  border-bottom: none;
}

.section h2 {
  color: #667eea;
  margin-bottom: 1rem;
  font-size: 1.2rem;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-item {
  padding: 1rem;
  background: #f9f9f9;
  border-left: 4px solid #667eea;
}

.info-item label {
  display: block;
  font-size: 0.85rem;
  color: #888;
  margin-bottom: 0.3rem;
  text-transform: uppercase;
  font-weight: bold;
}

.info-item .value {
  font-size: 1.1rem;
  color: #333;
  font-weight: bold;
}

.seats-box {
  background: #e3f2fd;
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
}

.seats-box .seats {
  font-size: 2rem;
  font-weight: bold;
  color: #1565c0;
  margin-top: 0.5rem;
}

.status-box {
  text-align: center;
  padding: 1rem;
  background: #d4edda;
  color: #155724;
  border-radius: 8px;
  font-weight: bold;
  font-size: 1.2rem;
}

.status-cancelled {
  background: #f8d7da;
  color: #721c24;
}

.qr-placeholder {
  text-align: center;
  padding: 2rem;
  background: #f9f9f9;
  border: 2px dashed #ddd;
  border-radius: 8px;
}

.qr-placeholder .box {
  width: 150px;
  height: 150px;
  background: #e0e0e0;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  color: #666;
}

.footer {
  text-align: center;
  padding: 2rem;
  background: #f9f9f9;
  color: #666;
  font-size: 0.9rem;
}

.print-btn {
  text-align: center;
  margin-bottom: 1rem;
}

.print-btn button {
  background: #667eea;
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: bold;
  cursor: pointer;
}

@media print {
  body { background: white; padding: 0; }
  .print-btn { display: none; }
}
</style>
</head>
<body>

<div class="print-btn">
  <button onclick="window.print()">🖨️ Yazdır / PDF Olarak Kaydet</button>
</div>

<div class="ticket-container">
  <div class="ticket-header">
    <h1>🎫 OTOBÜS BİLETİ</h1>
    <p style="opacity:0.9;">Sbilet - Online Bilet Sistemi</p>
  </div>

  <div class="ticket-body">
    <div class="section">
      <h2>👤 Yolcu Bilgileri</h2>
      <div class="info-grid">
        <div class="info-item">
          <label>Ad Soyad</label>
          <div class="value"><?= e($ticket['full_name']) ?></div>
        </div>
        <div class="info-item">
          <label>E-posta</label>
          <div class="value"><?= e($ticket['email']) ?></div>
        </div>
      </div>
    </div>

    <div class="section">
      <h2>🚌 Sefer Bilgileri</h2>
      <div class="info-grid">
        <div class="info-item">
          <label>Firma</label>
          <div class="value"><?= e($ticket['company_name']) ?></div>
        </div>
        <div class="info-item">
          <label>PNR Kodu</label>
          <div class="value"><?= e($ticketId) ?></div>
        </div>
        <div class="info-item">
          <label>Kalkış</label>
          <div class="value"><?= e($ticket['departure_city']) ?></div>
          <small><?= e($ticket['departure_time']) ?></small>
        </div>
        <div class="info-item">
          <label>Varış</label>
          <div class="value"><?= e($ticket['destination_city']) ?></div>
          <small><?= e($ticket['arrival_time']) ?></small>
        </div>
      </div>
    </div>

    <div class="section">
      <h2>🪑 Koltuk ve Ücret</h2>
      <div class="info-grid">
        <div class="seats-box">
          <label>Koltuk Numarası</label>
          <div class="seats"><?= e(implode(', ', $seats)) ?></div>
        </div>
        <div class="info-item">
          <label>Toplam Ücret</label>
          <div class="value" style="font-size:1.5rem; color:#667eea;">
            <?= $fmtPrice((int)$ticket['total_price']) ?>
          </div>
          <small>Satın alma: <?= e($ticket['created_at']) ?></small>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="status-box <?= $ticket['status'] !== 'ACTIVE' ? 'status-cancelled' : '' ?>">
        <?= $ticket['status'] === 'ACTIVE' ? '✓ AKTİF BİLET' : '✗ İPTAL EDİLMİŞ' ?>
      </div>
    </div>

    <div class="section">
      <div class="qr-placeholder">
        <div class="box">QR KOD</div>
        <small>Binişte gösteriniz</small>
      </div>
    </div>
  </div>

  <div class="footer">
    <p><strong>Sbilet</strong> - Güvenli ve hızlı bilet sistemi</p>
    <p style="margin-top:0.5rem; font-size:0.85rem;">
      Bu bilet resmi bir seyahat belgesidir. Seyahat sırasında yanınızda bulundurunuz.
    </p>
  </div>
</div>

<script>
</script>

</body>
</html>