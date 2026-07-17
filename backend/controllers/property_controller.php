<?php

require_once __DIR__ . '/../includes/db.php';

// Get property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($property_id <= 0) {
    header('Location: default.php');
    exit();
}

// Fetch property details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id AND status = 'available'");
$stmt->execute([':id' => $property_id]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: default.php');
    exit();
}

// Get similar properties (same location)
$stmt_similar = $pdo->prepare("SELECT * FROM properties WHERE location = :location AND id != :id AND status = 'available' LIMIT 4");
$stmt_similar->execute([
    ':location' => $property['location'],
    ':id' => $property_id
]);
$similar_properties = $stmt_similar->fetchAll();

// Format amenities as array
$amenities = !empty($property['amenities']) ? array_map('trim', explode(',', $property['amenities'])) : [];