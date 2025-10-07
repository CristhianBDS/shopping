# 🛒 Shopping — Proyecto PHP/MariaDB

## 📌 Descripción
Proyecto de tienda online en PHP con arquitectura modular y conexión a base de datos vía PDO.  
Incluye un **front público** (catálogo, producto, carrito y checkout simulado) y un **panel admin** (pedidos y productos).  

El objetivo es llegar a **v1.0 estable el 30 de octubre**, con todo el flujo completo en producción.

---

## 🚀 Estado del proyecto
### ✅ Fase 1 (completada)
- **Gestión de pedidos (admin)**
  - Listado de pedidos con filtros y paginación.
  - Cálculo de totales con items de pedido.
  - Panel básico de estado (`pendiente`, `pagado`, `enviado`, `cancelado`).
- **Catálogo público**
  - Carga de productos vía `/api/products.php`.
  - Catálogo con búsqueda, filtros y fallback de imágenes.
- **Checkout (público)**
  - Simulación de pedido (datos del cliente + carrito en localStorage).
  - Confirmación de pedido → guardado en `localStorage` como `last_order`.

---

### 🚧 Fase 2 (en cierre)
- **CRUD de productos (admin)**
  - `admin/productos.php` listado con búsqueda, filtros y acciones (activar, desactivar, inactivar).
  - `admin/producto_form.php` para crear y editar productos con subida de imágenes validada.
  - Subida de imágenes a `/uploads` con validación de extensión, MIME real y límite de 2MB.
- **Mensajes flash universales**
  - Añadido `inc/flash.php`.
  - Integrados en admin para mostrar resultado de acciones (éxito, error, info).
- **Navegación por contexto**
  - `nav_public.php` y `nav_admin.php`, con detección de `$CONTEXT`.
- **Secciones públicas**
  - Home (`index.php`) con hero opcional.
  - Catálogo dinámico con JS y API.
  - Producto con detalle y “Añadir al carrito”.
  - Carrito en `localStorage` con actualización de cantidades y total.
  - Checkout simulado con formulario y resumen.
  - Página `404.php` con links de retorno.

---

## 🔍 Puntos críticos a testear antes de `v0.2-fase2-final`
### Público
- Index → Catálogo → Detalle de producto.
- Añadir producto al carrito y verlo reflejado.
- Actualizar cantidades/eliminar en carrito.
- Completar checkout → ver alerta y redirección al catálogo.
- Ver que `404.php` funciona en rutas inexistentes.

### Admin
- Login/logout correctos (incluye CSRF + intentos fallidos limitados).
- Dashboard con métricas de productos.
- Crear producto (imagen incluida).
- Editar producto → cambiar nombre, precio, estado.
- Activar/desactivar desde listado.
- Inactivar (soft delete).
- Mensajes flash visibles tras cada acción.

---

## 📂 Estructura actual del proyecto
shopping/
├── admin/
│ ├── index.php
│ ├── login.php
│ ├── logout.php
│ ├── pedido.php
│ ├── productos.php
│ ├── producto_form.php
│ └── (usuarios.php pendiente)
├── api/
│ ├── health.php
│ ├── orders.php
│ └── products.php
├── config/
│ ├── app.php
│ ├── bootstrap.php
│ └── db.php
├── inc/
│ ├── auth.php
│ └── flash.php
├── public/
│ ├── index.php
│ ├── catalogo.php
│ ├── producto.php
│ ├── carrito.php
│ ├── checkout.php
│ └── 404.php
├── templates/
│ ├── header.php
│ ├── footer.php
│ ├── nav_admin.php
│ └── nav_public.php
├── uploads/ (carpeta para imágenes)
├── assets/
│ ├── base.css
│ └── tienda.css
└── README.md


---

## 📅 Roadmap
- **Fase 2 → v0.2 (octubre 2025)**  
  Cierre de CRUD productos + QA público/admin.
- **Fase 3 → v0.3**  
  Checkout conectado a BD (inserción de pedidos reales).
- **Fase 4 → v0.4**  
  Métodos de pago sandbox (Stripe/PayPal).
- **Fase 5 → v0.5**  
  Mejoras UX/performance (lazy load, accesibilidad, responsive).
- **Fase 6 → v0.6**  
  Hardening, deploy y backups.
- **v1.0.0 → 30 de octubre**  
  Versión final lista para producción.

---

## ⚙️ Requisitos técnicos
- PHP 8.2+
- MariaDB/MySQL
- Apache/Nginx con soporte para `.htaccess` (opcional)
- Extensiones:
  - `pdo_mysql`
  - `fileinfo` (para validación de imágenes)
- Composer (opcional para futuras fases)

---

## 📝 Notas de desarrollo
- Uso de `PDO` en todas las queries.
- CSRF implementado en formularios admin y login.
- Carrito manejado en `localStorage` (por ahora).
- Configuración de `Intelephense` en VSCode ajustada para evitar falsos positivos.
- Control de errores con mensajes flash + logs.

---
