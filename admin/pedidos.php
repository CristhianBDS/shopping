<?php
// admin/pedidos.php (versión PDO, sin estilos inline)
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
$pdo = getConnection();

// --- detectar nombres reales qty / price en order_items (por si difieren) ---
function pickColumn(PDO $pdo, string $table, array $cands): ?string {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table`");
    $stmt->execute();
    $cols = array_map(fn($r) => strtolower($r['Field']), $stmt->fetchAll(PDO::FETCH_ASSOC));
    foreach ($cands as $c) if (in_array(strtolower($c), $cols, true)) return $c;
    return null;
}
$qtyCol   = pickColumn($pdo, 'order_items', ['quantity','qty','cantidad','cant','qty_ordered']) ?? 'quantity';
$priceCol = pickColumn($pdo, 'order_items', ['unit_price','price','precio','unitprice']) ?? 'unit_price';

// --- filtros/paginación ---
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$status = $_GET['status'] ?? '';
$q      = trim((string)($_GET['q'] ?? ''));

$where  = [];
$params = [];

if ($status !== '') {
    $where[] = "o.status = :status";
    $params[':status'] = $status;
}
if ($q !== '') {
    if (ctype_digit($q)) {
        $where[] = "(o.id = :idbuscado OR o.customer_name LIKE :q)";
        $params[':idbuscado'] = (int)$q;
        $params[':q'] = "%{$q}%";
    } else {
        $where[] = "o.customer_name LIKE :q";
        $params[':q'] = "%{$q}%";
    }
}
$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// --- conteo ---
$sqlCount = "SELECT COUNT(*) FROM orders o $whereSql";
$stc = $pdo->prepare($sqlCount);
$stc->execute($params);
$totalRows  = (int)$stc->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// --- consulta listado ---
$sql = "
SELECT 
  o.id,
  o.customer_name,
  COALESCE(o.total_amount, (
    SELECT SUM(oi.`$qtyCol` * oi.`$priceCol`) FROM order_items oi WHERE oi.order_id = o.id
  )) AS total,
  o.created_at,
  o.status
FROM orders o
$whereSql
ORDER BY o.id DESC
LIMIT :limit OFFSET :offset
";
$st = $pdo->prepare($sql);
foreach ($params as $k => $v) $st->bindValue($k, $v);
$st->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset,  PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// --- helpers ---
function eur($n): string { return number_format((float)$n, 2, ',', '.') . ' €'; }
function fdate($s): string {
    if (!$s) return '-';
    try { return (new DateTime($s))->format('d/m/Y H:i'); } catch(Throwable) { return $s; }
}
function qs(array $extra = []): string {
    $base = $_GET; foreach ($extra as $k => $v) $base[$k] = $v; return http_build_query($base);
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<main class="admin admin-pedidos container">
  <h1 class="page-title">Pedidos</h1>

  <form method="get" class="filtros">
    <div class="campo">
      <label for="status">Estado</label>
      <select name="status" id="status" class="select">
        <option value="">(Todos)</option>
        <?php foreach (['pendiente','pagado','enviado','cancelado'] as $e): ?>
          <option value="<?= $e ?>" <?= $status === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo">
      <label for="q">Buscar</label>
      <input type="text" id="q" name="q" class="input" placeholder="ID o Cliente..." value="<?= htmlspecialchars($q) ?>">
    </div>
    <div class="acciones">
      <button type="submit" class="btn btn-primary">Aplicar</button>
      <a href="pedidos.php" class="btn btn-link">Limpiar</a>
    </div>
  </form>

  <div class="resumen">
    <?= $totalRows ?> pedido(s) • Página <?= $page ?> de <?= $totalPages ?>
  </div>

  <div class="tabla-wrapper">
    <table class="tabla tabla-pedidos">
      <thead>
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th class="num">Total</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th class="accion">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="tabla-empty">No hay pedidos.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['customer_name'] ?? '-') ?></td>
          <td class="num"><?= eur($r['total'] ?? 0) ?></td>
          <td><?= fdate($r['created_at'] ?? '') ?></td>
          <td>
            <span class="badge badge-<?= htmlspecialchars((string)$r['status']) ?>">
              <?= htmlspecialchars(ucfirst((string)$r['status'])) ?>
            </span>
          </td>
          <td class="accion">
            <a class="btn btn-sm" href="pedido.php?id=<?= (int)$r['id'] ?>">Ver</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="paginacion">
      <?php if ($page > 1): ?><a class="page-link" href="?<?= qs(['page'=>$page-1]) ?>">&laquo; Anterior</a><?php endif; ?>
      <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
          echo $i === $page ? "<span class=\"page-current\">$i</span>" : '<a class="page-link" href="?'.qs(['page'=>$i]).'">'.$i.'</a>';
        endfor;
      ?>
      <?php if ($page < $totalPages): ?><a class="page-link" href="?<?= qs(['page'=>$page+1]) ?>">Siguiente &raquo;</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
