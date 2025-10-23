<?php
declare(strict_types=1);
require __DIR__ . '/../src/helpers/utils.php';
require __DIR__ . '/../src/controllers/AuthController.php';

boot_session();

$_SESSION['logout_success'] = true;

AuthController::logout();