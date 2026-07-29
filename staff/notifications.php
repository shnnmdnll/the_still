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
$typeFilter = trim($_GET['type'] ?? '');

$stmt = $pdo->prepare("SELECT assigned_unit_id FROM users WHERE id = :id");
$stmt->execute([':id' => $staffId]);
$unitId = $stmt->fetchColumn();

$notifications = [];

if ($unitId) {
    // ===== 1. CLEANING SCHEDULE (upcoming check-ins/check-outs) =====
    if (empty($typeFilter) || $typeFilter === 'schedule') {
        $stmt = $pdo->prepare("
            SELECT b.check_in, b.check_out, g.name AS guest_name
            FROM bookings b
            JOIN users g ON g.id = b.user_id
            WHERE b.unit_id = :unit_id
              AND b.status IN ('confirmed', 'completed')
              AND (b.check_in >= CURRENT_DATE OR b.check_out >= CURRENT_DATE)
            ORDER BY b.check_in ASC
            LIMIT 15
        ");
        $stmt->execute([':unit_id' => $unitId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
            if ($b['check_in'] >= date('Y-m-d')) {
                $notifications[] = [
                    'type' => 'schedule',
                    'title' => 'Upcoming Check-in',
                    'message' => htmlspecialchars($b['guest_name']) . ' arrives on ' . date('M j, Y', strtotime($b['check_in'])),
                    'timestamp' => $b['check_in'] . ' 00:00:00',
                    'link' => 'tasks.php',
                ];
            }
            if ($b['check_out'] >= date('Y-m-d')) {
                $notifications[] = [
                    'type' => 'schedule',
                    'title' => 'Upcoming Check-out',
                    'message' => htmlspecialchars($b['guest_name']) . ' departs on ' . date('M j, Y', strtotime($b['check_out'])),
                    'timestamp' => $b['check_out'] . ' 00:00:01',
                    'link' => 'tasks.php',
                ];
            }
        }
    }

    // ===== 2. LOW STOCK ALERTS =====
    if (empty($typeFilter) || $typeFilter === 'stock') {
        $stmt = $pdo->prepare("
            SELECT item_name, quantity, status, updated_at
            FROM inventory_items
            WHERE unit_id = :unit_id AND category = 'supply' AND status IN ('low', 'out')
            ORDER BY quantity ASC
        ");
        $stmt->execute([':unit_id' => $unitId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $notifications[] = [
                'type' => 'stock',
                'title' => $item['status'] === 'out' ? 'Out of Stock' : 'Low Stock',
                'message' => htmlspecialchars($item['item_name']) . ' — ' . (int)$item['quantity'] . ' remaining',
                'timestamp' => $item['updated_at'],
                'link' => 'inventory.php',
            ];
        }
    }

    // ===== 3. AI MAINTENANCE ALERTS (appliances due soon/overdue) =====
    if (empty($typeFilter) || $typeFilter === 'maintenance') {
        $stmt = $pdo->prepare("SELECT id, item_name, last_maintained_date FROM inventory_items WHERE unit_id = :unit_id AND category = 'appliance'");
        $stmt->execute([':unit_id' => $unitId]);
        $applianceItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($applianceItems as $item) {
            $logStmt = $pdo->prepare("SELECT serviced_at FROM inventory_maintenance_log WHERE item_id = :id ORDER BY serviced_at ASC");
            $logStmt->execute([':id' => $item['id']]);
            $logs = $logStmt->fetchAll(PDO::FETCH_COLUMN);

            $avgInterval = 30;
            if (count($logs) >= 2) {
                $totalDays = 0;
                $count = 0;
                for ($i = 1; $i < count($logs); $i++) {
                    $totalDays += (strtotime($logs[$i]) - strtotime($logs[$i - 1])) / 86400;
                    $count++;
                }
                if ($count > 0) $avgInterval = round($totalDays / $count);
            }

            $lastMaintained = $item['last_maintained_date'] ?? date('Y-m-d');
            $predictedNext = date('Y-m-d', strtotime($lastMaintained . " +$avgInterval days"));
            $daysUntilDue = (int)((strtotime($predictedNext) - strtotime(date('Y-m-d'))) / 86400);

            if ($daysUntilDue <= 7) {
                $notifications[] = [
                    'type' => 'maintenance',
                    'title' => 'AI Maintenance Alert',
                    'message' => htmlspecialchars($item['item_name']) . ' — ' . ($daysUntilDue < 0 ? 'overdue by ' . abs($daysUntilDue) . ' day(s)' : 'due in ' . $daysUntilDue . ' day(s)'),
                    'timestamp' => $predictedNext . ' 00:00:00',
                    'link' => 'inventory.php',
                ];
            }
        }
    }

    // ===== 4. STATUS UPDATES sa sariling maintenance reports =====
    if (empty($typeFilter) || $typeFilter === 'reports') {
        $stmt = $pdo->prepare("
            SELECT description, status, reported_at, resolved_at
            FROM maintenance_reports
            WHERE staff_id = :staff_id AND status != 'pending'
            ORDER BY reported_at DESC
            LIMIT 15
        ");
        $stmt->execute([':staff_id' => $staffId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $label = $r['status'] === 'resolved' ? 'Report Resolved' : 'Report In Progress';
            $ts = $r['status'] === 'resolved' && $r['resolved_at'] ? $r['resolved_at'] : $r['reported_at'];
            $notifications[] = [
                'type' => 'reports',
                'title' => $label,
                'message' => 'Your report: "' . htmlspecialchars(mb_strimwidth($r['description'], 0, 60, '...')) . '"',
                'timestamp' => $ts,
                'link' => 'maintenance.php',
            ];
        }
    }
}

usort($notifications, function ($a, $b) {
    return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
});

function staffTypeIcon($type) {
    switch ($type) {
        case 'schedule':    return ['🧹', '#3c6b41'];
        case 'stock':       return ['📦', '#c0392b'];
        case 'maintenance': return ['⚠️', '#8a6d1a'];
        case 'reports':     return ['📋', '#2c5a7a'];
        default:            return ['🔔', '#6b6350'];
    }
}

function staffTimeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if (abs($diff) < 60) return 'just now';
    if ($diff < 0) {
        $diff = abs($diff);
        if ($diff < 86400) return 'in ' . floor($diff / 3600) . 'h';
        return 'in ' . floor($diff / 86400) . 'd';
    }
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications — Staff Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_notifications.css?v=<?php echo time(); ?>">
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
                <a href="tasks.php" class="nav-item">
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
                <a href="notifications.php" class="nav-item active">
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
                    <h1>Notifications</h1>
                    <p>Everything you need to know about your unit</p>
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
                <div class="notif-tabs">
                    <a href="notifications.php" class="notif-tab <?php echo empty($typeFilter) ? 'active' : ''; ?>">All (<?php echo count($notifications); ?>)</a>
                    <a href="?type=schedule" class="notif-tab <?php echo $typeFilter === 'schedule' ? 'active' : ''; ?>">Cleaning Schedule</a>
                    <a href="?type=stock" class="notif-tab <?php echo $typeFilter === 'stock' ? 'active' : ''; ?>">Low Stock</a>
                    <a href="?type=maintenance" class="notif-tab <?php echo $typeFilter === 'maintenance' ? 'active' : ''; ?>">Maintenance</a>
                    <a href="?type=reports" class="notif-tab <?php echo $typeFilter === 'reports' ? 'active' : ''; ?>">My Reports</a>
                </div>

                <?php if (count($notifications) === 0): ?>
                    <div class="empty-state">Walang notifications ngayon. Malinis lahat! ✨</div>
                <?php else: ?>
                    <div class="notif-feed">
                        <?php foreach ($notifications as $n):
                            [$icon, $color] = staffTypeIcon($n['type']);
                        ?>
                            <a href="<?php echo $n['link']; ?>" class="notif-card">
                                <div class="notif-icon" style="background:<?php echo $color; ?>1a; color:<?php echo $color; ?>;"><?php echo $icon; ?></div>
                                <div class="notif-body">
                                    <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <div class="notif-message"><?php echo $n['message']; ?></div>
                                </div>
                                <div class="notif-time"><?php echo staffTimeAgo($n['timestamp']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>