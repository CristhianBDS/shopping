<?php
// inc/auth.php — helpers de autenticación para el panel admin

// Asegura sesión activa
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Carga flashes si existe (para mensajes antes de redirigir)
$__flashPath = __DIR__ . '/flash.php';
if (file_exists($__flashPath)) {
  require_once $__flashPath;
}

/**
 * ¿Hay usuario logueado?
 */
function is_logged_in(): bool {
  return !empty($_SESSION['user']) && is_array($_SESSION['user']);
}

/**
 * ¿El usuario es admin?
 */
function is_admin(): bool {
  return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Exige login; si no hay, guarda a dónde quería ir y redirige a login.
 */
function require_login(): void {
  $base = defined('BASE_URL') ? BASE_URL : 'http://localhost/shopping';

  if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? ($base . '/admin/index.php');
    if (function_exists('flash_info')) {
      flash_info('Por favor, inicia sesión para continuar.');
    }
    header('Location: ' . $base . '/admin/login.php');
    exit;
  }
}

/**
 * Exige rol admin; si no cumple, manda a login (si no está logueado) o al dashboard.
 */
function require_admin(): void {
  $base = defined('BASE_URL') ? BASE_URL : 'http://localhost/shopping';

  if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? ($base . '/admin/index.php');
    if (function_exists('flash_info')) {
      flash_info('Por favor, inicia sesión como administrador.');
    }
    header('Location: ' . $base . '/admin/login.php');
    exit;
  }

  if (!is_admin()) {
    if (function_exists('flash_error')) {
      flash_error('No tienes permisos para acceder a esta sección.');
    }
    header('Location: ' . $base . '/admin/index.php');
    exit;
  }
}

/**
 * Helpers de autorización (roles/permisos simples)
 * Con tu esquema actual: role ENUM('admin','user')
 */
function current_user_role(): string {
  return $_SESSION['user']['role'] ?? 'user';
}

/** require_role: compara jerarquía simple */
function require_role(string $roleMin = 'admin'): void {
  require_login();
  $order = ['user' => 1, 'admin' => 2];
  $have  = $order[current_user_role()] ?? 0;
  $need  = $order[$roleMin] ?? PHP_INT_MAX;
  if ($have < $need) {
    if (function_exists('flash_error')) {
      flash_error('Acceso no autorizado.');
    }
    $base = defined('BASE_URL') ? BASE_URL : 'http://localhost/shopping';
    header('Location: ' . $base . '/admin/index.php');
    exit;
  }
}

/** can(): matriz de permisos */
function can(string $perm): bool {
  $role = current_user_role();
  $map = [
    'usuarios:list'   => ['admin','user'],
    'usuarios:view'   => ['admin','user'],
    'usuarios:create' => ['admin'],
    'usuarios:edit'   => ['admin'],
    'usuarios:state'  => ['admin'], // activar/desactivar
  ];
  return in_array($role, $map[$perm] ?? [], true);
}

/* -----------------------------------------------------------
 * Retrocompatibilidad (por si hay llamadas camelCase legacy)
 * ----------------------------------------------------------- */
if (!function_exists('requireLogin')) {
  function requireLogin(): void { require_login(); }
}
if (!function_exists('requireAdmin')) {
  function requireAdmin(): void { require_admin(); }
}

/* -----------------------------------------------------------
 * Alias de compatibilidad para proyectos que usaban set_flash()
 * ----------------------------------------------------------- */
if (!function_exists('set_flash')) {
  /**
   * set_flash($msg, $type) -> redirigido a nuestro sistema de flashes si existe.
   * $type: success|error|info (otros valores se tratan como info)
   */
  function set_flash(string $msg, string $type = 'info'): void {
    $type = strtolower($type);
    if ($type === 'success' && function_exists('flash_success')) { flash_success($msg); return; }
    if ($type === 'error'   && function_exists('flash_error'))   { flash_error($msg); return; }
    if (function_exists('flash_info')) { flash_info($msg); }
  }
}
