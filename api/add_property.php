<?php
// api/add_property.php

// Disable error display to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Try to include database connection
try {
    // Use absolute path to avoid issues
    require_once __DIR__ . '/../includes/db.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Check if PDO is defined
if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection not established'
    ]);
    exit();
}

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if data is received
    if (!$data) {
        throw new Exception('No data received. Please check your request.');
    }
    
    // Extract data with validation
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price_per_night'] ?? 0);
    $location = trim($data['address'] ?? '');
    $max_guests = intval($data['max_guests'] ?? 1);
    $bedrooms = intval($data['bedrooms'] ?? 0);
    $bathrooms = intval($data['bathrooms'] ?? 0);
    $amenities = isset($data['amenities']) ? implode(', ', $data['amenities']) : '';
    $user_id = intval($data['user_id'] ?? 1);
    
    // Validate required fields
    if (empty($name)) {
        throw new Exception('Property name is required');
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
    
    // Set default status
    $status = 'available';
    
    // Prepare SQL query
    $sql = "INSERT INTO properties (
        user_id, 
        name, 
        description, 
        price, 
        location, 
        max_guests, 
        bedrooms, 
        bathrooms, 
        amenities, 
        status, 
        created_at
    ) VALUES (
        :user_id, 
        :name, 
        :description, 
        :price, 
        :location, 
        :max_guests, 
        :bedrooms, 
        :bathrooms, 
        :amenities, 
        :status, 
        NOW()
    )";
    
    // Prepare and execute statement
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':user_id' => $user_id,
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
    
    // Get the ID of the newly inserted property
    $property_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Property added successfully!',
        'property_id' => $property_id,
        'property' => [
            'id' => $property_id,
            'name' => $name,
            'price' => $price,
            'location' => $location
        ]
    ]);
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>