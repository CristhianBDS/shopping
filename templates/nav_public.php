<?php
// templates/nav_public.php
$BASE = BASE_URL;

// Detectar página activa por path
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$active = function(string $file) use ($path): string {
  return str_ends_with($path, "/public/$file") ? ' active' : '';
};
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $BASE ?>/public/index.php">MiTienda</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublic" aria-controls="navPublic" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navPublic">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= $active('index.php') ?>" href="<?= $BASE ?>/public/index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $active('catalogo.php') ?>" href="<?= $BASE ?>/public/catalogo.php">Catálogo</a>
        </li>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-secondary position-relative" href="<?= $BASE ?>/public/carrito.php" id="btnCart">
          <span class="me-1">Carrito</span>
          <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">0</span>
        </a>
        <a class="btn btn-primary<?= $active('carrito.php') ?>" href="<?= $BASE ?>/public/carrito.php">Ir a carrito</a>
      </div>
    </div>
  </div>
</nav>

<script>
// Badge del carrito: on load + cambios en otras pestañas + evento custom
(function(){
  function getCartQty() {
    try {
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      return cart.reduce((acc, it) => acc + (parseInt(it.qty || 1, 10) || 1), 0);
    } catch { return 0; }
  }
  function refreshCartBadge() {
    var b = document.getElementById('cartBadge');
    if (b) b.textContent = String(getCartQty());
  }
  // Inicial
  refreshCartBadge();
  // Al cambiar en otras pestañas
  window.addEventListener('storage', function(ev){
    if (ev && ev.key === 'cart') refreshCartBadge();
  });
  // Evento custom para este mismo tab (lánzalo tras modificar el carrito)
  window.addEventListener('cart:updated', refreshCartBadge);
})();
</script>
