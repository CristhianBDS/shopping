<?php
// admin/events_form.php — Alta/Edición de evento (unificado)
// Requisitos: tabla `events` con columnas: id, title, type, start_at, end_at, all_day, color, notes, created_at, updated_at

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Evento';
$BASE       = defined('BASE_URL') ? BASE_URL : '';

requireAdmin();
$pdo = getConnection();

// ── Contexto: ¿edición o creación?
$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;

// Breadcrumbs simples
$BREADCRUMB = 'Dashboard / Calendario / ' . ($edit ? 'Editar' : 'Nuevo');

// ── Modelo en memoria
$title = $type = $color = $notes = '';
$all_day = 0;
$start_date = $start_time = $end_date = $end_time = '';

// ── Cargar evento si es edición
if ($edit) {
  try {
    $st = $pdo->prepare("SELECT * FROM events WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $ev = $st->fetch(PDO::FETCH_ASSOC);
    if (!$ev) {
      flash_error('Evento no encontrado.');
      header('Location: ' . $BASE . '/admin/calendario.php'); exit;
    }

    $title = (string)$ev['title'];
    $type  = (string)$ev['type'];
    $color = (string)($ev['color'] ?? '');
    $notes = (string)($ev['notes'] ?? '');
    $all_day = (int)$ev['all_day'];

    $start_ts   = strtotime($ev['start_at']);
    $start_date = $start_ts ? date('Y-m-d', $start_ts) : '';
    $start_time = $start_ts ? date('H:i', $start_ts) : '';

    if (!empty($ev['end_at'])) {
      $end_ts   = strtotime($ev['end_at']);
      $end_date = $end_ts ? date('Y-m-d', $end_ts) : '';
      $end_time = $end_ts ? date('H:i', $end_ts) : '';
    }
  } catch (Throwable $e) {
    if (defined('DEBUG') && DEBUG) flash_error($e->getMessage());
    flash_error('No se pudo cargar el evento.');
    header('Location: ' . $BASE . '/admin/calendario.php'); exit;
  }
}

// ── POST: guardar
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  }

  $title   = trim((string)($_POST['title'] ?? ''));
  $type    = (string)($_POST['type'] ?? 'otro');
  $all_day = isset($_POST['all_day']) ? 1 : 0;
  $color   = trim((string)($_POST['color'] ?? ''));
  $notes   = trim((string)($_POST['notes'] ?? ''));

  $start_date = trim((string)($_POST['start_date'] ?? ''));
  $start_time = trim((string)($_POST['start_time'] ?? ''));
  $end_date   = trim((string)($_POST['end_date'] ?? ''));
  $end_time   = trim((string)($_POST['end_time'] ?? ''));

  if ($title === '') { $errors[] = 'El título es obligatorio.'; }
  if (!in_array($type, ['sorteo','descuento','lanzamiento','otro'], true)) $type = 'otro';
  if ($start_date === '') { $errors[] = 'La fecha de inicio es obligatoria.'; }

  // Construcción de datetimes
  $start_at = $start_date ? ($start_date . ' ' . ($start_time !== '' ? $start_time : '00:00:00')) : null;
  $end_at   = null;
  if ($end_date !== '') {
    $end_at = $end_date . ' ' . ($end_time !== '' ? $end_time : '23:59:59');
  }

  if (!$errors) {
    try {
      if ($edit) {
        $st = $pdo->prepare("
          UPDATE events
          SET title=?, type=?, start_at=?, end_at=?, all_day=?, color=?, notes=?, updated_at=NOW()
          WHERE id=? LIMIT 1
        ");
        $st->execute([
          $title,
          $type,
          $start_at,
          $end_at,
          $all_day,
          $color !== '' ? $color : null,
          $notes !== '' ? $notes : null,
          $id
        ]);
        flash_success('Evento actualizado.');
      } else {
        $st = $pdo->prepare("
          INSERT INTO events (title,type,start_at,end_at,all_day,color,notes,created_at,updated_at)
          VALUES (?,?,?,?,?,?,?,NOW(),NOW())
        ");
        $st->execute([
          $title,
          $type,
          $start_at,
          $end_at,
          $all_day,
          $color !== '' ? $color : null,
          $notes !== '' ? $notes : null
        ]);
        flash_success('Evento creado.');
      }

      header('Location: ' . $BASE . '/admin/calendario.php'); exit;
    } catch (Throwable $e) {
      $errors[] = 'Error al guardar.';
      if (defined('DEBUG') && DEBUG) { $errors[] = $e->getMessage(); }
    }
  }
}

// ── Render
include __DIR__ . '/../templates/header.php';
?>
<h1 class="h4 mb-3"><?= $edit ? 'Editar evento' : 'Nuevo evento' ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="form-container" autocomplete="off" novalidate>
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(auth_csrf()) ?>">

  <div class="mb-3">
    <label class="form-label">Título</label>
    <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($title) ?>">
  </div>

  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">Tipo</label>
      <select name="type" class="form-select">
        <option value="descuento"   <?= $type==='descuento'?'selected':'' ?>>Descuento</option>
        <option value="sorteo"      <?= $type==='sorteo'?'selected':'' ?>>Sorteo</option>
        <option value="lanzamiento" <?= $type==='lanzamiento'?'selected':'' ?>>Lanzamiento</option>
        <option value="otro"        <?= $type==='otro'?'selected':'' ?>>Otro</option>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Color (opcional)</label>
      <input type="color" name="color" class="form-control form-control-color" value="<?= htmlspecialchars($color ?: '#0066FF') ?>">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="all_day" id="all_day" <?= $all_day ? 'checked' : '' ?>>
        <label class="form-check-label" for="all_day">Todo el día</label>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Inicio</label>
      <div class="d-flex gap-2">
        <input type="date" name="start_date" class="form-control" required value="<?= htmlspecialchars($start_date) ?>">
        <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($start_time) ?>">
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Fin (opcional)</label>
      <div class="d-flex gap-2">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($end_time) ?>">
      </div>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Notas</label>
    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($notes) ?></textarea>
  </div>

  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/calendario.php">Volver</a>
    <button class="btn btn-primary">Guardar</button>
  </div>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
