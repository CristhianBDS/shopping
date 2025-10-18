<?php
// inc/auth.php — helpers de autenticación (público + admin)

// Sesión
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// CSRF bootstrap mín.
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// Flashes si existen
$__flashPath = __DIR__ . '/flash.php';
if (file_exists($__flashPath)) {
  require_once $__flashPath;
}

/** ===== API pública mínima ===== */

/** Usuario autenticado o null */
function auth_user(): ?array {
  return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

/** ¿hay sesión iniciada? */
function auth_check(): bool {
  return auth_user() !== null;
}

/** Iniciar sesión con datos mínimos */
function auth_login(array $user): void {
  if (session_status() === PHP_SESSION_NONE) session_start();
  $_SESSION['user'] = [
    'id'       => $user['id'] ?? null,
    'username' => $user['username'] ?? ($user['email'] ?? ($user['name'] ?? 'usuario')),
    'name'     => $user['name'] ?? ($user['username'] ?? 'usuario'),
    'role'     => $user['role'] ?? 'user',
  ];
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
}

/** Cerrar sesión limpiamente */
function auth_logout(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

/** Token CSRF actual */
function auth_csrf(): ?string {
  return $_SESSION['csrf'] ?? null;
}

/** ===== Compatibilidad con tu API previa (admin) ===== */

function is_logged_in(): bool { return auth_check(); }
function is_admin(): bool {
  return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function require_login(): void {
  $base = defined('BASE_URL') ? BASE_URL : 'http://localhost/shopping';
  if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? ($base . '/admin/index.php');
    if (function_exists('flash_info')) { flash_info('Por favor, inicia sesión para continuar.'); }
    header('Location: ' . $base . '/admin/login.php'); exit;
  }
}

function require_admin(): void {
  $base = defined('BASE_URL') ? BASE_URL : 'http://localhost/shopping';
  if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? ($base . '/admin/index.php');
    if (function_exists('flash_info')) { flash_info('Por favor, inicia sesión como administrador.'); }
    header('Location: ' . $base . '/admin/login.php'); exit;
  }
  if (!is_admin()) {
    if (function_exists('flash_error')) { flash_error('No tienes permisos para acceder a esta sección.'); }
    header('Location: ' . $base . '/admin/index.php'); exit;
  }
}

function current_user_role(): string { return $_SESSION['user']['role'] ?? 'user'; }

function require_role(string $roleMin = 'admin'): void {
  require_login();
  $order = ['user' => 1, 'admin' => 2];
  $have  = $order[current_user_role()] ?? 0;
  $need  = $order[$roleMin] ?? PHP_INT_MAX;
  if ($have < $need) {
    if (function_exists('flash_error')) { flash_error('Acceso no autorizado.'); }
    $base = defined('BASE_URL') ? BASE_URL : 'http://localhost/shopping';
    header('Location: ' . $base . '/admin/index.php'); exit;
  }
}

function can(string $perm): bool {
  $role = current_user_role();
  $map = [
    'usuarios:list'   => ['admin','user'],
    'usuarios:view'   => ['admin','user'],
    'usuarios:create' => ['admin'],
    'usuarios:edit'   => ['admin'],
    'usuarios:state'  => ['admin'],
  ];
  return in_array($role, $map[$perm] ?? [], true);
}

// Retrocompatibilidad
if (!function_exists('requireLogin')) { function requireLogin(): void { require_login(); } }
if (!function_exists('requireAdmin')) { function requireAdmin(): void { require_admin(); } }

// Alias flashes legacy
if (!function_exists('set_flash')) {
  function set_flash(string $msg, string $type = 'info'): void {
    $type = strtolower($type);
    if ($type === 'success' && function_exists('flash_success')) { flash_success($msg); return; }
    if ($type === 'error'   && function_exists('flash_error'))   { flash_error($msg); return; }
    if (function_exists('flash_info')) { flash_info($msg); }
  }
}
