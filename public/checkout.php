<?php
// public/checkout.php — Checkout sin método de pago (adaptado a schema real)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
requireLogin(); // <= protección

require_once __DIR__ . '/../templates/header.php';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
?>
<main class="checkout-page container py-4">
  <h1 class="h3 mb-3">Finalizar compra</h1>
  <p class="text-muted mb-4">Completa tus datos para registrar el pedido. Te contactaremos para coordinar la entrega a domicilio.</p>

  <form id="checkout-form" class="card shadow-sm">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre y apellido</label>
          <input type="text" name="name" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono</label>
          <input type="text" name="phone" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">Dirección de entrega</label>
          <input type="text" name="address" class="form-control" required />
        </div>

        <!-- Opcionales -->
        <div class="col-md-6">
          <label class="form-label">Ciudad (opcional)</label>
          <input type="text" name="city" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Código postal (opcional)</label>
          <input type="text" name="zip" class="form-control" />
        </div>

        <div class="col-12">
          <label class="form-label">Notas (opcional)</label>
          <textarea name="notes" rows="3" class="form-control" placeholder="Horario preferido, referencias, piso/puerta, etc."></textarea>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-4">
        <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/carrito.php">← Volver al carrito</a>
        <button type="submit" class="btn btn-primary">Confirmar pedido</button>
      </div>
    </div>
  </form>

  <div class="alert alert-info mt-3" role="alert">
    No realizamos cobros online. Registramos tu pedido y <strong>te contactamos para confirmar la entrega a domicilio</strong>.
  </div>
</main>

<script>
(function(){
  const BASE = <?= json_encode($BASE) ?>;

  function notify(message, type, title) {
    if (typeof window.showToast === 'function') {
      window.showToast(message, type || 'info', title || null);
    } else {
      alert(message);
    }
  }

  function getCartItems(){
    try { return JSON.parse(localStorage.getItem('cart') || '[]'); }
    catch { return []; }
  }

  function normalizeItems(items){
    return items.map(it => ({
      product_id: Number(it.product_id ?? it.id ?? 0),
      name: String(it.name ?? ''),
      qty: Math.max(1, Number(it.qty ?? 1)),
      price: Number(it.price ?? 0)
    })).filter(x => x.product_id > 0 && x.qty > 0);
  }

  const form = document.getElementById('checkout-form');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const items = normalizeItems(getCartItems());
    if (!items.length){
      notify('Tu carrito está vacío.', 'info', 'Carrito');
      window.location.href = BASE + '/public/carrito.php';
      return;
    }

    const fd = new FormData(form);
    const payload = {
      name:    (fd.get('name')    || '').trim(),
      email:   (fd.get('email')   || '').trim(),
      phone:   (fd.get('phone')   || '').trim(),
      address: (fd.get('address') || '').trim(),
      city:    (fd.get('city')    || '').trim(),
      zip:     (fd.get('zip')     || '').trim(),
      notes:   (fd.get('notes')   || '').trim(),
      items
    };

    if (!payload.name || !payload.email || !payload.address){
      notify('Por favor completa nombre, email y dirección.', 'warning', 'Datos incompletos');
      return;
    }

    try {
      const res = await fetch(BASE + '/api/checkout_create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      let data;
      try { data = await res.json(); } catch {
        const t = await res.text();
        console.error('Respuesta no JSON:', t);
        throw new Error('Respuesta inválida del servidor');
      }

      if (!res.ok || !data.ok){
        console.error('Checkout error:', {status: res.status, data});
        throw new Error(data.error || 'No se pudo registrar el pedido');
      }

      localStorage.removeItem('cart');
      notify('Pedido registrado correctamente. Nº ' + data.order_id, 'success', 'Pedido creado');

      setTimeout(() => {
        window.location.href = BASE + '/public/gracias.php?order=' + encodeURIComponent(String(data.order_id));
      }, 900);

    } catch (err) {
      console.error(err);
      notify(err.message || 'Ocurrió un error al confirmar el pedido. Intenta nuevamente en unos segundos.', 'error', 'Error');
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
