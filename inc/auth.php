<?php
// inc/auth.php — Helpers de autenticación, autorización y RBAC

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ========= ESTADO DE SESIÓN ========= */

function isLoggedIn(): bool {
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function isMember(): bool {
    return isLoggedIn() && (($_SESSION['user']['role'] ?? '') === 'member' || ($_SESSION['user']['role'] ?? '') === 'user');
}

function currentUser(): ?array {
    return isLoggedIn() ? $_SESSION['user'] : null;
}

/* ========= REDIRECCIONES Y GUARDAS ========= */

function redirect(string $url): void {
    header('Location: '.$url);
    exit;
}

/** Exige sesión (si no hay, manda a login con ?next=) */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $current  = currentUrl();
        $loginUrl = url('/public/login.php', ['next' => $current]);
        redirect($loginUrl);
    }
}

/** Exige rol admin; si no hay sesión, manda a login; si no es admin, 403 */
function requireAdmin(): void {
    if (!isLoggedIn()) {
        $current  = currentUrl();
        $loginUrl = url('/public/login.php', ['next' => $current]);
        redirect($loginUrl);
    }
    if (!isAdmin()) {
        http_response_code(403);
        echo '<h1>403 Prohibido</h1><p>No tienes permisos para acceder a esta sección.</p>';
        exit;
    }
}

/* ========= HELPERS DE URL ========= */

function baseUrl(): string {
    return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
}

function url(string $path, array $params = []): string {
    $u = baseUrl().$path;
    if ($params) {
        $q = http_build_query($params);
        $u .= (str_contains($u, '?') ? '&' : '?').$q;
    }
    return $u;
}

function currentUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme.'://'.$host.$uri;
}

/* ========= CSRF ========= */

function auth_csrf(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(string $token): bool {
    return $token && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ========= LOGOUT ========= */

function auth_logout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    session_start();
    if (function_exists('session_regenerate_id')) session_regenerate_id(true);
}

/* ========= COMPATIBILIDAD CON TU NAV ========= */

function auth_user(): ?array { return currentUser(); }

/* ========= RBAC SENCILLO ========= */

/** 'admin' | 'member' (o 'user') | '' */
function current_role(): string {
    if (!isLoggedIn()) return '';
    $r = (string)($_SESSION['user']['role'] ?? '');
    return $r === 'user' ? 'member' : $r; // normaliza 'user' -> 'member'
}

/** True si el usuario tiene alguno de los roles dados (acepta 'user' como sinónimo de 'member') */
function hasRole(string|array $roles): bool {
    $r = current_role();
    if ($r === '') return false;
    $want = array_map(fn($x) => $x === 'user' ? 'member' : $x, (array)$roles);
    return in_array($r, $want, true);
}

/** require_role('admin') o require_role(['admin','member']) */
function require_role(string|array $roles): void {
    if (!isLoggedIn()) {
        $current  = currentUrl();
        $loginUrl = url('/public/login.php', ['next' => $current]);
        redirect($loginUrl);
    }
    if (!hasRole($roles)) {
        http_response_code(403);
        echo '<h1>403 Prohibido</h1><p>No tienes permisos para acceder a esta sección.</p>';
        exit;
    }
}

/**
 * can($permission): permisos por rol
 * - admin: acceso total
 * - member: por defecto solo 'usuarios:list' (ajústalo a tu gusto)
 */
function can(string $permission): bool {
    if (!isLoggedIn()) return false;
    $role = current_role();
    if ($role === 'admin') return true;

    static $ROLE_PERMS = [
        'member' => [
            'usuarios:list',
            // añade más permisos para member si hace falta:
            // 'algo:ver', 'algo:crear', ...
        ],
    ];

    $perms = $ROLE_PERMS[$role] ?? [];
    return in_array($permission, $perms, true);
}

/* ========= ALIAS SNAKE_CASE (compatibilidad con código viejo) ========= */

if (!function_exists('require_admin'))  { function require_admin(): void  { requireAdmin(); } }
if (!function_exists('require_login'))  { function require_login(): void  { requireLogin(); } }
if (!function_exists('is_admin'))       { function is_admin(): bool       { return isAdmin(); } }
if (!function_exists('is_member'))      { function is_member(): bool      { return isMember(); } }
