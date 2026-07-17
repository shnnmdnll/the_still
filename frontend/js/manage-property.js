// frontend/js/manage-property.js
// Idikit ang <script src="frontend/js/manage-property.js"></script> sa page
// na may Edit/Delete buttons (hal. dashboard o property-detail.php).
//
// HTML na kailangan mo, per property card:
//   <a href="edit-property.php?id=5" class="btn-edit">Edit</a>
//   <button onclick="deleteProperty(5)" class="btn-delete">Delete</button>

function deleteProperty(propertyId) {
    if (!confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
        return;
    }

    fetch(`api/delete_property.php?id=${propertyId}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Property deleted successfully!');
            window.location.href = 'homepage.php';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to delete property.');
    });
}

// Para naman sa edit-property.php page: i-load ang existing data, tapos i-save.
function loadPropertyForEdit(propertyId, formFieldIds) {
    // formFieldIds = { name: 'name', description: 'description', price: 'price',
    //                  address: 'address', maxGuests: 'maxGuests' }
    fetch(`api/get_property.php?id=${propertyId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Error loading property: ' + data.error);
                return;
            }
            const p = data.property;
            document.getElementById(formFieldIds.name).value = p.name;
            document.getElementById(formFieldIds.description).value = p.description;
            document.getElementById(formFieldIds.price).value = p.price;
            document.getElementById(formFieldIds.address).value = p.location;
            document.getElementById(formFieldIds.maxGuests).value = p.max_guests;
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Failed to load property details.');
        });
}

function saveEditedProperty(propertyId, propertyData) {
    fetch(`api/update_property.php?id=${propertyId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(propertyData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Property updated successfully!');
            window.location.href = 'homepage.php';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to update property.');
    });
}