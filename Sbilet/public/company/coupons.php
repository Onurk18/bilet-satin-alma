<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/helpers/company_guard.php';
require __DIR__ . '/../../src/models/CouponModel.php';

$companyAdmin = require_company_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    
    if ($act === 'create') {
        $id   = uuidv4();
        $code = strtoupper(trim((string)$_POST['code']));
        $disc = (int)$_POST['discount'];
        $lim  = (int)$_POST['usage_limit'];
        $exp  = (string)$_POST['expire_date'];
        
        CouponModel::create($id, $code, $disc, $companyAdmin['company_id'], $lim, $exp);
        $_SESSION['success'] = 'Kupon oluşturuldu';
        redirect('/company/coupons.php');
        
    } elseif ($act === 'update') {
        $id   = (string)$_POST['id'];
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT company_id FROM Coupons WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $coupon = $stmt->fetch();
        
        if (!$coupon || $coupon['company_id'] !== $companyAdmin['company_id']) {
            http_response_code(403);
            exit('Bu kuponu düzenleme yetkiniz yok');
        }
        
        $code = strtoupper(trim((string)$_POST['code']));
        $disc = (int)$_POST['discount'];
        $lim  = (int)$_POST['usage_limit'];
        $exp  = (string)$_POST['expire_date'];
        
        CouponModel::update($id, $code, $disc, $companyAdmin['company_id'], $lim, $exp);
        $_SESSION['success'] = 'Kupon güncellendi';
        redirect('/company/coupons.php');
        
    } elseif ($act === 'delete') {
        $id = (string)$_POST['id'];
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT company_id FROM Coupons WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $coupon = $stmt->fetch();
        
        if (!$coupon || $coupon['company_id'] !== $companyAdmin['company_id']) {
            http_response_code(403);
            exit('Bu kuponu silme yetkiniz yok');
        }
        
        CouponModel::delete($id);
        $_SESSION['success'] = 'Kupon silindi';
        redirect('/company/coupons.php');
    }
}

$pdo = db();
$stmt = $pdo->prepare("
    SELECT c.id, c.code, c.discount, c.usage_limit, c.expire_date, c.created_at
    FROM Coupons c
    WHERE c.company_id = :cid
    ORDER BY c.created_at DESC
");
$stmt->execute([':cid' => $companyAdmin['company_id']]);
$coupons = $stmt->fetchAll();

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

.form-group input[name="code"] {
  text-transform: uppercase;
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

.actions {
  display: flex;
  gap: 0.5rem;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.coupon-code {
  font-family: 'Courier New', monospace;
  background: #fff9e6;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  font-weight: bold;
  color: #f57c00;
  border: 2px dashed #ffc107;
  display: inline-block;
}

.discount-badge {
  background: #4caf50;
  color: white;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-weight: bold;
  font-size: 1.1rem;
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
  <a href="/company/index.php">← Firma Paneli</a>
</nav>

<div class="container">
  <div class="page-header">
    <h1>🎟️ Kupon Yönetimi</h1>
    <p>Firmanıza özel indirim kuponları oluşturun ve yönetin.</p>
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
          <input type="text" name="code" required placeholder="YILBASI2025" maxlength="20">
        </div>
        
        <div class="form-group">
          <label>İndirim Oranı (%) *</label>
          <input type="number" name="discount" min="1" max="100" required placeholder="20">
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
            <th>Kupon Kodu</th>
            <th>İndirim</th>
            <th>Kullanım Limiti</th>
            <th>Son Kullanma</th>
            <th>Oluşturulma</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($coupons as $c): ?>
          <tr>
            <td><span class="coupon-code"><?= e($c['code']) ?></span></td>
            <td><span class="discount-badge">%<?= (int)$c['discount'] ?></span></td>
            <td><?= (int)$c['usage_limit'] ?> kullanım</td>
            <td><?= e($c['expire_date']) ?></td>
            <td><small><?= e(substr($c['created_at'], 0, 16)) ?></small></td>
            <td>
              <div class="actions">
                <button class="btn btn-edit" onclick="editCoupon('<?= e($c['id']) ?>', '<?= e($c['code']) ?>', <?= (int)$c['discount'] ?>, <?= (int)$c['usage_limit'] ?>, '<?= e($c['expire_date']) ?>')">
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
          <input type="text" name="code" id="edit_code" required>
        </div>
        
        <div class="form-group">
          <label>İndirim % *</label>
          <input type="number" name="discount" id="edit_discount" min="1" max="100" required>
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
        <button type="button" class="btn btn-secondary" onclick="closeEdit()">❌ İptal</button>
        <button type="submit" class="btn btn-primary">💾 Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
  function editCoupon(id, code, discount, limit, expire) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_discount').value = discount;
    document.getElementById('edit_limit').value = limit;
    document.getElementById('edit_expire').value = expire;
    document.getElementById('editModal').classList.add('active');
  }
  
  function closeEdit() {
    document.getElementById('editModal').classList.remove('active');
  }
  
  document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
  });
</script>

</body>
</html>