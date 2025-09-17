<?php
require_once __DIR__ . '/../config/app.php';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Catálogo</title>
  <!-- RUTAS CORRECTAS A CSS -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/base.css">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/tienda.css">
</head>
<body>
  <main class="page">
    <header class="catalogo-header">
      <h1>Catálogo</h1>
      <div class="toolbar">
        <input id="q" class="search" type="search" placeholder="Buscar producto..." />
        <span id="count" class="badge">0 items</span>
        <a id="cart-link" class="badge" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
      </div>
    </header>

    <section id="grid" class="catalogo-grid" aria-live="polite"></section>
    <!-- evita inline styles; usa clase hidden y pon su CSS en base.css -->
    <div id="empty" class="empty hidden">No hay productos para mostrar.</div>
  </main>

  <script>
  (function () {
    const BASE = <?= json_encode($BASE) ?>;
    const API_URL = BASE + "/api/products.php?active=1";
    // RUTA CORRECTA A LAS IMÁGENES
    const IMG_DIR = BASE + "/images/";
    const PLACEHOLDER = IMG_DIR + "placeholder.jpg";

    const grid = document.getElementById("grid");
    const empty = document.getElementById("empty");
    const count = document.getElementById("count");
    const q = document.getElementById("q");

    let all = [];
    let filtered = [];

    function fmtPrice(n) {
      try { return new Intl.NumberFormat('es-ES', { style:'currency', currency:'EUR' }).format(n); }
      catch (_) { return "€ " + Number(n).toFixed(2); }
    }

    function setEmpty(show, msg) {
      empty.classList.toggle('hidden', !show);
      if (msg) empty.textContent = msg;
    }

    function render(items) {
      grid.innerHTML = "";
      if (!items || items.length === 0) {
        setEmpty(true, "No hay productos para mostrar.");
        count.textContent = "0 items";
        return;
      }
      setEmpty(false);
      count.textContent = items.length + (items.length === 1 ? " item" : " items");

      const frag = document.createDocumentFragment();

      items.forEach(p => {
        const card = document.createElement("article");
        card.className = "card";
        const href = `${BASE}/public/producto.php?id=${p.id}`;
        const imgName = (p.image && String(p.image).trim()) ? p.image : 'placeholder.jpg';
        card.innerHTML = `
          <a class="cover" href="${href}" aria-label="Ver ${p.name || 'producto'}">
            <img src="${IMG_DIR + imgName}" alt="${p.name || ''}" onerror="this.src='${PLACEHOLDER}'" />
          </a>
          <div class="name" title="${p.name || ''}">
            <a href="${href}">${p.name || ''}</a>
          </div>
          <div class="muted">${p.description ? p.description : ''}</div>
          <div class="price">${fmtPrice(p.price || 0)}</div>
        `;
        frag.appendChild(card);
      });

      grid.appendChild(frag);
    }

    function applyFilter() {
      const term = (q.value || "").toLowerCase().trim();
      if (!term) { filtered = all.slice(); render(filtered); return; }
      filtered = all.filter(p =>
        (p.name || "").toLowerCase().includes(term) ||
        (p.description || "").toLowerCase().includes(term)
      );
      render(filtered);
    }

    async function load() {
      try {
        const res = await fetch(API_URL);
        const json = await res.json();

        // ACEPTA 'is_active' o 'active' (según cómo lo devuelva la API)
        let rows = Array.isArray(json?.data) ? json.data : [];
        // normaliza flags
        rows = rows.map(p => ({
          ...p,
          is_active: p.hasOwnProperty('is_active') ? p.is_active : (p.hasOwnProperty('active') ? p.active : 1)
        }));
        // filtra activos (acepta "1", 1, true)
        all = rows.filter(p => String(p.is_active) === "1" || p.is_active === 1 || p.is_active === true);

        applyFilter();
      } catch (err) {
        console.error(err);
        setEmpty(true, "Error cargando productos.");
      }
    }

    // Mini carrito (contador)
    function getCart() { try { return JSON.parse(localStorage.getItem("cart") || "[]"); } catch { return []; } }
    function cartCount() { return getCart().reduce((acc, it) => acc + (Number(it.qty) || 0), 0); }
    function refreshCartBadge(){ const c = document.getElementById("cart-count"); if (c) c.textContent = String(cartCount()); }
    window.addEventListener("storage", (ev) => { if (ev.key === "cart") refreshCartBadge(); });

    q.addEventListener("input", applyFilter);
    load();
    refreshCartBadge();
  })();
  </script>
</body>
</html>
