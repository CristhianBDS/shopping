<?php
// public/index.php — Home de la tienda (vista pública)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$CONTEXT = 'public';
$PAGE_TITLE = 'Inicio';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

include __DIR__ . '/../templates/header.php';
?>

<!-- ====== PANTALLA 1: HERO + INFO/CTA ====== -->
<section class="home-section home-hero py-4">
  <div class="container">
    <!-- Carrusel principal -->
    <div id="heroCarousel" class="carousel slide carousel-fade carousel-frame" data-bs-ride="carousel" data-bs-touch="true">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
      </div>

      <div class="carousel-inner">
        <div class="carousel-item active"
          data-title="Producto Estrella 1"
          data-text="Calidad y diseño para tu día a día."
          data-url="<?= $BASE ?>/public/producto.php?id=1">
          <img src="<?= $BASE ?>/images/zapatillas-urban.jpg" class="d-block w-100" alt="Producto 1">
        </div>
        <div class="carousel-item"
          data-title="Producto Estrella 2"
          data-text="Novedad de temporada con envío 24/48h."
          data-url="<?= $BASE ?>/public/producto.php?id=2">
          <img src="<?= $BASE ?>/images/camiseta-basica.jpg" class="d-block w-100" alt="Producto 2" loading="lazy">
        </div>
        <div class="carousel-item"
          data-title="Producto Estrella 3"
          data-text="Edición limitada, stock reducido."
          data-url="<?= $BASE ?>/public/producto.php?id=3">
          <img src="<?= $BASE ?>/images/chaqueta-wind.jpg" class="d-block w-100" alt="Producto 3" loading="lazy">
        </div>
      </div>

      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Anterior">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Siguiente">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
      </button>
    </div>

    <!-- Texto + CTA sincronizado con el slide activo -->
    <div class="hero-info text-center mt-3">
      <h2 class="h4 mb-1" id="heroTitle">Producto Estrella 1</h2>
      <p class="text-muted mb-2" id="heroText">Calidad y diseño para tu día a día."Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
      <a id="heroBtn" href="<?= $BASE ?>/public/producto.php?id=1" class="btn btn-primary">Ver producto</a>
    </div>
  </div>
</section>

<!-- ====== PANTALLA 2: DOS FILAS (Carrusel izq + Texto der) ====== -->
<section class="home-section section-alt py-5">
  <div class="container">
    <div class="row gy-5">

      <!-- ===== Fila 1 ===== -->
      <div class="col-12">
        <div class="row g-4 align-items-center">
          <!-- Carrusel izq -->
          <div class="col-md-6">
            <div id="leftCarousel" class="carousel slide carousel-frame" data-bs-ride="carousel" data-bs-touch="true">
              <div class="carousel-inner">
                <div class="carousel-item active">
                  <img src="<?= $BASE ?>/images/camiseta-basica.jpg" class="d-block w-100" alt="Slide A">
                </div>
                <div class="carousel-item">
                  <img src="<?= $BASE ?>/images/zapatillas-urban.jpg" class="d-block w-100" alt="Slide B" loading="lazy">
                </div>
                <div class="carousel-item">
                  <img src="<?= $BASE ?>/images/chaqueta-wind.jpg" class="d-block w-100" alt="Slide C" loading="lazy">
                </div>
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#leftCarousel" data-bs-slide="prev" aria-label="Anterior">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#leftCarousel" data-bs-slide="next" aria-label="Siguiente">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
              </button>
            </div>
          </div>
          <!-- Texto der -->
          <div class="col-md-6">
            <div class="info-box text-md-start text-center">
              <h3 class="h4 mb-2">Colección Urban</h3>
              <p class="text-muted mb-3">Diseños versátiles para el día a día. Comodidad y estilo urbano."Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
              <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-outline-primary">Ver colección</a>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== Fila 2 ===== -->
      <div class="col-12">
        <div class="row g-4 align-items-center">
          <!-- Carrusel izq -->
          <div class="col-md-6">
            <div id="rightCarousel" class="carousel slide carousel-frame" data-bs-ride="carousel" data-bs-touch="true">
              <div class="carousel-inner">
                <div class="carousel-item active">
                  <img src="<?= $BASE ?>/images/pantalon-jogger.jpg" class="d-block w-100" alt="Slide 1">
                </div>
                <div class="carousel-item">
                  <img src="<?= $BASE ?>/images/camiseta-basica.jpg" class="d-block w-100" alt="Slide 2" loading="lazy">
                </div>
                <div class="carousel-item">
                  <img src="<?= $BASE ?>/images/chaqueta-wind.jpg" class="d-block w-100" alt="Slide 3" loading="lazy">
                </div>
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#rightCarousel" data-bs-slide="prev" aria-label="Anterior">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#rightCarousel" data-bs-slide="next" aria-label="Siguiente">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
              </button>
            </div>
          </div>
          <!-- Texto der -->
          <div class="col-md-6">
            <div class="info-box text-md-start text-center">
              <h3 class="h4 mb-2">Línea Outdoor</h3>
              <p class="text-muted mb-3">Prendas técnicas, resistentes y ligeras para tus escapadas."Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
              <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-outline-primary">Explorar</a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ====== PANTALLA 3: TRES CARRUSELES PEQUEÑOS + TEXTO/BOTÓN ====== -->
<section class="home-section py-5">
  <div class="container">
    <h2 class="h4 text-center mb-5">Colecciones Destacadas</h2>
    <div class="row g-4">
      <!-- Carrusel 1 -->
      <div class="col-md-4">
        <div id="smallCarousel1" class="carousel slide carousel-frame small" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="<?= $BASE ?>/images/zapatillas-urban.jpg" class="d-block w-100" alt="Urban 1">
            </div>
            <div class="carousel-item">
              <img src="<?= $BASE ?>/images/chaqueta-wind.jpg" class="d-block w-100" alt="Urban 2">
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#smallCarousel1" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#smallCarousel1" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
          </button>
        </div>
        <div class="text-info-box text-center mt-3">
          <h5>Colección Urbana</h5>
          <p>Estilo y comodidad para tu día a día."Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
          <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-outline-primary btn-sm">Ver catálogo</a>
        </div>
      </div>

      <!-- Carrusel 2 -->
      <div class="col-md-4">
        <div id="smallCarousel2" class="carousel slide carousel-frame small" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="<?= $BASE ?>/images/pantalon-jogger.jpg" class="d-block w-100" alt="Outdoor 1">
            </div>
            <div class="carousel-item">
              <img src="<?= $BASE ?>/images/camiseta-basica.jpg" class="d-block w-100" alt="Outdoor 2">
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#smallCarousel2" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#smallCarousel2" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
          </button>
        </div>
        <div class="text-info-box text-center mt-3">
          <h5>Línea Outdoor</h5>
          <p>Ropa técnica y ligera para tus escapadas."Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
          <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-outline-primary btn-sm">Explorar</a>
        </div>
      </div>

      <!-- Carrusel 3 -->
      <div class="col-md-4">
        <div id="smallCarousel3" class="carousel slide carousel-frame small" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="<?= $BASE ?>/images/chaqueta-wind.jpg" class="d-block w-100" alt="Novedades 1">
            </div>
            <div class="carousel-item">
              <img src="<?= $BASE ?>/images/zapatillas-urban.jpg" class="d-block w-100" alt="Novedades 2">
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#smallCarousel3" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#smallCarousel3" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
          </button>
        </div>
        <div class="text-info-box text-center mt-3">
          <h5>Nuevas Temporadas</h5>
          <p>Descubre nuestras últimas prendas destacadas.
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."
          </p>
          <a href="<?= $BASE ?>/public/producto.php?id=1" class="btn btn-outline-primary btn-sm">Ver producto</a>
        </div>
      </div>
    </div>

    <!-- CTA registro -->
    <div class="register-cta text-center mt-5">
      <p class="text-muted mb-2">Accede a <strong>promos exclusivas</strong> registrándote como socio.</p>
      <a href="<?= $BASE ?>/public/registro.php" class="btn btn-dark">Crear mi cuenta</a>
    </div>
  </div>
</section>

<!-- ====== JS: Sincroniza el CTA del hero con el slide activo ====== -->
<script>
  (function() {
    const el = document.getElementById('heroCarousel');
    if (!el) return;

    const title = document.getElementById('heroTitle');
    const text = document.getElementById('heroText');
    const btn = document.getElementById('heroBtn');

    function applyFrom(activeItem) {
      if (!activeItem) return;
      const t = activeItem.getAttribute('data-title') || '';
      const x = activeItem.getAttribute('data-text') || '';
      const u = activeItem.getAttribute('data-url') || '#';
      if (title) title.textContent = t;
      if (text) text.textContent = x;
      if (btn) btn.setAttribute('href', u);
    }

    // Inicial
    applyFrom(el.querySelector('.carousel-item.active'));

    // Al cambiar slide
    el.addEventListener('slide.bs.carousel', function(e) {
      applyFrom(e.relatedTarget);
    });
  })();
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>