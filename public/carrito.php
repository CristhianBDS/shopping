<?php
// public/carrito.php — Carrito + Checkout (frontend con fetch a API)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Carrito';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

$user = auth_user();

include __DIR__ . '/../templates/header.php';
?>

<main class="page page-cart py-4">
  <section class="container cart-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Tu carrito</h1>
      <a href="<?= htmlspecialchars($BASE) ?>/public/catalogo.php" class="btn btn-outline-secondary btn-sm">
        ← Seguir comprando
      </a>
    </div>

    <div id="cart-empty" class="alert alert-info d-none">
      Tu carrito está vacío. Añade productos desde el catálogo.
    </div>

    <div id="cart-wrapper" class="row g-4 d-none">
      <!-- Lista de productos -->
      <section class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Producto</th>
                    <th class="text-center" style="width:140px">Cantidad</th>
                    <th class="text-end" style="width:120px">Precio</th>
                    <th class="text-end" style="width:120px">Subtotal</th>
                    <th class="text-end" style="width:80px">Quitar</th>
                  </tr>
                </thead>
                <tbody id="cart-body"></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- Resumen + Checkout -->
      <aside class="col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3">Resumen del pedido</h2>

            <p class="d-flex justify-content-between mb-1">
              <span>Subtotal</span>
              <strong id="cart-subtotal">€ 0,00</strong>
            </p>
            <p class="small text-muted mb-3">
              Los gastos de envío y el método de pago se pueden ajustar más adelante con el cliente.
            </p>

            <?php if (!$user): ?>
              <div class="alert alert-warning small">
                Para finalizar la compra debes iniciar sesión.
                <br>
                <a href="<?= htmlspecialchars($BASE) ?>/public/login.php" class="alert-link">Ir a iniciar sesión</a>
              </div>
            <?php endif; ?>

            <form id="checkout-form" class="vstack gap-2 mt-3">
              <div class="mb-2">
                <label class="form-label small mb-1">Método de pago</label>
                <select id="checkout-pay" class="form-select form-select-sm">
                  <option value="pendiente">A acordar con el cliente</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia bancaria</option>
                </select>
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Notas del pedido</label>
                <textarea
                  id="checkout-notes"
                  class="form-control form-control-sm"
                  rows="2"
                  placeholder="Ej: Entrega por la tarde, dejar en portería, etc."></textarea>
              </div>

              <button
                type="submit"
                id="btn-checkout"
                class="btn btn-primary w-100">
                Finalizar compra
              </button>

              <small class="text-muted d-block mt-1">
                Al confirmar, se creará el pedido en el sistema y podrás verlo en el panel de administración.
              </small>
            </form>
          </div>
        </div>
      </aside>
    </div>
  </section>
</main>

<script>
(function () {
  const KEY      = 'cart';
  const BASE     = <?= json_encode($BASE) ?>;
  const isLogged = <?= $user ? 'true' : 'false' ?>;

  const bodyEl      = document.getElementById('cart-body');
  const emptyEl     = document.getElementById('cart-empty');
  const wrapEl      = document.getElementById('cart-wrapper');
  const subtotalEl  = document.getElementById('cart-subtotal');
  const formEl      = document.getElementById('checkout-form');
  const notesEl     = document.getElementById('checkout-notes');
  const payEl       = document.getElementById('checkout-pay');
  const btnCheckout = document.getElementById('btn-checkout');

  // Helper de notificación: usa showToast si existe
  function notify(message, type = 'info', title = null) {
    if (typeof window.showToast === 'function') {
      window.showToast(message, type, title);
    } else {
      alert((title ? title + ': ' : '') + message);
    }
  }

  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(KEY) || '[]') || [];
    } catch (e) {
      console.error(e);
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(KEY, JSON.stringify(cart));
  }

  function fmtPrice(n) {
    const num = Number(n) || 0;
    try {
      return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR'
      }).format(num);
    } catch (_) {
      return '€ ' + num.toFixed(2);
    }
  }

  function render() {
    const cart = getCart();
    bodyEl.innerHTML = '';

    if (!cart.length) {
      emptyEl.classList.remove('d-none');
      wrapEl.classList.add('d-none');
      subtotalEl.textContent = '€ 0,00';
      return;
    }

    emptyEl.classList.add('d-none');
    wrapEl.classList.remove('d-none');

    let total = 0;
    const frag = document.createDocumentFragment();

    cart.forEach((item, index) => {
      const tr = document.createElement('tr');

      // Columna nombre + miniatura
      const nameTd = document.createElement('td');
      nameTd.innerHTML = `
        <div class="d-flex align-items-center gap-2">
          ${item.image ? `<img src="${BASE}/uploads/${item.image}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">` : ''}
          <div>
            <div class="fw-semibold">${item.name || 'Producto'}</div>
          </div>
        </div>
      `;

      // Cantidad
      const qtyTd = document.createElement('td');
      qtyTd.className = 'text-center';
      qtyTd.innerHTML = `
        <div class="input-group input-group-sm justify-content-center" style="max-width:130px;margin:0 auto;">
          <button type="button" class="btn btn-outline-secondary btn-sm btn-minus">-</button>
          <input type="number" min="1" class="form-control text-center input-qty" value="${item.qty || 1}">
          <button type="button" class="btn btn-outline-secondary btn-sm btn-plus">+</button>
        </div>
      `;

      // Precio unitario
      const priceTd = document.createElement('td');
      priceTd.className = 'text-end';
      priceTd.textContent = fmtPrice(item.price || 0);

      // Subtotal
      const sub = (Number(item.price) || 0) * (Number(item.qty) || 1);
      total += sub;
      const subtotalTd = document.createElement('td');
      subtotalTd.className = 'text-end';
      subtotalTd.textContent = fmtPrice(sub);

      // Quitar
      const removeTd = document.createElement('td');
      removeTd.className = 'text-end';
      removeTd.innerHTML = `
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove" title="Quitar">
          ✕
        </button>
      `;

      // Eventos
      qtyTd.querySelector('.btn-minus').addEventListener('click', () => {
        const c = getCart();
        if (!c[index]) return;
        c[index].qty = Math.max(1, (Number(c[index].qty) || 1) - 1);
        saveCart(c);
        render();
        if (window.refreshCartBadge) window.refreshCartBadge();
      });

      qtyTd.querySelector('.btn-plus').addEventListener('click', () => {
        const c = getCart();
        if (!c[index]) return;
        c[index].qty = (Number(c[index].qty) || 1) + 1;
        saveCart(c);
        render();
        if (window.refreshCartBadge) window.refreshCartBadge();
      });

      qtyTd.querySelector('.input-qty').addEventListener('change', (ev) => {
        const val = Math.max(1, Number(ev.target.value) || 1);
        const c = getCart();
        if (!c[index]) return;
        c[index].qty = val;
        saveCart(c);
        render();
        if (window.refreshCartBadge) window.refreshCartBadge();
      });

      removeTd.querySelector('.btn-remove').addEventListener('click', () => {
        const c = getCart();
        c.splice(index, 1);
        saveCart(c);
        render();
        if (window.refreshCartBadge) window.refreshCartBadge();
        notify('Producto eliminado del carrito.', 'info', 'Carrito');
      });

      tr.appendChild(nameTd);
      tr.appendChild(qtyTd);
      tr.appendChild(priceTd);
      tr.appendChild(subtotalTd);
      tr.appendChild(removeTd);

      frag.appendChild(tr);
    });

    bodyEl.appendChild(frag);
    subtotalEl.textContent = fmtPrice(total);
  }

  // Enviar pedido desde carrito
  if (formEl) {
    formEl.addEventListener('submit', async function (ev) {
      ev.preventDefault();

      const cart = getCart();
      if (!cart.length) {
        notify('Tu carrito está vacío.', 'info', 'Carrito');
        return;
      }

      if (!isLogged) {
        if (confirm('Debes iniciar sesión para finalizar la compra. ¿Ir a la página de login?')) {
          window.location.href = BASE + '/public/login.php';
        }
        return;
      }

      if (btnCheckout) {
        btnCheckout.disabled = true;
        btnCheckout.textContent = 'Procesando pedido...';
      }

      const payload = {
        cart,
        notes: (notesEl?.value || '').trim(),
        pay_method: (payEl?.value || 'pendiente')
      };

      try {
        const res = await fetch(BASE + '/api/checkout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const json = await res.json().catch(() => ({}));

        if (!res.ok || !json.ok) {
          throw new Error(json.error || 'No se pudo crear el pedido.');
        }

        localStorage.removeItem(KEY);
        render();
        if (window.refreshCartBadge) window.refreshCartBadge();

        notify('Pedido creado correctamente. Nº ' + json.order_id, 'success', 'Pedido creado');

        setTimeout(() => {
          window.location.href = BASE + '/public/index.php';
        }, 1000);

      } catch (err) {
        console.error(err);
        notify(err.message || 'Error al procesar el pedido. Inténtalo de nuevo.', 'error', 'Error');
      } finally {
        if (btnCheckout) {
          btnCheckout.disabled = false;
          btnCheckout.textContent = 'Finalizar compra';
        }
      }
    });
  }

  render();
})();
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
