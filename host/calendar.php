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

// ===== MONTH/YEAR NAVIGATION =====
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$startWeekday = date('w', $firstDayOfMonth); // 0 = Sunday
$monthLabel = date('F Y', $firstDayOfMonth);

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// ===== FETCH BOOKINGS FOR THIS MONTH =====
$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthEnd = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

$stmt = $pdo->prepare("
    SELECT b.id, b.check_in, b.check_out, b.status, un.name AS unit_name, g.name AS guest_name
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    JOIN users g ON g.id = b.user_id
    WHERE un.host_id = :host_id
      AND b.status IN ('confirmed', 'completed')
      AND (
          (b.check_in BETWEEN :start1 AND :end1) OR
          (b.check_out BETWEEN :start2 AND :end2)
      )
");
$stmt->execute([
    ':host_id' => $hostId,
    ':start1' => $monthStart, ':end1' => $monthEnd,
    ':start2' => $monthStart, ':end2' => $monthEnd,
]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// I-organize by day number
$eventsByDay = [];
foreach ($bookings as $b) {
    $checkInDay = (int)date('j', strtotime($b['check_in']));
    $checkInMonth = (int)date('n', strtotime($b['check_in']));
    $checkInYear = (int)date('Y', strtotime($b['check_in']));
    if ($checkInMonth === $month && $checkInYear === $year) {
        $eventsByDay[$checkInDay][] = [
            'type' => 'checkin',
            'unit' => $b['unit_name'],
            'guest' => $b['guest_name'],
        ];
    }

    $checkOutDay = (int)date('j', strtotime($b['check_out']));
    $checkOutMonth = (int)date('n', strtotime($b['check_out']));
    $checkOutYear = (int)date('Y', strtotime($b['check_out']));
    if ($checkOutMonth === $month && $checkOutYear === $year) {
        $eventsByDay[$checkOutDay][] = [
            'type' => 'checkout',
            'unit' => $b['unit_name'],
            'guest' => $b['guest_name'],
        ];
    }
}

$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$today = (int)date('j');
$isCurrentMonth = ((int)date('n') === $month && (int)date('Y') === $year);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Calendar — Host Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/host_calendar.css?v=<?php echo time(); ?>">
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
                <a href="calendar.php" class="nav-item active">
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
                    <h1>Calendar</h1>
                    <p>Check-ins and check-outs across all your units</p>
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
                <div class="calendar-header">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="cal-nav-btn">‹</a>
                    <form method="GET" class="cal-jump-form">
                        <select name="month" onchange="this.form.submit()">
                            <?php foreach ($monthNames as $i => $mn):
                                $mNum = $i + 1;
                            ?>
                                <option value="<?php echo $mNum; ?>" <?php echo $mNum === $month ? 'selected' : ''; ?>><?php echo $mn; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" onchange="this.form.submit()">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 3; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="cal-nav-btn">›</a>
                </div>

                <div class="calendar-legend">
                    <span class="legend-item"><span class="legend-dot checkin"></span> Check-in</span>
                    <span class="legend-item"><span class="legend-dot checkout"></span> Check-out</span>
                </div>

                <div class="calendar-grid">
                    <?php foreach ($dayNames as $dn): ?>
                        <div class="calendar-daylabel"><?php echo $dn; ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $startWeekday; $i++): ?>
                        <div class="calendar-cell empty"></div>
                    <?php endfor; ?>

                    <?php for ($d = 1; $d <= $daysInMonth; $d++):
                        $isToday = $isCurrentMonth && $d === $today;
                        $events = $eventsByDay[$d] ?? [];
                    ?>
                        <div class="calendar-cell <?php echo $isToday ? 'today' : ''; ?>">
                            <div class="cell-date"><?php echo $d; ?></div>
                            <div class="cell-events">
                                <?php foreach (array_slice($events, 0, 3) as $ev): ?>
                                    <div class="cell-event <?php echo $ev['type']; ?>" title="<?php echo htmlspecialchars($ev['guest'] . ' - ' . $ev['unit']); ?>">
                                        <?php echo $ev['type'] === 'checkin' ? '🛬' : '🛫'; ?> <?php echo htmlspecialchars(mb_strimwidth($ev['unit'], 0, 10, '...')); ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($events) > 3): ?>
                                    <div class="cell-event-more">+<?php echo count($events) - 3; ?> more</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>