<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $totalUnits = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();
    $totalHosts = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'host'")->fetchColumn();
    $totalGuests = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();
    $totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

    $pendingApplications = $pdo->query("SELECT COUNT(*) FROM host_applications WHERE status = 'pending'")->fetchColumn();
    $pendingMaintenance = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status = 'pending'")->fetchColumn();

    // Units under maintenance (gamit ang property_status enum: available/booked/maintenance)
    $unitsInMaintenance = $pdo->query("SELECT COUNT(*) FROM units WHERE status = 'maintenance'")->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenue: 'completed' status gamit ang booking_status enum
    $revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'completed'")->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_units' => (int)$totalUnits,
            'total_hosts' => (int)$totalHosts,
            'total_guests' => (int)$totalGuests,
            'total_bookings' => (int)$totalBookings,
            'pending_applications' => (int)$pendingApplications,
            'pending_maintenance' => (int)$pendingMaintenance,
            'units_in_maintenance' => (int)$unitsInMaintenance,
            'total_revenue' => (float)$revenue
        ],
        'notifications' => $recentNotifications
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}