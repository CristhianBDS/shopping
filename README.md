# 🛍️ Mi Tienda

Proyecto de tienda online simple con carrito de compras y envío de pedidos vía **WhatsApp + QR**.  
Tecnologías: **HTML, CSS, JavaScript, PHP, MariaDB/MySQL**.

---

---------------INFORMACION SOBRE SHOPPING-------------------------------

### `/public/`
Parte pública (lo que ve el cliente).
- **index.php** → Página de inicio con presentación de la empresa.
Propósito:Es la página de inicio. Presenta la empresa, misión, beneficios o promociones.
Incluye header.php y footer.php desde /templates para mantener coherencia en todo el sitio.
Puede contener un “call to action” → botón o enlace hacia catalogo.php.
Qué va dentro:
include 'templates/header.php';
Sección “Hero” con imagen/logo + texto.
Breve descripción de la empresa/tienda.
Botón/enlace “Ver Catálogo” que lleva a catalogo.php.
include 'templates/footer.php';
Por qué va aquí:
Es la puerta de entrada de tu tienda. Todo visitante aterriza aquí antes de comprar.

- **catalogo.php** → Catálogo de productos, permite agregar al carrito.
Propósito:Mostrar el listado de productos disponibles.
Conectar con la API (/api/productos.php) mediante JS.
Permitir al usuario agregar productos al carrito.
Mostrar un contador/badge del carrito en el menú.
Qué va dentro:include 'templates/header.php';
Contenedor <div id="catalog-grid"></div> donde JS pintará productos dinámicamente.
Botón/badge del carrito <span id="cart-count"></span>.
include 'templates/footer.php';
Carga de api.js, cart.js y app.js para manejar catálogo y carrito.
Por qué va aquí:
s la estantería de la tienda. El usuario ve productos, precios, imágenes y puede armar su pedido.

- **checkout.php** → Carrito con total, genera enlace y QR a WhatsApp.
Propósito:Mostrar al usuario el detalle del carrito (productos, cantidades, precios).
Calcular el total.
Generar automáticamente el enlace de WhatsApp con el pedido.
Mostrar un código QR para que el cliente te contacte fácilmente.
Qué va dentro:include 'templates/header.php';
<div id="cart-list"></div> para mostrar items.
<p id="cart-total"></p> para mostrar el total.
Botón “Enviar por WhatsApp”.
<div id="qrcode"></div> donde se renderiza el código QR.
Scripts: cart.js para leer carrito, app.js para renderizar checkout, qr.min.js para generar QR.
include 'templates/footer.php';
Por qué va aquí:Es el mostrador de caja. Aquí el cliente revisa, confirma y envía el pedido directamente a tu WhatsApp.
- **templates/** → Plantillas reutilizables (header, nav, footer).

- **assets/** → Recursos estáticos (
---CSS---
La carpeta /css contiene los estilos del sitio.
Separamos lo "base" (reset, utilidades) de lo "personalizado" (diseño de la tienda).
Así logramos orden, claridad y fácil mantenimiento de los estilos.

1. base.css
Normalizar estilos entre navegadores (reset/normalize).
Definir utilidades globales y tipografía por defecto.
Establecer una base común para todo el sitio.
Reset de márgenes, paddings y box-sizing:
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
Tipografía global y colores por defecto:
body {
  font-family: Arial, sans-serif;
  color: #333;
  background: #fff;
}
Clases utilitarias simples (.container, .grid, .btn, .text-center).
Es como la fundación de la casa: asegura que todo tenga un mismo punto de partida.

2. tienda.css
Estilos específicos de la tienda.
Controla la identidad visual: header, footer, catálogo, carrito, botones, responsive.
Estilos del header/nav/footer (colores, alturas, hover en links).
Grid de productos en catalogo.php: tarjetas con imagen, título, precio y botón “Agregar”.
Carrito (checkout.php): tabla/lista, totales, botón de WhatsApp, QR centrado.
Media queries (@media) para diseño responsive en móviles.
Es la decoración de la casa: define cómo se ve tu tienda y cómo interactúa el usuario.

---JS---
Esta carpeta contiene toda la lógica JavaScript del frontend.
Cada archivo cumple una responsabilidad clara: configuración, carrito,
consumo de la API, inicialización de páginas y generación de QR.
Así mantenemos el código modular y fácil de mantener.

1. config.js
Centralizar configuraciones globales que se usarán en todo el frontend.
window.APP_CONFIG = {
  WHATSAPP_NUMBER: '34XXXXXXXXX', // tu número con prefijo de país
  API_BASE: '/api',
  CURRENCY: '€'
};

Para no repetir valores en todo el código. Si cambias el número de WhatsApp, lo haces en un solo lugar.

2. cart.js
Manejar la lógica del carrito usando localStorage.
Funciones: get(), save(), add(producto), remove(id), clear(), count(), total().
Internamente guarda un array JSON en localStorage["cart"].
Es el corazón de compras del cliente: lo que recuerda qué productos eligió.

3. api.js
Gestionar las llamadas a la API (/api/productos.php).
Función fetchProducts() que retorna el JSON de productos.
async function fetchProducts() {
  const res = await fetch(`${APP_CONFIG.API_BASE}/productos.php`);
  return res.json();
}

Mantiene separado el “hablar con la API” de la lógica del carrito o la interfaz.

4. app.js
Inicializar la lógica dependiendo de la página en la que estás (index, catalogo, checkout)
Detectar la página con document.body.dataset.page.
Si es catalogo: llamar fetchProducts(), renderizar tarjetas, usar cart.add().
Si es checkout: mostrar lista del carrito, calcular total, generar link de WhatsApp, mostrar QR.
Si es index: nada especial, solo actualizar el contador de carrito.
Es como el director de orquesta: sabe qué hacer en cada página y llama a los otros archivos (cart, api, config).

5. qr.min.js
Librería externa ya minificada para generar códigos QR en el checkout.
checkout.php con:
new QRCode(document.getElementById("qrcode"), linkWhatsApp);

Da la función de QR lista sin reinventar la rueda.

Con estos 5 archivos tu frontend queda modular:
-config.js → valores globales
-cart.js → carrito
-api.js → conexión con backend
-app.js → inicialización de páginas
-qr.min.js → QR
 imágenes, fuentes).

### `/api/`
Endpoints que devuelven JSON para el frontend.
La carpeta /api contiene endpoints en JSON.Estos archivos no muestran páginas, sino datos.Sirven para que el frontend (JavaScript) consulte información dinámica,
como productos o estado del sistema, sin recargar todo el sitio.


- **productos.php** → Lista de productos activos para el catálogo.
Devuelve en JSON la lista de productos activos que están en la base de datos.
Es consumido por catalogo.php mediante fetch() desde JavaScript.
Futuro: puede ampliarse con filtros (categorías, precios, stock).
Qué va dentro:
require '../config/bootstrap.php'; para conexión PDO y settings

- **health.php** → Respuesta `{"ok": true}` para probar que todo funciona.
Endpoint de prueba rápida para saber si la aplicación funciona.
Devuelve un JSON fijo {"ok": true}.
Sirve para comprobar conexión y despliegue (ideal en testing o monitoreo).
Qué va dentro:
Un header Content-Type: application/json.
Un simple echo json_encode(["ok" => true]);.
Por qué va aquí:
Es el pulso del sistema: permite verificar en segundos si el servidor responde correctamente, sin cargar toda la app.


### `/admin/`
Panel privado para gestionar productos.
La carpeta /admin contiene el panel de administración.
Aquí se manejan usuarios y productos, con seguridad mediante login.
Incluye interfaz CRUD, endpoints protegidos, soporte de autenticación
y la carpeta uploads para almacenar imágenes subidas por el administrador.

- **index.php**

index.php dentro de /admin es la puerta de entrada al panel.
Su propósito es redirigir automáticamente al login si no hay sesión,
o al panel principal (productos.php) si el administrador ya está autenticado.

Evitar que alguien entre a /admin/ y vea una carpeta vacía o un error.
hacer más profesional el acceso al panel.
Guiar al admin al lugar correcto según su estado (logueado o no).
(lógica típica)
<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Si hay sesión de admin → enviar a productos
if (isset($_SESSION['admin'])) {
    header("Location: productos.php");
    exit;
}
// Si no hay sesión → enviar al login
header("Location: login.php");
exit;
Es el portero de la entrada al área admin.
Así, si un admin escribe solo tudominio.com/admin/, no se queda en blanco ni se expone nada, sino que es dirigido automáticamente al flujo correcto.


- **login.php** → Acceso del administrador
Página de acceso al panel admin.
Valida usuario y contraseña (hash en la BD).
Inicia sesión y redirige a productos.php si el login es correcto.
Formulario <form> con username y password.
Validación en el servidor (password_verify).
Iniciar $_SESSION['admin'] al loguear.
Redirección a productos.php.
Es la puerta de entrada segura al panel de gestión.

- **logout.php** 
logout.php → cierra la sesión del administrador.
Su propósito es destruir la sesión activa y redirigir
al login, asegurando que nadie siga con acceso no autorizado.

Cerrar la sesión activa.
Destruir $_SESSION para evitar que quede abierta.
Redirigir al login.php.
session_start();
session_unset(); y session_destroy();

Redirigir a login.php.
<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit;

Es la puerta de salida segura: asegura que un admin cierre sesión y nadie más use el mismo navegador para entrar al panel.

- **productos.php** → CRUD de productos (crear, editar, eliminar).
Interfaz gráfica para que el administrador cree, edite o elimine productos.
Lista productos en una tabla con botones editar/eliminar.
Incluye un formulario para crear nuevos productos.
include '../config/bootstrap.php'; y verificación de sesión (auth.php).
Formulario: nombre, precio, descripción, imagen.
Tabla con productos cargados desde la BD.
Botones para editar o eliminar que se comunican con api_productos.php.
Es el panel de control donde mantienes el catálogo actualizado sin tocar la BD a mano.

- **api_productos.php** → Endpoint protegido para CRUD.
Endpoint protegido (solo admin) para ejecutar operaciones CRUD sobre productos.
Recibe POST (crear), PUT (editar), DELETE (eliminar).
Responde siempre en JSON (éxito o error).
Verificación de sesión (auth.php).
Validaciones de entrada (nombre, precio, imagen).
Consultas PDO preparadas (insert, update, delete).
Respuestas en JSON ({"status":"ok"} o {"error":"..."}).
Es la capa de comunicación: separa la lógica del frontend admin y la base de datos.

- **inc/** → Archivos de soporte (auth, CSRF).
Carpeta con scripts auxiliares que refuerzan seguridad y sesiones.
auth.php
Verifica si existe $_SESSION['admin'].
Redirige al login si no hay sesión.
Evita acceso no autorizado a CRUD.
csrf.php (opcional)
Genera y valida tokens CSRF en formularios.
Protege contra ataques de envío de formularios externos.
Son los guardianes de la puerta: aseguran que nadie externo manipule tu sistema.

### `/config/`
La carpeta /config centraliza la configuración del sistema.
Aquí se definen constantes globales, la conexión a la base de datos
y un bootstrap que inicializa sesiones y carga dependencias.
Permite mantener el código ordenado y fácil de mantener.

Configuraciones del sistema.
- **app.php** → Variables globales (BASE_URL, TZ).
Definir constantes y variables globales del proyecto.
Aquí ajustas cosas como la URL base, zona horaria, modo debug.
<?php
define('BASE_URL', 'http://localhost/mi-tienda/public');
define('TIMEZONE', 'Europe/Madrid');
define('DEBUG', true);
date_default_timezone_set(TIMEZONE);

Es el panel de configuración general, para que no tengas que repetir estos valores en todos los archivos.

- **db.php** → Conexión PDO a la BD.
Manejar la conexión a la base de datos mediante PDO.
Centraliza la lógica de conexión para que se use desde cualquier archivo.
<?php
function getPDO() {
  $dsn = 'mysql:host=localhost;dbname=tienda;charset=utf8mb4';
  $user = 'root';
  $pass = '';
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];
  return new PDO($dsn, $user, $pass, $options);
}
Es la puerta hacia los datos: en lugar de duplicar código de conexión, llamas siempre a getPDO().

- **bootstrap.php** → Inicialización común (cargar config, sesiones).
Punto de arranque común para cada script.
Carga app.php y db.php, arranca la sesión y aplica configuraciones iniciales.

<?php
session_start();
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/db.php';
// Opcional: activar logs si DEBUG está en true
if (DEBUG) {
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
}
Es como el interruptor general: asegura que cualquier archivo del proyecto arranque con la configuración y conexión correctas.

### `/src/` (opcional)
La carpeta /src organiza la lógica del dominio.
Models describen datos (Producto),
Repositories hablan con la base,
Services resuelven tareas transversales (imágenes),
y Helpers agrupan utilidades comunes.
Así mantenemos el código limpio, testeable y escalable.
Código organizado en clases y servicios.
- **Models/** → Entidades (ej: Product.php).
- **Repositories/** → Consultas SQL.
- **Services/** → Servicios auxiliares (ej: manejo de imágenes).
- **Helpers/** → Funciones de utilidad.

### `/storage/`
Archivos internos (no públicos).
- **logs/** → Archivos de log.
- **cache/** → Archivos de caché.

---

## 🚀 Funcionalidades principales
1. **Catálogo dinámico** desde la base de datos.
2. **Carrito de compras** en localStorage (frontend).
3. **Checkout** con total y generación de **enlace/QR de WhatsApp**.
4. **Panel admin** para CRUD de productos.
5. **Estructura escalable** lista para crecer (stock, categorías, pagos online).

---

## ⚙️ Requisitos
- PHP 8+
- MariaDB/MySQL
- Servidor local (XAMPP, Laragon, etc.)
- Navegador moderno

---

## 📌 Próximos pasos
1. Crear la base de datos y la tabla `products`.
2. Configurar la conexión en `config/db.php`.
3. Implementar las páginas públicas (inicio, catálogo, checkout).
4. Desarrollar el panel admin (CRUD).
5. Mejorar estilos y experiencia de usuario.





-----------------------------------------------------------------------------------
-----------------------------------------------------------------------------------





ESTRUCTURA DE CARPETAS Y DESARROLLO.

shopping/
├── .gitignore                 # Ignorar archivos innecesarios en Git
│
├── api/                       # Endpoints de la API (responden en JSON)
│   ├── health.php             # Verifica estado (ok/db conectado)
│   ├── products.php           # API de productos
│   └── orders.php             # (pendiente) API para registrar pedidos
│
├── assets/                    # Recursos estáticos
│   ├── css/
│   │   ├── base.css           # Estilos globales: reset, tipografía, utilidades
│   │   └── tienda.css         # Estilos de catálogo, producto, carrito y checkout
│   ├── js/                    # (opcional) JS externo si decides separar
│   └── img/                   # Imágenes del sitio
│       ├── placeholder.jpg
│       └── (imágenes de productos)
│
├── config/                    # Configuración del sistema
│   ├── app.php                # Configuración general (BASE_URL, DEBUG, TZ, etc.)
│   ├── bootstrap.php          # Inicialización del proyecto
│   └── db.php                 # Conexión a la base de datos
│
├── public/                    # Páginas públicas accesibles
│   ├── index.php              # Página inicial
│   ├── catalogo.php           # Listado de productos
│   ├── producto.php           # Detalle de producto
│   ├── carrito.php            # Carrito de compras
│   └── checkout.php           # Checkout (formulario + resumen)
│
├── templates/                 # Componentes comunes (opcional)
│   ├── header.php             # Cabecera con <head> y menú
│   ├── nav.php                # Navegación (si está separada)
│   └── footer.php             # Pie de página
│
├── admin/                     # Panel administrativo (pendiente)
│   ├── index.php              # Dashboard admin
│   ├── productos.php          # CRUD productos
│   └── pedidos.php            # Gestión de pedidos
│
├── uploads/                   # Archivos subidos por usuarios/admin
│   ├── productos/             # Imágenes de productos cargadas por admin
│   └── usuarios/              # Avatares o documentos
│
└── README.md                  # Documentación del proyecto



🚀 Funcionalidades implementadas
1. API
api/health.php: responde {ok:true, db:"conectado"} para verificar estado del sistema.
api/products.php: expone productos en JSON con filtros (ej. ?active=1).
(Pendiente) api/orders.php: recibirá pedidos para guardarlos en la BD.
2. Frontend público (public/)
catalogo.php:
Lista de productos en tarjetas con imagen, nombre, descripción y precio.
Buscador en tiempo real.
Contador de carrito (🛒) sincronizado.
producto.php:
Muestra detalle de producto (imagen grande, precio, descripción).
Botón “Añadir al carrito” → guarda en localStorage.
Permite elegir cantidad.
carrito.php:
Tabla con productos añadidos (editar cantidades, eliminar, vaciar).
Cálculo de subtotal y total.
Botón “Finalizar compra” que enlaza a checkout.
checkout.php:
Formulario de cliente (nombre, email, teléfono, dirección, ciudad, CP).
Métodos de pago simulados (tarjeta, transferencia, contraentrega).
Resumen del pedido con total.
Confirmación simulada: guarda last_order en localStorage, vacía carrito y redirige.

3. Estilos (assets/css/)
base.css: reset + estilos globales.
tienda.css: catálogo, producto, carrito, checkout.

4. Configuración (config/)
app.php: define BASE_URL, zona horaria, etc.
db.php: conexión a MySQL/MariaDB.
bootstrap.php: inicialización del entorno.

5. Estructura para crecer
templates/: componentes comunes (header.php, footer.php, nav.php) listos para incluir en las páginas.
admin/: panel de administración para productos y pedidos (a desarrollar).
uploads/: carpeta para almacenar imágenes y archivos subidos.

✅ Estado actual

Catálogo → Detalle → Carrito → Checkout → Confirmación: funciona de extremo a extremo.
Contador del carrito 🛒 sincronizado en todo el sitio.
Estilos organizados en CSS separado.
Flujo de compra probado correctamente.