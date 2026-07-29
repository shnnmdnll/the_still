<?php
// api/get_notifications.php
// Returns booking-related notifications for the logged-in user (JSON), covering TWO angles:
//   1. GUEST side  - bookings THIS user made (status updates on their own trips)
//   2. HOST side   - bookings OTHER guests made on units THIS user hosts
// Both are merged into one list so the bell works the same whether the
// logged-in account is booking a stay or hosting one.

require_once __DIR__ . '/../backend/includes/auth_guard.php';
require_once __DIR__ . '/../backend/includes/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

try {
    $notifications = [];

    // ---------- 1. GUEST SIDE: bookings this user made ----------
    $stmt = $pdo->prepare("
        SELECT b.id, b.check_in, b.check_out, b.status,
               un.name AS unit_name
        FROM bookings b
        JOIN units un ON un.id = b.unit_id
        WHERE b.user_id = ?
        ORDER BY b.id DESC
        LIMIT 15
    ");
    $stmt->execute([$user_id]);
    $guestRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $guestLabels = [
        'confirmed' => ['title' => 'Booking Confirmed', 'verb' => 'has been confirmed'],
        'pending'   => ['title' => 'Booking Pending',   'verb' => 'is pending confirmation'],
        'completed' => ['title' => 'Stay Completed',    'verb' => 'has been completed'],
        'declined'  => ['title' => 'Booking Declined',  'verb' => 'has been declined'],
    ];

    foreach ($guestRows as $b) {
        $info = $guestLabels[$b['status']] ?? ['title' => 'Booking Update', 'verb' => 'was updated'];
        $notifications[] = [
            'id'      => 'g' . $b['id'],
            'sortKey' => (int) $b['id'],
            'title'   => $info['title'],
            'message' => 'Your booking at ' . $b['unit_name'] . ' ('
                         . $b['check_in'] . ' → ' . $b['check_out'] . ') ' . $info['verb'] . '.',
            'status'  => $b['status'],
        ];
    }

    // ---------- 2. HOST SIDE: bookings guests made on units this user hosts ----------
    $stmt = $pdo->prepare("
        SELECT b.id, b.check_in, b.check_out, b.status,
               un.name AS unit_name,
               g.name AS guest_name
        FROM bookings b
        JOIN units un ON un.id = b.unit_id
        JOIN users g ON g.id = b.user_id
        WHERE un.host_id = ?
        ORDER BY b.id DESC
        LIMIT 15
    ");
    $stmt->execute([$user_id]);
    $hostRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hostLabels = [
        'pending'   => ['title' => 'New Booking Request', 'verb' => 'requested to book'],
        'confirmed' => ['title' => 'Booking Confirmed',    'verb' => 'confirmed a booking for'],
        'completed' => ['title' => 'Stay Completed',       'verb' => 'completed their stay at'],
        'declined'  => ['title' => 'Booking Declined',     'verb' => 'had a declined booking for'],
    ];

    foreach ($hostRows as $b) {
        $info = $hostLabels[$b['status']] ?? ['title' => 'Booking Update', 'verb' => 'updated a booking for'];
        $notifications[] = [
            'id'      => 'h' . $b['id'],
            'sortKey' => (int) $b['id'],
            'title'   => $info['title'],
            'message' => htmlspecialchars($b['guest_name']) . ' ' . $info['verb'] . ' ' . $b['unit_name'] . ' ('
                         . $b['check_in'] . ' → ' . $b['check_out'] . ').',
            'status'  => $b['status'],
        ];
    }

    // Newest bookings first, regardless of guest/host side.
    usort($notifications, function ($a, $b) {
        return $b['sortKey'] <=> $a['sortKey'];
    });
    $notifications = array_slice($notifications, 0, 20);

    // Drop the internal sort key before sending to the client.
    $notifications = array_map(function ($n) {
        unset($n['sortKey']);
        return $n;
    }, $notifications);

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load notifications.']);
}