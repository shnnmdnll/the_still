<?php
// auth_controller.php - BACKEND: Authentication logic for login/register
// (moved from auth_logic.php)

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize message variables
$message = '';
$msgType = '';

// ========================================
// DATABASE CONNECTION
// ========================================
require_once __DIR__ . '/../includes/db.php';

// ========================================
// CREATE USERS TABLE IF NOT EXISTS
// ========================================
try {
    // Check if users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        // Create users table
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        error_log("Users table created successfully!");
    }
} catch (PDOException $e) {
    error_log('Table creation error: ' . $e->getMessage());
    $message = 'Database setup error: ' . $e->getMessage();
    $msgType = 'error';
}

// ========================================
// LOGIN HANDLER
// ========================================
if (isset($_POST['loginBtn'])) {
    $email = trim($_POST['loginEmail'] ?? '');
    $password = $_POST['loginPassword'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        $message = 'Please fill in all fields.';
        $msgType = 'error';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login successful - set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['logged_in'] = true;

                $message = 'Welcome back, ' . htmlspecialchars($user['name']) . '!';
                $msgType = 'success';

                // Redirect to homepage after 1.5 seconds
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "homepage.php";
                    }, 1500);
                </script>';
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
    $name = trim($_POST['regName'] ?? '');
    $email = trim($_POST['regEmail'] ?? '');
    $password = $_POST['regPassword'] ?? '';

    // Debug logging
    error_log("Registration attempt - Name: $name, Email: $email");

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = 'Please fill in all fields.';
        $msgType = 'error';
        error_log("Registration failed: Empty fields");
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $msgType = 'error';
        error_log("Registration failed: Password too short");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $msgType = 'error';
        error_log("Registration failed: Invalid email");
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $message = 'Email already registered. Please login.';
                $msgType = 'error';
                error_log("Registration failed: Email already exists - $email");
            } else {
                // Hash password and insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                error_log("Password hashed successfully");

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, NOW())");
                $result = $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);

                if ($result) {
                    $userId = $pdo->lastInsertId();
                    error_log("User inserted successfully! ID: $userId");

                    // Auto-login after registration
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['logged_in'] = true;

                    $message = 'Registration successful! Welcome, ' . htmlspecialchars($name) . '!';
                    $msgType = 'success';

                    // Redirect to homepage after 1.5 seconds
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 1500);
                    </script>';
                } else {
                    $message = 'Registration failed. Please try again.';
                    $msgType = 'error';
                    error_log("Registration failed: Insert query returned false");
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $msgType = 'error';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}

// ========================================
// PREPARE MESSAGE FOR FRONTEND
// ========================================
$msgJson = json_encode([
    'text' => $message,
    'type' => $msgType
]);

// Debug: Log the message being sent
error_log("Message JSON: " . $msgJson);

// ========================================
// REMOVED: Auto-redirect to dashboard
// The redirect now only happens on successful login/registration
// ========================================
?>