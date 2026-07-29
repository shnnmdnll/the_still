<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';

// Kung guest pa lang (hindi host/owner), i-redirect papunta sa Host Application form
// sa halip na ipakita ang "Add Property" form na hindi naman niya pwedeng gamitin.
if (!in_array($_SESSION['role'] ?? '', ['host', 'owner'], true)) {
    header('Location: apply_host.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Property — Pahingahan</title>
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
  .form-container {
    max-width: 600px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.6rem;
    margin: 0 0 6px;
    color: #3c6b41;
  }
  .subtitle {
    color: #8a8266;
    font-size: .9rem;
    margin: 0 0 24px;
  }
  .form-row {
    display: flex;
    gap: 14px;
  }
  .form-row .form-group { flex: 1; }
  .form-group { margin-bottom: 18px; }
  label { display: block; font-weight: 600; margin-bottom: 6px; font-size: .9rem; }
  input, textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e2ddc9;
    border-radius: 10px;
    font-family: inherit;
    font-size: .95rem;
  }
  textarea { min-height: 100px; resize: vertical; }
  .amenities-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-top: 6px;
  }
  .amenity-check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .9rem;
    font-weight: 500;
  }
  .amenity-check input { width: auto; }
  .actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
  }
  button, .btn-cancel {
    flex: 1;
    padding: 13px;
    border-radius: 10px;
    font-weight: 600;
    font-size: .95rem;
    text-align: center;
    cursor: pointer;
    border: none;
    text-decoration: none;
  }
  button[type="submit"] { background: #5c8a3a; color: #fff; }
  button[type="submit"]:hover { background: #4a7130; }
  .btn-cancel { background: #f2ede0; color: #3c6b41; display: inline-block; line-height: 1.4; }

  @media (max-width: 640px) {
    body { padding: 20px 12px; }
    .form-container { padding: 22px 20px; }
    h1 { font-size: 1.35rem; }
    .form-row { flex-direction: column; gap: 0; }
    .amenities-grid { grid-template-columns: 1fr; }
    .actions { flex-direction: column; }
  }
</style>
</head>
<body>

<div class="form-container">
  <h1>🏡 List a New Property</h1>
  <p class="subtitle">Fill in the details below to add your space to Pahingahan.</p>

  <form id="addPropertyForm">
    <div class="form-group">
      <label for="name">Property Name</label>
      <input type="text" id="name" placeholder="e.g. Coastal Breeze Villa" required>
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" placeholder="Tell guests what makes this place special..." required></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="price">Price per Night (₱)</label>
        <input type="number" id="price" min="1" step="0.01" required>
      </div>
      <div class="form-group">
        <label for="maxGuests">Max Guests</label>
        <input type="number" id="maxGuests" min="1" value="2" required>
      </div>
    </div>

    <div class="form-group">
      <label for="address">Address / Location</label>
      <input type="text" id="address" placeholder="e.g. La Union" required>
    </div>

    <div class="form-group">
      <label for="imageUrl">Image URL</label>
      <input type="url" id="imageUrl" placeholder="https://images.unsplash.com/photo-..." >
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="bedrooms">Bedrooms</label>
        <input type="number" id="bedrooms" min="0" value="1">
      </div>
      <div class="form-group">
        <label for="bathrooms">Bathrooms</label>
        <input type="number" id="bathrooms" min="0" value="1">
      </div>
    </div>

    <div class="form-group">
      <label>Amenities</label>
      <div class="amenities-grid">
        <label class="amenity-check"><input type="checkbox" value="WiFi"> WiFi</label>
        <label class="amenity-check"><input type="checkbox" value="Pool"> Pool</label>
        <label class="amenity-check"><input type="checkbox" value="Parking"> Parking</label>
        <label class="amenity-check"><input type="checkbox" value="Air Conditioning"> Air Conditioning</label>
        <label class="amenity-check"><input type="checkbox" value="Kitchen"> Kitchen</label>
        <label class="amenity-check"><input type="checkbox" value="Beach Access"> Beach Access</label>
      </div>
    </div>

    <div class="actions">
      <a href="hosting.php" class="btn-cancel">Cancel</a>
      <button type="submit">✅ List Property</button>
    </div>
  </form>
</div>

<script src="frontend/js/add-property.js"></script>
</body>
</html>