<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', sans-serif;
    background: #f7f0d8;
    color: #2f2a20;
    margin: 0;
    padding: 40px 20px;5
  }
  .profile-container {
    max-width: 480px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 36px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  .avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #5c8a3a;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
  }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    margin: 0 0 4px;
    color: #3c6b41;
  }
  .email {
    color: #8a8266;
    font-size: .9rem;
    margin: 0 0 24px;
  }
  .placeholder-note {
    background: #f7f0d8;
    border-radius: 10px;
    padding: 16px;
    font-size: .88rem;
    color: #6b6350;
    margin-bottom: 24px;
  }
  .btn-back {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 10px;
    background: #f2ede0;
    color: #3c6b41;
    text-decoration: none;
    font-weight: 600;
    font-size: .9rem;
  }
</style>
</head>
<body>

<div class="profile-container">
  <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($currentUserName, 0, 1))); ?></div>
  <h1><?php echo htmlspecialchars($currentUserName); ?></h1>
  <p class="email">Pahingahan Account</p>

  <div class="placeholder-note">
    🚧 Profile editing is coming soon. For now, this page just confirms you're logged in.
  </div>

  <a href="homepage.php#top" class="btn-back">← Back to Home</a>
</div>

</body>
</html>