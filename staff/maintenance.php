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

$stmt = $pdo->prepare("
    SELECT mr.*, un.name AS unit_name, h.name AS host_name
    FROM maintenance_reports mr
    JOIN units un ON un.id = mr.unit_id
    JOIN users h ON h.id = un.host_id
    WHERE mr.staff_id = :staff_id
    ORDER BY mr.reported_at DESC
");
$stmt->execute([':staff_id' => $staffId]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = 0;
$inProgressCount = 0;
$resolvedCount = 0;
foreach ($reports as $r) {
    if ($r['status'] === 'pending') $pendingCount++;
    elseif ($r['status'] === 'in_progress') $inProgressCount++;
    elseif ($r['status'] === 'resolved') $resolvedCount++;
}

$hostStmt = $pdo->prepare("
    SELECT h.name AS host_name, un.name AS unit_name
    FROM users s
    JOIN units un ON un.id = s.assigned_unit_id
    JOIN users h ON h.id = un.host_id
    WHERE s.id = :staff_id
");
$hostStmt->execute([':staff_id' => $staffId]);
$hostInfo = $hostStmt->fetch(PDO::FETCH_ASSOC);
$assignedHostName = $hostInfo['host_name'] ?? 'No host assigned';

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
    <title>Maintenance Reports — Staff Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_settings.css?v=<?php echo time(); ?>">
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
                <a href="maintenance.php" class="nav-item active">
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
                    <h1>Maintenance Reports</h1>
                    <p>Report issues in your assigned unit</p>
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

                <div class="listings-toolbar">
                    <span class="listings-count"></span>
                    <button type="button" class="btn-filter" id="openReportModalBtn">+ Report an Issue</button>
                </div>

                <?php if (count($reports) === 0): ?>
                    <div class="empty-state">Wala ka pang na-submit na reports.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th>Sent To</th>
                                    <th>Reported</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r):
                                    [$bg, $fg] = maintStatusColor($r['status']);
                                ?>
                                    <tr>
                                        <td><div class="cell-primary"><?php echo htmlspecialchars($r['unit_name']); ?></div></td>
                                        <td><?php echo htmlspecialchars($r['description']); ?></td>
                                        <td><?php echo htmlspecialchars($r['host_name']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($r['reported_at'])); ?></td>
                                        <td>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                                <?php echo str_replace('_', ' ', $r['status']); ?>
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

    <!-- ===== REPORT MODAL ===== -->
    <div id="reportModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button type="button" class="modal-close" id="reportModalCloseBtn">&times;</button>
            <h2 style="margin-bottom:16px;">Report an Issue</h2>
            <form id="reportForm">
                <div class="form-row">
                    <label>Send To</label>
                    <select disabled style="background:#f4f1e8; color:var(--color-text-muted);">
                        <option><?php echo htmlspecialchars($assignedHostName); ?></option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Description</label>
                    <textarea id="reportDescription" rows="4" placeholder="Ilarawan ang isyu (hal. 'Sirang aircon sa Unit 2, hindi na lumalamig')" required></textarea>
                </div>
                <button type="submit" class="btn-save">Submit Report</button>
            </form>
        </div>
    </div>

    <script>
        const reportModal = document.getElementById('reportModal');
        document.getElementById('openReportModalBtn').addEventListener('click', () => {
            reportModal.style.display = 'flex';
        });
        document.getElementById('reportModalCloseBtn').addEventListener('click', () => {
            reportModal.style.display = 'none';
        });
        reportModal.addEventListener('click', (e) => {
            if (e.target === reportModal) reportModal.style.display = 'none';
        });

        document.getElementById('reportForm').addEventListener('submit', (e) => {
            e.preventDefault();

            fetch('../api/submit_maintenance_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    description: document.getElementById('reportDescription').value,
                })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.error || 'Something went wrong.');
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
        });
    </script>
</body>
</html>