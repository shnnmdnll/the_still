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
$unitFilter = intval($_GET['unit_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT b.id, b.check_in, b.check_out, b.guest_count, b.total_price, b.status,
           b.payment_status, b.downpayment_amount,
           un.id AS unit_id, un.name AS unit_name,
           g.name AS guest_name, g.email AS guest_email
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    JOIN users g ON g.id = b.user_id
    WHERE un.host_id = :host_id AND b.status != 'awaiting_payment'
";
$params = [':host_id' => $hostId];

if (!empty($statusFilter)) {
    $sql .= " AND b.status = :status";
    $params[':status'] = $statusFilter;
}
if ($unitFilter > 0) {
    $sql .= " AND un.id = :unit_id";
    $params[':unit_id'] = $unitFilter;
}
if (!empty($search)) {
    $sql .= " AND g.name ILIKE :search";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY b.check_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== SUMMARY STATS =====
$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN units un ON un.id = b.unit_id WHERE un.host_id = :h AND b.status = 'pending'");
$pendingCount->execute([':h' => $hostId]);
$pendingCount = $pendingCount->fetchColumn();

$confirmedCount = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN units un ON un.id = b.unit_id WHERE un.host_id = :h AND b.status = 'confirmed'");
$confirmedCount->execute([':h' => $hostId]);
$confirmedCount = $confirmedCount->fetchColumn();

$completedCount = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN units un ON un.id = b.unit_id WHERE un.host_id = :h AND b.status = 'completed'");
$completedCount->execute([':h' => $hostId]);
$completedCount = $completedCount->fetchColumn();

// ===== UNIT LIST (for filter) =====
$units = $pdo->prepare("SELECT id, name FROM units WHERE host_id = :h ORDER BY name");
$units->execute([':h' => $hostId]);
$units = $units->fetchAll(PDO::FETCH_ASSOC);

function bkgStatusColor($status) {
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
    <title>Bookings — Host Dashboard</title>
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
                <a href="bookings.php" class="nav-item active">
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
                    <h1>Bookings</h1>
                    <p>Manage reservations for your units</p>
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
                        <h3>Confirmed</h3>
                        <p style="color:#3c6b41;"><?php echo (int)$confirmedCount; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Completed</h3>
                        <p style="color:#2c5a7a;"><?php echo (int)$completedCount; ?></p>
                    </div>
                </div>

                <form class="filters-bar" method="GET">
                    <input type="text" name="search" placeholder="Search guest name..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending"   <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="declined"  <?php echo $statusFilter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    </select>
                    <select name="unit_id">
                        <option value="">All Units</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $unitFilter === (int)$u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if ($statusFilter || $unitFilter || $search): ?>
                        <a href="bookings.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if (count($bookings) === 0): ?>
                    <div class="empty-state">Walang bookings na tumutugma sa filter mo.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Unit</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b):
                                    [$bg, $fg] = bkgStatusColor($b['status']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="cell-primary"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                            <div class="cell-secondary"><?php echo htmlspecialchars($b['guest_email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($b['unit_name']); ?></td>
                                        <td><?php echo htmlspecialchars($b['check_in']); ?></td>
                                        <td><?php echo htmlspecialchars($b['check_out']); ?></td>
                                        <td><?php echo (int)$b['guest_count']; ?></td>
                                        <td>₱<?php echo number_format($b['total_price'], 2); ?></td>
                                        <td>
                                            <?php 
                                        $dpAmount = (float)($b['downpayment_amount'] ?? 0);
                                        ?>
                                        <?php if ($b['payment_status'] === 'downpayment_paid' || $b['payment_status'] === 'paid'): ?>
                                            <div class="cell-primary" style="color:#3c6b41;">₱<?php echo number_format($dpAmount, 2); ?> paid</div>
                                            <div class="cell-secondary">Balance: ₱<?php echo number_format($b['total_price'] - $dpAmount, 2); ?></div>
                                        <?php else: ?>
                                            <span style="color:#b7ae94; font-size:12px;">Unpaid</span>
                                        <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                                <?php echo htmlspecialchars($b['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <div class="action-btns">
                                                    <button type="button" class="btn-approve btn-accept-booking" data-id="<?php echo $b['id']; ?>">Accept</button>
                                                    <button type="button" class="btn-reject btn-decline-booking" data-id="<?php echo $b['id']; ?>">Decline</button>
                                                </div>
                                            <?php elseif ($b['status'] === 'confirmed'): ?>
                                                <button type="button" class="btn-reject btn-decline-booking" data-id="<?php echo $b['id']; ?>">Decline</button>
                                            <?php else: ?>
                                                <span style="color:#b7ae94; font-size:12px;">—</span>
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
        function updateBookingStatus(id, status) {
            var label = status === 'confirmed' ? 'accept' : 'decline';
            if (!confirm('Are you sure you want to ' + label + ' this booking?')) return;

            fetch('../api/update_booking_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ booking_id: id, status: status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Unable to update booking.');
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
        }

        document.querySelectorAll('.btn-accept-booking').forEach(btn => {
            btn.addEventListener('click', () => updateBookingStatus(btn.dataset.id, 'confirmed'));
        });
        document.querySelectorAll('.btn-decline-booking').forEach(btn => {
            btn.addEventListener('click', () => updateBookingStatus(btn.dataset.id, 'declined'));
        });
    </script>
</body>
</html>