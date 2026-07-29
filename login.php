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
    <?php if (isset($_GET['embed'])): ?>
    <style>
        /* Kapag naka-embed sa loob ng iframe/modal (default.php), tanggalin ang
           extra padding/background ng body para punuin ng card ang buong box. */
        body { background: #fff; padding: 0; align-items: flex-start; }
        .auth-card {
            box-shadow: none;
            border-radius: 0;
            border: none;
            max-width: 100%;
            width: 100%;
            min-height: 100vh;
        }
    </style>
    <?php endif; ?>
    <!-- Google Sign-In -->
    <meta name="google-signin-client_id" content="272230183031-aemso8q166qf1g35plbr23ii7rtfbjjm.apps.googleusercontent.com">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="auth-card" id="app">
        <!-- PAHINGAHAN -->
        <div style="text-align: center; margin-bottom: 0.5rem;">
            <h1 style="display: inline-block; font-size: 2.2rem; font-weight: 700; color: #3c6b41;">
                Pahingahan
            </h1>
        </div>
        
        <!-- login or create account -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="color: #6b6350; font-weight: 500; font-size: 0.95rem;">
                <i class="fas fa-shield-alt" style="color: #5c8a3a; margin-right: 0.4rem;"></i> by The Still
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
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="loginPassword" name="loginPassword" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" data-target="loginPassword" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; color:#b5ad94;"></i>
                    </div>
                    <div style="text-align:right; margin-top:8px;">
                        <a href="forgot_password.php" style="font-size:.82rem; color:#5c8a3a; text-decoration:none; font-weight:600;">Forgot password?</a>
                    </div>
                </div>
                <button type="submit" class="btn-primary" name="loginBtn"><i class="fas fa-arrow-right-to-bracket"></i> Log in</button>
            </form>
        </div>

        <!-- register form -->
        <div id="registerForm" class="form-container hidden">
            <form action="" method="POST" id="registerFormElement">
                <div class="form-group" style="display:flex; gap:0.7rem;">
                    <div class="input-wrapper" style="flex:1;">
                        <i class="fas fa-user"></i>
                        <input type="text" id="regFirstName" name="regFirstName" placeholder="First name" required>
                    </div>
                    <div class="input-wrapper" style="flex:1;">
                        <i class="fas fa-user"></i>
                        <input type="text" id="regLastName" name="regLastName" placeholder="Last name" required>
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
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="regContact" name="regContact" placeholder="e.g. 09171234567" inputmode="tel" maxlength="11" pattern="(09\d{9})" title="Enter a valid PH mobile number: 09XXXXXXXXX (11 digits)" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="regPassword" name="regPassword" placeholder="Password (min 6 chars)" required minlength="6">
                        <i class="fas fa-eye toggle-password" data-target="regPassword" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; color:#b5ad94;"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength" style="display:none; margin-top:8px;">
                        <div class="strength-bar" style="height:5px; border-radius:4px; background:#eee6cf; overflow:hidden;">
                            <div class="strength-fill" id="strengthFill" style="height:100%; width:0%; border-radius:4px; transition:width .25s ease, background-color .25s ease;"></div>
                        </div>
                        <span class="strength-label" id="strengthLabel" style="display:block; margin-top:4px; font-size:.78rem; font-weight:600;"></span>
                    </div>
                </div>
                <button type="submit" class="btn-primary" name="registerBtn"><i class="fas fa-user-plus"></i> Create account</button>
                <input type="hidden" name="regRole" value="guest">
            </form>
        </div>

        <!-- Google Sign-In (shown below whichever form is active) -->
        <div style="display:flex; align-items:center; gap:0.8rem; margin: 1.4rem 0 1.2rem;">
            <div style="flex:1; height:1px; background:#eee6cf;"></div>
            <span style="color:#b5ad94; font-size:0.85rem; font-weight:500;">or</span>
            <div style="flex:1; height:1px; background:#eee6cf;"></div>
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

        // ----- Show/Hide password toggle -----
        (function() {
            document.querySelectorAll('.toggle-password').forEach(function(icon) {
                icon.addEventListener('click', function() {
                    const input = document.getElementById(this.dataset.target);
                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    this.classList.toggle('fa-eye', !isHidden);
                    this.classList.toggle('fa-eye-slash', isHidden);
                });
            });
        })();

        // ----- Password strength indicator -----
        (function() {
            const passwordInput = document.getElementById('regPassword');
            const strengthBox = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthLabel = document.getElementById('strengthLabel');

            function getStrength(password) {
                let score = 0;
                if (password.length >= 8) score++;
                if (password.length >= 12) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^A-Za-z0-9]/.test(password)) score++;
                return score; // 0-6
            }

            passwordInput.addEventListener('input', function() {
                const val = passwordInput.value;

                if (!val) {
                    strengthBox.style.display = 'none';
                    return;
                }
                strengthBox.style.display = 'block';

                const score = getStrength(val);
                let percent, color, label;

                if (score <= 2) {
                    percent = 33; color = '#e74c3c'; label = 'Weak password';
                } else if (score <= 4) {
                    percent = 66; color = '#f39c12'; label = 'Medium password';
                } else {
                    percent = 100; color = '#2ecc71'; label = 'Strong password';
                }

                strengthFill.style.width = percent + '%';
                strengthFill.style.backgroundColor = color;
                strengthLabel.textContent = label;
                strengthLabel.style.color = color;
            });
        })();

        function handleGoogleSignIn(response) {
            fetch('backend/controllers/google_auth_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'credential=' + encodeURIComponent(response.credential)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.top.location.href = 'homepage.php';
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