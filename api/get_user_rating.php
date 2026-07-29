<?php
// api/get_user_rating.php
// Nagbabalik ng average rating + bilang ng ratings ng isang user
// (puwede itong host o guest — parehong table lang ang ginagamit).

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $userId = intval($_GET['user_id'] ?? 0);

    if ($userId <= 0) {
        throw new Exception('Valid user id is required.');
    }

    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(rating)::numeric, 2) AS average_rating, COUNT(*) AS total_ratings
        FROM host_guest_ratings
        WHERE ratee_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'average_rating' => $result['average_rating'] !== null ? (float) $result['average_rating'] : null,
        'total_ratings' => (int) $result['total_ratings'],
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}