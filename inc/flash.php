<?php
// inc/flash.php
// Gestor universal de mensajes flash para el área admin.
// Uso: flash_success("..."); flash_error("..."); flash_info("..."); y luego redirect limpio.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}

/**
 * Agrega un mensaje flash tipado.
 * @param string $type success|error|info
 * @param string $msg
 */
function flash_add(string $type, string $msg): void {
    $type = in_array($type, ['success', 'error', 'info'], true) ? $type : 'info';
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg, 'ts' => time()];
}

/** Helpers */
function flash_success(string $msg): void { flash_add('success', $msg); }
function flash_error(string $msg): void   { flash_add('error', $msg); }
function flash_info(string $msg): void    { flash_add('info', $msg); }

/**
 * Renderiza y consume todos los flashes.
 * Llamar una sola vez por request (p.ej., en templates/header.php al inicio de <main>).
 */
function flash_render(): void {
    if (empty($_SESSION['flash'])) return;

    $map = [
        'success' => 'alert alert-success',
        'error'   => 'alert alert-danger',
        'info'    => 'alert alert-info',
    ];

    echo '<div class="flash-container" aria-live="polite" style="margin:16px 0;">';
    foreach ($_SESSION['flash'] as $f) {
        $type = $f['type'] ?? 'info';
        $cls  = $map[$type] ?? $map['info'];
        $msg  = htmlspecialchars($f['msg'] ?? '', ENT_QUOTES, 'UTF-8');
        echo "<div class=\"$cls\" role=\"status\" style=\"margin-bottom:8px;\">$msg</div>";
    }
    echo '</div>';

    // Consumir
    $_SESSION['flash'] = [];
}
