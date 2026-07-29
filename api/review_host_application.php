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

    $application_id = intval($data['application_id'] ?? 0);
    $action = trim($data['action'] ?? '');

    if ($application_id <= 0) {
        throw new Exception('Valid application_id is required.');
    }
    if (!in_array($action, ['approve', 'reject'], true)) {
        throw new Exception('Invalid action.');
    }

    // Kunin ang application
    $stmt = $pdo->prepare("SELECT * FROM host_applications WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Application not found.');
    }
    if ($application['status'] !== 'pending') {
        throw new Exception('This application has already been reviewed.');
    }

    $pdo->beginTransaction();

    if ($action === 'approve') {
        // 1. I-update ang application status
        $stmt = $pdo->prepare("UPDATE host_applications SET status = 'approved', reviewed_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $application_id]);

        // 2. I-update ang role ng user papuntang 'host'
        if (!empty($application['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'host' WHERE id = :user_id");
            $stmt->execute([':user_id' => $application['user_id']]);
        } else {
            throw new Exception('No linked user account found for this application.');
        }

        // 3. Auto-create ng unit listing (draft/incomplete, dapat kumpletuhin ng Host sa dashboard niya)
        $stmt = $pdo->prepare("
            INSERT INTO units (host_id, name, description, location, price, max_guests, bedrooms, bathrooms, status, created_at)
            VALUES (:host_id, :name, :description, :location, 0, 1, 0, 0, 'maintenance', NOW())
        ");
        $stmt->execute([
            ':host_id' => $application['user_id'],
            ':name' => $application['business_name'] ?: 'New Unit',
            ':description' => $application['unit_description'],
            ':location' => $application['unit_address'],
        ]);

        $message = 'Application approved. Host account activated and a draft unit listing was created.';
    } else {
        // Reject
        $stmt = $pdo->prepare("UPDATE host_applications SET status = 'declined', reviewed_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $application_id]);
        $message = 'Application rejected.';
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}