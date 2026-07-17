<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

// HARD-CODED MUNA: si Host/Owner ang gumagamit (User ID = 1).
// Sa future (multi-account login/roles), papalitan ito ng: $_SESSION['user_id']
$hostId = 1;

$stmt = $pdo->prepare("
    SELECT b.id, b.check_in, b.check_out, b.guest_count, b.total_price, b.status,
           p.id AS property_id, p.name AS property_name,
           u.name AS guest_name, u.email AS guest_email
    FROM bookings b
    JOIN properties p ON p.id = b.property_id
    JOIN users u ON u.id = b.user_id
    WHERE p.user_id = ?
    ORDER BY b.check_in DESC
");
$stmt->execute([$hostId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function statusColor($status) {
    switch ($status) {
        case 'confirmed': return ['#e6f2e0', '#3c6b41'];
        case 'pending':   return ['#fdf3d9', '#8a6d1a'];
        case 'cancelled': return ['#fbe4e1', '#c0392b'];
        default:          return ['#f0ece0', '#6b6350'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Management — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', sans-serif;
    background: #f7f0d8;
    color: #2f2a20;
    margin: 0;
    padding: 40px 20px;
  }
  .page {
    max-width: 820px;
    margin: 0 auto;
  }
  .page-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
  }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.5rem;
    margin: 0;
    color: #3c6b41;
  }
  .btn-back {
    padding: 10px 18px;
    border-radius: 10px;
    background: #fff;
    color: #3c6b41;
    text-decoration: none;
    font-weight: 600;
    font-size: .85rem;
    border: 1px solid #e2ddc9;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
  }
  th, td {
    padding: 14px 16px;
    text-align: left;
    font-size: .88rem;
  }
  th {
    background: #f2ede0;
    color: #4a4536;
    font-weight: 700;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .03em;
  }
  tbody tr:not(:last-child) td {
    border-bottom: 1px solid #f0ece0;
  }
  .guest-name { font-weight: 600; }
  .guest-email { color: #8a8266; font-size: .8rem; }
  .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    text-transform: capitalize;
  }
  .empty-state {
    background: #fff;
    border-radius: 14px;
    padding: 40px 20px;
    text-align: center;
    color: #8a8266;
  }
</style>
</head>
<body>

<div class="page">
  <div class="page-head">
    <h1>📋 Booking Management</h1>
    <a href="hosting.php" class="btn-back">← Back to Hosting</a>
  </div>

  <?php if (count($bookings) === 0): ?>
    <div class="empty-state">
      Wala pang bookings sa mga property mo.
    </div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Guest</th>
          <th>Property</th>
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
              <div class="guest-name"><?php echo htmlspecialchars($b['guest_name']); ?></div>
              <div class="guest-email"><?php echo htmlspecialchars($b['guest_email']); ?></div>
            </td>
            <td><?php echo htmlspecialchars($b['property_name']); ?></td>
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
  <?php endif; ?>
</div>

</body>
</html>
