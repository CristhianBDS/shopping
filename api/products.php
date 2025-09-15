<?php
// shopping/api/products.php
header("Content-Type: application/json; charset=UTF-8");
// (Opcional) CORS si luego lo llamas desde otro origen:
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../config/db.php";
$cnn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Helper para respuestas
function respond($code, $payload) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Leer JSON de entrada
function json_input() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// GET /api/products.php          -> lista
// GET /api/products.php?id=123   -> detalle
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $cnn->prepare("SELECT id, name, price, description, image, is_active, created_at, updated_at FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res) respond(404, ["status"=>"error","message"=>"Producto no encontrado"]);
        respond(200, ["status"=>"success","data"=>$res]);
    } else {
        // Puedes filtrar solo activos si quieres:
        // $sql = "SELECT id, name, price, description, image, is_active, created_at, updated_at FROM products WHERE is_active = 1 ORDER BY id DESC";
        $sql = "SELECT id, name, price, description, image, is_active, created_at, updated_at FROM products ORDER BY id DESC";
        $res = $cnn->query($sql);
        $items = [];
        while ($row = $res->fetch_assoc()) $items[] = $row;
        respond(200, ["status"=>"success","data"=>$items]);
    }
}

// POST /api/products.php  (JSON)
if ($method === 'POST') {
    $in = json_input();

    // Requeridos
    if (!isset($in['name']) || !isset($in['price'])) {
        respond(400, ["status"=>"error","message"=>"Campos requeridos: name, price"]);
    }

    $name = trim($in['name']);
    $price = floatval($in['price']);
    $description = isset($in['description']) ? trim($in['description']) : null;
    $image = isset($in['image']) ? trim($in['image']) : null; // ej: "camiseta-basica.jpg"
    $is_active = isset($in['is_active']) ? intval($in['is_active']) : 1;

    $stmt = $cnn->prepare("
        INSERT INTO products (name, price, description, image, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sdssi", $name, $price, $description, $image, $is_active);

    if ($stmt->execute()) {
        respond(201, ["status"=>"success","id"=>$cnn->insert_id]);
    } else {
        respond(500, ["status"=>"error","message"=>$stmt->error]);
    }
}

// PUT /api/products.php?id=123  (JSON)
if ($method === 'PUT') {
    if (!isset($_GET['id'])) respond(400, ["status"=>"error","message"=>"Falta id"]);
    $id = intval($_GET['id']);
    $in = json_input();

    // Tomamos valores si llegan; si no, dejamos NULL para no tocarlos
    $fields = [];
    $types  = "";
    $vals   = [];

    if (isset($in['name']))        { $fields[]="name=?";        $types.="s"; $vals[] = trim($in['name']); }
    if (isset($in['price']))       { $fields[]="price=?";       $types.="d"; $vals[] = floatval($in['price']); }
    if (isset($in['description'])) { $fields[]="description=?"; $types.="s"; $vals[] = $in['description'] === null ? null : trim($in['description']); }
    if (isset($in['image']))       { $fields[]="image=?";       $types.="s"; $vals[] = $in['image'] === null ? null : trim($in['image']); }
    if (isset($in['is_active']))   { $fields[]="is_active=?";   $types.="i"; $vals[] = intval($in['is_active']); }

    if (empty($fields)) respond(400, ["status"=>"error","message"=>"No hay campos para actualizar"]);

    $sql = "UPDATE products SET ".implode(", ", $fields).", updated_at=NOW() WHERE id = ?";
    $types .= "i";
    $vals[] = $id;

    $stmt = $cnn->prepare($sql);
    $stmt->bind_param($types, ...$vals);

    if ($stmt->execute()) {
        respond(200, ["status"=>"success","updated"=>$stmt->affected_rows]);
    } else {
        respond(500, ["status"=>"error","message"=>$stmt->error]);
    }
}

// DELETE /api/products.php?id=123
// Puedes hacer borrado lógico (is_active=0) o físico. Dejamos lógico para no perder datos.
if ($method === 'DELETE') {
    if (!isset($_GET['id'])) respond(400, ["status"=>"error","message"=>"Falta id"]);
    $id = intval($_GET['id']);

    // Borrado lógico
    $stmt = $cnn->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        respond(200, ["status"=>"success","deleted_logically"=>true]);
    } else {
        respond(500, ["status"=>"error","message"=>$stmt->error]);
    }
}

// Método no permitido
respond(405, ["status"=>"error","message"=>"Método no permitido"]);
