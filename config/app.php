<?php
// URL base del proyecto (ajústala si cambia la carpeta)
define('BASE_URL', 'http://localhost/shopping');

// Zona horaria y modo debug
define('TZ', 'Europe/Madrid');
define('DEBUG', true);

// Config tienda
define('WHATSAPP_NUMBER', '+34600000000');
define('CURRENCY', 'EUR');

// Errores según DEBUG
if (DEBUG) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', 0);
  error_reporting(0);
}
