<?php
// templates/header.php — mínimamente funcional para que cargue CSS/JS y BASE_URL
// Intenta traer BASE_URL desde tu config; si no existe, define fallback local.
@include_once __DIR__ . '/../config/app.php';
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/shopping'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopping</title>

  <!-- CSS global + público (para que el catálogo vuelva a verse con estilo) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/tienda.css">

  <!-- Exponer BASE_URL para JS (fetch, imágenes, etc.) -->
  <script>window.BASE_URL = "<?= BASE_URL ?>";</script>
</head>
<body>
<header class="site-header">
  <nav class="admin-nav">
    <a href="<?= BASE_URL ?>/admin/index.php">Panel</a>
    <a href="<?= BASE_URL ?>/admin/pedidos.php">Pedidos</a>
    <a href="<?= BASE_URL ?>/admin/productos.php">Productos</a>
    <a href="<?= BASE_URL ?>/logout.php">Salir</a>
  </nav>
</header>
