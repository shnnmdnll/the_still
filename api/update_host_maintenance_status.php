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

$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT mr.id, mr.description, mr.status, mr.reported_at, mr.resolved_at,
           un.name AS unit_name, s.name AS staff_name
    FROM maintenance_reports mr
    JOIN units un ON un.id = mr.unit_id
    LEFT JOIN users s ON s.id = mr.staff_id
    WHERE un.host_id = :host_id
";
$params = [':host_id' => $hostId];
if (!empty($statusFilter)) {
    $sql .= " AND mr.status = :status";
    $params[':status'] = $statusFilter;
}
$sql .= " ORDER BY mr.reported_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports mr JOIN units un ON un.id = mr.unit_id WHERE un.host_id = :h AND mr.status = 'pending'");
$pendingCount->execute([':h' => $hostId]);
$pendingCount = $pendingCount->fetchColumn();

$inProgressCount = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports mr JOIN units un ON un.id = mr.unit_id WHERE un.host_id = :h AND mr.status = 'in_progress'");
$inProgressCount->execute([':h' => $hostId]);
$inProgressCount = $inProgressCount->fetchColumn();

$resolvedCount = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports mr JOIN units un ON un.id = mr.unit_id WHERE un.host_id = :h AND mr.status = 'resolved'");
$resolvedCount->execute([':h' => $hostId]);
$resolvedCount = $resolvedCount->fetchColumn();

function maintStatusColor($status) {
    switch ($status) {
        case 'in_progress': return ['#e5eef7', '#2c5a7a'];
        case 'resolved':    return ['#e6f2e0', '#3c6b41'];
        default:            return ['#fdf3d9', '#8a6d1a'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance — Host Dashboard</title>
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
                    <h1>Maintenance</h1>
                    <p>Issues reported by your cleaning staff</p>
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
                    <div class="empty-state">Walang maintenance reports.</div>
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
                                        <td><?php echo htmlspecialchars($r['description']); ?></td>
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
        function updateReportStatus(id, status) {
            fetch('../api/update_host_maintenance_status.php', {
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
            btn.addEventListener('click', () => updateReportStatus(btn.dataset.id, 'in_progress'));
        });
        document.querySelectorAll('.btn-mark-resolved').forEach(btn => {
            btn.addEventListener('click', () => updateReportStatus(btn.dataset.id, 'resolved'));
        });
    </script>
</body>
</html>