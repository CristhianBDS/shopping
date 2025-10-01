<?php
// templates/header.php
// Requiere: BASE_URL y TZ definidos en config/app.php
if (!isset($CONTEXT))   { $CONTEXT = 'public'; } // 'public' | 'admin'
if (!isset($PAGE_TITLE)){ $PAGE_TITLE = 'Tienda'; }

// Cargar gestor de flashes si existe (no rompe si aún no lo agregaste)
$flashPath = __DIR__ . '/../inc/flash.php';
if (file_exists($flashPath)) {
  require_once $flashPath;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($PAGE_TITLE) ?></title>

  <!-- CSS del proyecto -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/tienda.css">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php
  // Navegación según contexto
  if ($CONTEXT === 'admin') {
    include __DIR__ . '/nav_admin.php';
  } else {
    include __DIR__ . '/nav_public.php';
  }
?>
<main class="container py-4">
  <?php if ($CONTEXT === 'admin' && function_exists('flash_render')): ?>
    <?php flash_render(); ?>
  <?php endif; ?>
