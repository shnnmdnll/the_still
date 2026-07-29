<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'host') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $hostId = $_SESSION['user_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $unitId = intval($data['unit_id'] ?? 0);

    if (empty($name)) {
        throw new Exception('Name is required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid email is required.');
    }
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters.');
    }
    if ($unitId <= 0) {
        throw new Exception('Please select a unit to assign this cleaner to.');
    }

    // I-verify na ang unit ay pag-aari mismo ng naka-login na host
    $checkStmt = $pdo->prepare("SELECT id FROM units WHERE id = :id AND host_id = :host_id LIMIT 1");
    $checkStmt->execute([':id' => $unitId, ':host_id' => $hostId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Invalid unit selected.');
    }

    // I-check kung ginagamit na ba ang email
    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $emailCheck->execute([':email' => $email]);
    if ($emailCheck->fetch()) {
        throw new Exception('This email is already registered.');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, assigned_unit_id, created_at)
        VALUES (:name, :email, :password, 'staff', :unit_id, NOW())
    ");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':unit_id' => $unitId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Cleaner account created successfully.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}