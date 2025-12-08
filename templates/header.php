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

// Settings (nombre tienda, colores, branding)
$settingsPath = __DIR__ . '/../inc/settings.php';
$SHOP_NAME   = 'Mi Tienda';
$SHOP_SLOGAN = '';
$PRIMARY     = '#0066FF';
$BG          = '#FFFFFF';
$TEXT        = '#1d1f23';
$MUTED       = '#6c757d';
$BORDER      = '#e9ecef';
$LOGO_URL    = '';
$FAVICON_URL = '';

if (file_exists($settingsPath)) {
  require_once $settingsPath;

  $SHOP_NAME   = setting_get('shop_name', 'Mi Tienda');
  $SHOP_SLOGAN = setting_get('shop_slogan', '');

  // Colores personalizados
  $primary_color    = setting_get('primary_color', '#0066FF');
  $background_color = setting_get('background_color', '#FFFFFF');

  // Sanitizar hex (#rrggbb o #rgb)
  $sanitize_hex = function (?string $value, string $fallback): string {
    $value = trim((string)$value);
    if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $value)) {
      return strtoupper($value);
    }
    return $fallback;
  };

  $PRIMARY = $sanitize_hex($primary_color, '#0066FF');
  $BG      = $sanitize_hex($background_color, '#FFFFFF');

  // Otros colores base (podrías exponerlos también en config si quieres)
  $TEXT   = '#1d1f23';
  $MUTED  = '#6c757d';
  $BORDER = '#e9ecef';

  // Logo y favicon (rutas relativas)
  $logo_setting    = setting_get('shop_logo', 'images/logo.svg');
  $favicon_setting = setting_get('shop_favicon', 'images/favicon.ico');

  $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
  $LOGO_URL    = $base . '/' . ltrim($logo_setting, '/');
  $FAVICON_URL = $base . '/' . ltrim($favicon_setting, '/');
}

// Título final
$title = $PAGE_TITLE ? ($PAGE_TITLE . ' | ' . $SHOP_NAME) : $SHOP_NAME;
?>
<!DOCTYPE html>
<html lang="es">
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
      --surface: #ffffff;
      --text: <?= htmlspecialchars($TEXT) ?>;
      --muted: <?= htmlspecialchars($MUTED) ?>;
      --border: <?= htmlspecialchars($BORDER) ?>;
      --shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
  </style>
</head>

<!-- body con clase para compensar navbar fija -->
<body class="has-fixed-nav">
  <?php
    // Navegación según contexto
    if ($CONTEXT === 'admin') {
      include __DIR__ . '/nav_admin.php';
    } else {
      include __DIR__ . '/nav_public.php';
    }
  ?>
  <main class="container pt-2">
    <?php if (function_exists('flash_render')): ?>
      <?php flash_render(); ?>
    <?php endif; ?>

    <?php if (!empty($BREADCRUMB)): ?>
      <nav class="mb-3 text-muted small" aria-label="breadcrumb">
        <?= $BREADCRUMB /* imprime HTML simple tipo "Dashboard / Productos" */ ?>
      </nav>
    <?php endif; ?>
