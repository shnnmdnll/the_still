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
$typeFilter = trim($_GET['type'] ?? '');

$notifications = [];

// ===== 1. BOOKINGS (pending, needing action) =====
if (empty($typeFilter) || $typeFilter === 'booking') {
    $stmt = $pdo->prepare("
        SELECT b.id, b.check_in, b.check_out, b.status, un.name AS unit_name, g.name AS guest_name, b.check_in AS ts
        FROM bookings b
        JOIN units un ON un.id = b.unit_id
        JOIN users g ON g.id = b.user_id
        WHERE un.host_id = :host_id AND b.status = 'pending'
        ORDER BY b.id DESC
        LIMIT 20
    ");
    $stmt->execute([':host_id' => $hostId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $notifications[] = [
            'type' => 'booking',
            'title' => 'New Booking Request',
            'message' => htmlspecialchars($row['guest_name']) . ' requested to book ' . htmlspecialchars($row['unit_name']) . ' (' . $row['check_in'] . ' → ' . $row['check_out'] . ')',
            'timestamp' => $row['ts'],
            'link' => 'bookings.php',
        ];
    }
}

// ===== 2. MAINTENANCE REPORTS (from staff, pending/in_progress) =====
if (empty($typeFilter) || $typeFilter === 'maintenance') {
    $stmt = $pdo->prepare("
        SELECT mr.id, mr.description, mr.status, mr.reported_at, un.name AS unit_name, s.name AS staff_name
        FROM maintenance_reports mr
        JOIN units un ON un.id = mr.unit_id
        LEFT JOIN users s ON s.id = mr.staff_id
        WHERE un.host_id = :host_id AND mr.status != 'resolved'
        ORDER BY mr.reported_at DESC
        LIMIT 20
    ");
    $stmt->execute([':host_id' => $hostId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $notifications[] = [
    'type' => 'maintenance',
    'title' => 'Maintenance Reported',
    'message' => htmlspecialchars($row['staff_name'] ?? 'Staff') . ' reported an issue at ' . htmlspecialchars($row['unit_name']) . ': ' . htmlspecialchars(mb_strimwidth($row['description'] ?? '', 0, 60, '...')),
    'timestamp' => $row['reported_at'],
    'link' => 'maintenance.php',
];
    }
}

// ===== 3. NEW REVIEWS =====
if (empty($typeFilter) || $typeFilter === 'review') {
    $stmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, un.name AS unit_name, g.name AS guest_name
        FROM reviews r
        JOIN units un ON un.id = r.unit_id
        JOIN users g ON g.id = r.user_id
        WHERE un.host_id = :host_id
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':host_id' => $hostId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $stars = str_repeat('★', (int)$row['rating']) . str_repeat('☆', 5 - (int)$row['rating']);
        $notifications[] = [
            'type' => 'review',
            'title' => 'New Review — ' . $stars,
            'message' => htmlspecialchars($row['guest_name']) . ' left a review on ' . htmlspecialchars($row['unit_name']),
            'timestamp' => $row['created_at'],
            'link' => 'ratings.php',
        ];
    }
}

usort($notifications, function ($a, $b) {
    return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
});

function hostTypeIcon($type) {
    switch ($type) {
        case 'booking':     return ['📅', '#3c6b41'];
        case 'maintenance': return ['🔧', '#c0392b'];
        case 'review':      return ['⭐', '#c98a1f'];
        default:            return ['🔔', '#6b6350'];
    }
}

function hostTimeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications — Host Dashboard</title>
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
                    <p>Everything happening across your units</p>
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
                <div class="notif-tabs">
                    <a href="notifications.php" class="notif-tab <?php echo empty($typeFilter) ? 'active' : ''; ?>">All (<?php echo count($notifications); ?>)</a>
                    <a href="?type=booking" class="notif-tab <?php echo $typeFilter === 'booking' ? 'active' : ''; ?>">Bookings</a>
                    <a href="?type=maintenance" class="notif-tab <?php echo $typeFilter === 'maintenance' ? 'active' : ''; ?>">Maintenance</a>
                    <a href="?type=review" class="notif-tab <?php echo $typeFilter === 'review' ? 'active' : ''; ?>">Reviews</a>
                </div>

                <?php if (count($notifications) === 0): ?>
                    <div class="empty-state">Walang notifications ngayon. Malinis lahat! ✨</div>
                <?php else: ?>
                    <div class="notif-feed">
                        <?php foreach ($notifications as $n):
                            [$icon, $color] = hostTypeIcon($n['type']);
                        ?>
                            <a href="<?php echo $n['link']; ?>" class="notif-card">
                                <div class="notif-icon" style="background:<?php echo $color; ?>1a; color:<?php echo $color; ?>;"><?php echo $icon; ?></div>
                                <div class="notif-body">
                                    <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <div class="notif-message"><?php echo $n['message']; ?></div>
                                </div>
                                <div class="notif-time"><?php echo hostTimeAgo($n['timestamp']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>