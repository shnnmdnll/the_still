<?php
// api/get_property.php

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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'error' => 'Database connection not established']);
    exit();
}

try {
    $unit_id = intval($_GET['id'] ?? 0);

    if ($unit_id <= 0) {
        throw new Exception('Valid unit id is required');
    }

    $sql = "SELECT u.id, u.host_id, u.name, u.description, u.price, u.location, u.max_guests,
                   u.bedrooms, u.bathrooms, u.amenities, u.status, u.image_url, u.images,
                   h.name AS host_name
            FROM units u
            LEFT JOIN users h ON h.id = u.host_id
            WHERE u.id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $unit_id]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$unit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Unit not found']);
        exit();
    }

    // Ang `images` column ay JSONB sa Postgres, kaya string pa rin ang balik ng PDO —
    // i-decode natin dito para array na agad ang matatanggap ng frontend.
    $decodedImages = [];
    if (!empty($unit['images'])) {
        $decoded = json_decode($unit['images'], true);
        if (is_array($decoded)) {
            $decodedImages = $decoded;
        }
    }
    $unit['images'] = $decodedImages;

    // Average rating + bilang ng reviews para sa unit na ito
    $ratingStmt = $pdo->prepare("
        SELECT ROUND(AVG(rating)::numeric, 2) AS average_rating, COUNT(*) AS review_count
        FROM reviews
        WHERE unit_id = :id
    ");
    $ratingStmt->execute([':id' => $unit_id]);
    $ratingResult = $ratingStmt->fetch(PDO::FETCH_ASSOC);

    $unit['average_rating'] = $ratingResult['average_rating'] !== null ? (float) $ratingResult['average_rating'] : null;
    $unit['review_count'] = (int) $ratingResult['review_count'];

    echo json_encode(['success' => true, 'property' => $unit]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}