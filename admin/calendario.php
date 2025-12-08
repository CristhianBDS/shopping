<?php
// admin/calendario.php — vista completa del calendario
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
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">🗓️ Calendario de promociones</h1>
    <a class="btn btn-primary" href="<?= $BASE ?>/admin/evento_form.php">Nuevo evento</a>
  </div>

  <p class="text-muted mb-3">
    Planifica sorteos, descuentos y fechas clave de tu tienda.
  </p>

  <div class="card">
    <div class="card-body">
      <div id="adminCalendar"></div>
    </div>
  </div>
</main>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const el = document.getElementById('adminCalendar');
  if (!el) return;

  const calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    height: 'auto',
    locale: 'es',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: {
      url: '<?= $BASE ?>/api/events.php',
      failure: () => alert('No se pudo cargar el calendario.')
    },
    eventClick: ({ event }) => {
      if (event && event.id) {
        window.location.href = '<?= $BASE ?>/admin/evento_form.php?id=' + event.id;
      }
    }
  });

  calendar.render();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
