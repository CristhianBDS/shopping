<?php
// templates/nav_admin.php
$BASE = BASE_URL;
$name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Usuario';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $BASE ?>/admin/index.php">Admin</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin" aria-controls="navAdmin" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navAdmin">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= $BASE ?>/admin/index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $BASE ?>/admin/pedidos.php">Pedidos</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $BASE ?>/admin/productos.php">Productos</a></li>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <span class="navbar-text text-white-50 me-2"><?= htmlspecialchars($name) ?></span>
        <a class="btn btn-outline-light" href="<?= $BASE ?>/admin/logout.php">Logout</a>
      </div>
    </div>
  </div>
</nav>
