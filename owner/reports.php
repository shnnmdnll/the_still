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

// ===== SUMMARY METRICS =====
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'completed'")->fetchColumn();

$thisMonthRevenue = $pdo->query("
    SELECT COALESCE(SUM(total_price), 0) FROM bookings 
    WHERE status = 'completed' AND check_in >= date_trunc('month', CURRENT_DATE)
")->fetchColumn();

$avgBookingValue = $pdo->query("
    SELECT COALESCE(AVG(total_price), 0) FROM bookings WHERE status IN ('completed', 'confirmed')
")->fetchColumn();

$totalBookingsAll = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$declinedBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'declined'")->fetchColumn();
$cancellationRate = $totalBookingsAll > 0 ? round(($declinedBookings / $totalBookingsAll) * 100, 1) : 0;

// ===== REVENUE BY MONTH (last 6 months) =====
$stmt = $pdo->query("
    SELECT 
        to_char(date_trunc('month', check_in), 'Mon YYYY') AS month_label,
        date_trunc('month', check_in) AS month_sort,
        SUM(total_price) AS revenue
    FROM bookings
    WHERE status = 'completed'
      AND check_in >= CURRENT_DATE - INTERVAL '6 months'
    GROUP BY month_label, month_sort
    ORDER BY month_sort ASC
");
$monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
$maxRevenue = 0;
foreach ($monthlyRevenue as $m) {
    $maxRevenue = max($maxRevenue, (float)$m['revenue']);
}

// ===== TOP 5 MOST BOOKED UNITS =====
$stmt = $pdo->query("
    SELECT un.name AS unit_name, h.name AS host_name, COUNT(b.id) AS booking_count
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    JOIN users h ON h.id = un.host_id
    GROUP BY un.name, h.name
    ORDER BY booking_count DESC
    LIMIT 5
");
$topUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
$maxBookingCount = 0;
foreach ($topUnits as $u) {
    $maxBookingCount = max($maxBookingCount, (int)$u['booking_count']);
}

// ===== BOOKING STATUS BREAKDOWN =====
$statusCounts = [
    'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'confirmed' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn(),
    'declined'  => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'declined'")->fetchColumn(),
];
$statusTotal = array_sum($statusCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports — Owner Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_reports.css?v=<?php echo time(); ?>">
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
                <a href="reports.php" class="nav-item active">
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
                    <h1>Reports</h1>
                    <p>Platform-wide revenue and performance analytics</p>
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
                <!-- ===== SUMMARY CARDS ===== -->
                <div class="stats-grid">
                    <div class="stat-card revenue">
                        <h3>Total Revenue</h3>
                        <p>₱<?php echo number_format($totalRevenue, 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>This Month</h3>
                        <p style="color:#3c6b41;">₱<?php echo number_format($thisMonthRevenue, 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Avg. Booking Value</h3>
                        <p>₱<?php echo number_format($avgBookingValue, 2); ?></p>
                    </div>
                    <div class="stat-card <?php echo $cancellationRate > 15 ? 'alert' : ''; ?>">
                        <h3>Cancellation Rate</h3>
                        <p style="<?php echo $cancellationRate > 15 ? '' : 'color:#3c6b41;'; ?>"><?php echo $cancellationRate; ?>%</p>
                    </div>
                </div>

                <!-- ===== REVENUE CHART ===== -->
                <div class="report-panel">
                    <h2>Revenue — Last 6 Months</h2>
                    <?php if (count($monthlyRevenue) === 0): ?>
                        <div class="empty-state">Wala pang completed bookings para gumawa ng chart.</div>
                    <?php else: ?>
                        <div class="bar-chart">
                            <?php foreach ($monthlyRevenue as $m):
                                $heightPct = $maxRevenue > 0 ? round(((float)$m['revenue'] / $maxRevenue) * 100) : 0;
                            ?>
                                <div class="bar-col">
                                    <div class="bar-value">₱<?php echo number_format($m['revenue'], 0); ?></div>
                                    <div class="bar" style="height:<?php echo max($heightPct, 4); ?>%;"></div>
                                    <div class="bar-label"><?php echo htmlspecialchars($m['month_label']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="report-grid-2">
                    <!-- ===== TOP UNITS ===== -->
                    <div class="report-panel">
                        <h2>Top 5 Most Booked Units</h2>
                        <?php if (count($topUnits) === 0): ?>
                            <div class="empty-state">Wala pang bookings.</div>
                        <?php else: ?>
                            <div class="top-units-list">
                                <?php foreach ($topUnits as $i => $u):
                                    $widthPct = $maxBookingCount > 0 ? round(($u['booking_count'] / $maxBookingCount) * 100) : 0;
                                ?>
                                    <div class="top-unit-row">
                                        <div class="top-unit-rank">#<?php echo $i + 1; ?></div>
                                        <div class="top-unit-info">
                                            <div class="top-unit-name"><?php echo htmlspecialchars($u['unit_name']); ?></div>
                                            <div class="top-unit-host"><?php echo htmlspecialchars($u['host_name']); ?></div>
                                            <div class="top-unit-bar-track">
                                                <div class="top-unit-bar-fill" style="width:<?php echo max($widthPct, 5); ?>%;"></div>
                                            </div>
                                        </div>
                                        <div class="top-unit-count"><?php echo $u['booking_count']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ===== STATUS BREAKDOWN ===== -->
                    <div class="report-panel">
                        <h2>Booking Status Breakdown</h2>
                        <?php if ($statusTotal === 0): ?>
                            <div class="empty-state">Wala pang bookings.</div>
                        <?php else: ?>
                            <div class="status-breakdown">
                                <?php
                                $statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'declined' => 'Declined'];
                                $statusColors = ['pending' => '#c98a1f', 'confirmed' => '#2c5a7a', 'completed' => '#3c6b41', 'declined' => '#c0392b'];
                                foreach ($statusCounts as $status => $count):
                                    $pct = $statusTotal > 0 ? round(($count / $statusTotal) * 100) : 0;
                                ?>
                                    <div class="status-row">
                                        <div class="status-row-label">
                                            <span><?php echo $statusLabels[$status]; ?></span>
                                            <span><?php echo $count; ?> (<?php echo $pct; ?>%)</span>
                                        </div>
                                        <div class="status-bar-track">
                                            <div class="status-bar-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $statusColors[$status]; ?>;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>