<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/helpers/admin_guard.php';
require __DIR__ . '/../../src/models/FirmModel.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = $_POST['act'] ?? '';
  if ($act === 'create') {
      $id = uuidv4();
      FirmModel::create($id, trim((string)$_POST['name']), $_POST['logo_path'] !== '' ? (string)$_POST['logo_path'] : null);
      $_SESSION['success'] = 'Firma başarıyla eklendi';
  } elseif ($act === 'update') {
      FirmModel::update((string)$_POST['id'], trim((string)$_POST['name']), $_POST['logo_path'] !== '' ? (string)$_POST['logo_path'] : null);
      $_SESSION['success'] = 'Firma güncellendi';
  } elseif ($act === 'delete') {
      FirmModel::delete((string)$_POST['id']);
      $_SESSION['success'] = 'Firma silindi';
  }
  redirect('/admin/firms.php');
}

$firms = FirmModel::all();

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Firma Yönetimi</title>
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
  transition: opacity 0.3s;
}

.navbar a:hover {
  opacity: 0.8;
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

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
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
  max-width: 500px;
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
    <h1>🏢 Firma Yönetimi</h1>
    <p>Otobüs firmalarını ekleyin, düzenleyin veya silin.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert-success">✓ <?= e($success) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <h2>➕ Yeni Firma Ekle</h2>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="create">
      
      <div class="form-group">
        <label>Firma Adı *</label>
        <input type="text" name="name" required placeholder="Metro Turizm">
      </div>
      
      <div class="form-group">
        <label>Logo Yolu (Opsiyonel)</label>
        <input type="text" name="logo_path" placeholder="/public/assets/logo.png">
      </div>
      
      <button type="submit" class="btn btn-primary">➕ Firma Ekle</button>
    </form>
  </div>

  <div class="table-card">
    <h2>📋 Mevcut Firmalar (<?= count($firms) ?>)</h2>
    
    <?php if (!$firms): ?>
      <div class="empty-state">
        <p>Henüz firma eklenmemiş.</p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Firma Adı</th>
            <th>Logo Yolu</th>
            <th>Oluşturulma</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($firms as $f): ?>
          <tr>
            <td><strong><?= e($f['name']) ?></strong></td>
            <td><?= e((string)$f['logo_path']) ?: '-' ?></td>
            <td><small><?= e(substr($f['created_at'], 0, 16)) ?></small></td>
            <td>
              <div class="actions">
                <button class="btn btn-edit" onclick="editFirm('<?= e($f['id']) ?>', '<?= e($f['name']) ?>', '<?= e((string)$f['logo_path']) ?>')">
                  ✏️ Düzenle
                </button>
                <form method="post" style="display:inline" onsubmit="return confirm('Bu firmayı silmek istediğinize emin misiniz?')">
                  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= e($f['id']) ?>">
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
      <h3>✏️ Firma Düzenle</h3>
    </div>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="update">
      <input type="hidden" name="id" id="edit_id">
      
      <div class="form-group">
        <label>Firma Adı *</label>
        <input type="text" name="name" id="edit_name" required>
      </div>
      
      <div class="form-group">
        <label>Logo Yolu</label>
        <input type="text" name="logo_path" id="edit_logo">
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ İptal</button>
        <button type="submit" class="btn btn-primary">💾 Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
function editFirm(id, name, logo) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_logo').value = logo;
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