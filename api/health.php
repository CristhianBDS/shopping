<?php
// shopping/api/health.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

$response = [
    "ok" => true,
    "time" => date("Y-m-d H:i:s"),
    "db" => "desconectado"
];

try {
    $conn = getConnection();
    if ($conn && !$conn->connect_error) {
        $response["db"] = "conectado";
    }
} catch (Exception $e) {
    $response["db"] = "error: " . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
