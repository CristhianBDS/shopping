<?php
// templates/header.php
// Requiere: BASE_URL y TZ definidos en config/app.php

if (!isset($CONTEXT))    { $CONTEXT = 'public'; } // 'public' | 'admin'
if (!isset($PAGE_TITLE)) { $PAGE_TITLE = 'Tienda'; }

require_once __DIR__ . '/../config/security.php';
send_security_headers();

// Flashes
$flashPath = __DIR__ . '/../inc/flash.php';
if (file_exists($flashPath)) {
  require_once $flashPath;
}

// Settings (nombre tienda, colores, branding, tema)
$settingsPath = __DIR__ . '/../inc/settings.php';

$SHOP_NAME   = 'Mi Tienda';
$SHOP_SLOGAN = '';

$PRIMARY = '#0066FF';
$BG      = '#FFFFFF';
$SURFACE = '#FFFFFF';
$TEXT    = '#1d1f23';
$MUTED   = '#6c757d';
$BORDER  = '#e9ecef';

$LOGO_URL    = '';
$FAVICON_URL = '';

$THEME_MODE = 'light'; // light | dark | auto

if (file_exists($settingsPath)) {
  require_once $settingsPath;

  // Nombre y slogan
  $SHOP_NAME   = setting_get('shop_name', 'Mi Tienda');
  $SHOP_SLOGAN = setting_get('shop_slogan', '');

  // Colores personalizados (modo claro)
  $primary_color    = setting_get('primary_color', '#0066FF');
  $background_color = setting_get('background_color', '#FFFFFF');

  // Modo de tema (guardado en configuración)
  $THEME_MODE = setting_get('theme_mode', 'light'); // light | dark | auto

  // Sanitizar hex (#rrggbb o #rgb)
  $sanitize_hex = function (?string $value, string $fallback): string {
    $value = trim((string)$value);
    if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $value)) {
      return strtoupper($value);
    }
    return $fallback;
  };

  $PRIMARY = $sanitize_hex($primary_color, '#0066FF');

  // Paleta según modo de tema
  if (!in_array($THEME_MODE, ['light', 'dark', 'auto'], true)) {
    $THEME_MODE = 'light';
  }

  if ($THEME_MODE === 'dark') {
    // PALETA OSCURA
    $BG      = '#020617'; // fondo general
    $SURFACE = '#020617'; // tarjetas / navbar
    $TEXT    = '#E5E7EB';
    $MUTED   = '#9CA3AF';
    $BORDER  = '#1F2937';
  } else {
    // PALETA CLARA (light o auto -> claro por ahora)
    $BG      = $sanitize_hex($background_color, '#FFFFFF');
    $SURFACE = '#FFFFFF';
    $TEXT    = '#1d1f23';
    $MUTED   = '#6c757d';
    $BORDER  = '#e9ecef';
  }

  // Logo y favicon (rutas relativas)
  $logo_setting    = setting_get('shop_logo', 'images/logo.svg');
  $favicon_setting = setting_get('shop_favicon', 'images/favicon.ico');

  $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
  $LOGO_URL    = $base . '/' . ltrim($logo_setting, '/');
  $FAVICON_URL = $base . '/' . ltrim($favicon_setting, '/');
}

// Título final
$title = $PAGE_TITLE ? ($PAGE_TITLE . ' | ' . $SHOP_NAME) : $SHOP_NAME;

// data-theme para CSS (solo usamos dark/light, auto = light de momento)
$dataTheme = ($THEME_MODE === 'dark') ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($dataTheme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>

  <?php if ($SHOP_SLOGAN): ?>
    <meta name="description" content="<?= htmlspecialchars($SHOP_SLOGAN) ?>">
  <?php endif; ?>

  <?php if ($FAVICON_URL): ?>
    <link rel="icon" href="<?= htmlspecialchars($FAVICON_URL) ?>">
  <?php endif; ?>

  <!-- Bootstrap 5 CSS (primero) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- CSS del proyecto (después de Bootstrap para poder sobrescribir) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/tienda.css">

  <!-- Variables de color dinámicas para toda la UI -->
  <style>
    :root {
      --primary: <?= htmlspecialchars($PRIMARY) ?>;
      --primary-600: <?= htmlspecialchars($PRIMARY) ?>;
      --primary-700: <?= htmlspecialchars($PRIMARY) ?>;

      --bg: <?= htmlspecialchars($BG) ?>;
      --surface: <?= htmlspecialchars($SURFACE) ?>;
      --text: <?= htmlspecialchars($TEXT) ?>;
      --muted: <?= htmlspecialchars($MUTED) ?>;
      --border: <?= htmlspecialchars($BORDER) ?>;

      --shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
  </style>
</head>

<body class="has-fixed-nav">
  <?php
    // Navegación según contexto (admin / pública)
    if ($CONTEXT === 'admin') {
      include __DIR__ . '/nav_admin.php';
    } else {
      include __DIR__ . '/nav_public.php';
    }
  ?>
<!-- 🔔 Contenedor global para notificaciones JS -->
  <div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>

  <main class="container pt-2">
    <?php if (function_exists('flash_render')): ?>
      <div class="flash-container">
        <?php flash_render(); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($BREADCRUMB)): ?>
      <nav class="mb-3 text-muted small" aria-label="breadcrumb">
        <?= $BREADCRUMB /* imprime HTML simple tipo "Dashboard / Productos" */ ?>
      </nav>
    <?php endif; ?>
