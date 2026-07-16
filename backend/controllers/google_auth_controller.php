<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

define('GOOGLE_CLIENT_ID', '272230183031-aemso8q166qf1g35plbr23ii7rtfbjjm.apps.googleusercontent.com');

require_once __DIR__ . '/../includes/db.php';

$credential = $_POST['credential'] ?? '';

if (empty($credential)) {
    echo json_encode(['success' => false, 'message' => 'Missing Google credential.']);
    exit;
}

// Google recommends verifying the JWT signature locally instead.)
$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$response = @file_get_contents($verifyUrl);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Could not reach Google to verify sign-in.']);
    exit;
}

$payload = json_decode($response, true);

// Basic validation: token must be genuinely for THIS app.
if (!$payload || !isset($payload['aud']) || $payload['aud'] !== GOOGLE_CLIENT_ID) {
    echo json_encode(['success' => false, 'message' => 'Invalid Google sign-in token.']);
    exit;
}

if (empty($payload['email']) || empty($payload['email_verified']) || $payload['email_verified'] !== 'true') {
    echo json_encode(['success' => false, 'message' => 'Google account email is not verified.']);
    exit;
}

$email = trim($payload['email']);
$name  = trim($payload['name'] ?? explode('@', $email)[0]);

try {
    // Does a user with this email already exist?
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, NOW())");
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => $randomPassword
        ]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
        $name   = $user['name']; // keep the name already on file
    }

    // Log them in
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['logged_in']  = true;

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Google sign-in error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
