<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../backend/includes/db.php';

$ownerName = $_SESSION['user_name'] ?? 'Owner';

$roleFilter = trim($_GET['role'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT id, name, email, contact_number, role, is_active, created_at FROM users WHERE 1=1";
$params = [];

if (!empty($roleFilter)) {
    $sql .= " AND role = :role";
    $params[':role'] = $roleFilter;
}

if (!empty($search)) {
    $sql .= " AND (name ILIKE :search OR email ILIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalOwners = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn();
$totalHosts  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'host'")->fetchColumn();
$totalStaff  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
$totalGuests = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();

function roleColor($role) {
    switch ($role) {
        case 'owner': return ['#f0e5d8', '#8a5a1f'];
        case 'host':  return ['#e5eef7', '#2c5a7a'];
        case 'staff': return ['#eae5f2', '#5a3c8a'];
        default:      return ['#e6f2e0', '#3c6b41'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Access — Owner Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
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
                <a href="dashboard.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    Dashboard
                </a>
                <a href="bookings.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    Bookings
                </a>
                <a href="units.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                    Units
                </a>
                <a href="guests.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Guests
                </a>
                <a href="host_applications.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    Host Applications
                </a>
                <a href="maintenance.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/></svg>
                    Maintenance
                </a>
                <a href="notifications.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6z"/><path d="M9.5 18a2.5 2.5 0 0 0 5 0"/></svg>
                    Notification & Alerts
                </a>
                <a href="reports.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
                    Reports
                </a>
                <a href="user_access.php" class="nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0z"/></svg>
                    User Access
                </a>
            </nav>
            <div class="sidebar-bottom">
                <a href="settings.php" class="nav-item muted">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
                <a href="help.php" class="nav-item muted">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 2-3 4"/><path d="M12 17h.01"/></svg>
                    Help
                </a>
                <a href="../logout.php" class="nav-item muted">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Log Out
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>User Access</h1>
                    <p>Manage all accounts across the platform</p>
                </div>
                <div class="topbar-user">
                    <div class="topbar-user-text">
                        <strong><?php echo htmlspecialchars($ownerName); ?></strong>
                        <span>Owner</span>
                    </div>
                    <div class="topbar-avatar"><?php echo strtoupper(substr($ownerName, 0, 1)); ?></div>
                </div>
            </header>

            <div class="dashboard-container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p><?php echo (int)$totalUsers; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Owners</h3>
                        <p><?php echo (int)$totalOwners; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Hosts</h3>
                        <p><?php echo (int)$totalHosts; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Staff</h3>
                        <p><?php echo (int)$totalStaff; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Guests</h3>
                        <p><?php echo (int)$totalGuests; ?></p>
                    </div>
                </div>

                <form class="filters-bar" method="GET">
                    <input type="text" name="search" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="owner" <?php echo $roleFilter === 'owner' ? 'selected' : ''; ?>>Owner</option>
                        <option value="host"  <?php echo $roleFilter === 'host' ? 'selected' : ''; ?>>Host</option>
                        <option value="staff" <?php echo $roleFilter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="guest" <?php echo $roleFilter === 'guest' ? 'selected' : ''; ?>>Guest</option>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if ($roleFilter || $search): ?>
                        <a href="user_access.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if (count($users) === 0): ?>
                    <div class="empty-state">Walang users na tumutugma sa filter mo.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u):
                                    [$bg, $fg] = roleColor($u['role']);
                                    $isActive = $u['is_active'] === null || $u['is_active'] === 't' || $u['is_active'] === true;
                                    $isSelf = (int)$u['id'] === (int)$_SESSION['user_id'];
                                ?>
                                    <tr>
                                        <td><div class="cell-primary"><?php echo htmlspecialchars($u['name']); ?></div></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                                <?php echo htmlspecialchars($u['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge" style="background:<?php echo $isActive ? '#e6f2e0' : '#fbe4e1'; ?>; color:<?php echo $isActive ? '#3c6b41' : '#c0392b'; ?>;">
                                                <?php echo $isActive ? 'Active' : 'Suspended'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <?php if ($isSelf): ?>
                                                <span style="color:#b7ae94; font-size:12px;">— you —</span>
                                            <?php else: ?>
                                                <button type="button"
                                                    class="btn-toggle-status <?php echo $isActive ? 'btn-suspend' : 'btn-activate'; ?>"
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-active="<?php echo $isActive ? '1' : '0'; ?>">
                                                    <?php echo $isActive ? 'Suspend' : 'Activate'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', () => {
                const userId = btn.dataset.id;
                const currentlyActive = btn.dataset.active === '1';
                const newStatus = !currentlyActive;
                const label = newStatus ? 'activate' : 'suspend';

                if (!confirm(`Are you sure you want to ${label} this account?`)) return;

                fetch('../api/toggle_user_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ user_id: userId, is_active: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Something went wrong.');
                    }
                })
                .catch(() => alert('Something went wrong. Please try again.'));
            });
        });
    </script>
</body>
</html>