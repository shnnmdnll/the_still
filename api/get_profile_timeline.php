<?php
// api/get_profile_timeline.php
// Pinagsasama ang bookings at natanggap na ratings sa isang chronological timeline

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $userId = $_SESSION['user_id'];
    $events = [];

    // ===== Bookings na ginawa ng user na ito =====
    $stmt = $pdo->prepare("
        SELECT b.id, b.check_in, b.check_out, b.status, b.created_at, u.name AS unit_name
        FROM bookings b
        JOIN units u ON u.id = b.unit_id
        WHERE b.guest_id = :user_id
        ORDER BY b.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':user_id' => $userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $events[] = [
            'type' => 'booking',
            'icon' => '🏡',
            'title' => 'Booked ' . $b['unit_name'],
            'detail' => date('M j, Y', strtotime($b['check_in'])) . ' – ' . date('M j, Y', strtotime($b['check_out'])) . ' · ' . ucfirst($b['status']),
            'date' => $b['created_at'],
        ];
    }

    // ===== Ratings na natanggap mula sa host (kung guest ang user) =====
    $stmt = $pdo->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name AS rater_name
        FROM host_guest_ratings r
        JOIN users u ON u.id = r.rater_id
        WHERE r.ratee_id = :user_id
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':user_id' => $userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $stars = str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']);
        $events[] = [
            'type' => 'rating',
            'icon' => '⭐',
            'title' => 'Received a ' . $stars . ' rating from ' . $r['rater_name'],
            'detail' => $r['comment'] ?: '(No comment left)',
            'date' => $r['created_at'],
        ];
    }

    // I-sort lahat ng events pababa (pinaka-bago muna)
    usort($events, function ($a, $b) {
        return strtotime($b['date']) <=> strtotime($a['date']);
    });

    echo json_encode(['success' => true, 'events' => $events]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}