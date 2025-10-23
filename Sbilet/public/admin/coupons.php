<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/helpers/admin_guard.php';
require __DIR__ . '/../../src/models/CouponModel.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = $_POST['act'] ?? '';
  if ($act === 'create') {
      $id   = uuidv4();
      $code = strtoupper(trim((string)$_POST['code']));
      $disc = (int)$_POST['discount'];
      $cid  = (string)($_POST['company_id'] ?? '');
      $cid  = ($cid !== '') ? $cid : null; 
      $lim  = (int)$_POST['usage_limit'];
      $exp  = (string)$_POST['expire_date'];
      CouponModel::create($id, $code, $disc, $cid, $lim, $exp);
      $_SESSION['success'] = 'Kupon başarıyla oluşturuldu';
  } elseif ($act === 'update') {
      $id = (string)$_POST['id'];
      $code = strtoupper(trim((string)$_POST['code']));
      $disc = (int)$_POST['discount'];
      $cid  = (string)($_POST['company_id'] ?? '');
      $cid  = ($cid !== '') ? $cid : null;
      $lim  = (int)$_POST['usage_limit'];
      $exp  = (string)$_POST['expire_date'];
      CouponModel::update($id, $code, $disc, $cid, $lim, $exp);
      $_SESSION['success'] = 'Kupon güncellendi';
  } elseif ($act === 'delete') {
      CouponModel::delete((string)$_POST['id']);
      $_SESSION['success'] = 'Kupon silindi';
  }
  redirect('/admin/coupons.php');
}

$coupons = CouponModel::all();
$firms   = $pdo->query("SELECT id,name FROM Bus_Company ORDER BY name")->fetchAll();

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kupon Yönetimi</title>
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

.form-card {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.form-card h2 {
  color: #667eea;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #555;
  font-size: 0.9rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border 0.3s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
}

.btn {
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-edit {
  background: #2196f3;
  color: white;
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

.btn-edit:hover {
  background: #1976d2;
}

.btn-delete {
  background: #f44336;
  color: white;
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

.btn-delete:hover {
  background: #d32f2f;
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
}

tbody tr:hover {
  background: #f9f9f9;
}

.scope-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: bold;
}

.scope-global {
  background: #ff9800;
  color: white;
}

.scope-company {
  background: #2196f3;
  color: white;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 999;
  align-items: center;
  justify-content: center;
}

.modal.active {
  display: flex;
}

.modal-content {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  max-width: 600px;
  width: 90%;
  box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.modal-header h3 {
  color: #667eea;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #d0d0d0;
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="/admin/index.php">← Admin Paneli</a>
</nav>

<div class="container">
  <div class="page-header">
    <h1>🎟️ Kupon Yönetimi</h1>
    <p>Global ve firma özel indirim kuponları oluşturun ve yönetin.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert-success">✓ <?= e($success) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <h2>➕ Yeni Kupon Oluştur</h2>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="create">
      
      <div class="form-grid">
        <div class="form-group">
          <label>Kupon Kodu *</label>
          <input type="text" name="code" required placeholder="YILBASI2025" style="text-transform:uppercase" maxlength="20">
        </div>
        
        <div class="form-group">
          <label>İndirim Oranı (%) *</label>
          <input type="number" name="discount" min="1" max="100" required placeholder="20">
        </div>
        
        <div class="form-group">
          <label>Kapsam</label>
          <select name="company_id">
            <option value="">🌍 GLOBAL (Tüm Firmalar)</option>
            <?php foreach($firms as $f): ?>
              <option value="<?= e($f['id']) ?>">🏢 <?= e($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label>Kullanım Limiti *</label>
          <input type="number" name="usage_limit" min="1" required placeholder="100">
        </div>
        
        <div class="form-group">
          <label>Son Kullanma Tarihi *</label>
          <input type="date" name="expire_date" required>
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary">➕ Kupon Oluştur</button>
    </form>
  </div>

  <div class="table-card">
    <h2>📋 Mevcut Kuponlar (<?= count($coupons) ?>)</h2>
    
    <?php if (!$coupons): ?>
      <div class="empty-state">
        <p>Henüz kupon oluşturulmamış.</p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Kod</th>
            <th>İndirim</th>
            <th>Kapsam</th>
            <th>Kullanım</th>
            <th>Son Tarih</th>
            <th>Oluşturulma</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($coupons as $c): ?>
          <?php
            $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = :id");
            $usageStmt->execute([':id' => $c['id']]);
            $usedCount = $usageStmt->fetchColumn();
          ?>
          <tr>
            <td><strong style="font-size:1.1rem;"><?= e($c['code']) ?></strong></td>
            <td><strong style="color:#4caf50;">%<?= (int)$c['discount'] ?></strong></td>
            <td>
              <?php if ($c['company_id']): ?>
                <span class="scope-badge scope-company">
                  🏢 <?= e($c['company_name']) ?>
                </span>
              <?php else: ?>
                <span class="scope-badge scope-global">🌍 GLOBAL</span>
              <?php endif; ?>
            </td>
            <td>
              <small><?= $usedCount ?> / <?= (int)$c['usage_limit'] ?></small>
            </td>
            <td><?= e($c['expire_date']) ?></td>
            <td><small><?= e(substr($c['created_at'], 0, 16)) ?></small></td>
            <td>
              <div class="actions">
                <button class="btn btn-edit" onclick="editCoupon(
                  '<?= e($c['id']) ?>', 
                  '<?= e($c['code']) ?>', 
                  <?= (int)$c['discount'] ?>, 
                  '<?= e((string)$c['company_id']) ?>', 
                  <?= (int)$c['usage_limit'] ?>, 
                  '<?= e($c['expire_date']) ?>'
                )">
                  ✏️ Düzenle
                </button>
                <form method="post" style="display:inline" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?')">
                  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                  <button type="submit" class="btn btn-delete">🗑️ Sil</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>✏️ Kupon Düzenle</h3>
    </div>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="update">
      <input type="hidden" name="id" id="edit_id">
      
      <div class="form-grid">
        <div class="form-group">
          <label>Kupon Kodu *</label>
          <input type="text" name="code" id="edit_code" required style="text-transform:uppercase">
        </div>
        
        <div class="form-group">
          <label>İndirim % *</label>
          <input type="number" name="discount" id="edit_discount" min="1" max="100" required>
        </div>
        
        <div class="form-group">
          <label>Kapsam</label>
          <select name="company_id" id="edit_company">
            <option value="">🌍 GLOBAL</option>
            <?php foreach($firms as $f): ?>
              <option value="<?= e($f['id']) ?>">🏢 <?= e($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label>Kullanım Limiti *</label>
          <input type="number" name="usage_limit" id="edit_limit" min="1" required>
        </div>
        
        <div class="form-group">
          <label>Son Tarih *</label>
          <input type="date" name="expire_date" id="edit_expire" required>
        </div>
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ İptal</button>
        <button type="submit" class="btn btn-primary">💾 Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
function editCoupon(id, code, discount, companyId, limit, expire) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_code').value = code;
  document.getElementById('edit_discount').value = discount;
  document.getElementById('edit_company').value = companyId;
  document.getElementById('edit_limit').value = limit;
  document.getElementById('edit_expire').value = expire;
  document.getElementById('editModal').classList.add('active');
}

function closeModal() {
  document.getElementById('editModal').classList.remove('active');
}

document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

</body>
</html>