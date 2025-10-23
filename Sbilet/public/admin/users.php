<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/helpers/admin_guard.php';
require __DIR__ . '/../../src/db.php';

require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $uid  = (string)($_POST['user_id'] ?? '');
  $cid  = (string)($_POST['company_id'] ?? '');
  if ($uid !== '') {
      $st = $pdo->prepare("UPDATE User SET role='COMPANY_ADMIN', company_id=:c WHERE id=:u");
      $st->execute([':c'=>($cid !== '' ? $cid : null), ':u'=>$uid]);
      $_SESSION['success'] = 'Kullanıcı firma admini yapıldı';
  }
  redirect('/admin/users.php');
}

$users = $pdo->query("SELECT id,full_name,email,role,company_id,balance,created_at FROM User ORDER BY created_at DESC")->fetchAll();
$firms = $pdo->query("SELECT id,name FROM Bus_Company ORDER BY name")->fetchAll();

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kullanıcı Yönetimi</title>
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
}

.container {
  max-width: 1400px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.page-header {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.page-header h1 {
  color: #667eea;
  margin-bottom: 0.5rem;
}

.alert-success {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.table-card {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  overflow-x: auto;
}

.table-card h2 {
  color: #667eea;
  margin-bottom: 1.5rem;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
}

td {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

tbody tr:hover {
  background: #f9f9f9;
}

.role-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: bold;
}

.role-admin {
  background: #ff9800;
  color: white;
}

.role-company {
  background: #2196f3;
  color: white;
}

.role-user {
  background: #4caf50;
  color: white;
}

.promote-form {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.promote-form select {
  padding: 0.5rem;
  border: 2px solid #e0e0e0;
  border-radius: 5px;
  font-size: 0.9rem;
}

.promote-form button {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s;
  white-space: nowrap;
}

.promote-form button:hover {
  background: #764ba2;
}

.filter-section {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.filter-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.75rem 1.5rem;
  border: 2px solid #667eea;
  background: white;
  color: #667eea;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s;
}

.filter-btn:hover,
.filter-btn.active {
  background: #667eea;
  color: white;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="/admin/index.php">← Admin Paneli</a>
</nav>

<div class="container">
  <div class="page-header">
    <h1>👥 Kullanıcı Yönetimi</h1>
    <p>Kullanıcıları görüntüleyin ve firma admin yetkisi atayın.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert-success">✓ <?= e($success) ?></div>
  <?php endif; ?>

  <div class="filter-section">
    <div class="filter-buttons">
      <button class="filter-btn active" onclick="filterUsers('all')">Tümü (<?= count($users) ?>)</button>
      <button class="filter-btn" onclick="filterUsers('ADMIN')">
        Adminler (<?= count(array_filter($users, fn($u) => $u['role'] === 'ADMIN')) ?>)
      </button>
      <button class="filter-btn" onclick="filterUsers('COMPANY_ADMIN')">
        Firma Adminleri (<?= count(array_filter($users, fn($u) => $u['role'] === 'COMPANY_ADMIN')) ?>)
      </button>
      <button class="filter-btn" onclick="filterUsers('USER')">
        Kullanıcılar (<?= count(array_filter($users, fn($u) => $u['role'] === 'USER')) ?>)
      </button>
    </div>
  </div>

  <div class="table-card">
    <h2>📋 Kullanıcılar</h2>
    
    <?php if (!$users): ?>
      <div class="empty-state">
        <p>Henüz kullanıcı yok.</p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Ad Soyad</th>
            <th>E-posta</th>
            <th>Rol</th>
            <th>Firma</th>
            <th>Bakiye</th>
            <th>Kayıt Tarihi</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody id="userTable">
          <?php foreach($users as $u): ?>
          <tr data-role="<?= e($u['role']) ?>">
            <td><strong><?= e($u['full_name']) ?></strong></td>
            <td><?= e($u['email']) ?></td>
            <td>
              <span class="role-badge role-<?= strtolower($u['role']) === 'admin' ? 'admin' : (strtolower($u['role']) === 'company_admin' ? 'company' : 'user') ?>">
                <?= e($u['role']) ?>
              </span>
            </td>
            <td>
              <?php if ($u['company_id']): ?>
                <?php
                  $companyStmt = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = :id");
                  $companyStmt->execute([':id' => $u['company_id']]);
                  $companyName = $companyStmt->fetchColumn();
                ?>
                <small><?= e($companyName ?: 'Bilinmeyen') ?></small>
              <?php else: ?>
                <small style="color:#999;">-</small>
              <?php endif; ?>
            </td>
            <td><?= number_format((int)$u['balance'], 0, ',', '.') ?> ₺</td>
            <td><small><?= e(substr($u['created_at'], 0, 16)) ?></small></td>
            <td>
              <?php if ($u['role'] !== 'ADMIN'): ?>
                <form method="post" class="promote-form">
                  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                  <select name="company_id">
                    <option value="">Firma Seçin</option>
                    <?php foreach($firms as $f): ?>
                      <option value="<?= e($f['id']) ?>" <?= $u['company_id'] === $f['id'] ? 'selected' : '' ?>>
                        <?= e($f['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit">👔 Firma Admin Yap</button>
                </form>
              <?php else: ?>
                <small style="color:#ff9800;">🛡️ Süper Admin</small>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
function filterUsers(role) {
  const rows = document.querySelectorAll('#userTable tr');
  const buttons = document.querySelectorAll('.filter-btn');
  
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  rows.forEach(row => {
    if (role === 'all') {
      row.style.display = '';
    } else {
      row.style.display = row.dataset.role === role ? '' : 'none';
    }
  });
}
</script>

</body>
</html>