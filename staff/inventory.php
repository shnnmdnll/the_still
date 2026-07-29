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

$stmt = $pdo->prepare("SELECT assigned_unit_id FROM users WHERE id = :id");
$stmt->execute([':id' => $staffId]);
$unitId = $stmt->fetchColumn();

$supplies = [];
$appliances = [];
$suppliesBySubcat = [];
$appliancesBySubcat = [];

if ($unitId) {
    $suppliesStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE unit_id = :unit_id AND category = 'supply' ORDER BY subcategory, item_name");
    $suppliesStmt->execute([':unit_id' => $unitId]);
    $supplies = $suppliesStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($supplies as $item) {
        $subcat = $item['subcategory'] ?: 'Other';
        $suppliesBySubcat[$subcat][] = $item;
    }

    $appliancesStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE unit_id = :unit_id AND category = 'appliance' ORDER BY subcategory, item_name");
    $appliancesStmt->execute([':unit_id' => $unitId]);
    $appliances = $appliancesStmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== AI PREDICTIVE MAINTENANCE: kalkulahin ang predicted next maintenance date =====
    foreach ($appliances as &$item) {
        $logStmt = $pdo->prepare("SELECT serviced_at FROM inventory_maintenance_log WHERE item_id = :id ORDER BY serviced_at ASC");
        $logStmt->execute([':id' => $item['id']]);
        $logs = $logStmt->fetchAll(PDO::FETCH_COLUMN);

        $avgInterval = 30;
        if (count($logs) >= 2) {
            $totalDays = 0;
            $count = 0;
            for ($i = 1; $i < count($logs); $i++) {
                $diff = (strtotime($logs[$i]) - strtotime($logs[$i - 1])) / 86400;
                $totalDays += $diff;
                $count++;
            }
            if ($count > 0) {
                $avgInterval = round($totalDays / $count);
            }
        }

        $lastMaintained = $item['last_maintained_date'] ?? date('Y-m-d');
        $predictedNext = date('Y-m-d', strtotime($lastMaintained . " +$avgInterval days"));
        $daysUntilDue = (strtotime($predictedNext) - strtotime(date('Y-m-d'))) / 86400;

        $item['predicted_next'] = $predictedNext;
        $item['days_until_due'] = (int)$daysUntilDue;
        $item['avg_interval'] = $avgInterval;
        $item['history_count'] = count($logs);
    }
    unset($item);

    foreach ($appliances as $item) {
        $subcat = $item['subcategory'] ?: 'Other';
        $appliancesBySubcat[$subcat][] = $item;
    }
}

function invStatusColor($status) {
    switch ($status) {
        case 'available':    return ['#e6f2e0', '#3c6b41'];
        case 'low':          return ['#fdf3d9', '#8a6d1a'];
        case 'out':          return ['#fbe4e1', '#c0392b'];
        case 'needs_repair': return ['#eae5f2', '#5a3c8a'];
        default:             return ['#f0ece0', '#6b6350'];
    }
}

// AI-driven na status para sa Appliances — base sa predicted maintenance timeline
function aiAppliancesStatus($daysUntilDue) {
    if ($daysUntilDue < 0) {
        return ['Needs Repair', '#fbe4e1', '#c0392b'];
    } elseif ($daysUntilDue <= 7) {
        return ['Check Needed Soon', '#fdf3d9', '#8a6d1a'];
    } else {
        return ['Working', '#e6f2e0', '#3c6b41'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory — Staff Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/owner_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_bookings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/owner_settings.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/css/staff_inventory.css?v=<?php echo time(); ?>">
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
                <a href="inventory.php" class="nav-item active">
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
                    <h1>Inventory</h1>
                    <p>Track supplies and appliances for your unit</p>
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
                <?php if (!$unitId): ?>
                    <div class="empty-state">Wala ka pang naka-assign na unit.</div>
                <?php else: ?>
                    <div class="inv-tabs">
                        <button type="button" class="inv-tab active" data-tab="supplies">Supplies (<?php echo count($supplies); ?>)</button>
                        <button type="button" class="inv-tab" data-tab="appliances">Appliances (<?php echo count($appliances); ?>)</button>
                    </div>

                    <!-- ===== SUPPLIES TAB ===== -->
                    <div class="inv-panel" id="panel-supplies">
                        <div class="listings-toolbar">
                            <span class="listings-count"></span>
                            <button type="button" class="btn-filter open-add-item" data-category="supply">+ Add Supply Item</button>
                        </div>
                        <?php if (count($supplies) === 0): ?>
                            <div class="empty-state">Wala pang supply items. I-click ang "+ Add Supply Item" para magsimula.</div>
                        <?php else: ?>
                            <?php foreach ($suppliesBySubcat as $subcatName => $items): ?>
                                <h3 class="inv-subcat-heading"><?php echo htmlspecialchars($subcatName); ?></h3>
                                <div class="inv-grid">
                                    <?php foreach ($items as $item):
                                        [$bg, $fg] = invStatusColor($item['status']);
                                    ?>
                                        <div class="inv-card">
                                            <div class="inv-card-top">
                                                <div class="inv-card-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <button type="button" class="inv-delete-btn btn-delete-item" data-id="<?php echo $item['id']; ?>">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;"><?php echo str_replace('_', ' ', $item['status']); ?></span>
                                            <div class="inv-qty-row">
                                                <label>Quantity</label>
                                                <div class="inv-qty-controls">
                                                    <button type="button" class="qty-btn qty-minus" data-id="<?php echo $item['id']; ?>">−</button>
                                                    <input type="number" class="qty-input" data-id="<?php echo $item['id']; ?>" value="<?php echo (int)($item['quantity'] ?? 0); ?>" min="0">
                                                    <button type="button" class="qty-btn qty-plus" data-id="<?php echo $item['id']; ?>">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ===== APPLIANCES TAB ===== -->
                    <div class="inv-panel" id="panel-appliances" style="display:none;">
                        <div class="listings-toolbar">
                            <span class="listings-count"></span>
                            <button type="button" class="btn-filter open-add-item" data-category="appliance">+ Add Appliance</button>
                        </div>
                        <?php if (count($appliances) === 0): ?>
                            <div class="empty-state">Wala pang appliances. I-click ang "+ Add Appliance" para magsimula.</div>
                        <?php else: ?>
                            <?php foreach ($appliancesBySubcat as $subcatName => $items): ?>
                                <h3 class="inv-subcat-heading"><?php echo htmlspecialchars($subcatName); ?></h3>
                                <div class="inv-grid">
                                    <?php foreach ($items as $item):
                                        [$aiLabel, $bg, $fg] = aiAppliancesStatus($item['days_until_due']);
                                    ?>
                                        <div class="inv-card">
                                            <div class="inv-card-top">
                                                <div class="inv-card-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <button type="button" class="inv-delete-btn btn-delete-item" data-id="<?php echo $item['id']; ?>">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;"><?php echo htmlspecialchars($aiLabel); ?></span>

                                            <?php if ($item['days_until_due'] <= 7): ?>
                                                <div class="ai-alert-badge">
                                                    ⚠ AI Prediction: Maintenance <?php echo $item['days_until_due'] < 0 ? 'Overdue by ' . abs($item['days_until_due']) . ' day(s)' : 'Due in ' . $item['days_until_due'] . ' day(s)'; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="ai-info-text">Next maintenance: <?php echo date('M j, Y', strtotime($item['predicted_next'])); ?></div>
                                            <?php endif; ?>

                                            <button type="button" class="btn-mark-maintained" data-id="<?php echo $item['id']; ?>">✓ Mark as Maintained</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ===== ADD ITEM MODAL ===== -->
    <div id="addItemModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button type="button" class="modal-close" id="itemModalCloseBtn">&times;</button>
            <h2 style="margin-bottom:16px;">Add Item</h2>
            <form id="addItemForm">
                <input type="hidden" id="itemCategory">
                <div class="form-row">
                    <label>Item Name</label>
                    <input type="text" id="itemName" required>
                </div>
                <div class="form-row" id="subcategoryFieldRow">
                    <label>Subcategory</label>
                    <select id="itemSubcategory"></select>
                </div>
                <div class="form-row" id="qtyFieldRow">
                    <label>Starting Quantity</label>
                    <input type="number" id="itemQuantity" min="0" value="0">
                </div>
                <button type="submit" class="btn-save">Add Item</button>
            </form>
        </div>
    </div>

    <script>
        // Tab switching (may "memory" gamit ang sessionStorage, para hindi mabura ang active tab sa reload)
        function activateTab(tabName) {
            document.querySelectorAll('.inv-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.inv-tab[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById('panel-supplies').style.display = tabName === 'supplies' ? 'block' : 'none';
            document.getElementById('panel-appliances').style.display = tabName === 'appliances' ? 'block' : 'none';
            sessionStorage.setItem('inventoryActiveTab', tabName);
        }

        document.querySelectorAll('.inv-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                activateTab(tab.dataset.tab);
            });
        });

        // I-restore ang huling active tab pagka-load ng page
        const savedTab = sessionStorage.getItem('inventoryActiveTab');
        if (savedTab) {
            activateTab(savedTab);
        }

        // Quantity update (para sa Supplies)
        let qtyRequestInProgress = {};

        const statusLabels = {
            'available': 'Available',
            'low': 'Low',
            'out': 'out of stock'
        };
        const statusColors = {
            'available': ['#e6f2e0', '#3c6b41'],
            'low': ['#fdf3d9', '#8a6d1a'],
            'out': ['#fbe4e1', '#c0392b']
        };

        function updateQuantity(itemId, newQty) {
            if (newQty < 0) newQty = 0;
            if (qtyRequestInProgress[itemId]) return;
            qtyRequestInProgress[itemId] = true;

            const input = document.querySelector(`.qty-input[data-id="${itemId}"]`);
            if (input) input.value = newQty;

            fetch('../api/update_inventory_quantity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ item_id: itemId, quantity: newQty })
            })
            .then(res => res.json())
            .then(data => {
                qtyRequestInProgress[itemId] = false;

                if (data.success) {
                    const card = input.closest('.inv-card');
                    const badge = card.querySelector('.status-badge');
                    const [bg, fg] = statusColors[data.new_status] || statusColors['available'];
                    badge.style.background = bg;
                    badge.style.color = fg;
                    badge.textContent = statusLabels[data.new_status] || data.new_status;
                } else {
                    alert(data.error || 'Something went wrong.');
                }
            })
            .catch(() => {
                qtyRequestInProgress[itemId] = false;
            });
        }

        document.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.querySelector(`.qty-input[data-id="${btn.dataset.id}"]`);
                updateQuantity(btn.dataset.id, parseInt(input.value) + 1);
            });
        });

        document.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.querySelector(`.qty-input[data-id="${btn.dataset.id}"]`);
                updateQuantity(btn.dataset.id, parseInt(input.value) - 1);
            });
        });

        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', () => {
                updateQuantity(input.dataset.id, parseInt(input.value) || 0);
            });
        });

        // Mark as Maintained (AI predictive maintenance reset)
        document.querySelectorAll('.btn-mark-maintained').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('Mark this appliance as maintained today?')) return;

                fetch('../api/mark_appliance_maintained.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ item_id: btn.dataset.id })
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
            });
        });

        // Add item modal
        const addItemModal = document.getElementById('addItemModal');
        const subcategoryOptions = {
            supply: ['Toiletries', 'Linens', 'Kitchen/Dining', 'Cleaning', 'Other'],
            appliance: ['Cooling/Comfort', 'Kitchen', 'Entertainment', 'Bathroom', 'Other'],
        };

        document.querySelectorAll('.open-add-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                document.getElementById('itemCategory').value = category;

                const select = document.getElementById('itemSubcategory');
                select.innerHTML = '';
                subcategoryOptions[category].forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt;
                    option.textContent = opt;
                    select.appendChild(option);
                });

                document.getElementById('qtyFieldRow').style.display = category === 'supply' ? 'block' : 'none';
                addItemModal.style.display = 'flex';
            });
        });
        document.getElementById('itemModalCloseBtn').addEventListener('click', () => {
            addItemModal.style.display = 'none';
        });
        addItemModal.addEventListener('click', (e) => {
            if (e.target === addItemModal) addItemModal.style.display = 'none';
        });

        document.getElementById('addItemForm').addEventListener('submit', (e) => {
            e.preventDefault();
            fetch('../api/add_inventory_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    item_name: document.getElementById('itemName').value,
                    category: document.getElementById('itemCategory').value,
                    subcategory: document.getElementById('itemSubcategory').value,
                    quantity: parseInt(document.getElementById('itemQuantity').value || 0),
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

        // Delete item
        document.querySelectorAll('.btn-delete-item').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('Are you sure you want to delete this item?')) return;

                fetch('../api/delete_inventory_item.php?id=' + btn.dataset.id, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Unable to delete item.');
                    }
                })
                .catch(() => alert('Something went wrong. Please try again.'));
            });
        });
    </script>
</body>
</html>