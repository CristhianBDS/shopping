<?php
// admin/producto_form.php (esqueleto)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

$pdo = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$producto = [
  'name' => '', 'description' => '', 'price' => '', 'image' => '', 'is_active' => 1
];

if ($id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->execute([$id]);
  $producto = $stmt->fetch(PDO::FETCH_ASSOC) ?: $producto;
}

include __DIR__ . '/../templates/header.php';
?>
<h1 class="h3 mb-3"><?= $id ? 'Editar' : 'Nuevo' ?> producto</h1>

<div class="card shadow-sm">
  <div class="card-body">
    <form action="<?= BASE_URL ?>/admin/producto_form.php<?= $id ? '?id='.$id : '' ?>" method="post" enctype="multipart/form-data">
      <!-- Aquí luego añadimos los inputs con validación -->
      <p class="text-muted mb-0">Formulario en preparación…</p>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
