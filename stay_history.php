<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, un.name, un.location, un.image_url
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    WHERE b.user_id = :user_id
      AND (b.status = 'completed' OR b.check_out < CURRENT_DATE)
    ORDER BY b.check_out DESC
");
$stmt->execute([':user_id' => $userId]);
$pastStays = $stmt->fetchAll(PDO::FETCH_ASSOC);

// I-check kung anong stays ang meron nang review
$reviewedUnitIds = [];
$reviewStmt = $pdo->prepare("SELECT booking_id FROM reviews WHERE user_id = :user_id");
$reviewStmt->execute([':user_id' => $userId]);
$reviewedBookingIds = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stay History — Pahingahan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            max-width: 800px;
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
            font-size: 1.6rem;
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
        .stay-card {
            display: flex;
            gap: 16px;
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
        }
        .stay-img {
            width: 120px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0ece0;
        }
        .stay-body { flex: 1; }
        .stay-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 0 0 4px;
        }
        .stay-location {
            color: #8a8266;
            font-size: .85rem;
            margin: 0 0 10px;
        }
        .stay-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: .85rem;
            color: #4a4536;
            margin-bottom: 10px;
        }
        .btn-review {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 8px;
            background: #5c8a3a;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: .82rem;
        }
        .btn-review:hover {
            background: #4a7130;
        }
        .reviewed-note {
            color: #3c6b41;
            font-size: .82rem;
            font-weight: 600;
        }
        .empty-state {
            background: #fff;
            border-radius: 14px;
            padding: 60px 20px;
            text-align: center;
            color: #8a8266;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="page-head">
        <h1>🕰️ Stay History</h1>
        <a href="homepage.php#top" class="btn-back">← Back to Home</a>
    </div>

    <?php if (count($pastStays) === 0): ?>
        <div class="empty-state">
            Wala ka pang natapos na stay. Kapag natapos na ang isang booking mo, lalabas ito dito.
        </div>
    <?php else: ?>
        <?php foreach ($pastStays as $s):
            $nights = (strtotime($s['check_out']) - strtotime($s['check_in'])) / 86400;
            $alreadyReviewed = in_array($s['id'], $reviewedBookingIds);
        ?>
            <div class="stay-card">
                <?php if (!empty($s['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($s['image_url']); ?>" class="stay-img" alt="">
                <?php else: ?>
                    <div class="stay-img"></div>
                <?php endif; ?>
                <div class="stay-body">
                    <p class="stay-title"><?php echo htmlspecialchars($s['name']); ?></p>
                    <p class="stay-location">📍 <?php echo htmlspecialchars($s['location']); ?></p>
                    <div class="stay-meta">
                        <span>🗓 <?php echo htmlspecialchars($s['check_in']); ?> → <?php echo htmlspecialchars($s['check_out']); ?></span>
                        <span>🌙 <?php echo (int)$nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?></span>
                        <span>₱<?php echo number_format($s['total_price'], 2); ?></span>
                    </div>
                    <?php if ($alreadyReviewed): ?>
                        <span class="reviewed-note">✓ Na-review mo na ang stay na ito</span>
                    <?php else: ?>
                        <a href="submit_review.php?booking_id=<?php echo $s['id']; ?>" class="btn-review">⭐ Mag-leave ng Review</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>