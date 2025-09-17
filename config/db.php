<?php
// config/db.php
function getConnection(): PDO {
    $host = '127.0.0.1';
    $db   = 'shopping';      // <- tu base
    $user = 'root';          // <- tu usuario
    $pass = '';              // <- tu clave (en XAMPP suele ser vacía)
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                     // prepares nativos
    ];
    return new PDO($dsn, $user, $pass, $options);
}
