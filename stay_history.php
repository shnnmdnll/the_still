<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stay History — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', sans-serif;
    background: #f7f0d8;
    color: #2f2a20;
    margin: 0;
    padding: 40px 20px;
  }
  .container {
    max-width: 560px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 36px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    margin: 0 0 18px;
    color: #3c6b41;
  }
  .placeholder-note {
    background: #f7f0d8;
    border-radius: 10px;
    padding: 18px;
    font-size: .9rem;
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

<div class="container">
  <h1>🕒 Stay History</h1>
  <div class="placeholder-note">
    🚧 A record of your completed stays will show up here soon. This page is a placeholder for now.
  </div>
  <a href="homepage.php#top" class="btn-back">← Back to Home</a>
</div>

</body>
</html>