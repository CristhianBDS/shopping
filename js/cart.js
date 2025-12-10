// js/cart.js
// ======================================================
//  Sistema de carrito + sistema global de notificaciones
// ======================================================
(function () {
  const KEY = "cart";

  // ===============================================
  // 1) SISTEMA GLOBAL DE TOASTS
  //    - showToast(mensaje, tipo, titulo?)
//    - toast(titulo, mensaje, tipo?)  (alias)
//    Tipos: "success" | "error" | "warning" | "info"
// ===============================================
  function ensureToastStack() {
    let stack = document.getElementById("toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.id = "toast-stack";
      // Posición fija: esquina inferior derecha
      stack.style.position = "fixed";
      stack.style.right = "1rem";
      stack.style.bottom = "1rem";
      stack.style.zIndex = "9999";
      stack.style.display = "flex";
      stack.style.flexDirection = "column";
      stack.style.gap = ".5rem";
      document.body.appendChild(stack);
    }
    return stack;
  }

  function createToastElement(title, message, type) {
    const toast = document.createElement("div");
    toast.className = `toast-msg toast-msg--${type}`;

    toast.innerHTML = `
      <div class="toast-msg__icon">!</div>
      <div class="toast-msg__content">
        <div class="toast-msg__title">${title}</div>
        <div>${message}</div>
      </div>
      <button class="toast-msg__close" type="button" aria-label="Cerrar">&times;</button>
    `;

    // Cerrar manual
    toast.querySelector(".toast-msg__close").addEventListener("click", () => {
      toast.remove();
    });

    return toast;
  }

  function baseToast({ title, message, type = "info" }) {
    const stack = ensureToastStack();
    const t = createToastElement(title, message, type);

    stack.appendChild(t);

    // Animación de entrada
    requestAnimationFrame(() => {
      t.classList.add("show");
    });

    // Auto-cierre
    setTimeout(() => {
      t.classList.remove("show");
      setTimeout(() => t.remove(), 200);
    }, 4500);
  }

  /**
   * API principal: showToast(mensaje, tipo?, titulo?)
   * Ejemplos:
   *   showToast("Producto añadido", "success");
   *   showToast("Stock insuficiente", "error", "Error");
   */
  window.showToast = function (message, type = "info", title = null) {
    let autoTitle = title;
    if (!autoTitle) {
      if (type === "success") autoTitle = "Listo";
      else if (type === "error") autoTitle = "Error";
      else if (type === "warning") autoTitle = "Aviso";
      else autoTitle = "Información";
    }
    baseToast({ title: autoTitle, message, type });
  };

  /**
   * Alias compatible: toast(titulo, mensaje, tipo?)
   * Ejemplo:
   *   toast("Carrito", "Producto añadido", "success");
   */
  window.toast = function (title, message, type = "info") {
    baseToast({ title, message, type });
  };

  // ===============================================
  // 2) UTILIDADES DEL CARRITO (localStorage)
  // ===============================================
  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(KEY) || "[]") || [];
    } catch {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(KEY, JSON.stringify(cart));
  }

  function cartCount() {
    return getCart().reduce((acc, it) => acc + (Number(it.qty) || 0), 0);
  }

  function refreshCartBadge() {
    const c = document.getElementById("cart-count");
    if (c) c.textContent = String(cartCount());
  }

  /**
   * Añadir producto al carrito
   * id     → ID del producto (número)
   * name   → nombre para mostrar
   * price  → precio
   * image  → nombre de archivo de imagen
   */
  function addToCart(id, name, price, image) {
    const item = { id, name, price, image, qty: 1 };

    try {
      const cart = getCart();
      const idx = cart.findIndex((p) => String(p.id) === String(id));

      if (idx >= 0) {
        // Si ya existe, sumamos cantidad
        cart[idx].qty = Math.min(99, (Number(cart[idx].qty) || 0) + 1);
      } else {
        cart.push(item);
      }

      saveCart(cart);
      refreshCartBadge();

      // ✅ Toast bonito en lugar de alert()
      showToast("Se agregó al carrito correctamente.", "success", "Producto añadido");
    } catch (e) {
      console.error(e);
      showToast("No se pudo añadir al carrito. Inténtalo de nuevo.", "error", "Error");
    }
  }

  // ===============================================
  // 3) EXPORTAR COSAS ÚTILES AL ÁMBITO GLOBAL
  // ===============================================
  window.addToCart = addToCart;
  window.refreshCartBadge = refreshCartBadge;
  window.getCartItems = getCart;       // por si lo necesitas en otras páginas

  // Badge del carrito al cargar
  document.addEventListener("DOMContentLoaded", refreshCartBadge);

  // Actualizar badge si el storage cambia en otra pestaña
  window.addEventListener("storage", function (ev) {
    if (ev.key === KEY) refreshCartBadge();
  });
})();
