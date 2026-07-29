<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $idType = trim($_POST['id_type'] ?? '');

    $validIdTypes = [
        'Philippine Passport',
        'Driver\'s License',
        'UMID',
        'PhilSys National ID',
        'Postal ID',
        'Voter\'s ID/Certificate',
        'PRC ID',
        'SSS ID',
        'GSIS ID',
    ];

    if (empty($idType) || !in_array($idType, $validIdTypes, true)) {
        throw new Exception('Please select a valid ID type.');
    }

    if (!isset($_FILES['id_photo']) || $_FILES['id_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a valid ID photo to upload.');
    }

    $file = $_FILES['id_photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes, true)) {
        throw new Exception('Only JPG and PNG files are allowed.');
    }
    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }

    $uploadDir = __DIR__ . '/../uploads/ids/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'id_' . $userId . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to save the uploaded file.');
    }

    $relativePath = 'uploads/ids/' . $filename;

    // Kunin ang registered name ng user para sa AI/OCR name matching
    $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = :id");
    $userStmt->execute([':id' => $userId]);
    $registeredName = $userStmt->fetchColumn();

    require_once __DIR__ . '/../backend/includes/ocr_helper.php';
    $ocrResult = verifyIdWithOCR($destination, $registeredName);

    // ===== Desisyon ng status base sa AI confidence level =====
    // high     (>=70% match)  -> awtomatikong verified
    // low      (40-69% match) -> hindi lubos sigurado, kailangan ng manual review (pending)
    // no_match (<40% match)   -> malinaw na hindi tugma ang pangalan sa ID -> REJECTED (hindi na "pending")
    // error    (nag-fail ang OCR call mismo, hindi napatunayang mismatch)  -> pending para sa manual review
    switch ($ocrResult['confidence_level']) {
        case 'high':
            $newStatus = 'verified';
            break;
        case 'low':
            $newStatus = 'pending';
            break;
        case 'no_match':
            $newStatus = 'rejected';
            break;
        default:
            $newStatus = 'pending';
            break;
    }

    $stmt = $pdo->prepare("
        UPDATE users 
        SET valid_id_path = :path, id_type = :id_type, id_verification_status = :status, 
            id_uploaded_at = NOW(), ai_match_confidence = :confidence, ai_extracted_text = :extracted_text
        WHERE id = :id
    ");
    $stmt->execute([
        ':path' => $relativePath,
        ':id_type' => $idType,
        ':status' => $newStatus,
        ':confidence' => $ocrResult['confidence_level'],
        ':extracted_text' => $ocrResult['extracted_text'],
        ':id' => $userId,
    ]);

    switch ($newStatus) {
        case 'verified':
            $message = 'ID uploaded and automatically verified via AI! ✓';
            break;
        case 'rejected':
            $message = 'Your ID could not be verified — the name on it doesn\'t match your registered account. Please upload the correct ID under your own name.';
            break;
        default:
            $message = 'ID uploaded successfully! The AI wasn\'t fully confident in the match, so it will remain "Pending" for manual review.';
            break;
    }

    echo json_encode(['success' => true, 'status' => $newStatus, 'message' => $message]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}