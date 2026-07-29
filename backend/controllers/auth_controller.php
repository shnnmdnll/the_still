<?php
// auth_controller.php - BACKEND: Authentication logic for login/register

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$msgType = '';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

// ========================================
// LOGIN HANDLER
// ========================================
if (isset($_POST['loginBtn'])) {
    $email = trim($_POST['loginEmail'] ?? '');
    $password = $_POST['loginPassword'] ?? '';

    if (empty($email) || empty($password)) {
        $message = 'Please fill in all fields.';
        $msgType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $isActive = $user['is_active'] === null || $user['is_active'] === 't' || $user['is_active'] === true;
                $isVerified = $user['is_verified'] === 't' || $user['is_verified'] === true;

                if (!$isActive) {
                    $message = 'Your account has been suspended. Please contact support.';
                    $msgType = 'error';
                } elseif (!$isVerified) {
                    $message = 'Please verify your email first. Check your inbox for the verification link we sent you.';
                    $msgType = 'error';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;

                    $message = 'Welcome back, ' . htmlspecialchars($user['name']) . '!';
                    $msgType = 'success';

                    $redirectPage = match ($user['role']) {
                        'owner' => 'owner/dashboard.php',
                        'host'  => 'host/today.php',
                        'staff' => 'staff/tasks.php',
                        default => 'homepage.php',
                    };

                    echo '<script>
                        setTimeout(function() {
                            window.top.location.href = "' . $redirectPage . '";
                        }, 1500);
                    </script>';
                }
            } else {
                $message = 'Invalid email or password. Please try again.';
                $msgType = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $msgType = 'error';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

// ========================================
// REGISTER HANDLER
// ========================================
if (isset($_POST['registerBtn'])) {
    $firstName = trim($_POST['regFirstName'] ?? '');
    $lastName = trim($_POST['regLastName'] ?? '');
    $name = trim($firstName . ' ' . $lastName);
    $email = trim($_POST['regEmail'] ?? '');
    $contact = trim($_POST['regContact'] ?? '');
    $password = $_POST['regPassword'] ?? '';
    $role = trim($_POST['regRole'] ?? 'guest');

    error_log("Registration attempt - Name: $name, Email: $email, Contact: $contact, Role: $role");

    if (empty($firstName) || empty($lastName) || empty($email) || empty($contact) || empty($password)) {
        $message = 'Please fill in all fields.';
        $msgType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $msgType = 'error';
    } elseif (!preg_match('/^(09\d{9}|\+63\d{10})$/', $contact)) {
        $message = 'Please enter a valid PH mobile number (09XXXXXXXXX).';
        $msgType = 'error';
    } else {
        if (!in_array($role, ['guest', 'host', 'owner'])) {
            $role = 'guest';
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $message = 'Email already registered. Please login.';
                $msgType = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpExpiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $stmt = $pdo->prepare("INSERT INTO users (name, email, contact_number, password, role, is_verified, otp_code, otp_expires_at, created_at) VALUES (:name, :email, :contact, :password, :role, FALSE, :otp, :otp_expires, NOW())");
                $result = $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':contact' => $contact,
                    ':password' => $hashedPassword,
                    ':role' => $role,
                    ':otp' => $otp,
                    ':otp_expires' => $otpExpiresAt
                ]);

                if ($result) {
                    $emailSent = sendOtpEmail($email, $name, $otp);

                    if ($emailSent) {
                        $message = 'Registration successful! Please check your email (' . htmlspecialchars($email) . ') for your verification code.';
                    } else {
                        $message = 'Registration successful, pero hindi na-send ang verification code. Please contact support.';
                    }
                    $msgType = 'success';

                    echo '<script>
                        setTimeout(function() {
                            window.top.location.href = "verify_otp.php?email=' . urlencode($email) . '";
                        }, 2000);
                    </script>';
                } else {
                    $message = 'Registration failed. Please try again.';
                    $msgType = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $msgType = 'error';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}

$msgJson = json_encode([
    'text' => $message,
    'type' => $msgType
]);
?>