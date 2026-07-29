<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../backend/includes/db.php';

$ownerName = $_SESSION['user_name'] ?? 'Owner';

// ===== FILTERS =====
$statusFilter = trim($_GET['status'] ?? '');
$hostFilter   = intval($_GET['host_id'] ?? 0);
$search       = trim($_GET['search'] ?? '');
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to'] ?? '');

$sql = "
    SELECT b.id, b.check_in, b.check_out, b.guest_count, b.total_price, b.status,
           un.id AS unit_id, un.name AS unit_name,
           h.id AS host_id, h.name AS host_name,
           g.name AS guest_name, g.email AS guest_email
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    JOIN users h ON h.id = un.host_id
    JOIN users g ON g.id = b.user_id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND b.status = :status";
    $params[':status'] = $statusFilter;
}

if ($hostFilter > 0) {
    $sql .= " AND h.id = :host_id";
    $params[':host_id'] = $hostFilter;
}

if (!empty($search)) {
    $sql .= " AND (g.name ILIKE :search OR un.name ILIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($dateFrom)) {
    $sql .= " AND b.check_in >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND b.check_out <= :date_to";
    $params[':date_to'] = $dateTo;
}

$sql .= " ORDER BY b.check_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== SUMMARY STATS (unfiltered, platform-wide) =====
$totalCount     = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingCount   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$confirmedCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$completedCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$declinedCount  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'declined'")->fetchColumn();

// ===== HOST LIST (for filter dropdown) =====
$hosts = $pdo->query("SELECT id, name FROM users WHERE role = 'host' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

function statusColor($status) {
    switch ($status) {
        case 'confirmed': return ['#e6f2e0', '#3c6b41'];
        case 'pending':   return ['#fdf3d9', '#8a6d1a'];
        case 'declined':  return ['#fbe4e1', '#c0392b'];
        case 'completed': return ['#e5eef7', '#2c5a7a'];
        default:          return ['#f0ece0', '#6b6350'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bookings — Owner Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css">
</head>
<body>
    <div class="app-layout">
        <!-- ===== SIDEBAR ===== -->
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
                <a href="bookings.php" class="nav-item active">
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

        <!-- ===== MAIN CONTENT ===== -->
        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>Bookings</h1>
                    <p>Platform-wide view of all reservations across every host and unit</p>
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
                <!-- ===== SUMMARY STATS ===== -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Bookings</h3>
                        <p><?php echo (int)$totalCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Pending</h3>
                        <p style="color:#8a6d1a;"><?php echo (int)$pendingCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Confirmed</h3>
                        <p style="color:#3c6b41;"><?php echo (int)$confirmedCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Completed</h3>
                        <p style="color:#2c5a7a;"><?php echo (int)$completedCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Declined</h3>
                        <p style="color:#c0392b;"><?php echo (int)$declinedCount; ?></p>
                    </div>
                </div>

                <!-- ===== FILTERS ===== -->
                <form class="filters-bar" method="GET">
                    <input type="text" name="search" placeholder="Search guest or unit name..." value="<?php echo htmlspecialchars($search); ?>">

                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending"   <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="declined"  <?php echo $statusFilter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    </select>

                    <select name="host_id">
                        <option value="">All Hosts</option>
                        <?php foreach ($hosts as $h): ?>
                            <option value="<?php echo $h['id']; ?>" <?php echo $hostFilter === (int)$h['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" title="Check-in from">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" title="Check-out until">

                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if ($statusFilter || $hostFilter || $search || $dateFrom || $dateTo): ?>
                        <a href="bookings.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- ===== BOOKINGS TABLE ===== -->
                <?php if (count($bookings) === 0): ?>
                    <div class="empty-state">Walang bookings na tumutugma sa filter mo.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Unit</th>
                                    <th>Host</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b):
                                    [$bg, $fg] = statusColor($b['status']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="cell-primary"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                            <div class="cell-secondary"><?php echo htmlspecialchars($b['guest_email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($b['unit_name']); ?></td>
                                        <td><?php echo htmlspecialchars($b['host_name']); ?></td>
                                        <td><?php echo htmlspecialchars($b['check_in']); ?></td>
                                        <td><?php echo htmlspecialchars($b['check_out']); ?></td>
                                        <td><?php echo (int)$b['guest_count']; ?></td>
                                        <td>₱<?php echo number_format($b['total_price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                                <?php echo htmlspecialchars($b['status']); ?>
                                            </span>
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
</body>
</html>