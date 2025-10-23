<?php
declare(strict_types=1);
require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/helpers/admin_guard.php';
require_admin();

$pdo = db();

$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM User")->fetchColumn(),
    'companies' => $pdo->query("SELECT COUNT(*) FROM Bus_Company")->fetchColumn(),
    'trips' => $pdo->query("SELECT COUNT(*) FROM Trips")->fetchColumn(),
    'tickets' => $pdo->query("SELECT COUNT(*) FROM Tickets WHERE status='ACTIVE'")->fetchColumn(),
    'coupons' => $pdo->query("SELECT COUNT(*) FROM Coupons")->fetchColumn(),
];

$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Paneli</title>
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

.welcome-card p {
  color: #666;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  color: #888;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: #667eea;
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
  <div class="navbar-brand">⚙️ Admin Panel</div>
  <div class="navbar-menu">
    <span class="user-badge">👤 <?= e($user['full_name']) ?></span>
    <a href="/index.php">🏠 Anasayfa</a>
    <a href="/logout.php">🚪 Çıkış</a>
  </div>
</nav>

<div class="container">
  <div class="welcome-card">
    <h1>Hoş Geldiniz, <?= e($user['full_name']) ?>! 👋</h1>
    <p>Sistem yönetim paneline hoş geldiniz. Aşağıdaki menülerden istediğiniz bölüme erişebilirsiniz.</p>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-label">Toplam Kullanıcı</div>
      <div class="stat-value"><?= number_format($stats['users']) ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🏢</div>
      <div class="stat-label">Otobüs Firması</div>
      <div class="stat-value"><?= number_format($stats['companies']) ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🚌</div>
      <div class="stat-label">Toplam Sefer</div>
      <div class="stat-value"><?= number_format($stats['trips']) ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🎫</div>
      <div class="stat-label">Aktif Bilet</div>
      <div class="stat-value"><?= number_format($stats['tickets']) ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🎟️</div>
      <div class="stat-label">Toplam Kupon</div>
      <div class="stat-value"><?= number_format($stats['coupons']) ?></div>
    </div>
  </div>

  <div class="menu-grid">
    <a href="/admin/firms.php" class="menu-card">
      <span class="menu-icon">🏢</span>
      <h3>Firmalar</h3>
      <p>Otobüs firmalarını ekleyin, düzenleyin veya silin. Firma bilgilerini yönetin.</p>
      <div class="arrow">Yönet →</div>
    </a>

    <a href="/admin/users.php" class="menu-card">
      <span class="menu-icon">👥</span>
      <h3>Kullanıcılar</h3>
      <p>Kullanıcıları görüntüleyin ve firma admin yetkisi atayın.</p>
      <div class="arrow">Yönet →</div>
    </a>

    <a href="/admin/coupons.php" class="menu-card">
      <span class="menu-icon">🎟️</span>
      <h3>Kuponlar</h3>
      <p>Global ve firma özel indirim kuponları oluşturun ve yönetin.</p>
      <div class="arrow">Yönet →</div>
    </a>
  </div>
</div>

</body>
</html>