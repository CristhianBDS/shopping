<?php
// templates/nav_public.php
$BASE = BASE_URL;
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
          <a class="nav-link" href="<?= $BASE ?>/public/index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= $BASE ?>/public/catalogo.php">Catálogo</a>
        </li>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-secondary position-relative" href="<?= $BASE ?>/public/carrito.php" id="btnCart">
          <span class="me-1">Carrito</span>
          <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">0</span>
        </a>
        <a class="btn btn-primary" href="<?= $BASE ?>/public/carrito.php">Ir a carrito</a>
      </div>
    </div>
  </div>
</nav>

<script>
// Actualiza el badge del carrito desde localStorage
(function(){
  try {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const qty = cart.reduce((acc, it) => acc + (parseInt(it.qty || 1) || 1), 0);
    const b = document.getElementById('cartBadge');
    if (b) b.textContent = qty;
  } catch (e) {}
})();
</script>
