<?php
declare(strict_types=1);
require __DIR__ . '/../../src/helpers/utils.php';
require __DIR__ . '/../../src/helpers/company_guard.php';
require __DIR__ . '/../../src/models/companyTripModel.php';

$company = require_company_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    
    if ($act === 'create') {
        $id = uuidv4();
        $errors = [];
        
        $dep  = trim((string)$_POST['departure_city']);
        $dest = trim((string)$_POST['destination_city']);
        $dtime = trim((string)$_POST['departure_time']);
        $atime = trim((string)$_POST['arrival_time']);
        $price = (int)$_POST['price'];
        $cap   = (int)$_POST['capacity'];
        
        if ($dep === '') $errors[] = 'Kalkış şehri boş olamaz';
        if ($dest === '') $errors[] = 'Varış şehri boş olamaz';
        if ($price <= 0) $errors[] = 'Fiyat 0\'dan büyük olmalı';
        if ($cap <= 0 || $cap > 100) $errors[] = 'Kapasite 1-100 arası olmalı';
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dtime)) {
            $errors[] = 'Kalkış tarihi formatı hatalı';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $atime)) {
            $errors[] = 'Varış tarihi formatı hatalı';
        }
        
        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            redirect('/company/trips.php');
        }

        CompanyTripModel::create($id, $company['company_id'], $dep, $dest, $dtime, $atime, $price, $cap);
        $_SESSION['success'] = 'Sefer başarıyla eklendi';
        redirect('/company/trips.php');
        
    } elseif ($act === 'update') {
        $id = (string)$_POST['id'];
        
        if (!check_trip_ownership($id, $company['company_id'])) {
            http_response_code(403);
            exit('Bu seferi güncelleme yetkiniz yok');
        }
        
        $errors = [];
        $dep  = trim((string)$_POST['departure_city']);
        $dest = trim((string)$_POST['destination_city']);
        $dtime = trim((string)$_POST['departure_time']);
        $atime = trim((string)$_POST['arrival_time']);
        $price = (int)$_POST['price'];
        $cap   = (int)$_POST['capacity'];
        
        if ($dep === '') $errors[] = 'Kalkış şehri boş olamaz';
        if ($dest === '') $errors[] = 'Varış şehri boş olamaz';
        if ($price <= 0) $errors[] = 'Fiyat 0\'dan büyük olmalı';
        if ($cap <= 0 || $cap > 100) $errors[] = 'Kapasite 1-100 arası olmalı';
        
        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            redirect('/company/trips.php');
        }

        CompanyTripModel::update($id, $dep, $dest, $dtime, $atime, $price, $cap);
        $_SESSION['success'] = 'Sefer güncellendi';
        redirect('/company/trips.php');

    } elseif ($act === 'delete') {
        $id = (string)$_POST['id'];
        
        if (!check_trip_ownership($id, $company['company_id'])) {
            http_response_code(403);
            exit('Bu seferi silme yetkiniz yok');
        }

        $success = CompanyTripModel::delete($id);
        if ($success) {
            $_SESSION['success'] = 'Sefer silindi';
        } else {
            $_SESSION['form_errors'] = ['Bu sefere ait biletler var, silinemez!'];
        }
        redirect('/company/trips.php');
    }
}

$trips = CompanyTripModel::getByCompany($company['company_id']);
$errors = $_SESSION['form_errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['form_errors'], $_SESSION['success']);

$fmtPrice = fn(int $tl) => number_format($tl, 0, ',', '.') . ' ₺';

$pdo = db();
$stmt = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = :id");
$stmt->execute([':id' => $company['company_id']]);
$companyName = $stmt->fetchColumn() ?: 'Firma';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sefer Yönetimi - <?= e($companyName) ?></title>
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

.navbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.navbar-left a {
  color: white;
  text-decoration: none;
  transition: opacity 0.3s;
}

.navbar-left a:hover {
  opacity: 0.8;
}

.company-badge {
  background: rgba(255,255,255,0.2);
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: bold;
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
  font-size: 2rem;
}

.page-header p {
  color: #666;
}

.alert {
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
}

.alert-success {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.alert-error {
  background: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.alert ul {
  margin: 0.5rem 0 0 1.5rem;
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
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
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn {
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
  font-size: 0.85rem;
  color: #888;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 2rem;
  font-weight: bold;
  color: #667eea;
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  white-space: nowrap;
}

td {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
}

tbody tr {
  transition: background 0.2s;
}

tbody tr:hover {
  background: #f9f9f9;
}

.route-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #e3f2fd;
  border-radius: 20px;
  font-weight: bold;
  color: #1565c0;
}

.capacity-indicator {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.capacity-bar {
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.capacity-fill {
  height: 100%;
  background: linear-gradient(90deg, #4caf50 0%, #ff9800 70%, #f44336 100%);
  transition: width 0.3s;
}

.actions {
  display: flex;
  gap: 0.5rem;
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

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.empty-state-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.6);
  z-index: 999;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(5px);
}

.modal.active {
  display: flex;
}

.modal-content {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  max-width: 700px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  animation: modalSlide 0.3s ease;
}

@keyframes modalSlide {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.modal-header h3 {
  color: #667eea;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #d0d0d0;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  table {
    font-size: 0.9rem;
  }
  
  th, td {
    padding: 0.75rem 0.5rem;
  }
}
</style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-left">
    <a href="/company/index.php">← Firma Paneli</a>
    <div class="company-badge">🏢 <?= e($companyName) ?></div>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <h1>🚌 Sefer Yönetimi</h1>
    <p>Seferlerinizi ekleyin, düzenleyin ve yönetin.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">
      ✓ <?= e($success) ?>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <strong>❌ Hata:</strong>
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📊</div>
      <div class="stat-label">Toplam Sefer</div>
      <div class="stat-value"><?= count($trips) ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-label">Aktif Sefer</div>
      <div class="stat-value">
        <?php 
          $now = date('Y-m-d H:i:s');
          $active = array_filter($trips, fn($t) => $t['departure_time'] > $now);
          echo count($active);
        ?>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🎫</div>
      <div class="stat-label">Satılan Bilet</div>
      <div class="stat-value">
        <?php 
          $totalSold = array_sum(array_map(fn($t) => (int)$t['capacity'] - (int)$t['available_seats'], $trips));
          echo $totalSold;
        ?>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div class="stat-label">Toplam Gelir</div>
      <div class="stat-value">
        <?php 
          $totalRevenue = 0;
          foreach ($trips as $t) {
            $sold = (int)$t['capacity'] - (int)$t['available_seats'];
            $totalRevenue += $sold * (int)$t['price'];
          }
          echo number_format($totalRevenue, 0, ',', '.');
        ?> ₺
      </div>
    </div>
  </div>

  <div class="form-card">
    <h2>➕ Yeni Sefer Ekle</h2>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="create">
      
      <div class="form-grid">
        <div class="form-group">
          <label>📍 Kalkış Şehri</label>
          <input type="text" name="departure_city" required placeholder="İstanbul" autocomplete="off">
        </div>
        
        <div class="form-group">
          <label>📍 Varış Şehri</label>
          <input type="text" name="destination_city" required placeholder="Ankara" autocomplete="off">
        </div>
        
        <div class="form-group">
          <label>🕐 Kalkış Tarihi/Saati</label>
          <input type="datetime-local" name="departure_time" required>
        </div>
        
        <div class="form-group">
          <label>🕐 Varış Tarihi/Saati</label>
          <input type="datetime-local" name="arrival_time" required>
        </div>
        
        <div class="form-group">
          <label>💰 Fiyat (₺)</label>
          <input type="number" name="price" min="1" required placeholder="250">
        </div>
        
        <div class="form-group">
          <label>🪑 Kapasite (Koltuk)</label>
          <input type="number" name="capacity" min="1" max="100" required placeholder="45">
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary">
        ➕ Sefer Ekle
      </button>
    </form>
  </div>

  <div class="table-card">
    <h2>📋 Mevcut Seferler (<?= count($trips) ?>)</h2>
    
    <?php if (!$trips): ?>
      <div class="empty-state">
        <div class="empty-state-icon">🚌</div>
        <h3>Henüz sefer eklenmemiş</h3>
        <p>Yukarıdaki formdan yeni sefer ekleyebilirsiniz.</p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Güzergah</th>
            <th>Kalkış</th>
            <th>Varış</th>
            <th>Fiyat</th>
            <th>Doluluk</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trips as $t): ?>
            <?php 
              $sold = (int)$t['capacity'] - (int)$t['available_seats'];
              $percentage = ((int)$t['capacity'] > 0) ? ($sold / (int)$t['capacity']) * 100 : 0;
              $isPast = $t['departure_time'] <= date('Y-m-d H:i:s');
            ?>
            <tr style="<?= $isPast ? 'opacity: 0.6;' : '' ?>">
              <td>
                <div class="route-badge">
                  <?= e($t['departure_city']) ?> → <?= e($t['destination_city']) ?>
                </div>
              </td>
              <td>
                <strong><?= e(date('d.m.Y', strtotime($t['departure_time']))) ?></strong><br>
                <small><?= e(date('H:i', strtotime($t['departure_time']))) ?></small>
              </td>
              <td>
                <strong><?= e(date('d.m.Y', strtotime($t['arrival_time']))) ?></strong><br>
                <small><?= e(date('H:i', strtotime($t['arrival_time']))) ?></small>
              </td>
              <td>
                <strong style="color: #667eea; font-size: 1.1rem;">
                  <?= $fmtPrice((int)$t['price']) ?>
                </strong>
              </td>
              <td>
                <div class="capacity-indicator">
                  <small><?= $sold ?> / <?= (int)$t['capacity'] ?> koltuk</small>
                  <div class="capacity-bar">
                    <div class="capacity-fill" style="width: <?= $percentage ?>%"></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="actions">
                  <?php if (!$isPast): ?>
                    <button class="btn btn-edit" onclick="editTrip(
                      '<?= e($t['id']) ?>', 
                      '<?= e($t['departure_city']) ?>', 
                      '<?= e($t['destination_city']) ?>', 
                      '<?= e($t['departure_time']) ?>', 
                      '<?= e($t['arrival_time']) ?>', 
                      <?= (int)$t['price'] ?>, 
                      <?= (int)$t['capacity'] ?>
                    )">
                      ✏️ Düzenle
                    </button>
                  <?php else: ?>
                    <small style="color: #999;">Geçmiş</small>
                  <?php endif; ?>
                  
                  <form method="post" style="display:inline" onsubmit="return confirm('Bu seferi silmek istediğinize emin misiniz?<?= $sold > 0 ? '\n\nDikkat: ' . $sold . ' adet satılmış bilet var!' : '' ?>')">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="id" value="<?= e($t['id']) ?>">
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
      <h3>✏️ Sefer Düzenle</h3>
    </div>
    
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="update">
      <input type="hidden" name="id" id="edit_id">
      
      <div class="form-grid">
        <div class="form-group">
          <label>📍 Kalkış Şehri</label>
          <input type="text" name="departure_city" id="edit_dep" required>
        </div>
        
        <div class="form-group">
          <label>📍 Varış Şehri</label>
          <input type="text" name="destination_city" id="edit_dest" required>
        </div>
        
        <div class="form-group">
          <label>🕐 Kalkış Tarihi/Saati</label>
          <input type="datetime-local" name="departure_time" id="edit_dtime" required>
        </div>
        
        <div class="form-group">
          <label>🕐 Varış Tarihi/Saati</label>
          <input type="datetime-local" name="arrival_time" id="edit_atime" required>
        </div>
        
        <div class="form-group">
          <label>💰 Fiyat (₺)</label>
          <input type="number" name="price" id="edit_price" min="1" required>
        </div>
        
        <div class="form-group">
          <label>🪑 Kapasite</label>
          <input type="number" name="capacity" id="edit_cap" min="1" max="100" required>
        </div>
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">
          ❌ İptal
        </button>
        <button type="submit" class="btn btn-primary">
          💾 Kaydet
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function editTrip(id, dep, dest, dtime, atime, price, cap) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_dep').value = dep;
  document.getElementById('edit_dest').value = dest;
  document.getElementById('edit_dtime').value = dtime.substring(0, 16);
  document.getElementById('edit_atime').value = atime.substring(0, 16);
  document.getElementById('edit_price').value = price;
  document.getElementById('edit_cap').value = cap;
  document.getElementById('editModal').classList.add('active');
}

function closeModal() {
  document.getElementById('editModal').classList.remove('active');
}

document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeModal();
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeModal();
  }
});
</script>

</body>
</html>