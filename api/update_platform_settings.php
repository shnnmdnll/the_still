<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $siteName = trim($data['site_name'] ?? '');
    $supportEmail = trim($data['support_email'] ?? '');
    $commissionRate = floatval($data['commission_rate'] ?? 0);
    $cancellationPolicy = trim($data['cancellation_policy'] ?? '');

    if (empty($siteName)) {
        throw new Exception('Site name is required.');
    }
    if (!filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid support email is required.');
    }
    if ($commissionRate < 0 || $commissionRate > 100) {
        throw new Exception('Commission rate must be between 0 and 100.');
    }

    $stmt = $pdo->prepare("
        UPDATE platform_settings 
        SET site_name = :site_name, support_email = :support_email, 
            commission_rate = :commission_rate, cancellation_policy = :cancellation_policy,
            updated_at = NOW()
        WHERE id = (SELECT id FROM platform_settings LIMIT 1)
    ");
    $stmt->execute([
        ':site_name' => $siteName,
        ':support_email' => $supportEmail,
        ':commission_rate' => $commissionRate,
        ':cancellation_policy' => $cancellationPolicy,
    ]);

    echo json_encode(['success' => true, 'message' => 'Platform settings updated.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}