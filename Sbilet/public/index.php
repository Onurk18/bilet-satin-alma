<?php

declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/controllers/TripController.php';

boot_session();

$dep  = isset($_GET['dep'])  ? (string)$_GET['dep']  : '';
$arr  = isset($_GET['arr'])  ? (string)$_GET['arr']  : '';
$date = isset($_GET['date']) ? (string)$_GET['date'] : '';

$trips = TripController::searchFromQuery($_GET);


$user = $_SESSION['user'] ?? null;

$fmtPrice = function (int $tl): string {
    return number_format($tl, 0, ',', '.') . ' ₺';
};
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sbilet — Otobüs Bileti Ara</title>
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navbar-brand {
  font-size: 1.5rem;
  font-weight: bold;
  text-decoration: none;
  color: white;
}

.navbar-menu {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.navbar-menu a {
  color: white;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  transition: background 0.3s;
}

.navbar-menu a:hover {
  background: rgba(255,255,255,0.2);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(255,255,255,0.15);
  padding: 0.5rem 1rem;
  border-radius: 20px;
}

.balance {
  font-weight: bold;
  color: #ffd700;
}


.search-container {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.search-card {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.search-card h1 {
  margin-bottom: 1.5rem;
  color: #667eea;
}

.search-form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #555;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.autocomplete-items {
  position: absolute;
  border: 1px solid #d4d4d4;
  border-top: none;
  z-index: 99;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.autocomplete-items div {
  padding: 10px;
  cursor: pointer;
  border-bottom: 1px solid #e0e0e0;
}

.autocomplete-items div:hover {
  background-color: #f0f0f0;
}

.autocomplete-active {
  background-color: #667eea !important;
  color: white !important;
}

.btn-search {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.2s;
  grid-column: span 1;
}

.btn-search:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-clear {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-clear:hover {
  background: #667eea;
  color: white;
}


.trips-container {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.trips-table {
  width: 100%;
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.trips-table table {
  width: 100%;
  border-collapse: collapse;
}

.trips-table th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
}

.trips-table td {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
}

.trips-table tr:hover {
  background: #f9f9f9;
}

.btn-detail {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 5px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: background 0.3s;
}

.btn-detail:hover {
  background: #764ba2;
}

.no-trips {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.hint {
  text-align: center;
  color: #999;
  font-size: 0.9rem;
  margin-top: 1rem;
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="/index.php" class="navbar-brand">🚌 Sbilet</a>
  <div class="navbar-menu">
    <?php if ($user): ?>
      <div class="user-info">
        <span><?= e($user['full_name']) ?></span>
        <?php if ($user['role'] === 'USER'): ?>
          <?php
            $pdo = db();
            $stmt = $pdo->prepare("SELECT balance FROM User WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            $balance = $stmt->fetchColumn();
          ?>
          <span class="balance"><?= number_format((int)$balance, 0, ',', '.') ?> ₺</span>
        <?php endif; ?>
      </div>
      
      <?php if ($user['role'] === 'USER'): ?>
        <a href="/my_tickets.php">📋 Biletlerim</a>
      <?php elseif ($user['role'] === 'COMPANY_ADMIN'): ?>
        <a href="/company/index.php">🏢 Firma Paneli</a>
      <?php elseif ($user['role'] === 'ADMIN'): ?>
        <a href="/admin/index.php">⚙️ Admin Paneli</a>
      <?php endif; ?>
      
      <a href="/logout.php">🚪 Çıkış</a>
    <?php else: ?>
      <a href="/login.php">🔐 Giriş Yap</a>
      <a href="/register.php">📝 Kayıt Ol</a>
    <?php endif; ?>
  </div>
</nav>


<div class="search-container">
  <div class="search-card">
    <h1>🔍 Otobüs Bileti Ara</h1>
    
    <form method="get" action="/index.php">
      <div class="search-form">
        <div class="form-group">
          <label for="dep">Nereden</label>
          <input type="text" id="dep" name="dep" value="<?= e($dep) ?>" 
                 placeholder="Kalkış şehri" autocomplete="off">
          <div id="dep-autocomplete" class="autocomplete-items"></div>
        </div>
        
        <div class="form-group">
          <label for="arr">Nereye</label>
          <input type="text" id="arr" name="arr" value="<?= e($arr) ?>" 
                 placeholder="Varış şehri" autocomplete="off">
          <div id="arr-autocomplete" class="autocomplete-items"></div>
        </div>
        
        <div class="form-group">
          <label for="date">Tarih</label>
          <input type="date" id="date" name="date" value="<?= e($date) ?>">
        </div>
        
        <div class="form-group" style="display: flex; gap: 0.5rem; align-items: flex-end;">
          <button type="submit" class="btn-search">🔍 Ara</button>
          <a href="/index.php" class="btn-clear">✖ Temizle</a>
        </div>
      </div>
    </form>
    
    <div class="hint">
      💡 İpucu: Kalkış ve varış için şehir adı yazmaya başlayın
    </div>
  </div>
</div>


<div class="trips-container">
  <?php if (!$trips): ?>
    <div class="trips-table">
      <div class="no-trips">
        <h2>😔 Sefer Bulunamadı</h2>
        <p>Bu kriterlere uygun sefer yok. Farklı tarih veya şehir deneyin.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="trips-table">
      <table>
        <thead>
          <tr>
            <th>Firma</th>
            <th>Güzergah</th>
            <th>Kalkış Saati</th>
            <th>Varış Saati</th>
            <th>Fiyat</th>
            <th>Boş Koltuk</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trips as $t): ?>
            <tr>
              <td><strong><?= e($t['company_name']) ?></strong></td>
              <td>
                <?= e($t['departure_city']) ?> → <?= e($t['destination_city']) ?>
              </td>
              <td><?= e($t['departure_time']) ?></td>
              <td><?= e($t['arrival_time']) ?></td>
              <td><strong><?= $fmtPrice((int)$t['price_tl']) ?></strong></td>
              <td><?= (int)$t['available_seats'] ?> / <?= (int)$t['capacity'] ?></td>
              <td>
                <a href="/trip.php?id=<?= e($t['id']) ?>" class="btn-detail">Detay →</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>

let cities = [];
fetch('/api/cities.php')
  .then(res => res.json())
  .then(data => { cities = data; });


function setupAutocomplete(inputId, containerId) {
  const input = document.getElementById(inputId);
  const container = document.getElementById(containerId);
  
  input.addEventListener('input', function() {
    const val = this.value.toLowerCase();
    container.innerHTML = '';
    
    if (!val) return;
    
    const matches = cities.filter(city => 
      city.toLowerCase().includes(val)
    ).slice(0, 8);
    
    matches.forEach(city => {
      const div = document.createElement('div');
      div.textContent = city;
      div.addEventListener('click', function() {
        input.value = city;
        container.innerHTML = '';
      });
      container.appendChild(div);
    });
  });
  

  document.addEventListener('click', function(e) {
    if (e.target !== input) {
      container.innerHTML = '';
    }
  });
}

setupAutocomplete('dep', 'dep-autocomplete');
setupAutocomplete('arr', 'arr-autocomplete');
</script>

</body>
</html>