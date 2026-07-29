<?php
// backend/controllers/property_controller.php
// Fetches unit details for display on property-detail.php
// NO auth/role restriction here — viewing units is open to everyone (guest, host, owner)

require_once __DIR__ . '/../includes/db.php';

// Get unit ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($property_id <= 0) {
    header('Location: default.php');
    exit();
}

// Fetch unit details
$stmt = $pdo->prepare("SELECT * FROM units WHERE id = :id AND status = 'available'");
$stmt->execute([':id' => $property_id]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: default.php');
    exit();
}

// Get similar units (same location)
$stmt_similar = $pdo->prepare("SELECT * FROM units WHERE location = :location AND id != :id AND status = 'available' LIMIT 4");
$stmt_similar->execute([
    ':location' => $property['location'],
    ':id' => $property_id
]);
$similar_properties = $stmt_similar->fetchAll();

// Format amenities as array
$amenities = !empty($property['amenities']) ? array_map('trim', explode(',', $property['amenities'])) : [];