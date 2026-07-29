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
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT mr.id, mr.description, mr.photo_path, mr.status, mr.reported_at, mr.resolved_at,
           un.name AS unit_name, s.name AS staff_name
    FROM maintenance_reports mr
    JOIN units un ON un.id = mr.unit_id
    LEFT JOIN users s ON s.id = mr.staff_id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND mr.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY mr.reported_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount    = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status = 'pending'")->fetchColumn();
$inProgressCount = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status = 'in_progress'")->fetchColumn();
$resolvedCount   = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status = 'resolved'")->fetchColumn();

function maintStatusColor($status) {
    switch ($status) {
        case 'in_progress': return ['#e5eef7', '#2c5a7a'];
        case 'resolved':    return ['#e6f2e0', '#3c6b41'];
        default:             return ['#fdf3d9', '#8a6d1a'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance — Owner Dashboard</title>
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
                <a href="maintenance.php" class="nav-item active">
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
                <a href="user_access.php" class="nav-item">
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
                    <h1>Maintenance</h1>
                    <p>Track maintenance and repair reports across all units</p>
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
                    <div class="stat-card alert">
                        <h3>Pending</h3>
                        <p><?php echo (int)$pendingCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>In Progress</h3>
                        <p style="color:#2c5a7a;"><?php echo (int)$inProgressCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Resolved</h3>
                        <p style="color:#3c6b41;"><?php echo (int)$resolvedCount; ?></p>
                    </div>
                </div>

                <form class="filters-bar" method="GET">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending"     <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved"    <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if ($statusFilter): ?>
                        <a href="maintenance.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if (count($reports) === 0): ?>
                    <div class="empty-state">Walang maintenance reports na tumutugma sa filter mo.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th>Reported By</th>
                                    <th>Reported</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r):
                                    [$bg, $fg] = maintStatusColor($r['status']);
                                ?>
                                    <tr>
                                        <td><div class="cell-primary"><?php echo htmlspecialchars($r['unit_name']); ?></div></td>
                                        <td><?php echo htmlspecialchars(mb_strimwidth($r['description'] ?? '', 0, 60, '...')); ?></td>
                                        <td><?php echo htmlspecialchars($r['staff_name'] ?? '—'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($r['reported_at'])); ?></td>
                                        <td>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                                <?php echo str_replace('_', ' ', $r['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($r['status'] !== 'resolved'): ?>
                                                <div class="action-btns">
                                                    <?php if ($r['status'] === 'pending'): ?>
                                                        <button type="button" class="btn-view-details btn-mark-progress" data-id="<?php echo $r['id']; ?>">Mark In Progress</button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn-approve btn-mark-resolved" data-id="<?php echo $r['id']; ?>">Mark Resolved</button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#b7ae94; font-size:12px;">Resolved <?php echo $r['resolved_at'] ? date('M j', strtotime($r['resolved_at'])) : ''; ?></span>
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
        function updateMaintenanceStatus(id, status) {
            fetch('../api/update_maintenance_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ report_id: id, status: status })
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
        }

        document.querySelectorAll('.btn-mark-progress').forEach(btn => {
            btn.addEventListener('click', () => updateMaintenanceStatus(btn.dataset.id, 'in_progress'));
        });
        document.querySelectorAll('.btn-mark-resolved').forEach(btn => {
            btn.addEventListener('click', () => updateMaintenanceStatus(btn.dataset.id, 'resolved'));
        });
    </script>
</body>
</html>