<?php
// templates/nav_admin.php — Navbar del panel admin (Bootstrap, dark, fixed-top)
// Logout por POST con CSRF. Activo por prefijo de ruta.

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/auth.php';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$user = currentUser();
$name = $user['name'] ?? 'Administrador';
$csrf = auth_csrf();

// Detectar activo por prefijo de ruta
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$active = function (string $prefix) use ($path): string {
  return str_starts_with($path, $prefix) ? ' active' : '';
};
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom fixed-top" id="adminNav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $BASE ?>/admin/index.php">Admin</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin"
            aria-controls="navAdmin" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navAdmin">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link<?= $active($BASE . '/admin/index.php') ?>"    href="<?= $BASE ?>/admin/index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link<?= $active($BASE . '/admin/pedidos') ?>"      href="<?= $BASE ?>/admin/pedidos.php">Pedidos</a></li>
        <li class="nav-item"><a class="nav-link<?= $active($BASE . '/admin/productos') ?>"    href="<?= $BASE ?>/admin/productos.php">Productos</a></li>
        <li class="nav-item"><a class="nav-link<?= $active($BASE . '/admin/usuarios') ?>"     href="<?= $BASE ?>/admin/usuarios.php">Usuarios</a></li>
        <li class="nav-item"><a class="nav-link<?= $active($BASE . '/admin/calendario') ?>"   href="<?= $BASE ?>/admin/calendario.php">Calendario</a></li>
        <li class="nav-item"><a class="nav-link<?= $active($BASE . '/admin/configuracion') ?>" href="<?= $BASE ?>/admin/configuracion.php">Configuración</a></li>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-light" href="<?= $BASE ?>/public/index.php">Ver tienda</a>

        <div class="dropdown">
          <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            👤 <span class="d-none d-sm-inline"><?= htmlspecialchars($name) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= $BASE ?>/public/cuenta.php">Mi cuenta</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <!-- Logout por POST con CSRF -->
              <form action="<?= $BASE ?>/public/logout.php" method="post" class="px-3 py-2">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <button type="submit" class="btn btn-sm btn-danger w-100">Salir</button>
              </form>
            </li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</nav>
