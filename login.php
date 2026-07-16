<?php
// Include the backend logic
require_once __DIR__ . '/backend/controllers/auth_controller.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pahingahan · Login & Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/auth.css">
    <!-- Google Sign-In -->
    <meta name="google-signin-client_id" content="272230183031-aemso8q166qf1g35plbr23ii7rtfbjjm.apps.googleusercontent.com">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="auth-card" id="app">
        <!-- PAHINGAHAN -->
        <div style="text-align: center; margin-bottom: 0.5rem;">
            <h1 style="display: inline-block; font-size: 2.2rem; font-weight: 700; color: #0b1a2e;">
                Pahingahan
            </h1>
        </div>
        
        <!-- login or create account -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="color: #4a5c72; font-weight: 500; font-size: 0.95rem;">
                <i class="fas fa-shield-alt" style="color: #3b7cff; margin-right: 0.4rem;"></i> by The Still
            </span>
        </div>

        <!-- Welcome Back! -->
        <div style="text-align: center; margin-bottom: 0.5rem;">
            <h2 style="display: inline-block; font-size: 1.8rem; font-weight: 600; color: #0b1a2e;">
                Welcome Back!
            </h2>
        </div>

        <!-- login or create account -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="color: #4a5c72; font-weight: 500; font-size: 0.95rem;">
                <i class="fas fa-shield-alt" style="color: #3b7cff; margin-right: 0.4rem;"></i> login or create account
            </span>
        </div>

        <!-- toggle tabs -->
        <div class="toggle-tabs" role="tablist">
            <button class="active" id="tabLogin" data-tab="login"><i class="fas fa-sign-in-alt"></i> Login</button>
            <button id="tabRegister" data-tab="register"><i class="fas fa-user-plus"></i> Register</button>
        </div>

        <!-- login form -->
        <div id="loginForm" class="form-container">
            <form action="" method="POST" id="loginFormElement">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="loginEmail" name="loginEmail" placeholder="Email" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="loginPassword" name="loginPassword" placeholder="Password" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary" name="loginBtn"><i class="fas fa-arrow-right-to-bracket"></i> Log in</button>
            </form>
        </div>

        <!-- register form -->
        <div id="registerForm" class="form-container hidden">
            <form action="" method="POST" id="registerFormElement">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="regName" name="regName" placeholder="Full name" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="regEmail" name="regEmail" placeholder="Email" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="regPassword" name="regPassword" placeholder="Password (min 6 chars)" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn-primary" name="registerBtn"><i class="fas fa-user-plus"></i> Create account</button>
            </form>
        </div>

        <!-- Google Sign-In (shown below whichever form is active) -->
        <div style="display:flex; align-items:center; gap:0.8rem; margin: 1.4rem 0 1.2rem;">
            <div style="flex:1; height:1px; background:#e2e9f2;"></div>
            <span style="color:#a3b5cc; font-size:0.85rem; font-weight:500;">or</span>
            <div style="flex:1; height:1px; background:#e2e9f2;"></div>
        </div>
        <div id="g_id_onload"
             data-client_id="272230183031-aemso8q166qf1g35plbr23ii7rtfbjjm.apps.googleusercontent.com"
             data-callback="handleGoogleSignIn"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="pill"
             data-theme="outline"
             data-text="continue_with"
             data-size="large"
             data-logo_alignment="left"
             style="display:flex; justify-content:center;">
        </div>

        <!-- dynamic message -->
        <div id="messageBox" class="message-box hidden">
            <i class="fas fa-circle-info"></i>
            <span id="messageText"></span>
        </div>

    <script>
        const msgData = <?php echo $msgJson; ?>;


        function handleGoogleSignIn(response) {
            fetch('backend/controllers/google_auth_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'credential=' + encodeURIComponent(response.credential)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'homepage.php';
                } else {
                    alert(data.message || 'Google sign-in failed. Please try again.');
                }
            })
            .catch(() => {
                alert('Something went wrong connecting to Google sign-in. Please try again.');
            });
        }
    </script>
    <script src="frontend/js/login.js"></script>
</body>
</html>