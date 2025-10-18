<?php
// templates/footer.php — Footer público + scripts
if (!defined('BASE_URL')) { require_once __DIR__ . '/../config/app.php'; }
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
?>
<footer class="site-footer site-footer--light mt-auto" role="contentinfo">
  <div class="footer-main py-5">
    <div class="container">
      <div class="row gy-4">
        <nav class="col-12 col-md-4" aria-labelledby="footer-ayuda-title">
          <h6 id="footer-ayuda-title" class="footer-title">Ayuda</h6>
          <ul class="footer-list">
            <li><a href="<?= $BASE ?>/public/ayuda.php">Obtener ayuda</a></li>
            <li><a href="<?= $BASE ?>/public/pedidos.php">Estado del pedido</a></li>
            <li><a href="<?= $BASE ?>/public/envios.php">Envíos y entregas</a></li>
            <li><a href="<?= $BASE ?>/public/devoluciones.php">Devoluciones</a></li>
          </ul>
        </nav>

        <div class="col-6 col-md-4">
          <h6 class="footer-title">Redes</h6>
          <ul class="footer-list footer-social">
            <li><a class="footer-social-link" href="#" aria-label="TikTok"><img src="<?= $BASE ?>/images/social/tiktok.svg" width="20" height="20" alt=""><span>TikTok</span></a></li>
            <li><a class="footer-social-link" href="#" aria-label="Instagram"><img src="<?= $BASE ?>/images/social/instagram.svg" width="20" height="20" alt=""><span>Instagram</span></a></li>
            <li><a class="footer-social-link" href="#" aria-label="Facebook"><img src="<?= $BASE ?>/images/social/facebook.svg" width="20" height="20" alt=""><span>Facebook</span></a></li>
          </ul>
        </div>

        <nav class="col-6 col-md-4" aria-labelledby="footer-descuentos-title">
          <h6 id="footer-descuentos-title" class="footer-title">Descuentos</h6>
          <ul class="footer-list">
            <li><a href="<?= $BASE ?>/public/descuento-estudiante.php">Estudiante</a></li>
            <li><a href="<?= $BASE ?>/public/descuento-docente.php">Docente</a></li>
            <li><a href="<?= $BASE ?>/public/descuento-servicios.php">Servicios de emergencias</a></li>
          </ul>
        </nav>
      </div>
    </div>
  </div>

  <div class="footer-brand py-4">
    <div class="container">
      <div class="footer-brand-inner">
        <img class="footer-logo" src="<?= $BASE ?>/images/logo.svg" width="48" height="48" alt="Logo de la tienda" loading="lazy">
        <p class="footer-signature m-0">Desarrollado con ❤️ — <strong>Cristhian Sena</strong></p>
      </div>
    </div>
  </div>

  <div class="footer-legal py-3">
    <div class="container text-center small text-body-secondary">
      © <?= date('Y') ?> Tu Tienda. Todos los derechos reservados.
    </div>
  </div>
</footer>

<!-- SCRIPTS (reemplaza todo este bloque si ya tenías uno) -->
<!-- Bootstrap bundle (necesario para dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Logout fallback: convierte link en POST con CSRF si hiciera falta -->
<script>
document.addEventListener('click', function (e) {
  const a = e.target.closest('a[data-logout="1"]');
  if (!a) return;

  e.preventDefault();
  // Busca el token del formulario real (si existe)
  const form = document.querySelector('.dropdown-logout-form');
  const csrf = form?.querySelector('input[name="csrf"]')?.value || '';

  // Construye y envía POST
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
