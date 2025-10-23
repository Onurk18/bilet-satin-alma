<?php
// src/controllers/AuthController.php
declare(strict_types=1);

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers/utils.php';

final class AuthController
{
    /** POST /register işlemi */
    public static function register(): void {
        csrf_check();
        $pdo = db();

        $full_name = trim($_POST['full_name'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        // Basit doğrulamalar
        $errors = [];
        if ($full_name === '') $errors[] = 'İsim boş olamaz.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-posta geçersiz.';
        if (strlen($password) < 6) $errors[] = 'Parola en az 6 karakter olmalı.';
        if ($password !== $password2) $errors[] = 'Parolalar eşleşmiyor.';

        // E-posta benzersiz mi?
        $stmt = $pdo->prepare('SELECT 1 FROM User WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        if ($stmt->fetchColumn()) $errors[] = 'Bu e-posta zaten kayıtlı.';

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old'] = compact('full_name','email');
            redirect('/register.php');
        }

        // Hash ve ekleme
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id   = uuidv4(); // basit Id

        $stmt = $pdo->prepare("
          INSERT INTO User (id, full_name, email, password, role, company_id, balance, created_at)
          VALUES (:id, :name, :email, :pass, 'USER', NULL, 800, datetime('now'))
        ");
        $stmt->execute([
            ':id'   => $id,
            ':name' => $full_name,
            ':email'=> $email,
            ':pass' => $hash,
        ]);

        // Oturum aç
        $_SESSION['user'] = ['id'=>$id, 'full_name'=>$full_name, 'email'=>$email, 'role'=>'USER'];
        redirect('/index.php');
    }

    /** POST /login işlemi */
    public static function login(): void {
        csrf_check();
        $pdo = db();

        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT id, full_name, email, password, role FROM User WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        $u = $stmt->fetch();

        $ok = $u && password_verify($password, (string)$u['password']);
        if (!$ok) {
            $_SESSION['form_errors'] = ['E-posta veya parola hatalı.'];
            $_SESSION['form_old'] = ['email' => $email];
            redirect('/login.php');
        }

        $_SESSION['user'] = [
            'id'        => $u['id'],
            'full_name' => $u['full_name'],
            'email'     => $u['email'],
            'role'      => $u['role'],
        ];
        redirect('/index.php');
    }

    /** GET /logout */
    public static function logout(): void {
        boot_session();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('/login.php');
    }
}
