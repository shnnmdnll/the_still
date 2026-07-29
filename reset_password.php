<?php
// reset_password.php
require_once __DIR__ . '/backend/includes/db.php';

$token = trim($_GET['token'] ?? '');
$tokenValid = false;

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && strtotime($user['reset_token_expires_at']) > time()) {
        $tokenValid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: #f7f0d8;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
    }
    .card {
        background: #fff;
        border-radius: 18px;
        padding: 40px 32px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
    }
    h1 { font-size: 1.4rem; color: #0b1a2e; margin: 0 0 8px; }
    p.sub { color: #4a5c72; font-size: 0.9rem; margin: 0 0 24px; }
    .input-wrapper { position: relative; margin-bottom: 18px; text-align: left; }
    .input-wrapper i.fa-lock {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #a3b5cc;
    }
    .input-wrapper i.fa-eye {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #a3b5cc; cursor: pointer;
    }
    input[type="password"], input[type="text"] {
        width: 100%; padding: 12px 40px 12px 40px; border: 1.5px solid #e2e9f2;
        border-radius: 10px; font-size: 0.95rem; font-family: inherit;
    }
    input:focus { outline: none; border-color: #3b7cff; }
    button[type="submit"] {
        width: 100%; padding: 13px; border: none; border-radius: 10px;
        background: #3b7cff; color: #fff; font-weight: 700; font-size: 1rem; cursor: pointer;
    }
    button[type="submit"]:hover { background: #2f66d6; }
    button[type="submit"]:disabled { background: #cfc9b0; cursor: not-allowed; }
    .message-box {
        padding: 12px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; display: none;
    }
    .message-box.success { display: block; background: #e2f2e2; color: #2e6b2f; }
    .message-box.error { display: block; background: #fbe4e1; color: #c0392b; }
    .icon { font-size: 3rem; margin-bottom: 12px; }
    a.btn-link {
        display: inline-block; margin-top: 20px; padding: 12px 28px; background: #5c8a3a;
        color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;
    }
</style>
</head>
<body>
    <div class="card">
        <?php if (!$tokenValid): ?>
            <div class="icon">⚠️</div>
            <h1>Invalid or expired link</h1>
            <p class="sub">This password reset link is no longer valid. Please request a new one.</p>
            <a href="forgot_password.php" class="btn-link">Request New Link</a>
        <?php else: ?>
            <h1>Set a new password</h1>
            <p class="sub">Choose a new password for your account.</p>

            <div class="message-box" id="messageBox"></div>

            <form id="resetForm">
                <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="newPassword" placeholder="New password (min 6 chars)" required minlength="6">
                    <i class="fas fa-eye toggle-password" data-target="newPassword"></i>
                </div>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmPassword" placeholder="Confirm new password" required minlength="6">
                    <i class="fas fa-eye toggle-password" data-target="confirmPassword"></i>
                </div>
                <button type="submit" id="submitBtn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function(icon) {
            icon.addEventListener('click', function() {
                const input = document.getElementById(this.dataset.target);
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                this.classList.toggle('fa-eye', !isHidden);
                this.classList.toggle('fa-eye-slash', isHidden);
            });
        });

        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('submitBtn');
                const msgBox = document.getElementById('messageBox');
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const token = document.getElementById('token').value;

                msgBox.className = 'message-box';

                if (newPassword !== confirmPassword) {
                    msgBox.textContent = 'Passwords do not match.';
                    msgBox.classList.add('error');
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Resetting...';

                fetch('api/reset_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, new_password: newPassword })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        msgBox.textContent = data.message + ' Redirecting to login...';
                        msgBox.classList.add('success');
                        setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                    } else {
                        msgBox.textContent = data.error || 'Something went wrong.';
                        msgBox.classList.add('error');
                        btn.disabled = false;
                        btn.textContent = 'Reset Password';
                    }
                })
                .catch(() => {
                    msgBox.textContent = 'Something went wrong. Please try again.';
                    msgBox.classList.add('error');
                    btn.disabled = false;
                    btn.textContent = 'Reset Password';
                });
            });
        }
    </script>
</body>
</html>