<?php
// public/index.php — Home de la tienda
require_once __DIR__ . '/../config/app.php';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
include __DIR__ . '/../templates/header.php';

// Hero opcional si existe /images/hero.jpg
$heroFile = __DIR__ . '/../images/hero.jpg';
$heroUrl  = is_file($heroFile) ? ($BASE . '/images/hero.jpg') : null;
?>

<section class="py-5 text-center">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <h1 class="display-5 fw-bold">Bienvenido a Shopping</h1>
      <p class="lead text-muted mb-4">
        Calidad, precios justos y envíos rápidos. Encuentra tus productos favoritos en un solo lugar.
      </p>
      <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-primary btn-lg">Ver catálogo</a>
    </div>
  </div>
</section>

<?php if ($heroUrl): ?>
<section class="mb-4">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card border-0 shadow-sm">
        <div class="row g-0 align-items-center">
          <div class="col-md-5">
            <img src="<?= $heroUrl ?>" class="img-fluid rounded-start" alt="Promoción destacada">
          </div>
          <div class="col-md-7">
            <div class="card-body">
              <h3 class="card-title mb-2">Novedades de temporada</h3>
              <p class="card-text text-muted mb-3">
                Descubre lanzamientos recientes y ofertas de bienvenida.
              </p>
              <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-outline-primary">Ir al catálogo</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="mb-4">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🚚 Envíos rápidos</h5>
          <p class="card-text text-muted">Entregas 24/48h en península y seguimiento en tiempo real.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🔒 Pago seguro</h5>
          <p class="card-text text-muted">Pasarelas certificadas, tarjetas y opciones locales confiables.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🤝 Soporte cercano</h5>
          <p class="card-text text-muted">Atención por correo y WhatsApp. Cambios y devoluciones fáciles.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="text-center py-4">
  <p class="text-muted mb-2">¿Listo para empezar?</p>
  <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-dark">Explorar catálogo</a>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
