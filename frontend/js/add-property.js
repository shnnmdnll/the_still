// frontend/js/add-property.js
// Handles the "List a New Property" form submission on add-property.php

document.getElementById('addPropertyForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Gather checked amenities
    const amenities = Array.from(
        document.querySelectorAll('.amenity-check input[type="checkbox"]:checked')
    ).map(cb => cb.value);

    const propertyData = {
        name: document.getElementById('name').value,
        description: document.getElementById('description').value,
        price_per_night: parseFloat(document.getElementById('price').value),
        address: document.getElementById('address').value,
        image_url: document.getElementById('imageUrl').value,
        max_guests: parseInt(document.getElementById('maxGuests').value),
        bedrooms: parseInt(document.getElementById('bedrooms').value || 0),
        bathrooms: parseInt(document.getElementById('bathrooms').value || 0),
        amenities: amenities
        // user_id is not sent — the backend defaults to the hardcoded host (1)
        // for now, until proper multi-account login/roles are added.
    };

    fetch('api/add_property.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(propertyData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Property listed successfully!');
            window.location.href = 'homepage.php';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to add property.');
    });
});