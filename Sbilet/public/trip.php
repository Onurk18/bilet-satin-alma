<?php
// public/trip.php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/controllers/TripController.php';

boot_session();

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($id === '') { http_response_code(400); exit('Geçersiz istek: id gerekli'); }

$trip = TripController::getDetail($id);
if (!$trip) { http_response_code(404); exit('Sefer bulunamadı'); }

$fmtPrice = fn(int $tl) => number_format($tl, 0, ',', '.') . ' ₺';
$capacity = (int)$trip['capacity'];
$booked = array_flip($trip['booked_seats']);
$cols = 4;
$user = $_SESSION['user'] ?? null;
$errors = $_SESSION['form_errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['form_errors'], $_SESSION['success']);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($trip['company_name']) ?> | <?= e($trip['departure_city']) ?> → <?= e($trip['destination_city']) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
  background: #f5f5f5;
  color: #333;
}

.navbar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navbar a {
  color: white;
  text-decoration: none;
  font-size: 1rem;
}

.container {
  max-width: 1400px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.alert-error {
  background: #fee;
  border: 1px solid #fcc;
  color: #c00;
}

.alert-success {
  background: #efe;
  border: 1px solid #cfc;
  color: #060;
}

.alert ul {
  margin: 0.5rem 0 0 1.5rem;
}

.trip-card {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.trip-header h1 {
  color: #667eea;
  margin-bottom: 0.5rem;
  font-size: 1.8rem;
}

.trip-route {
  font-size: 1.5rem;
  color: #555;
  margin-bottom: 1.5rem;
}

.trip-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.info-item label {
  display: block;
  font-size: 0.85rem;
  color: #888;
  margin-bottom: 0.3rem;
  text-transform: uppercase;
  font-weight: 600;
}

.info-item .value {
  font-size: 1.2rem;
  font-weight: bold;
  color: #333;
}

.login-warning {
  background: #fff3cd;
  border: 1px solid #ffc107;
  color: #856404;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

/* MAIN LAYOUT: Sol taraf (koltuklar) + Sağ taraf (özet) */
.main-content {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
}

@media (max-width: 1024px) {
  .main-content {
    grid-template-columns: 1fr;
  }
}

/* SOL TARAF - KOLTUKLAR */
.seat-section {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.seat-section h2 {
  color: #667eea;
  margin-bottom: 1.5rem;
}

.seat-legend {
  display: flex;
  gap: 2rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-box {
  width: 40px;
  height: 40px;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.legend-available { background: #e8f5e9; border: 2px solid #4caf50; color: #2e7d32; }
.legend-booked { background: #ffebee; border: 2px solid #f44336; color: #c62828; }
.legend-selected { background: #e3f2fd; border: 2px solid #2196f3; color: #1565c0; }

.seats-container {
  background: #fafafa;
  padding: 2rem;
  border-radius: 10px;
  display: inline-block;
}

.seat-table {
  border-spacing: 10px;
}

.seat {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  transition: all 0.2s;
  cursor: pointer;
}

.seat-available {
  background: #e8f5e9;
  border: 2px solid #4caf50;
  color: #2e7d32;
}

.seat-available:hover {
  background: #c8e6c9;
  transform: scale(1.05);
}

.seat-booked {
  background: #ffebee;
  border: 2px solid #f44336;
  color: #c62828;
  cursor: not-allowed;
}

.seat-selected {
  background: #2196f3 !important;
  border: 2px solid #1565c0 !important;
  color: white !important;
  transform: scale(1.1);
}

.seat input[type="checkbox"] {
  display: none;
}

/* SAĞ TARAF - ÖZET PANELİ */
.summary-panel {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  height: fit-content;
  position: sticky;
  top: 2rem;
}

.summary-panel h3 {
  color: #667eea;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.selected-info {
  background: #f9f9f9;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.selected-info p {
  margin: 0.5rem 0;
  color: #555;
}

.selected-info .highlight {
  font-weight: bold;
  color: #667eea;
  font-size: 1.1rem;
}

/* KUPON BÖLÜMÜ */
.coupon-box {
  background: #fff9e6;
  border: 2px dashed #ffc107;
  padding: 1.5rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
}

.coupon-box h4 {
  color: #f57c00;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.coupon-input {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.coupon-input input {
  flex: 1;
  padding: 0.75rem;
  border: 2px solid #ffc107;
  border-radius: 8px;
  font-size: 1rem;
  text-transform: uppercase;
}

.coupon-input button {
  padding: 0.75rem 1.5rem;
  background: #ffc107;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s;
}

.coupon-input button:hover {
  background: #ffa000;
}

.coupon-message {
  padding: 0.75rem;
  border-radius: 5px;
  margin-top: 0.5rem;
  font-size: 0.9rem;
}

.coupon-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.coupon-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* FİYAT ÖZETİ */
.price-breakdown {
  background: #f9f9f9;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  margin: 0.75rem 0;
  color: #555;
}

.price-row.discount {
  color: #4caf50;
  font-weight: bold;
}

.price-row.total {
  font-size: 1.5rem;
  font-weight: bold;
  color: #667eea;
  padding-top: 1rem;
  border-top: 2px solid #ddd;
}

.btn-buy {
  width: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1.25rem;
  border-radius: 8px;
  font-size: 1.2rem;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-buy:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-buy:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #ccc;
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="/index.php">← Geri Dön</a>
</nav>

<div class="container">
  <?php if ($success): ?>
    <div class="alert alert-success">✓ <?= e($success) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <b>❌ İşlem başarısız:</b>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= e($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- TRIP INFO -->
  <div class="trip-card">
    <div class="trip-header">
      <h1><?= e($trip['company_name']) ?></h1>
      <div class="trip-route">
        <?= e($trip['departure_city']) ?> → <?= e($trip['destination_city']) ?>
      </div>
    </div>

    <div class="trip-info">
      <div class="info-item">
        <label>🕐 Kalkış</label>
        <div class="value"><?= e($trip['departure_time']) ?></div>
      </div>
      <div class="info-item">
        <label>🕐 Varış</label>
        <div class="value"><?= e($trip['arrival_time']) ?></div>
      </div>
      <div class="info-item">
        <label>💰 Fiyat (tek koltuk)</label>
        <div class="value"><?= $fmtPrice((int)$trip['price_tl']) ?></div>
      </div>
      <div class="info-item">
        <label>🪑 Boş Koltuk</label>
        <div class="value"><?= (int)$trip['available_seats'] ?> / <?= $capacity ?></div>
      </div>
    </div>

    <?php if (!$user): ?>
      <div class="login-warning">
        <b>⚠️ Satın almak için giriş yapmalısınız.</b>
        <a href="/login.php">Giriş Yap</a> veya <a href="/register.php">Kayıt Ol</a>
      </div>
    <?php endif; ?>
  </div>

  <form method="post" action="/buy.php" id="buyForm">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="trip_id" value="<?= e($trip['id']) ?>">
    <input type="hidden" name="coupon_code" id="appliedCoupon" value="">

    <div class="main-content">
      <!-- SOL: KOLTUK SEÇİMİ -->
      <div class="seat-section">
        <h2>🪑 Koltuk Seçimi</h2>

        <div class="seat-legend">
          <div class="legend-item">
            <div class="legend-box legend-available">5</div>
            <span>Boş</span>
          </div>
          <div class="legend-item">
            <div class="legend-box legend-booked">12</div>
            <span>Dolu</span>
          </div>
          <div class="legend-item">
            <div class="legend-box legend-selected">8</div>
            <span>Seçili</span>
          </div>
        </div>

        <div class="seats-container">
          <table class="seat-table">
            <?php for ($r = 0, $n = 1; $n <= $capacity; $r++): ?>
              <tr>
                <?php for ($c = 0; $c < $cols && $n <= $capacity; $c++, $n++): 
                  $isBooked = isset($booked[$n]); 
                ?>
                  <td>
                    <?php if ($isBooked): ?>
                      <div class="seat seat-booked">
                        <span><?= $n ?></span>
                        <small style="font-size:0.7rem;">DOLU</small>
                      </div>
                    <?php else: ?>
                      <label class="seat seat-available" data-seat="<?= $n ?>">
                        <input type="checkbox" name="seats[]" value="<?= $n ?>" 
                               <?= $user ? '' : 'disabled' ?>
                               onchange="updateSelection(this)">
                        <span><?= $n ?></span>
                      </label>
                    <?php endif; ?>
                  </td>
                <?php endfor; ?>
              </tr>
            <?php endfor; ?>
          </table>
        </div>
      </div>

      <!-- SAĞ: ÖZET PANELİ -->
      <?php if ($user): ?>
      <div class="summary-panel">
        <h3>📋 Satın Alma Özeti</h3>

        <div class="selected-info">
          <p>Seçili Koltuk: <span class="highlight" id="seatCount">0</span></p>
          <p id="seatNumbers" style="color:#888; font-size:0.9rem;">-</p>
        </div>

        <!-- KUPON -->
        <div class="coupon-box">
          <h4>🎟️ İndirim Kuponu</h4>
          <div class="coupon-input">
            <input type="text" id="couponCode" placeholder="Kupon kodunuz" maxlength="20">
            <button type="button" onclick="checkCoupon()">Uygula</button>
          </div>
          <div id="couponMessage"></div>
        </div>

        <!-- FİYAT -->
        <div class="price-breakdown">
          <div class="price-row">
            <span>Bilet Ücreti:</span>
            <span id="basePrice">0 ₺</span>
          </div>
          <div class="price-row discount" id="discountRow" style="display:none;">
            <span>İndirim:</span>
            <span id="discountAmount">0 ₺</span>
          </div>
          <div class="price-row total">
            <span>Toplam:</span>
            <span id="totalPrice">0 ₺</span>
          </div>
        </div>

        <button type="submit" class="btn-buy" id="btnBuy" disabled>
          🛒 Satın Al
        </button>
      </div>
      <?php endif; ?>
    </div>
  </form>
</div>

<script>
const unitPrice = <?= (int)$trip['price_tl'] ?>;
const tripId = '<?= e($trip['id']) ?>';
let selectedSeats = [];
let discountPercent = 0;

function updateSelection(checkbox) {
  const label = checkbox.parentElement;
  const seatNum = parseInt(label.dataset.seat);
  
  if (checkbox.checked) {
    label.classList.add('seat-selected');
    label.classList.remove('seat-available');
    selectedSeats.push(seatNum);
  } else {
    label.classList.remove('seat-selected');
    label.classList.add('seat-available');
    selectedSeats = selectedSeats.filter(s => s !== seatNum);
  }
  
  selectedSeats.sort((a, b) => a - b);
  updateSummary();
}

function updateSummary() {
  const count = selectedSeats.length;
  const baseTotal = count * unitPrice;
  const discountAmount = Math.floor(baseTotal * discountPercent / 100);
  const finalTotal = baseTotal - discountAmount;
  
  document.getElementById('seatCount').textContent = count;
  document.getElementById('seatNumbers').textContent = 
    count > 0 ? 'Koltuk No: ' + selectedSeats.join(', ') : '-';
  
  document.getElementById('basePrice').textContent = 
    baseTotal.toLocaleString('tr-TR') + ' ₺';
  
  if (discountPercent > 0) {
    document.getElementById('discountRow').style.display = 'flex';
    document.getElementById('discountAmount').textContent = 
      '-' + discountAmount.toLocaleString('tr-TR') + ' ₺';
  } else {
    document.getElementById('discountRow').style.display = 'none';
  }
  
  document.getElementById('totalPrice').textContent = 
    finalTotal.toLocaleString('tr-TR') + ' ₺';
  
  document.getElementById('btnBuy').disabled = count === 0;
}

async function checkCoupon() {
  const code = document.getElementById('couponCode').value.trim().toUpperCase();
  const msgDiv = document.getElementById('couponMessage');
  
  if (!code) {
    msgDiv.innerHTML = '<div class="coupon-error">Kupon kodu girin</div>';
    return;
  }
  
  msgDiv.innerHTML = '<div style="color:#666;">Kontrol ediliyor...</div>';
  
  try {
    const response = await fetch(`/api/check_coupon.php?code=${code}&trip_id=${tripId}`);
    const data = await response.json();
    
    if (data.success) {
      discountPercent = data.discount;
      document.getElementById('appliedCoupon').value = code;
      msgDiv.innerHTML = `<div class="coupon-success">✓ ${data.message}</div>`;
      updateSummary();
    } else {
      discountPercent = 0;
      document.getElementById('appliedCoupon').value = '';
      msgDiv.innerHTML = `<div class="coupon-error">✗ ${data.error}</div>`;
      updateSummary();
    }
  } catch (err) {
    msgDiv.innerHTML = '<div class="coupon-error">Kupon kontrol edilemedi</div>';
  }
}
</script>

</body>
</html>