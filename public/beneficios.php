<?php
// public/beneficios.php — Solo para usuarios logueados (SOCIO)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
requireLogin();

$PAGE_TITLE = 'Beneficios de socio';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

include __DIR__ . '/../templates/header.php';
?>
<section class="container py-5">
  <h1 class="mb-4">Beneficios de socio</h1>
  <p class="lead">Gracias por formar parte. Aquí encontrarás tus descuentos, promos y acceso prioritario.</p>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">10% OFF en nuevas colecciones</h5>
          <p class="card-text">Aplica automáticamente al finalizar tu compra.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Envíos prioritarios</h5>
          <p class="card-text">Procesamos tus pedidos antes que los visitantes sin cuenta.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
