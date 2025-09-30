<?php
// templates/nav.php
$base = rtrim(BASE_URL, '/');
$uri  = $_SERVER['REQUEST_URI'] ?? '';
function active($needle, $uri)
{
  return (strpos($uri, $needle) !== false) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= $base ?>/public/index.php">Shopping</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <!-- Cliente -->
        <li class="nav-item"><a class="nav-link <?= active('/public/catalogo.php', $uri) ?>" href="<?= $base ?>/public/catalogo.php">Catálogo</a></li>
        <li class="nav-item"><a class="nav-link <?= active('/public/carrito.php', $uri) ?>" href="<?= $base ?>/public/carrito.php">Carrito</a></li>
        <li class="nav-item"><a class="nav-link <?= active('/public/checkout.php', $uri) ?>" href="<?= $base ?>/public/checkout.php">Checkout</a></li>

        <!-- Admin -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= (active('/admin/', $uri) ? 'active' : '') ?>" href="#" data-bs-toggle="dropdown">Admin</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= $base ?>/admin/index.php">Panel</a></li>
            <li><a class="dropdown-item" href="<?= $base ?>/admin/pedidos.php">Pedidos</a></li>
            <li><a class="dropdown-item" href="<?= $base ?>/admin/productos.php">Productos</a></li>
          </ul>
        </li>
      </ul>

      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link <?= active('/login.php', $uri) ?>" href="<?= $base ?>/admin/login.php">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/logout.php">Salir</a></li>
      </ul>
    </div>
  </div>
</nav>