<?php
// public/logout.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/flash.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

if (!headers_sent() && function_exists('flash_info')) {
  session_start(); // para poder setear el flash tras destroy
  flash_info('Sesión cerrada correctamente.');
}
header('Location: '.BASE_URL.'/public/index.php'); exit;
