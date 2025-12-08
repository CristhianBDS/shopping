<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getConnection();

    // Trae todas las columnas disponibles (evita fallar si faltan algunas)
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normaliza cada producto a lo que necesita el front
    $out = [];
    foreach ($rows as $r) {
        $p = [
            'id'    => isset($r['id']) ? (int)$r['id'] : null,
            'name'  => $r['name'] ?? ($r['title'] ?? ''),              // por si usaste 'title'
            'price' => isset($r['price']) ? (float)$r['price'] : 0.0,
            'image' => trim((string)($r['image'] ?? '')),              // nombre de archivo
            // si no existe 'description', devuélvelo vacío
            'description' => $r['description'] ?? '',
        ];

        // Mapea is_active:
        // - si existe 'is_active', úsalo
        // - si no, pero existe 'active', úsalo
        // - si no existe ninguno, assume 1 (activo)
        if (array_key_exists('is_active', $r)) {
            $p['is_active'] = (int)$r['is_active'];
        } elseif (array_key_exists('active', $r)) {
            $p['is_active'] = (int)$r['active'];
        } else {
            $p['is_active'] = 1;
        }

        // Imagen por defecto si viene vacía
        if ($p['image'] === '') {
            $p['image'] = 'placeholder.jpg';
        }

        $out[] = $p;
    }

    echo json_encode(['ok' => true, 'data' => $out], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
