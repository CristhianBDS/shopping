<?php
// Sesiones (para el admin luego)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Cargar configuración y DB
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/db.php';

// Aplicar zona horaria
date_default_timezone_set(TZ);
