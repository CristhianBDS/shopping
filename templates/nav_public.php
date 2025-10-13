<?php
// templates/nav_public.php
if (session_status() === PHP_SESSION_NONE) session_start();
$BASE = BASE_URL;

// Detectar página activa por path
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$active = function(string $file) use ($path): string {
  return str_ends_with($path, "/public/$file") || str_ends_with($path, "/$file") ? ' active' : '';
};

$isUser = !empty($_SESSION['user']) && (($_SESSION['user']['role'] ?? 'user') === 'user');
$userName = $isUser ? ($_SESSION['user']['name'] ?? 'Cliente') : null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $BASE ?>/public/index.php">Tienda</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublic" aria-controls="navPublic" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navPublic">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link<?= $active('index.php') ?>" href="<?= $BASE ?>/public/index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('catalogo.php') ?>" href="<?= $BASE ?>/public/catalogo.php">Catálogo</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('contacto.php') ?>" href="<?= $BASE ?>/public/contacto.php">Contacto</a></li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <!-- Carrito -->
        <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/carrito.php" title="Carrito">
          🛒 <span class="d-none d-sm-inline">Carrito</span>
        </a>

        <!-- Cuenta / Socios -->
        <?php if ($isUser): ?>
          <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle btn-account" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Cuenta">
              <span class="icon-account">👤</span>
              <span class="d-none d-sm-inline"><?= htmlspecialchars($userName) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= $BASE ?>/public/cuenta.php">Mi cuenta</a></li>
              <li><a class="dropdown-item" href="<?= $BASE ?>/public/pedidos.php">Mis pedidos</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= $BASE ?>/public/logout.php">Salir</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-outline-primary btn-account" href="<?= $BASE ?>/public/registro.php" title="Hazte socio">
            <span class="icon-account">👤</span>
            <span class="d-none d-sm-inline">Registrarse</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
