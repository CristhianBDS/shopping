<?php
// public/beneficios.php — Zona solo para usuarios logueados (SOCIO)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Beneficios de socio';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

// Solo usuarios con sesión
requireLogin();

include __DIR__ . '/../templates/header.php';
?>

<main class="page page-beneficios py-4 py-md-5">
  <section class="container">

    <!-- Encabezado -->
    <header class="mb-4 mb-md-5 text-center text-md-start">
      <h1 class="mb-2">Beneficios de socio</h1>
      <p class="lead mb-0">
        Gracias por formar parte. Como socio tendrás descuentos exclusivos,
        promociones especiales y prioridad en el procesamiento de tus pedidos.
      </p>
    </header>

    <!-- Beneficios principales en cards -->
    <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
      <div class="col">
        <article class="card h-100 shadow-sm border-0 beneficios-card">
          <div class="card-body">
            <h5 class="card-title mb-2">10% OFF en nuevas colecciones</h5>
            <p class="card-text mb-0">
              El descuento se aplica automáticamente al finalizar tu compra
              cuando estás logueado como socio.
            </p>
          </div>
        </article>
      </div>

      <div class="col">
        <article class="card h-100 shadow-sm border-0 beneficios-card">
          <div class="card-body">
            <h5 class="card-title mb-2">Envíos prioritarios</h5>
            <p class="card-text mb-0">
              Procesamos tus pedidos antes que los visitantes sin cuenta,
              para que recibas tus productos lo antes posible.
            </p>
          </div>
        </article>
      </div>
    </div>

    <!-- Niveles de socio -->
    <hr class="my-5">

    <section class="mb-4">
      <h2 class="h4 mb-3">Niveles de socio</h2>
      <p class="text-muted">
        A medida que compras, subes de nivel y desbloqueas más beneficios.
      </p>
    </section>

    <div class="table-responsive mb-4">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th>Nivel</th>
            <th>Condición</th>
            <th>Beneficios</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Bronce</strong></td>
            <td>Desde tu primera compra</td>
            <td>10% OFF en nuevas colecciones</td>
          </tr>
          <tr>
            <td><strong>Plata</strong></td>
            <td>+150 € acumulados</td>
            <td>15% OFF fijo + envíos prioritarios</td>
          </tr>
          <tr>
            <td><strong>Oro</strong></td>
            <td>+300 € acumulados</td>
            <td>20% OFF + lanzamientos exclusivos + sorpresas especiales</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cómo funcionan los beneficios -->
    <section class="mt-5">
      <h2 class="h4 mb-3">¿Cómo funcionan tus beneficios?</h2>
      <ol class="list-group list-group-numbered">
        <li class="list-group-item">
          <strong>1. Crea tu cuenta</strong><br>
          Regístrate una sola vez y ya serás socio.
        </li>
        <li class="list-group-item">
          <strong>2. Compra logueado</strong><br>
          Inicia sesión antes de comprar para acumular tus beneficios.
        </li>
        <li class="list-group-item">
          <strong>3. Descuentos automáticos</strong><br>
          Los descuentos se aplican solos en el carrito si estás logueado.
        </li>
        <li class="list-group-item">
          <strong>4. Sube de nivel</strong><br>
          Cuanto más compres, mejores beneficios desbloqueas.
        </li>
      </ol>
    </section>

    <!-- Comparación socio vs invitado -->
    <section class="mt-5">
      <h2 class="h4 mb-3">Socio vs invitado</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <h3 class="h5">Visitante</h3>
              <ul class="small mb-0">
                <li>Puede ver todo el catálogo.</li>
                <li>Puede comprar de forma puntual.</li>
                <li>No acumula beneficios ni historial de pedidos.</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100 border-0 shadow-sm border-primary">
            <div class="card-body">
              <h3 class="h5">Socio</h3>
              <ul class="small mb-0">
                <li>Descuentos automáticos en nuevas colecciones.</li>
                <li>Envíos prioritarios cuando sea posible.</li>
                <li>Historial de pedidos y seguimiento más fácil.</li>
                <li>Acceso a promos exclusivas solo para miembros.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Próximos beneficios -->
    <section class="mt-5">
      <h2 class="h4 mb-2">Próximos beneficios para socios</h2>
      <p class="text-muted mb-3">
        Estamos trabajando en nuevas ventajas solo para miembros registrados:
      </p>
      <ul class="list-unstyled">
        <li>✔ Acceso anticipado a nuevas colecciones.</li>
        <li>✔ Sorteos mensuales solo para socios.</li>
        <li>✔ Descuentos especiales por cumpleaños.</li>
      </ul>
    </section>

    <!-- Preguntas frecuentes -->
    <section class="mt-5">
      <h2 class="h4 mb-3">Preguntas frecuentes</h2>
      <div class="accordion" id="beneficiosFaq">
        <div class="accordion-item">
          <h2 class="accordion-header" id="faq1">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1c">
              ¿Tiene costo ser socio?
            </button>
          </h2>
          <div id="faq1c" class="accordion-collapse collapse" data-bs-parent="#beneficiosFaq">
            <div class="accordion-body">
              No, registrarse es totalmente gratuito. Solo necesitas un correo válido.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header" id="faq2">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2c">
              ¿Los descuentos aplican en todas las compras?
            </button>
          </h2>
          <div id="faq2c" class="accordion-collapse collapse" data-bs-parent="#beneficiosFaq">
            <div class="accordion-body">
              Sí, siempre que estés logueado y el producto no esté marcado como “sin descuento”.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header" id="faq3">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3c">
              ¿Puedo usar mis beneficios en la tienda física?
            </button>
          </h2>
          <div id="faq3c" class="accordion-collapse collapse" data-bs-parent="#beneficiosFaq">
            <div class="accordion-body">
              Próximamente podrás usar tu cuenta de socio también en la tienda física de Encarnación.
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Condiciones de uso -->
    <section class="mt-5">
      <h2 class="h5 mb-2">Condiciones de uso de los beneficios</h2>
      <p class="small text-muted mb-1">
        · Los descuentos de socio no son acumulables con otras promociones especiales,
        salvo que se indique lo contrario.
      </p>
      <p class="small text-muted mb-1">
        · Los beneficios se aplican únicamente a las compras realizadas con tu cuenta de socio activa.
      </p>
      <p class="small text-muted mb-0">
        · La tienda puede actualizar o modificar los beneficios en cualquier momento para mejorar el programa.
      </p>
    </section>

    <!-- Mensaje final -->
    <section class="mt-5">
      <div class="card border-0 shadow-sm text-center p-4 p-md-5">
        <h2 class="h4 mb-2">Gracias por ser parte</h2>
        <p class="mb-0">
          Cada compra que haces como socio nos ayuda a crecer y a seguir mejorando la tienda.
          Queremos devolvértelo con beneficios reales y un servicio cada vez mejor.
        </p>
      </div>
    </section>

  </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
