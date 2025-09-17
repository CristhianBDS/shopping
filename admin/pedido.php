<?php
// admin/pedido.php — Detalle de pedido + cambio de estado (PDO, sin estilos inline)
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
$pdo = getConnection();

/* ID pedido */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "ID de pedido inválido."; exit; }

/* POST: cambio de estado */
$ok = (int)($_GET['ok'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo = $_POST['status'] ?? '';
    $permitidos = ['pendiente','pagado','enviado','cancelado'];
    if (!in_array($nuevo, $permitidos, true)) {
        $error = "Estado no válido.";
    } else {
        $st = $pdo->prepare("UPDATE orders SET status = :s WHERE id = :id");
        $st->execute([':s'=>$nuevo, ':id'=>$id]);
        header("Location: pedido.php?id={$id}&ok=1");
        exit;
    }
}

/* Pedido (campos reales de tu tabla) */
$sqlPedido = "
SELECT 
  o.id, o.status, o.created_at,
  o.customer_name, o.email, o.phone, o.address, o.city, o.zip,
  o.notes, o.pay_method, o.total_amount
FROM orders o
WHERE o.id = :id
";
$stp = $pdo->prepare($sqlPedido);
$stp->execute([':id'=>$id]);
$pedido = $stp->fetch(PDO::FETCH_ASSOC);
if (!$pedido) { http_response_code(404); echo "Pedido no encontrado."; exit; }

/* Ítems del pedido (según tu tabla order_items: name, price, qty, subtotal) */
$sqlItems = "
SELECT 
  oi.product_id,
  oi.name        AS product_name,
  oi.price       AS unit_price,
  oi.qty         AS qty,
  (oi.qty * oi.price) AS subtotal
FROM order_items oi
WHERE oi.order_id = :id
ORDER BY oi.id ASC
";
$sti = $pdo->prepare($sqlItems);
$sti->execute([':id'=>$id]);
$items = $sti->fetchAll(PDO::FETCH_ASSOC);

/* Total calculado desde ítems */
$totalCalc = 0.0;
foreach ($items as $it) $totalCalc += (float)$it['subtotal'];

/* Helpers */
function eur($n): string { return number_format((float)$n, 2, ',', '.') . ' €'; }
function fdate($s): string { if(!$s) return '-'; try { return (new DateTime($s))->format('d/m/Y H:i'); } catch(Throwable) { return $s; } }

include __DIR__ . '/../templates/header.php';
?>
<main class="admin admin-pedido container">
  <a class="link-back" href="pedidos.php">&laquo; Volver</a>
  <h1 class="page-title">Pedido #<?= (int)$pedido['id'] ?></h1>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($ok): ?>
    <div class="alert alert-ok">Estado actualizado correctamente.</div>
  <?php endif; ?>

  <section class="pedido-grid">
    <div class="card">
      <h3>Datos del cliente</h3>
      <dl class="meta">
        <div><dt>Nombre</dt><dd><?= htmlspecialchars($pedido['customer_name'] ?? '-') ?></dd></div>
        <div><dt>Email</dt><dd><?= htmlspecialchars($pedido['email'] ?? '-') ?></dd></div>
        <div><dt>Teléfono</dt><dd><?= htmlspecialchars($pedido['phone'] ?? '-') ?></dd></div>
        <div><dt>Dirección</dt><dd>
          <?= htmlspecialchars(($pedido['address'] ?? '')) ?>
          <?= $pedido['city'] ? ', ' . htmlspecialchars($pedido['city']) : '' ?>
          <?= $pedido['zip'] ? ' (' . htmlspecialchars($pedido['zip']) . ')' : '' ?>
        </dd></div>
        <?php if (!empty($pedido['notes'])): ?>
          <div><dt>Notas</dt><dd><?= nl2br(htmlspecialchars($pedido['notes'])) ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>

    <div class="card">
      <h3>Información del pedido</h3>
      <dl class="meta">
        <div><dt>Fecha</dt><dd><?= fdate($pedido['created_at'] ?? '') ?></dd></div>
        <div><dt>Estado</dt><dd>
          <span class="badge badge-<?= htmlspecialchars((string)$pedido['status']) ?>">
            <?= htmlspecialchars(ucfirst((string)$pedido['status'])) ?>
          </span>
        </dd></div>
        <div><dt>Método de pago</dt><dd><?= htmlspecialchars($pedido['pay_method'] ?? '-') ?></dd></div>
        <div><dt>Total (items)</dt><dd><?= eur($totalCalc) ?></dd></div>
        <?php if ($pedido['total_amount'] !== null): ?>
          <div><dt>Total (orders.total_amount)</dt><dd><?= eur($pedido['total_amount']) ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>

    <div class="card">
      <h3>Cambiar estado</h3>
      <form method="post" class="form-estado" onsubmit="return confirm('¿Confirmar cambio de estado?');">
        <label for="status">Estado</label>
        <select name="status" id="status" required>
          <?php foreach (['pendiente','pagado','enviado','cancelado'] as $e): ?>
            <option value="<?= $e ?>" <?= $pedido['status']===$e?'selected':'' ?>><?= ucfirst($e) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </section>

  <section class="pedido-items">
    <h2>Productos</h2>
    <div class="tabla-wrapper">
      <table class="tabla tabla-items">
        <thead>
          <tr>
            <th>Producto</th>
            <th class="num">Precio</th>
            <th class="num">Cantidad</th>
            <th class="num">Subtotal</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="4" class="tabla-empty">Sin ítems.</td></tr>
        <?php else: foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['product_name'] ?: ('ID ' . (int)$it['product_id'])) ?></td>
            <td class="num"><?= eur($it['unit_price']) ?></td>
            <td class="num"><?= (int)$it['qty'] ?></td>
            <td class="num"><?= eur($it['subtotal']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if ($items): ?>
        <tfoot>
          <tr>
            <th colspan="3" class="num">Total</th>
            <th class="num"><?= eur($totalCalc) ?></th>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </section>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
