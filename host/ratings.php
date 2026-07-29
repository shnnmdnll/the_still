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

// ===== SUMMARY =====
$avgRating = $pdo->prepare("
    SELECT COALESCE(AVG(r.rating), 0) FROM reviews r
    JOIN units un ON un.id = r.unit_id
    WHERE un.host_id = :h
");
$avgRating->execute([':h' => $hostId]);
$avgRating = round($avgRating->fetchColumn(), 1);

$totalReviews = $pdo->prepare("
    SELECT COUNT(*) FROM reviews r
    JOIN units un ON un.id = r.unit_id
    WHERE un.host_id = :h
");
$totalReviews->execute([':h' => $hostId]);
$totalReviews = $totalReviews->fetchColumn();

// ===== RATING DISTRIBUTION (5-star to 1-star) =====
$distribution = [];
for ($i = 5; $i >= 1; $i--) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reviews r
        JOIN units un ON un.id = r.unit_id
        WHERE un.host_id = :h AND r.rating = :rating
    ");
    $stmt->execute([':h' => $hostId, ':rating' => $i]);
    $distribution[$i] = $stmt->fetchColumn();
}

// ===== UNIT FILTER =====
$unitFilter = intval($_GET['unit_id'] ?? 0);
$units = $pdo->prepare("SELECT id, name FROM units WHERE host_id = :h ORDER BY name");
$units->execute([':h' => $hostId]);
$units = $units->fetchAll(PDO::FETCH_ASSOC);

// ===== REVIEWS LIST =====
$sql = "
    SELECT r.id, r.rating, r.comment, r.created_at, un.name AS unit_name, g.name AS guest_name
    FROM reviews r
    JOIN units un ON un.id = r.unit_id
    JOIN users g ON g.id = r.user_id
    WHERE un.host_id = :host_id
";
$params = [':host_id' => $hostId];
if ($unitFilter > 0) {
    $sql .= " AND un.id = :unit_id";
    $params[':unit_id'] = $unitFilter;
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderStars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating ? '★' : '☆';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ratings — Host Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_reports.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/host_ratings.css?v=<?php echo time(); ?>">
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
                <a href="ratings.php" class="nav-item active">
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
                    <h1>Ratings</h1>
                    <p>See what guests are saying about your units</p>
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
                <div class="ratings-summary-grid">
                    <div class="rating-score-card">
                        <div class="rating-score-num"><?php echo $avgRating > 0 ? $avgRating : '—'; ?></div>
                        <div class="rating-score-stars"><?php echo renderStars(round($avgRating)); ?></div>
                        <div class="rating-score-count"><?php echo (int)$totalReviews; ?> review(s)</div>
                    </div>
                    <div class="rating-distribution">
                        <?php foreach ($distribution as $star => $count):
                            $pct = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                        ?>
                            <div class="dist-row">
                                <span class="dist-star"><?php echo $star; ?> ★</span>
                                <div class="dist-bar-track">
                                    <div class="dist-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                                </div>
                                <span class="dist-count"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form class="filters-bar" method="GET">
                    <select name="unit_id">
                        <option value="">All Units</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $unitFilter === (int)$u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if ($unitFilter): ?>
                        <a href="ratings.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if (count($reviews) === 0): ?>
                    <div class="empty-state">Wala pang reviews.</div>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $r): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div>
                                        <div class="review-guest"><?php echo htmlspecialchars($r['guest_name']); ?></div>
                                        <div class="review-unit"><?php echo htmlspecialchars($r['unit_name']); ?></div>
                                    </div>
                                    <div class="review-stars"><?php echo renderStars($r['rating']); ?></div>
                                </div>
                                <?php if (!empty($r['comment'])): ?>
                                    <p class="review-comment"><?php echo htmlspecialchars($r['comment']); ?></p>
                                <?php endif; ?>
                                <div class="review-date"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>