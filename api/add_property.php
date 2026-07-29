<?php
// api/add_property.php

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['host', 'owner'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid request. Please check your request.');
    }

    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price_per_night'] ?? 0);
    $location = trim($data['address'] ?? '');
    $max_guests = intval($data['max_guests'] ?? 1);
    $bedrooms = intval($data['bedrooms'] ?? 0);
    $bathrooms = intval($data['bathrooms'] ?? 0);
    $amenities = isset($data['amenities']) ? implode(', ', $data['amenities']) : '';

    // Galing session, HINDI galing sa client-sent data
    $host_id = $_SESSION['user_id'];

    if (empty($name)) {
        throw new Exception('Unit name is required');
    }
    if ($price <= 0) {
        throw new Exception('Valid price is required');
    }
    if (empty($location)) {
        throw new Exception('Location is required');
    }
    if ($max_guests < 1) {
        throw new Exception('Max guests must be at least 1');
    }

    $status = 'available';

    $sql = "INSERT INTO units (
        host_id, name, description, price, location, 
        max_guests, bedrooms, bathrooms, amenities, status, created_at
    ) VALUES (
        :host_id, :name, :description, :price, :location, 
        :max_guests, :bedrooms, :bathrooms, :amenities, :status, NOW()
    ) RETURNING id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':host_id' => $host_id,
        ':name' => $name,
        ':description' => $description,
        ':price' => $price,
        ':location' => $location,
        ':max_guests' => $max_guests,
        ':bedrooms' => $bedrooms,
        ':bathrooms' => $bathrooms,
        ':amenities' => $amenities,
        ':status' => $status
    ]);

    $unit_id = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => 'Unit added successfully!',
        'unit_id' => $unit_id,
        'unit' => [
            'id' => $unit_id,
            'name' => $name,
            'price' => $price,
            'location' => $location
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}