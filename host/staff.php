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

// ===== STAFF LIST (naka-assign sa units ng host na ito) =====
$stmt = $pdo->prepare("
    SELECT s.id, s.name, s.email, s.created_at, un.name AS unit_name
    FROM users s
    JOIN units un ON un.id = s.assigned_unit_id
    WHERE un.host_id = :host_id AND s.role = 'staff'
    ORDER BY s.created_at DESC
");
$stmt->execute([':host_id' => $hostId]);
$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== UNIT LIST (for assign dropdown) =====
$unitsStmt = $pdo->prepare("SELECT id, name FROM units WHERE host_id = :host_id ORDER BY name");
$unitsStmt->execute([':host_id' => $hostId]);
$units = $unitsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff — Host Dashboard</title>
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
                <a href="listings.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                    Listings
                </a>
                <a href="staff.php" class="nav-item active">
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
                    <h1>Staff</h1>
                    <p>Manage cleaners assigned to your units</p>
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
                    <span class="listings-count"><?php echo count($staffList); ?> cleaner(s)</span>
                    <button type="button" class="btn-filter" id="openAddStaffBtn">+ Add Cleaner</button>
                </div>

                <?php if (count($units) === 0): ?>
                    <div class="empty-state">Kailangan mo munang gumawa ng unit sa <strong>Listings</strong> bago ka makapag-assign ng cleaner.</div>
                <?php elseif (count($staffList) === 0): ?>
                    <div class="empty-state">Wala ka pang cleaner. I-click ang "Add Cleaner" para magsimula.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Assigned Unit</th>
                                    <th>Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffList as $s): ?>
                                    <tr>
                                        <td><div class="cell-primary"><?php echo htmlspecialchars($s['name']); ?></div></td>
                                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                                        <td><?php echo htmlspecialchars($s['unit_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($s['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ===== ADD CLEANER MODAL ===== -->
    <div id="addStaffModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button type="button" class="modal-close" id="staffModalCloseBtn">&times;</button>
            <h2 style="margin-bottom:16px;">Add Cleaner</h2>
            <form id="addStaffForm">
                <div class="form-row">
                    <label>Full Name</label>
                    <input type="text" id="staffName" required>
                </div>
                <div class="form-row">
                    <label>Email</label>
                    <input type="email" id="staffEmail" required>
                </div>
                <div class="form-row">
                    <label>Password</label>
                    <input type="password" id="staffPassword" minlength="6" required placeholder="Minimum 6 characters">
                </div>
                <div class="form-row">
                    <label>Assign to Unit</label>
                    <select id="staffUnit" required>
                        <option value="">Select a unit</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-save">Create Account</button>
            </form>
        </div>
    </div>

    <script>
        const addStaffModal = document.getElementById('addStaffModal');
        document.getElementById('openAddStaffBtn').addEventListener('click', () => {
            addStaffModal.style.display = 'flex';
        });
        document.getElementById('staffModalCloseBtn').addEventListener('click', () => {
            addStaffModal.style.display = 'none';
        });
        addStaffModal.addEventListener('click', (e) => {
            if (e.target === addStaffModal) addStaffModal.style.display = 'none';
        });

        document.getElementById('addStaffForm').addEventListener('submit', (e) => {
            e.preventDefault();

            const data = {
                name: document.getElementById('staffName').value,
                email: document.getElementById('staffEmail').value,
                password: document.getElementById('staffPassword').value,
                unit_id: document.getElementById('staffUnit').value,
            };

            fetch('../api/add_staff.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    alert('Cleaner account created!');
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