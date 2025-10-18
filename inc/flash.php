<?php
// inc/flash.php — gestor universal de mensajes flash

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
  $_SESSION['flash'] = [];
}

function flash_add(string $type, string $msg): void {
  $type = in_array($type, ['success', 'error', 'info'], true) ? $type : 'info';
  $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg, 'ts' => time()];
}
function flash_success(string $msg): void { flash_add('success', $msg); }
function flash_error(string $msg): void   { flash_add('error', $msg); }
function flash_info(string $msg): void    { flash_add('info', $msg); }

/** Render + consume */
function flash_render(): void {
  if (empty($_SESSION['flash'])) return;
  $map = [
    'success' => 'alert alert-success',
    'error'   => 'alert alert-danger',
    'info'    => 'alert alert-info',
  ];
  echo '<div class="flash-container" aria-live="polite">';
  foreach ($_SESSION['flash'] as $f) {
    $type = $f['type'] ?? 'info';
    $cls  = $map[$type] ?? $map['info'];
    $msg  = htmlspecialchars($f['msg'] ?? '', ENT_QUOTES, 'UTF-8');
    echo "<div class=\"$cls\" role=\"status\">$msg</div>";
  }
  echo '</div>';
  $_SESSION['flash'] = [];
}
