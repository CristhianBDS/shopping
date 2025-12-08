<?php
// config/db.php
// Asegúrate de que antes se haya cargado config/app.php

function getConnection(): PDO {
    // Tomamos los datos de las constantes definidas en app.php
    $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $db   = defined('DB_NAME') ? DB_NAME : 'shopping';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                     // prepares nativos
    ];

    return new PDO($dsn, $user, $pass, $options);
}
