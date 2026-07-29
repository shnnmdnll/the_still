<?php
// verify_email.php
require_once __DIR__ . '/backend/includes/db.php';

$token = trim($_GET['token'] ?? '');
$status = 'invalid';
$userName = '';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id, name, is_verified FROM users WHERE verification_token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $update = $pdo->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = :id");
        $update->execute([':id' => $user['id']]);
        $status = 'success';
        $userName = $user['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pahingahan · Email Verification</title>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f7f0d8;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
    }
    .card {
        background: #fff;
        border-radius: 16px;
        padding: 40px 32px;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .icon { font-size: 3rem; margin-bottom: 12px; }
    h1 { font-size: 1.4rem; color: #2f2a20; margin-bottom: 8px; }
    p { color: #6a6350; font-size: 0.95rem; line-height: 1.5; }
    a.btn {
        display: inline-block;
        margin-top: 20px;
        padding: 12px 28px;
        background: #5c8a3a;
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
    }
</style>
</head>
<body>
    <div class="card">
        <?php if ($status === 'success'): ?>
            <div class="icon">✅</div>
            <h1>Verified na ang email mo!</h1>
            <p>Salamat, <?php echo htmlspecialchars($userName); ?>! Pwede ka nang mag-login gamit ang account mo.</p>
        <?php else: ?>
            <div class="icon">⚠️</div>
            <h1>Invalid o expired na ang link</h1>
            <p>Hindi na-verify ang email. Baka na-gamit na ito dati, o mali ang link. Subukan mo mag-register ulit o makipag-ugnayan sa support.</p>
        <?php endif; ?>
        <a href="login.php" class="btn">Pumunta sa Login</a>
    </div>
</body>
</html>