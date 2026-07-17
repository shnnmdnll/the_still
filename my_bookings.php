<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT b.id, b.check_in, b.check_out, b.guest_count, b.total_price, b.status,
           p.id AS property_id, p.name, p.location, p.image_url
    FROM bookings b
    JOIN properties p ON p.id = b.property_id
    WHERE b.user_id = ?
    ORDER BY b.check_in DESC
");
$stmt->execute([$user_id]);
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
<title>My Bookings — Pahingahan</title>
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
    max-width: 720px;
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
  .booking-card {
    display: flex;
    gap: 16px;
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
  }
  .booking-img {
    width: 110px;
    height: 90px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f0ece0;
  }
  .booking-body { flex: 1; }
  .booking-title {
    font-weight: 700;
    font-size: 1.05rem;
    margin: 0 0 4px;
  }
  .booking-location {
    color: #8a8266;
    font-size: .85rem;
    margin: 0 0 10px;
  }
  .booking-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    font-size: .85rem;
    color: #4a4536;
  }
  .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    text-transform: capitalize;
    margin-top: 8px;
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
    <h1>📅 My Bookings</h1>
    <a href="homepage.php#top" class="btn-back">← Back to Home</a>
  </div>

  <?php if (count($bookings) === 0): ?>
    <div class="empty-state">
      You don't have any bookings yet. Once you book a stay, it'll show up here.
    </div>
  <?php else: ?>
    <?php foreach ($bookings as $b):
        [$bg, $fg] = statusColor($b['status']);
        $nights = (strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400;
    ?>
      <div class="booking-card">
        <?php if (!empty($b['image_url'])): ?>
          <img src="<?php echo htmlspecialchars($b['image_url']); ?>" class="booking-img" alt="">
        <?php else: ?>
          <div class="booking-img"></div>
        <?php endif; ?>
        <div class="booking-body">
          <p class="booking-title"><?php echo htmlspecialchars($b['name']); ?></p>
          <p class="booking-location">📍 <?php echo htmlspecialchars($b['location']); ?></p>
          <div class="booking-meta">
            <span>🗓 <?php echo htmlspecialchars($b['check_in']); ?> → <?php echo htmlspecialchars($b['check_out']); ?></span>
            <span>🌙 <?php echo (int)$nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?></span>
            <span>👥 <?php echo (int)$b['guest_count']; ?> guest<?php echo $b['guest_count'] != 1 ? 's' : ''; ?></span>
            <span>₱<?php echo number_format($b['total_price'], 2); ?></span>
          </div>
          <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
            <?php echo htmlspecialchars($b['status']); ?>
          </span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>