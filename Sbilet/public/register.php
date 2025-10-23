<?php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/controllers/AuthController.php';

boot_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthController::register();
}

$old    = $_SESSION['form_old']    ?? ['full_name'=>'','email'=>''];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_old'], $_SESSION['form_errors']);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kayıt Ol - Sbilet</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body { 
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.auth-container {
  width: 100%;
  max-width: 500px;
}

.logo-section {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-section h1 {
  font-size: 3rem;
  color: white;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.logo-section p {
  color: rgba(255,255,255,0.9);
  font-size: 1.1rem;
}

.auth-card {
  background: white;
  border-radius: 20px;
  padding: 3rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.auth-card h2 {
  color: #333;
  margin-bottom: 0.5rem;
  font-size: 1.8rem;
}

.auth-card .subtitle {
  color: #666;
  margin-bottom: 2rem;
}

.alert {
  background: #fee;
  border: 1px solid #fcc;
  color: #c00;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
}

.alert ul {
  margin: 0.5rem 0 0 1.5rem;
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

.input-wrapper {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 1.2rem;
}

.form-group input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.password-strength {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: #666;
}

.strength-bar {
  height: 4px;
  background: #e0e0e0;
  border-radius: 2px;
  margin-top: 0.5rem;
  overflow: hidden;
}

.strength-bar-fill {
  height: 100%;
  width: 0%;
  background: #4caf50;
  transition: width 0.3s;
}

.info-box {
  background: #e3f2fd;
  border-left: 4px solid #2196f3;
  padding: 1rem;
  border-radius: 5px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  color: #1565c0;
}

.btn-register {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s;
  margin-top: 1rem;
}

.btn-register:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.login-link {
  text-align: center;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e0e0e0;
  color: #666;
}

.login-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: bold;
  transition: color 0.3s;
}

.login-link a:hover {
  color: #764ba2;
}

.back-link {
  text-align: center;
  margin-top: 1.5rem;
}

.back-link a {
  color: white;
  text-decoration: none;
  opacity: 0.9;
  transition: opacity 0.3s;
}

.back-link a:hover {
  opacity: 1;
}

.password-match {
  font-size: 0.85rem;
  margin-top: 0.5rem;
}

.match-success {
  color: #4caf50;
}

.match-error {
  color: #f44336;
}

@media (max-width: 480px) {
  .auth-card {
    padding: 2rem;
  }
  
  .logo-section h1 {
    font-size: 2rem;
  }
}
</style>
</head>
<body>

<div class="auth-container">
  <div class="logo-section">
    <h1>🚌 Sbilet</h1>
    <p>Güvenli ve Hızlı Bilet Sistemi</p>
  </div>

  <div class="auth-card">
    <h2>Hesap Oluştur 🎉</h2>
    <p class="subtitle">Bilet almaya başlamak için kayıt olun</p>

    <?php if ($errors): ?>
      <div class="alert">
        <strong>❌ Kayıt başarısız</strong>
        <ul>
          <?php foreach($errors as $e): ?>
            <li><?= e($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="info-box">
      💰 <strong>Hoş Geldin Bonusu:</strong> Kayıt olunca hesabınıza 800 ₺ yüklenir!
    </div>

    <form method="post" action="/register.php" id="registerForm">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      
      <div class="form-group">
        <label for="full_name">Ad Soyad</label>
        <div class="input-wrapper">
          <span class="input-icon">👤</span>
          <input 
            type="text" 
            id="full_name" 
            name="full_name" 
            value="<?= e($old['full_name']) ?>"
            placeholder="Ahmet Yılmaz"
            required
            autofocus
          >
        </div>
      </div>

      <div class="form-group">
        <label for="email">E-posta Adresi</label>
        <div class="input-wrapper">
          <span class="input-icon">📧</span>
          <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= e($old['email']) ?>"
            placeholder="ornek@email.com"
            required
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Parola</label>
        <div class="input-wrapper">
          <span class="input-icon">🔒</span>
          <input 
            type="password" 
            id="password" 
            name="password" 
            placeholder="En az 6 karakter"
            required
            minlength="6"
          >
        </div>
        <div class="password-strength">
          <div class="strength-bar">
            <div class="strength-bar-fill" id="strengthBar"></div>
          </div>
          <small id="strengthText">Parola gücü: -</small>
        </div>
      </div>

      <div class="form-group">
        <label for="password2">Parola (Tekrar)</label>
        <div class="input-wrapper">
          <span class="input-icon">🔒</span>
          <input 
            type="password" 
            id="password2" 
            name="password2" 
            placeholder="Parolayı tekrar girin"
            required
            minlength="6"
          >
        </div>
        <div id="matchMessage" class="password-match"></div>
      </div>

      <button type="submit" class="btn-register">
        📝 Hesap Oluştur
      </button>
    </form>

    <div class="login-link">
      Zaten hesabınız var mı? <a href="/login.php">Giriş yapın</a>
    </div>
  </div>

  <div class="back-link">
    <a href="/index.php">← Anasayfaya Dön</a>
  </div>
</div>

<script>
const passwordInput = document.getElementById('password');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

passwordInput.addEventListener('input', function() {
  const password = this.value;
  let strength = 0;
  
  if (password.length >= 6) strength += 25;
  if (password.length >= 8) strength += 25;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
  if (/[0-9]/.test(password)) strength += 25;
  
  strengthBar.style.width = strength + '%';
  
  if (strength <= 25) {
    strengthBar.style.background = '#f44336';
    strengthText.textContent = 'Parola gücü: Zayıf';
  } else if (strength <= 50) {
    strengthBar.style.background = '#ff9800';
    strengthText.textContent = 'Parola gücü: Orta';
  } else if (strength <= 75) {
    strengthBar.style.background = '#2196f3';
    strengthText.textContent = 'Parola gücü: İyi';
  } else {
    strengthBar.style.background = '#4caf50';
    strengthText.textContent = 'Parola gücü: Güçlü';
  }
});

const password2Input = document.getElementById('password2');
const matchMessage = document.getElementById('matchMessage');

password2Input.addEventListener('input', function() {
  const password = passwordInput.value;
  const password2 = this.value;
  
  if (password2.length === 0) {
    matchMessage.textContent = '';
    return;
  }
  
  if (password === password2) {
    matchMessage.textContent = '✓ Parolalar eşleşiyor';
    matchMessage.className = 'password-match match-success';
  } else {
    matchMessage.textContent = '✗ Parolalar eşleşmiyor';
    matchMessage.className = 'password-match match-error';
  }
});
</script>

</body>
</html>