<?php
// public/contacto.php — Página de contacto (3 pantallas)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
$CONTEXT   = 'public';
$PAGE_TITLE = 'Contacto';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

include __DIR__ . '/../templates/header.php';
?>

<main class="contacto-page body-with-fixed-nav">

  <!-- ===== Pantalla 1: Redes + Mensaje + Email ===== -->
  <section class="contact-hero container">
    <div class="row align-items-center g-4">
      <!-- Izquierda: 4 iconos cuadrados + email -->
      <div class="col-12 col-md-6">
        <h2 class="visually-hidden">Redes sociales</h2>

        <div class="social-grid" aria-label="Redes sociales">
          <a class="social-item" href="#" aria-label="Instagram">
            <img src="<?= $BASE ?>/images/instagram.png" alt="Instagram">
          </a>
          <a class="social-item" href="#" aria-label="Facebook">
            <img src="<?= $BASE ?>/images/facebook.png" alt="Facebook">
          </a>
          <a class="social-item" href="#" aria-label="TikTok">
            <img src="<?= $BASE ?>/images/tiktok.png" alt="TikTok">
          </a>
          <a class="social-item" href="#" aria-label="WhatsApp">
            <img src="<?= $BASE ?>/images/whatsapp.png" alt="WhatsApp">
          </a>
        </div>

        <!-- Input email + botón -->
        <form class="email-signup" action="<?= $BASE ?>/public/gracias_contacto.php" method="post" novalidate>
          <label for="contact-email" class="form-label visually-hidden">Correo electrónico</label>
          <input id="contact-email" name="email" type="email" class="form-control" placeholder="Ingresa tu correo" required>
          <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
      </div>

      <!-- Derecha: Título + texto -->
      <div class="col-12 col-md-6">
        <h1 class="display-6 mb-3">Contacto</h1>
        <p class="lead text-muted">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc dignissim, arcu ac dictum dapibus,
          urna leo varius risus, vitae egestas metus nibh ac nunc. Aliquam in lorem a nibh fermentum tempus.
        </p>
        <p class="text-muted">
          Curabitur posuere, felis at commodo ultrices, urna leo porta lacus, eget faucibus mauris mi sed sem.
          Escríbenos y te responderemos en menos de 24 horas.
        </p>
      </div>
    </div>
  </section>

  <!-- ===== Pantalla 2: Quiénes Somos + Carrusel ===== -->
  <section class="about-block container">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-6">
        <h2 class="mb-3">Quiénes Somos</h2>
        <p class="text-muted">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusamus, numquam. Pariatur earum illum
          eligendi cumque, adipisci doloremque repellendus nihil consequuntur, a quae nisi fuga veniam.
        </p>
        <p class="text-muted">
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Quibusdam, eveniet! Quidem ipsam dolore
          impedit vero beatae cupiditate labore?
        </p>
      </div>

      <div class="col-12 col-lg-6">
        <div id="aboutCarousel" class="carousel slide contact-carousel" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="<?= $BASE ?>/images/calcetines-pack3.jpg" class="d-block w-100" alt="Foto 1 de la empresa" loading="lazy">
            </div>
            <div class="carousel-item">
              <img src="<?= $BASE ?>/images/sudadera-hoodie.jpg" class="d-block w-100" alt="Foto 2 de la empresa" loading="lazy">
            </div>
            <div class="carousel-item">
              <img src="<?= $BASE ?>/images/sudadera-hoodie.jpg" class="d-block w-100" alt="Foto 3 de la empresa" loading="lazy">
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#aboutCarousel" data-bs-slide="prev" aria-label="Anterior">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#aboutCarousel" data-bs-slide="next" aria-label="Siguiente">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== Pantalla 3: Texto + CTA Registrarme ===== -->
  <section class="cta-block container text-center">
    <h2 class="mb-3">Únete a nuestra comunidad</h2>
    <p class="text-muted mx-auto cta-text">
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Eius quod quasi, aliquid repellendus officiis
      consequuntur omnis modi?
    </p>
    <a href="<?= $BASE ?>/public/registro.php" class="btn btn-primary btn-lg mt-3">Registrarme</a>
  </section>

</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
