<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT un.*, f.created_at AS favorited_at
    FROM favorites f
    JOIN units un ON un.id = f.unit_id
    WHERE f.user_id = :user_id
    ORDER BY f.created_at DESC
");
$stmt->execute([':user_id' => $userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites — Pahingahan</title>
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
            max-width: 1100px;
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
        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .fav-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
            cursor: pointer;
            transition: transform .15s ease;
            position: relative;
        }
        .fav-card:hover {
            transform: translateY(-3px);
        }
        .fav-image {
            height: 180px;
            background: #f0ece0;
            position: relative;
        }
        .fav-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .fav-heart-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,.9);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
        }
        .fav-body {
            padding: 16px;
        }
        .fav-name {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 0 0 4px;
        }
        .fav-location {
            color: #8a8266;
            font-size: .85rem;
            margin: 0 0 10px;
        }
        .fav-price {
            font-weight: 700;
            color: #3c6b41;
            font-size: 1rem;
        }
        .fav-price span {
            font-weight: 400;
            color: #8a8266;
            font-size: .85rem;
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
        <h1>❤️ My Favorites</h1>
        <a href="homepage.php#top" class="btn-back">← Back to Home</a>
    </div>

    <?php if (count($favorites) === 0): ?>
        <div class="empty-state">
            Wala ka pang na-save na units. I-explore ang mga staycation at i-click ang ❤️ icon para i-save dito.
        </div>
    <?php else: ?>
        <div class="fav-grid">
            <?php foreach ($favorites as $u): ?>
                <div class="fav-card" onclick="window.location.href='property-detail.php?id=<?php echo $u['id']; ?>'">
                    <div class="fav-image">
                        <?php if (!empty($u['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($u['image_url']); ?>" alt="">
                        <?php endif; ?>
                        <button type="button" class="fav-heart-btn remove-fav-btn" data-id="<?php echo $u['id']; ?>" onclick="event.stopPropagation();">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#c0392b" stroke="#c0392b" stroke-width="2"><path d="M20.8 4.6c-1.9-1.9-5-1.9-6.9 0L12 6.5l-1.9-1.9c-1.9-1.9-5-1.9-6.9 0-1.9 1.9-1.9 5 0 6.9L12 20.3l8.8-8.8c1.9-1.9 1.9-5 0-6.9z"/></svg>
                        </button>
                    </div>
                    <div class="fav-body">
                        <p class="fav-name"><?php echo htmlspecialchars($u['name']); ?></p>
                        <p class="fav-location">📍 <?php echo htmlspecialchars($u['location']); ?></p>
                        <p class="fav-price">₱<?php echo number_format($u['price'], 2); ?> <span>/ night</span></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.remove-fav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Alisin sa favorites?')) return;

        fetch('api/toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ unit_id: btn.dataset.id })
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
</script>

</body>
</html>