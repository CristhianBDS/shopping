<?php
// templates/header.php
@include_once __DIR__ . '/../config/app.php';
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/shopping'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopping</title>

  <!-- Bootstrap (CDN) para maqueta rápida y responsive -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- CSS del proyecto -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/tienda.css">

  <!-- BASE_URL expuesta para JS -->
  <script>window.BASE_URL = "<?= BASE_URL ?>";</script>
</head>
<body class="bg-light">
  <?php
    // Incluimos la barra de navegación Bootstrap
    $NAV_PATH = __DIR__ . '/nav.php';
    if (file_exists($NAV_PATH)) { include $NAV_PATH; }
  ?>
  <main class="container py-4">
