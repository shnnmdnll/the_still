<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'host') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../backend/includes/db.php';

$hostId = $_SESSION['user_id'];
$hostName = $_SESSION['user_name'] ?? 'Host';

$stmt = $pdo->prepare("SELECT * FROM units WHERE host_id = :host_id ORDER BY name ASC");
$stmt->execute([':host_id' => $hostId]);
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

function unitStatColor($status) {
    switch ($status) {
        case 'available':   return ['#e6f2e0', '#3c6b41'];
        case 'booked':      return ['#e5eef7', '#2c5a7a'];
        case 'maintenance': return ['#fbe4e1', '#c0392b'];
        default:            return ['#f0ece0', '#6b6350'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Listings — Host Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_settings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/host_listings.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <svg width="30" height="30" viewBox="0 0 512 512">
                    <path fill="#5c8a3a" stroke="#3c6b41" stroke-width="14" d="M104 20v112M408 20v112M40 236c0 0 96 0 96-0 26 0 26 40 0 40-13 0-96 0-96 0-22 0-22-40 0-40zM376 236c0 0 96 0 96 0 22 0 22 40 0 40-13 0-96 0-96 0-26 0-26-40 0-40z"/>
                    <path fill="#f7f0d8" stroke="#3c6b41" stroke-width="14" d="M104 132l64 104H40l64-104zM408 132l64 104H344l64-104z"/>
                    <path fill="#5c8a3a" stroke="#3c6b41" stroke-width="14" d="M40 276c0 110 96 200 216 200s216-90 216-200H40z"/>
                </svg>
                <span class="brand-text">The Still</span>
            </div>
            <nav class="sidebar-nav">
                <a href="today.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    Today
                </a>
                <a href="calendar.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    Calendar
                </a>
                <a href="bookings.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Bookings
                </a>
                <a href="listings.php" class="nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                    Listings
                </a>
                <a href="staff.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Staff
                </a>
                <a href="payments.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Payments
                </a>
                <a href="ratings.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9 12 2"/></svg>
                    Ratings
                </a>
                <a href="notifications.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6z"/><path d="M9.5 18a2.5 2.5 0 0 0 5 0"/></svg>
                    Notifications
                </a>
            </nav>
            <div class="sidebar-bottom">
                <a href="../logout.php" class="nav-item muted">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Log Out
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>Listings</h1>
                    <p>Manage your properties</p>
                </div>
                <div class="topbar-user">
                    <div class="topbar-user-text">
                        <strong><?php echo htmlspecialchars($hostName); ?></strong>
                        <span>Host</span>
                    </div>
                    <div class="topbar-avatar"><?php echo strtoupper(substr($hostName, 0, 1)); ?></div>
                </div>
            </header>

            <div class="dashboard-container">
                <div class="listings-toolbar">
                    <span class="listings-count"><?php echo count($units); ?> unit(s)</span>
                    <button type="button" class="btn-filter" id="openAddModalBtn">+ Add New Unit</button>
                </div>

                <?php if (count($units) === 0): ?>
                    <div class="empty-state">Wala ka pang unit. I-click ang "Add New Unit" para magsimula.</div>
                <?php else: ?>
                    <div class="listings-grid">
                        <?php foreach ($units as $u):
                            [$bg, $fg] = unitStatColor($u['status']);
                        ?>
                            <div class="listing-card">
                                <div class="listing-image">
                                    <?php if (!empty($u['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($u['image_url']); ?>" alt="">
                                    <?php else: ?>
                                        <div class="listing-image-placeholder">No photo</div>
                                    <?php endif; ?>
                                    <span class="status-badge listing-status" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                        <?php echo htmlspecialchars($u['status']); ?>
                                    </span>
                                </div>
                                <div class="listing-body">
                                    <div class="listing-name"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div class="listing-location">📍 <?php echo htmlspecialchars($u['location'] ?: 'No address set'); ?></div>
                                    <div class="listing-meta">
                                        <span>₱<?php echo number_format($u['price'], 2); ?>/night</span>
                                        <span>👥 <?php echo (int)$u['max_guests']; ?> guests</span>
                                    </div>
                                    <div class="listing-actions">
                                        <button type="button" class="btn-view-details btn-edit-unit" data-id="<?php echo $u['id']; ?>">Edit</button>
                                        <button type="button" class="btn-reject btn-delete-unit" data-id="<?php echo $u['id']; ?>">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ===== EDIT MODAL ===== -->
    <div id="editModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button type="button" class="modal-close" id="editModalCloseBtn">&times;</button>
            <h2 style="margin-bottom:16px;">Edit Unit</h2>
            <div id="editLoadingMsg" style="text-align:center; color:var(--color-text-muted); padding:20px 0;">Loading unit details…</div>
            <form id="editUnitForm" style="display:none;">
                <input type="hidden" id="editUnitId">
                <div class="form-row">
                    <label>Unit Name</label>
                    <input type="text" id="editName" required>
                </div>
                <div class="form-row">
                    <label>Description</label>
                    <textarea id="editDescription" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <label>Address / Location</label>
                    <input type="text" id="editAddress" required>
                </div>
                <div class="form-row">
                    <label>Price per Night (₱)</label>
                    <input type="number" id="editPrice" min="1" step="0.01" required>
                </div>
                <div class="form-row">
                    <label>Max Guests</label>
                    <input type="number" id="editMaxGuests" min="1" required>
                </div>
                <div class="form-row">
                    <label>Bedrooms</label>
                    <input type="number" id="editBedrooms" min="0">
                </div>
                <div class="form-row">
                    <label>Bathrooms</label>
                    <input type="number" id="editBathrooms" min="0">
                </div>
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- ===== ADD MODAL ===== -->
    <div id="addModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button type="button" class="modal-close" id="addModalCloseBtn">&times;</button>
            <h2 style="margin-bottom:16px;">Add New Unit</h2>
            <form id="addUnitForm">
                <div class="form-row">
                    <label>Unit Name</label>
                    <input type="text" id="addName" required>
                </div>
                <div class="form-row">
                    <label>Description</label>
                    <textarea id="addDescription" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <label>Address / Location</label>
                    <input type="text" id="addAddress" required>
                </div>
                <div class="form-row">
                    <label>Price per Night (₱)</label>
                    <input type="number" id="addPrice" min="1" step="0.01" required>
                </div>
                <div class="form-row">
                    <label>Max Guests</label>
                    <input type="number" id="addMaxGuests" min="1" required>
                </div>
                <div class="form-row">
                    <label>Bedrooms</label>
                    <input type="number" id="addBedrooms" min="0" value="0">
                </div>
                <div class="form-row">
                    <label>Bathrooms</label>
                    <input type="number" id="addBathrooms" min="0" value="0">
                </div>
                <button type="submit" class="btn-save">Create Listing</button>
            </form>
        </div>
    </div>

    <script>
        // ===== EDIT MODAL LOGIC =====
        const editModal = document.getElementById('editModal');
        const editModalCloseBtn = document.getElementById('editModalCloseBtn');
        const editLoadingMsg = document.getElementById('editLoadingMsg');
        const editUnitForm = document.getElementById('editUnitForm');

        function openEditModal(unitId) {
            editModal.style.display = 'flex';
            editLoadingMsg.style.display = 'block';
            editUnitForm.style.display = 'none';

            fetch(`../api/get_property.php?id=${unitId}`)
                .then(res => res.json())
                .then(data => {
                    editLoadingMsg.style.display = 'none';
                    if (!data.success) {
                        alert('Error loading unit: ' + data.error);
                        closeEditModal();
                        return;
                    }
                    const u = data.property;
                    document.getElementById('editUnitId').value = unitId;
                    document.getElementById('editName').value = u.name || '';
                    document.getElementById('editDescription').value = u.description || '';
                    document.getElementById('editAddress').value = u.location || '';
                    document.getElementById('editPrice').value = u.price || '';
                    document.getElementById('editMaxGuests').value = u.max_guests || '';
                    document.getElementById('editBedrooms').value = u.bedrooms || 0;
                    document.getElementById('editBathrooms').value = u.bathrooms || 0;
                    editUnitForm.style.display = 'block';
                })
                .catch(() => {
                    editLoadingMsg.textContent = 'Failed to load unit details.';
                });
        }

        function closeEditModal() {
            editModal.style.display = 'none';
        }

        document.querySelectorAll('.btn-edit-unit').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(btn.dataset.id));
        });

        editModalCloseBtn.addEventListener('click', closeEditModal);
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModal();
        });

        editUnitForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const data = {
                unit_id: document.getElementById('editUnitId').value,
                name: document.getElementById('editName').value,
                description: document.getElementById('editDescription').value,
                address: document.getElementById('editAddress').value,
                price_per_night: parseFloat(document.getElementById('editPrice').value),
                max_guests: parseInt(document.getElementById('editMaxGuests').value),
                bedrooms: parseInt(document.getElementById('editBedrooms').value || 0),
                bathrooms: parseInt(document.getElementById('editBathrooms').value || 0),
            };

            fetch('../api/update_property.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.error || 'Something went wrong.');
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
        });

        // ===== DELETE LOGIC =====
        document.querySelectorAll('.btn-delete-unit').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('Are you sure you want to delete this unit? This cannot be undone.')) return;

                fetch('../api/delete_property.php?id=' + btn.dataset.id, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Unable to delete unit.');
                    }
                })
                .catch(() => alert('Something went wrong. Please try again.'));
            });
        });

        // ===== ADD MODAL LOGIC =====
        const addModal = document.getElementById('addModal');
        document.getElementById('openAddModalBtn').addEventListener('click', () => {
            addModal.style.display = 'flex';
        });
        document.getElementById('addModalCloseBtn').addEventListener('click', () => {
            addModal.style.display = 'none';
        });
        addModal.addEventListener('click', (e) => {
            if (e.target === addModal) addModal.style.display = 'none';
        });

        document.getElementById('addUnitForm').addEventListener('submit', (e) => {
            e.preventDefault();

            const data = {
                name: document.getElementById('addName').value,
                description: document.getElementById('addDescription').value,
                address: document.getElementById('addAddress').value,
                price_per_night: parseFloat(document.getElementById('addPrice').value),
                max_guests: parseInt(document.getElementById('addMaxGuests').value),
                bedrooms: parseInt(document.getElementById('addBedrooms').value || 0),
                bathrooms: parseInt(document.getElementById('addBathrooms').value || 0),
            };

            fetch('../api/add_property.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.error || 'Something went wrong.');
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
        });
    </script>
</body>
</html>