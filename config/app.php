<?php
// config/app.php

// ─────────────────────────────────────────────
// ENTORNO: 'dev' (localhost) o 'prod' (Hostinger)
// ─────────────────────────────────────────────
define('APP_ENV', 'dev'); // ← cuando subas a Hostinger, cambia a 'prod'

// ─────────────────────────────────────────────
// BASE_URL según entorno
// ─────────────────────────────────────────────
if (APP_ENV === 'dev') {
    // XAMPP
    define('BASE_URL', 'http://localhost/shopping');
} else {
    // Hostinger (cambia por tu dominio real)
    define('BASE_URL', 'https://tudominio.com'); 
}

// ─────────────────────────────────────────────
// Zona horaria y modo debug
// ─────────────────────────────────────────────
define('TZ', 'Europe/Madrid');

if (APP_ENV === 'dev') {
    define('DEBUG', true);
} else {
    define('DEBUG', false);
}

// Config tienda
define('WHATSAPP_NUMBER', '+34600000000');
define('CURRENCY', 'EUR');

// ─────────────────────────────────────────────
// Config DB según entorno (lo usaremos en db.php)
// ─────────────────────────────────────────────
if (APP_ENV === 'dev') {
    // XAMPP
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'shopping');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // Hostinger (ajusta estos valores a los que te dé Hostinger)
    define('DB_HOST', 'localhost');           // normalmente 'localhost' en Hostinger
    define('DB_NAME', 'TU_BD_HOSTINGER');     // ej: u123456789_shopping
    define('DB_USER', 'TU_USUARIO_HOSTINGER');// ej: u123456789_root
    define('DB_PASS', 'TU_PASSWORD_AQUI');
}

// ─────────────────────────────────────────────
// Manejo de errores según DEBUG
// ─────────────────────────────────────────────
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
