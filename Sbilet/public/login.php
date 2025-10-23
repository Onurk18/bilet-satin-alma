<?php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/controllers/AuthController.php';

boot_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthController::login();
}

$old    = $_SESSION['form_old']    ?? ['email'=>''];
$errors = $_SESSION['form_errors'] ?? [];
$logoutSuccess = $_SESSION['logout_success'] ?? false;
unset($_SESSION['form_old'], $_SESSION['form_errors'], $_SESSION['logout_success']);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Giriş Yap - Sbilet</title>
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
  max-width: 450px;
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

.btn-login {
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

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.divider {
  text-align: center;
  margin: 2rem 0;
  color: #999;
  position: relative;
}

.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 40%;
  height: 1px;
  background: #e0e0e0;
}

.divider::before {
  left: 0;
}

.divider::after {
  right: 0;
}

.register-link {
  text-align: center;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e0e0e0;
  color: #666;
}

.register-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: bold;
  transition: color 0.3s;
}

.register-link a:hover {
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
    <h2>Hoş Geldiniz! 👋</h2>
    <p class="subtitle">Hesabınıza giriş yapın</p>

    <?php if ($errors): ?>
      <div class="alert">
        <strong>❌ Giriş başarısız</strong>
        <ul>
          <?php foreach($errors as $e): ?>
            <li><?= e($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="/login.php">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      
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
            autofocus
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
            placeholder="••••••••"
            required
          >
        </div>
      </div>

      <button type="submit" class="btn-login">
        🚪 Giriş Yap
      </button>
    </form>

    <div class="register-link">
      Hesabınız yok mu? <a href="/register.php">Hemen kayıt olun</a>
    </div>
  </div>

  <div class="back-link">
    <a href="/index.php">← Anasayfaya Dön</a>
  </div>
</div>

</body>
</html>