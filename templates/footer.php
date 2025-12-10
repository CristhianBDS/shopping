<?php
// templates/footer.php — Footer público + scripts
if (!defined('BASE_URL')) { require_once __DIR__ . '/../config/app.php'; }
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

// Cargar settings para datos de marca y redes
require_once __DIR__ . '/../inc/settings.php';

$shop_name   = setting_get('shop_name', 'Mi Tienda');
$shop_slogan = setting_get('shop_slogan', '');
$shop_address = setting_get('shop_address', '');
$shop_city    = setting_get('shop_city', '');
$shop_phone   = setting_get('shop_phone', '');
$contact_email = setting_get('contact_email', '');

$instagram_url = setting_get('instagram_url', '');
$facebook_url  = setting_get('facebook_url', '');
$tiktok_url    = setting_get('tiktok_url', '');

$logo_setting    = setting_get('shop_logo', 'images/logo.svg');
$footer_legal    = setting_get('footer_legal', 'Todos los derechos reservados.');

$logo_url = rtrim($BASE, '/') . '/' . ltrim($logo_setting, '/');
$year     = date('Y');
?>
<footer class="site-footer site-footer--light mt-auto" role="contentinfo">
  <div class="footer-main py-5">
    <div class="container">
      <div class="row gy-4">
        <!-- Columna: Ayuda -->
        <nav class="col-12 col-md-4" aria-labelledby="footer-ayuda-title">
          <h6 id="footer-ayuda-title" class="footer-title">Ayuda</h6>
          <ul class="footer-list">
            <li><a href="<?= $BASE ?>/public/ayuda.php">Obtener ayuda</a></li>
            <li><a href="<?= $BASE ?>/public/pedidos.php">Estado del pedido</a></li>
            <li><a href="<?= $BASE ?>/public/envios.php">Envíos y entregas</a></li>
            <li><a href="<?= $BASE ?>/public/devoluciones.php">Devoluciones</a></li>
          </ul>
        </nav>

        <!-- Columna: Redes -->
        <div class="col-6 col-md-4">
          <h6 class="footer-title">Redes</h6>
          <ul class="footer-list footer-social">
            <?php if ($tiktok_url): ?>
              <li>
                <a class="footer-social-link" href="<?= htmlspecialchars($tiktok_url) ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                  <img src="<?= $BASE ?>/images/social/tiktok.svg" width="20" height="20" alt="">
                  <span>TikTok</span>
                </a>
              </li>
            <?php endif; ?>
            <?php if ($instagram_url): ?>
              <li>
                <a class="footer-social-link" href="<?= htmlspecialchars($instagram_url) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                  <img src="<?= $BASE ?>/images/social/instagram.svg" width="20" height="20" alt="">
                  <span>Instagram</span>
                </a>
              </li>
            <?php endif; ?>
            <?php if ($facebook_url): ?>
              <li>
                <a class="footer-social-link" href="<?= htmlspecialchars($facebook_url) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                  <img src="<?= $BASE ?>/images/social/facebook.svg" width="20" height="20" alt="">
                  <span>Facebook</span>
                </a>
              </li>
            <?php endif; ?>
            <?php if (!$tiktok_url && !$instagram_url && !$facebook_url): ?>
              <li class="text-muted small">Configura tus redes en Configuración &gt; Redes sociales.</li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Columna: Descuentos -->
        <nav class="col-6 col-md-4" aria-labelledby="footer-descuentos-title">
          <h6 id="footer-descuentos-title" class="footer-title">Descuentos</h6>
          <ul class="footer-list">
            <li><a href="<?= $BASE ?>/public/descuento-estudiante.php">Estudiante</a></li>
            <li><a href="<?= $BASE ?>/public/descuento-docente.php">Docente</a></li>
            <li><a href="<?= $BASE ?>/public/descuento-servicios.php">Servicios de emergencias</a></li>
          </ul>
        </nav>
      </div>

      <!-- Bloque de datos de contacto -->
      <?php if ($shop_address || $shop_city || $shop_phone || $contact_email): ?>
        <div class="row mt-4">
          <div class="col-12 col-md-8">
            <p class="mb-1 fw-semibold"><?= htmlspecialchars($shop_name) ?></p>
            <?php if ($shop_address || $shop_city): ?>
              <p class="mb-0 text-body-secondary">
                <?= htmlspecialchars(trim($shop_address . ' ' . $shop_city)) ?>
              </p>
            <?php endif; ?>
          </div>
          <div class="col-12 col-md-4 small text-md-end text-body-secondary">
            <?php if ($shop_phone): ?>
              <div>Tel: <a href="tel:<?= htmlspecialchars($shop_phone) ?>"><?= htmlspecialchars($shop_phone) ?></a></div>
            <?php endif; ?>
            <?php if ($contact_email): ?>
              <div>Email: <a href="mailto:<?= htmlspecialchars($contact_email) ?>"><?= htmlspecialchars($contact_email) ?></a></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-brand py-4">
    <div class="container">
      <div class="footer-brand-inner">
        <img class="footer-logo" src="<?= htmlspecialchars($logo_url) ?>" width="48" height="48" alt="Logo de la tienda" loading="lazy">
        <p class="footer-signature m-0">
          <?= htmlspecialchars($shop_name) ?>
          <?php if ($shop_slogan): ?>
            <span class="text-body-secondary ms-2">· <?= htmlspecialchars($shop_slogan) ?></span>
          <?php endif; ?>
          <br>
          <span class="small text-body-secondary">Desarrollado por <strong>Cristhian Sena</strong></span>
        </p>
      </div>
    </div>
  </div>

  <div class="footer-legal py-3">
    <div class="container text-center small text-body-secondary">
      © <?= $year ?> <?= htmlspecialchars($shop_name) ?>. <?= htmlspecialchars($footer_legal) ?>
    </div>
  </div>
</footer>

<!-- SCRIPTS -->
<!-- Bootstrap bundle (necesario para dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Carrito (badge + addToCart global) -->
<script src="<?= $BASE ?>/js/cart.js"></script>

<!-- Logout fallback: convierte link en POST con CSRF si hiciera falta -->
<script>
document.addEventListener('click', function (e) {
  const a = e.target.closest('a[data-logout="1"]');
  if (!a) return;

  e.preventDefault();
  const form = document.querySelector('.dropdown-logout-form');
  const csrf = form?.querySelector('input[name="csrf"]')?.value || '';

  const f = document.createElement('form');
  f.method = 'POST';
  f.action = a.getAttribute('href');
  if (csrf) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf';
    input.value = csrf;
    f.appendChild(input);
  }
  document.body.appendChild(f);
  f.submit();
}, { passive: false });
</script>
</body>
</html>