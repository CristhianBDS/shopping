<?php
// admin/logout.php
require_once __DIR__ . '/../config/app.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Limpieza de sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params['path'], $params['domain'],
    $params['secure'], $params['httponly']
  );
}
session_destroy();

// Redirige al login con mensaje
header('Location: ' . BASE_URL . '/admin/login.php?bye=1');
exit;
