<?php
// templates/header.php
// Requiere: BASE_URL y TZ definidos en config/app.php
if (!isset($CONTEXT))   { $CONTEXT = 'public'; } // 'public' | 'admin'
if (!isset($PAGE_TITLE)){ $PAGE_TITLE = 'Tienda'; }

// Cargar gestor de flashes si existe
$flashPath = __DIR__ . '/../inc/flash.php';
if (file_exists($flashPath)) {
  require_once $flashPath;
}

// Cargar settings si existe (para el nombre de la tienda)
$settingsPath = __DIR__ . '/../inc/settings.php';
$SHOP_NAME = 'Mi Tienda';
if (file_exists($settingsPath)) {
  require_once $settingsPath;
  $SHOP_NAME = setting_get('shop_name', 'Mi Tienda');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($PAGE_TITLE ?: $SHOP_NAME) ?></title>

  <!-- Bootstrap 5 CSS (primero) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- CSS del proyecto (después de Bootstrap para poder sobrescribir) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/tienda.css">
</head>
<!-- Añadimos la clase para aplicar padding-top y evitar que el nav fijo tape el contenido -->
<body class="has-fixed-nav">
<?php
  // Navegación según contexto
  if ($CONTEXT === 'admin') {
    include __DIR__ . '/nav_admin.php';
  } else {
    include __DIR__ . '/nav_public.php';
  }
?>
<main class="container pt-2 pb-4">
  <?php if (function_exists('flash_render')): ?>
    <?php flash_render(); ?>
  <?php endif; ?>
