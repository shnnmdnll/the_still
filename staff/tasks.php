<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../backend/includes/db.php';

$staffId = $_SESSION['user_id'];
$staffName = $_SESSION['user_name'] ?? 'Staff';

// Kunin ang assigned unit ng staff na ito
$stmt = $pdo->prepare("SELECT assigned_unit_id FROM users WHERE id = :id");
$stmt->execute([':id' => $staffId]);
$assignedUnitId = $stmt->fetchColumn();

$unitInfo = null;
$todaySchedule = [];
$lowStockCount = 0;

if ($assignedUnitId) {
    $unitStmt = $pdo->prepare("SELECT id, name, location FROM units WHERE id = :id");
    $unitStmt->execute([':id' => $assignedUnitId]);
    $unitInfo = $unitStmt->fetch(PDO::FETCH_ASSOC);

    $scheduleStmt = $pdo->prepare("
        SELECT b.check_in, b.check_out, g.name AS guest_name
        FROM bookings b
        JOIN users g ON g.id = b.user_id
        WHERE b.unit_id = :unit_id
          AND (b.check_in = CURRENT_DATE OR b.check_out = CURRENT_DATE)
          AND b.status IN ('confirmed', 'completed')
    ");
    $scheduleStmt->execute([':unit_id' => $assignedUnitId]);
    $todaySchedule = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

    $lowStockStmt = $pdo->prepare("
        SELECT COUNT(*) FROM inventory_items 
        WHERE unit_id = :unit_id AND status IN ('low', 'out')
    ");
    $lowStockStmt->execute([':unit_id' => $assignedUnitId]);
    $lowStockCount = $lowStockStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Today's Tasks — Staff Dashboard</title>
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
                <a href="tasks.php" class="nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    Today's Tasks
                </a>
                <a href="inventory.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-3V6a4 4 0 0 0-8 0v1H6a1 1 0 0 0-1 1v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1zM9 6a3 3 0 0 1 6 0v1H9z"/></svg>
                    Inventory
                </a>
                <a href="maintenance.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/></svg>
                    Maintenance Reports
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
                    <h1>Today's Tasks</h1>
                    <p><?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="topbar-user">
                    <div class="topbar-user-text">
                        <strong><?php echo htmlspecialchars($staffName); ?></strong>
                        <span>Staff</span>
                    </div>
                    <div class="topbar-avatar"><?php echo strtoupper(substr($staffName, 0, 1)); ?></div>
                </div>
            </header>

            <div class="dashboard-container">
                <?php if (!$unitInfo): ?>
                    <div class="empty-state">Wala ka pang naka-assign na unit. Makipag-ugnayan sa iyong Host.</div>
                <?php else: ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Assigned Unit</h3>
                            <p style="font-size:18px;"><?php echo htmlspecialchars($unitInfo['name']); ?></p>
                        </div>
                        <div class="stat-card <?php echo count($todaySchedule) > 0 ? 'alert' : ''; ?>">
                            <h3>Today's Cleaning</h3>
                            <p><?php echo count($todaySchedule); ?></p>
                        </div>
                        <div class="stat-card <?php echo $lowStockCount > 0 ? 'alert' : ''; ?>">
                            <h3>Low Stock Items</h3>
                            <p><?php echo (int)$lowStockCount; ?></p>
                        </div>
                    </div>

                    <div class="report-panel">
                        <h2>Cleaning Schedule — <?php echo htmlspecialchars($unitInfo['name']); ?></h2>
                        <?php if (count($todaySchedule) === 0): ?>
                            <div class="empty-state">Walang check-in o check-out ngayong araw sa unit mo.</div>
                        <?php else: ?>
                            <div class="notif-feed">
                                <?php foreach ($todaySchedule as $s):
                                    $isCheckin = $s['check_in'] === date('Y-m-d');
                                ?>
                                    <div class="notif-card" style="cursor:default;">
                                        <div class="notif-icon" style="background:<?php echo $isCheckin ? '#e6f2e01a' : '#e5eef71a'; ?>; color:<?php echo $isCheckin ? '#3c6b41' : '#2c5a7a'; ?>;">
                                            <?php echo $isCheckin ? '🛬' : '🧹'; ?>
                                        </div>
                                        <div class="notif-body">
                                            <div class="notif-title"><?php echo $isCheckin ? 'Prepare for Check-in' : 'Clean after Check-out'; ?></div>
                                            <div class="notif-message">Guest: <?php echo htmlspecialchars($s['guest_name']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>