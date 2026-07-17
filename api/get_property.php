<?php
// backend/api/get_property.php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../backend/includes/db.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection not established'
    ]);
    exit();
}

try {
    $property_id = intval($_GET['id'] ?? 0);

    if ($property_id <= 0) {
        throw new Exception('Valid property id is required');
    }

    $sql = "SELECT id, user_id, name, description, price, location, max_guests,
                   bedrooms, bathrooms, amenities, status, image_url
            FROM properties
            WHERE id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Property not found'
        ]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'property' => $property
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>