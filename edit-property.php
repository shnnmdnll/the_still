<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';

$property_id = intval($_GET['id'] ?? 0);
if ($property_id <= 0) {
    header('Location: homepage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Property — Pahingahan</title>
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
  .edit-container {
    max-width: 560px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.6rem;
    margin: 0 0 24px;
    color: #3c6b41;
  }
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
  .loading-msg { text-align: center; color: #8a8266; padding: 20px 0; }
</style>
</head>
<body>

<div class="edit-container">
  <h1>✏️ Edit Property</h1>

  <div class="loading-msg" id="loadingMsg">Loading property details…</div>

  <form id="editPropertyForm" style="display:none;">
    <div class="form-group">
      <label for="name">Property Name</label>
      <input type="text" id="name" required>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" required></textarea>
    </div>
    <div class="form-group">
      <label for="price">Price per Night (₱)</label>
      <input type="number" id="price" min="1" step="0.01" required>
    </div>
    <div class="form-group">
      <label for="address">Address / Location</label>
      <input type="text" id="address" required>
    </div>
    <div class="form-group">
      <label for="maxGuests">Max Guests</label>
      <input type="number" id="maxGuests" min="1" required>
    </div>

    <div class="actions">
      <a href="homepage.php" class="btn-cancel">Cancel</a>
      <button type="submit">💾 Save Changes</button>
    </div>
  </form>
</div>

<script src="frontend/js/manage-property.js"></script>
<script>
  const propertyId = <?php echo json_encode($property_id); ?>;

  const fieldIds = {
    name: 'name',
    description: 'description',
    price: 'price',
    address: 'address',
    maxGuests: 'maxGuests'
  };

  // Load existing property data into the form
  fetch(`api/get_property.php?id=${propertyId}`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('loadingMsg').style.display = 'none';
      const form = document.getElementById('editPropertyForm');

      if (!data.success) {
        alert('Error loading property: ' + data.error);
        window.location.href = 'homepage.php';
        return;
      }

      const p = data.property;
      document.getElementById('name').value = p.name;
      document.getElementById('description').value = p.description;
      document.getElementById('price').value = p.price;
      document.getElementById('address').value = p.location;
      document.getElementById('maxGuests').value = p.max_guests;

      form.style.display = 'block';
    })
    .catch(err => {
      console.error(err);
      document.getElementById('loadingMsg').textContent = 'Failed to load property details.';
    });

  // Save changes
  document.getElementById('editPropertyForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const propertyData = {
      name: document.getElementById('name').value,
      description: document.getElementById('description').value,
      price_per_night: parseFloat(document.getElementById('price').value),
      address: document.getElementById('address').value,
      max_guests: parseInt(document.getElementById('maxGuests').value)
    };

    saveEditedProperty(propertyId, propertyData);
  });
</script>

</body>
</html>