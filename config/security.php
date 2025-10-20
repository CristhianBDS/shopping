<?php
// config/security.php — Cabeceras + funciones de seguridad

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Envía cabeceras de seguridad básicas.
 */
function send_security_headers(): void {
    if (headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content-Security-Policy básica (ajusta si usas CDN o inline)
    $csp = [
        "default-src 'self'",
        "img-src 'self' data:",
        "style-src 'self' 'unsafe-inline'", // permite CSS inline (Bootstrap)
        "script-src 'self' 'unsafe-inline'", // permite scripts inline
        "connect-src 'self'",
        "frame-ancestors 'self'"
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
}

/**
 * Genera y devuelve un token CSRF único por sesión.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF recibido.
 */
function csrf_verify(?string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Invalida el token CSRF (opcional, para regenerarlo).
 */
function csrf_reset(): void {
    unset($_SESSION['csrf_token']);
}
