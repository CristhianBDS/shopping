<?php
// admin/evento_form.php — Alta/Edición de evento
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Evento';
$BASE       = BASE_URL;

require_admin();
$pdo = getConnection();

// CSRF
if (empty($_SESSION['csrf_event'])) {
  $_SESSION['csrf_event'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_event'];

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;

$title = $type = $color = $notes = '';
$all_day = 0;
$start_date = $start_time = $end_date = $end_time = '';

if ($edit) {
  $st = $pdo->prepare("SELECT * FROM events WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $ev = $st->fetch(PDO::FETCH_ASSOC);
  if (!$ev) { flash_error('Evento no encontrado.'); header('Location: '.$BASE.'/admin/calendario.php'); exit; }

  $title = $ev['title'];
  $type  = $ev['type'];
  $color = $ev['color'] ?? '';
  $notes = $ev['notes'] ?? '';
  $all_day = (int)$ev['all_day'];

  $start_date = date('Y-m-d', strtotime($ev['start_at']));
  $start_time = date('H:i', strtotime($ev['start_at']));
  if ($ev['end_at']) {
    $end_date = date('Y-m-d', strtotime($ev['end_at']));
    $end_time = date('H:i', strtotime($ev['end_at']));
  }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_event'] ?? '', $token)) {
    $errors[] = 'CSRF inválido.';
  }

  $title = trim((string)($_POST['title'] ?? ''));
  $type  = ($_POST['type'] ?? 'otro');
  $all_day = isset($_POST['all_day']) ? 1 : 0;
  $color = trim((string)($_POST['color'] ?? ''));
  $notes = trim((string)($_POST['notes'] ?? ''));

  $start_date = trim((string)($_POST['start_date'] ?? ''));
  $start_time = trim((string)($_POST['start_time'] ?? ''));
  $end_date   = trim((string)($_POST['end_date'] ?? ''));
  $end_time   = trim((string)($_POST['end_time'] ?? ''));

  if ($title === '') { $errors[] = 'El título es obligatorio.'; }
  if (!in_array($type, ['sorteo','descuento','lanzamiento','otro'], true)) $type = 'otro';
  if ($start_date === '') { $errors[] = 'La fecha de inicio es obligatoria.'; }

  $start_at = $start_date . ' ' . ($start_time !== '' ? $start_time : '00:00:00');
  $end_at   = null;
  if ($end_date !== '') {
    $end_at = $end_date . ' ' . ($end_time !== '' ? $end_time : '23:59:59');
  }

  if (!$errors) {
    try {
      if ($edit) {
        $st = $pdo->prepare("UPDATE events SET title=?, type=?, start_at=?, end_at=?, all_day=?, color=?, notes=?, updated_at=NOW() WHERE id=? LIMIT 1");
        $st->execute([$title,$type,$start_at,$end_at,$all_day,$color ?: null,$notes ?: null,$id]);
        flash_success('Evento actualizado.');
      } else {
        $st = $pdo->prepare("INSERT INTO events (title,type,start_at,end_at,all_day,color,notes,created_at,updated_at)
                             VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
        $st->execute([$title,$type,$start_at,$end_at,$all_day,$color ?: null,$notes ?: null]);
        flash_success('Evento creado.');
      }
      unset($_SESSION['csrf_event']);
      header('Location: '.$BASE.'/admin/calendario.php'); exit;

    } catch (Throwable $e) {
      $errors[] = 'Error al guardar.';
      if (defined('DEBUG') && DEBUG) { $errors[] = $e->getMessage(); }
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<h1 class="h4 mb-3"><?= $edit ? 'Editar evento' : 'Nuevo evento' ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="form-container" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
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
