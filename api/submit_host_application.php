<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only guest accounts can apply to become a host.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $userId = $_SESSION['user_id'];

    $businessName = trim($_POST['business_name'] ?? '');
    $unitAddress = trim($_POST['unit_address'] ?? '');
    $unitDescription = trim($_POST['unit_description'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');

    if (empty($businessName) || empty($unitAddress) || empty($contactNumber)) {
        throw new Exception('Please fill in all required fields.');
    }

    // I-check kung may pending na application na siya
    $checkStmt = $pdo->prepare("SELECT id FROM host_applications WHERE user_id = :user_id AND status = 'pending'");
    $checkStmt->execute([':user_id' => $userId]);
    if ($checkStmt->fetch()) {
        throw new Exception('You already have a pending application.');
    }

    // Kunin ang pangalan at email ng guest
    $userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // I-handle ang ID upload
    if (!isset($_FILES['valid_id']) || $_FILES['valid_id']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please upload a valid ID.');
    }

    $file = $_FILES['valid_id'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes, true)) {
        throw new Exception('Only JPG and PNG files are allowed.');
    }
    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }

    $uploadDir = __DIR__ . '/../uploads/host_applications/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'hostapp_' . $userId . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to save the uploaded ID.');
    }

    $relativePath = 'uploads/host_applications/' . $filename;

    require_once __DIR__ . '/../backend/includes/ocr_helper.php';
    $ocrResult = verifyIdWithOCR($destination, $user['name']);

    // ===== Desisyon ng status base sa AI confidence level =====
    // high / low (may match, kahit partial) -> 'pending' pa rin, para sa Owner review
    //   (may AI badge na makikita ang Owner bilang hint)
    // no_match (<40% match)                 -> 'declined' AGAD — malinaw na hindi
    //   sariling ID ng applicant, hindi na kailangan pang abutin ng Owner review
    $applicationStatus = ($ocrResult['confidence_level'] === 'no_match') ? 'declined' : 'pending';

    $stmt = $pdo->prepare("
        INSERT INTO host_applications (
            user_id, applicant_name, applicant_email, contact_number,
            business_name, unit_address, unit_description, valid_id_path, status, submitted_at,
            ai_match_confidence, ai_extracted_text
        ) VALUES (
            :user_id, :applicant_name, :applicant_email, :contact_number,
            :business_name, :unit_address, :unit_description, :valid_id_path, :status, NOW(),
            :confidence, :extracted_text
        )
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':applicant_name' => $user['name'],
        ':applicant_email' => $user['email'],
        ':contact_number' => $contactNumber,
        ':business_name' => $businessName,
        ':unit_address' => $unitAddress,
        ':unit_description' => $unitDescription,
        ':valid_id_path' => $relativePath,
        ':status' => $applicationStatus,
        ':confidence' => $ocrResult['confidence_level'],
        ':extracted_text' => $ocrResult['extracted_text'],
    ]);

    if ($applicationStatus === 'declined') {
        $message = 'Your application could not be processed — the uploaded ID does not appear to match your registered name. Please apply again with your own valid ID.';
    } else {
        $message = 'Application submitted! An owner will review it shortly.';
    }

    echo json_encode(['success' => true, 'message' => $message, 'status' => $applicationStatus]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}