<?php
// admin/calendario.php — vista completa del calendario
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Calendario';
$BASE       = BASE_URL;

require_admin();

include __DIR__ . '/../templates/header.php';
?>
<h1 class="h4 mb-3">🗓️ Calendario de promociones</h1>

<div class="d-flex justify-content-between mb-3">
  <p class="text-muted mb-0">Planifica sorteos, descuentos y fechas clave.</p>
  <a class="btn btn-primary" href="<?= $BASE ?>/admin/evento_form.php">Nuevo evento</a>
</div>

<div class="card">
  <div class="card-body">
    <div id="adminCalendar"></div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const el = document.getElementById('adminCalendar');
  const calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    height: 'auto',
    locale: 'es',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: {
      url: '<?= $BASE ?>/api/events.php',
      failure: () => alert('No se pudo cargar el calendario.')
    },
    eventClick: ({ event }) => {
      // Lleva al editor del evento
      window.location.href = '<?= $BASE ?>/admin/evento_form.php?id=' + event.id;
    },
  });
  calendar.render();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
