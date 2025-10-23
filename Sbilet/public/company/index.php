<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/helpers/company_guard.php';

$companyAdmin = require_company_admin();

$pdo = db();
$stmt = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $companyAdmin['company_id']]);
$company = $stmt->fetch();
$companyName = $company ? $company['name'] : 'Bilinmeyen Firma';

?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Firma Admin Paneli</title>
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
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.navbar-brand {
  font-size: 1.5rem;
  font-weight: bold;
}

.navbar-menu {
  display: flex;
  gap: 1rem;
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

.user-badge {
  background: rgba(255,255,255,0.2);
  padding: 0.5rem 1rem;
  border-radius: 20px;
}

.container {
  max-width: 1400px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.welcome-card {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.welcome-card h1 {
  color: #667eea;
  margin-bottom: 0.5rem;
}

.company-info {
  display: flex;
  gap: 2rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid #f0f0f0;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #666;
}

.info-item .icon {
  font-size: 1.2rem;
}

.info-item .value {
  font-weight: bold;
  color: #333;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.menu-card {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  transition: transform 0.3s, box-shadow 0.3s;
  text-decoration: none;
  color: #333;
  display: block;
}

.menu-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
}

.menu-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.menu-card h3 {
  color: #667eea;
  margin-bottom: 0.5rem;
  font-size: 1.5rem;
}

.menu-card p {
  color: #666;
  line-height: 1.6;
}

.menu-card .arrow {
  margin-top: 1rem;
  color: #667eea;
  font-weight: bold;
}
</style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">🏢 Firma Admin Panel</div>
  <div class="navbar-menu">
    <span class="user-badge">👤 <?= e($companyAdmin['full_name']) ?></span>
    <a href="/index.php">🏠 Anasayfa</a>
    <a href="/logout.php">🚪 Çıkış</a>
  </div>
</nav>

<div class="container">
  <div class="welcome-card">
    <h1>Hoş Geldiniz! 👋</h1>
    <p>Firma yönetim paneline hoş geldiniz. Aşağıdaki menülerden istediğiniz bölüme erişebilirsiniz.</p>
    
    <div class="company-info">
      <div class="info-item">
        <span class="icon">🏢</span>
        <div>
          <small style="color:#888;">Firma</small><br>
          <span class="value"><?= e($companyName) ?></span>
        </div>
      </div>
      <div class="info-item">
        <span class="icon">👤</span>
        <div>
          <small style="color:#888;">Yetkili</small><br>
          <span class="value"><?= e($companyAdmin['full_name']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="menu-grid">
    <a href="/company/trips.php" class="menu-card">
      <span class="menu-icon">🚌</span>
      <h3>Seferler</h3>
      <p>Firmanıza ait seferleri ekleyin, düzenleyin veya silin. Sefer bilgilerini yönetin.</p>
      <div class="arrow">Yönet →</div>
    </a>

    <a href="/company/coupons.php" class="menu-card">
      <span class="menu-icon">🎟️</span>
      <h3>Kuponlar</h3>
      <p>Firma özel indirim kuponları oluşturun ve yönetin.</p>
      <div class="arrow">Yönet →</div>
    </a>
  </div>
</div>

</body>
</html>