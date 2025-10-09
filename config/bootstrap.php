<?php
// config/bootstrap.php — Inicialización global de sesión, errores y zona horaria

// Evita reiniciar sesión si ya está activa
if (session_status() === PHP_SESSION_NONE) {
  // Configurar parámetros de cookie antes de iniciar sesión
  session_set_cookie_params([
    'lifetime' => 0,              // Sesión expira al cerrar el navegador
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']), // Solo HTTPS si aplica
    'httponly' => true,
    'samesite' => 'Lax'
  ]);

  session_start();
}

// Configuración de zona horaria
if (defined('TZ')) {
  date_default_timezone_set(TZ);
} else {
  date_default_timezone_set('Europe/Madrid');
}

// Modo depuración
if (defined('DEBUG') && DEBUG) {
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
} else {
  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
  ini_set('display_errors', '0');
}

// Incluir funciones globales si existen
$helpers = __DIR__ . '/../inc/helpers.php';
if (file_exists($helpers)) {
  require_once $helpers;
}
