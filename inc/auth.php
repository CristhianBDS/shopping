<?php
// inc/auth.php

function isLogged(): bool {
  return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function requireLogin(): void {
  if (!isLogged()) {
    // opcional: flash de error
    $_SESSION['flash_error'] = 'Debes iniciar sesión';
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
  }
}
