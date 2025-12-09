<?php
// admin/calendario.php — Calendario de promociones y eventos de la tienda

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Calendario';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

requireAdmin();

include __DIR__ . '/../templates/header.php';
?>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h1 class="h4 mb-1">🗓️ Calendario de promociones</h1>
      <p class="text-muted mb-0">
        Planifica sorteos, descuentos, lanzamientos y fechas clave de tu tienda.
      </p>
    </div>
    <a class="btn btn-primary" href="<?= $BASE ?>/admin/evento_form.php">
      + Nuevo evento
    </a>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="small text-muted">
          <strong>Consejo:</strong> haz clic sobre un evento para editarlo.<br>
          Usa las vistas de <strong>mes</strong>, <strong>semana</strong> o <strong>día</strong> según lo que necesites.
        </div>
        <div class="small">
          <span class="badge bg-primary me-1">Promo</span>
          <span class="badge bg-success me-1">Lanzamiento</span>
          <span class="badge bg-warning text-dark me-1">Recordatorio</span>
        </div>
      </div>

      <div id="adminCalendar"></div>
    </div>
  </div>
</main>

<!-- FullCalendar CSS + JS (global build) -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const el = document.getElementById('adminCalendar');
  if (!el) return;

  const calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    height: 'auto',
    locale: 'es',
    firstDay: 1, // lunes
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay'
    },
    buttonText: {
      today:  'Hoy',
      month:  'Mes',
      week:   'Semana',
      day:    'Día'
    },
    events: {
      url: '<?= $BASE ?>/api/events.php',
      failure: () => {
        alert('No se pudo cargar el calendario. Revisa la API de eventos.');
      }
    },
    eventClick: function(info) {
      if (info.event && info.event.id) {
        window.location.href = '<?= $BASE ?>/admin/evento_form.php?id=' + info.event.id;
      }
    },
    eventDisplay: 'block',
    nowIndicator: true
  });

  calendar.render();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
