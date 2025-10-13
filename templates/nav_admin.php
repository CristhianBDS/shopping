<?php
// templates/nav_admin.php
require_once __DIR__ . '/../inc/auth.php';

$BASE = BASE_URL;
$name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Usuario';

// Detectar página activa por path
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$active = function(string $file) use ($path): string {
  return str_ends_with($path, "/admin/$file") ? ' active' : '';
};
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $BASE ?>/admin/index.php">Admin</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin" aria-controls="navAdmin" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navAdmin">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link<?= $active('index.php') ?>" href="<?= $BASE ?>/admin/index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('pedidos.php') ?>" href="<?= $BASE ?>/admin/pedidos.php">Pedidos</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('productos.php') ?>" href="<?= $BASE ?>/admin/productos.php">Productos</a></li>
        <?php if (can('usuarios:list')): ?>
          <li class="nav-item"><a class="nav-link<?= $active('usuarios.php') ?>" href="<?= $BASE ?>/admin/usuarios.php">Usuarios</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link<?= $active('configuracion.php') ?>" href="<?= $BASE ?>/admin/configuracion.php">Configuración</a></li>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <span class="navbar-text text-white-50 me-2"><?= htmlspecialchars($name) ?></span>
        <a class="btn btn-outline-light" href="<?= $BASE ?>/admin/logout.php">Logout</a>
      </div>
    </div>
  </div>
</nav>
