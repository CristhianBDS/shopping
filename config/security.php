<?php
// config/security.php — Cabeceras de seguridad globales
function send_security_headers(): void {
  if (headers_sent()) return;

  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');

  // Content-Security-Policy básica (ajusta si usas cdns o inline)
  $csp = [
    "default-src 'self'",
    "img-src 'self' data:",
    "style-src 'self' 'unsafe-inline'", // permitir CSS inline básico de Bootstrap
    "script-src 'self' 'unsafe-inline'", // si eliminas inline, quita 'unsafe-inline'
    "connect-src 'self'",
    "frame-ancestors 'self'"
  ];
  header('Content-Security-Policy: ' . implode('; ', $csp));
}
