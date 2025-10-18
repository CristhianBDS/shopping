<?php
// public/logout.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
  // Validar CSRF en POST
  $token = $_POST['csrf'] ?? '';
  if (!$token || !hash_equals(auth_csrf() ?? '', $token)) {
    flash_error('Acción no permitida.');
    header('Location: ' . $BASE . '/public/index.php'); exit;
  }
}

// Cerrar sesión (para POST válido o GET fallback)
auth_logout();

// Reabrimos sesión solo para el flash
session_start();
flash_success('Sesión cerrada correctamente.');
header('Location: ' . $BASE . '/public/index.php'); exit;
