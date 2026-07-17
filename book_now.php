<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Now — Pahingahan</title>
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
  .booking-container {
    max-width: 480px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.5rem;
    margin: 0 0 6px;
    color: #3c6b41;
  }
  .subtitle {
    color: #8a8266;
    font-size: .9rem;
    margin: 0 0 24px;
  }
  form {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  label {
    display: block;
    font-weight: 600;
    font-size: .9rem;
    margin-bottom: 6px;
  }
  input[type="date"],
  input[type="number"] {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e2ddc9;
    border-radius: 10px;
    font-family: inherit;
    font-size: .95rem;
    background: #fff;
  }
  input:focus {
    outline: none;
    border-color: #5c8a3a;
  }
  .field-row {
    display: flex;
    gap: 14px;
  }
  .field-row > div { flex: 1; }
  button[type="submit"] {
    margin-top: 6px;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: #5c8a3a;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: background .15s ease;
  }
  button[type="submit"]:hover {
    background: #4a7130;
  }
</style>
</head>
<body>

<div class="booking-container">
  <h1>🗓 Check Availability</h1>
  <p class="subtitle">Pick your dates and let us know how many guests are coming.</p>

  <form action="confirm_booking.php" method="POST">
    <input type="hidden" name="property_id" value="<?= htmlspecialchars($_GET['property_id'] ?? '') ?>">

    <div class="field-row">
      <div>
        <label>Check-in</label>
        <input type="date" name="check_in" required min="<?= date('Y-m-d') ?>">
      </div>
      <div>
        <label>Check-out</label>
        <input type="date" name="check_out" required>
      </div>
    </div>

    <div>
      <label>Guests</label>
      <input type="number" name="guest_count" value="1" min="1" required>
    </div>

    <button type="submit">Check Availability</button>
  </form>
</div>

</body>
</html>