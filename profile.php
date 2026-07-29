<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT name, email, contact_number, role, avatar_url FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

$ratingStmt = $pdo->prepare("
    SELECT ROUND(AVG(rating)::numeric, 2) AS average_rating, COUNT(*) AS total_ratings
    FROM host_guest_ratings
    WHERE ratee_id = :id
");
$ratingStmt->execute([':id' => $userId]);
$ratingSummary = $ratingStmt->fetch(PDO::FETCH_ASSOC);

$individualRatingsStmt = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, u.name AS rater_name
    FROM host_guest_ratings r
    JOIN users u ON u.id = r.rater_id
    WHERE r.ratee_id = :id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$individualRatingsStmt->execute([':id' => $userId]);
$individualRatings = $individualRatingsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Pahingahan</title>
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
  .page { max-width: 860px; margin: 0 auto; }

  .profile-container {
    background: #fff;
    border-radius: 16px;
    padding: 36px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    margin-bottom: 24px;
  }
  .avatar-wrap {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto 18px;
  }
  .avatar {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: #5c8a3a;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 3.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }
  .avatar img { width: 100%; height: 100%; object-fit: cover; }
  .avatar-edit-btn {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #3c6b41;
    color: #fff;
    border: 3px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.05rem;
  }
  .avatar-edit-btn:hover { background: #2e5232; }
  #avatarInput { display: none; }

  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    margin: 0 0 4px;
    color: #3c6b41;
  }
  .email { color: #8a8266; font-size: .9rem; margin: 0 0 4px; }
  .contact { color: #8a8266; font-size: .85rem; margin: 0 0 20px; }
  .role-badge {
    display: inline-block;
    background: #e9f0dc;
    color: #3c6b41;
    font-size: .75rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: 20px;
  }

  .rating-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fdf3d9;
    color: #8a6d1a;
    font-size: .9rem;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 999px;
    margin-bottom: 8px;
  }
  .rating-empty { color: #8a8266; font-size: .85rem; margin-bottom: 8px; }

  .btn-back {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 10px;
    background: #f2ede0;
    color: #3c6b41;
    text-decoration: none;
    font-weight: 600;
    font-size: .9rem;
    margin-top: 12px;
  }

  .section-box {
    background: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    margin-bottom: 24px;
  }
  .section-box h2 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.15rem;
    color: #3c6b41;
    margin: 0 0 18px;
  }

  /* 2-column layout: Timeline sa left, Ratings sa right */
  .two-col-row {
    display: flex;
    gap: 24px;
    align-items: flex-start;
  }
  .two-col-row .section-box {
    flex: 1;
    min-width: 0;
  }
  @media (max-width: 720px) {
    .two-col-row {
      flex-direction: column;
    }
  }

  /* Ratings list */
  .rating-item {
    padding: 14px 0;
    border-bottom: 1px solid #f0ece0;
  }
  .rating-item:last-child { border-bottom: none; }
  .rating-item-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
  }
  .rating-stars { color: #c98a1f; font-size: .95rem; }
  .rating-from { font-size: .82rem; color: #8a8266; }
  .rating-comment { font-size: .88rem; color: #4a4536; }
  .rating-date { font-size: .75rem; color: #a39d87; margin-top: 4px; }

  /* Timeline */
  .timeline-item {
    display: flex;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f0ece0;
  }
  .timeline-item:last-child { border-bottom: none; }
  .timeline-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f7f0d8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
  }
  .timeline-title { font-size: .9rem; font-weight: 600; color: #2f2a20; }
  .timeline-detail { font-size: .82rem; color: #6b6350; margin-top: 2px; }
  .timeline-date { font-size: .72rem; color: #a39d87; margin-top: 4px; }
  .timeline-empty { text-align: center; color: #8a8266; font-size: .88rem; padding: 20px 0; }
</style>
</head>
<body>

<div class="page">
  <div class="profile-container">
    <div class="avatar-wrap">
      <div class="avatar" id="avatarDisplay">
        <?php if (!empty($profileUser['avatar_url'])): ?>
          <img src="<?php echo htmlspecialchars($profileUser['avatar_url']); ?>" alt="Profile picture">
        <?php else: ?>
          <?php echo htmlspecialchars(strtoupper(substr($currentUserName, 0, 1))); ?>
        <?php endif; ?>
      </div>
      <label for="avatarInput" class="avatar-edit-btn" title="Change profile picture">✎</label>
      <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp">
    </div>

    <h1><?php echo htmlspecialchars($profileUser['name']); ?></h1>
    <p class="email"><?php echo htmlspecialchars($profileUser['email']); ?></p>
    <?php if (!empty($profileUser['contact_number'])): ?>
      <p class="contact">📞 <?php echo htmlspecialchars($profileUser['contact_number']); ?></p>
    <?php endif; ?>
    <div class="role-badge"><?php echo htmlspecialchars($profileUser['role']); ?></div>

    <div>
      <?php if ($ratingSummary && $ratingSummary['average_rating'] !== null): ?>
        <div class="rating-badge">⭐ <?php echo htmlspecialchars($ratingSummary['average_rating']); ?> (<?php echo (int) $ratingSummary['total_ratings']; ?> rating<?php echo $ratingSummary['total_ratings'] == 1 ? '' : 's'; ?>)</div>
      <?php else: ?>
        <div class="rating-empty">No ratings yet</div>
      <?php endif; ?>
    </div>

    <div>
      <a href="homepage.php#top" class="btn-back">← Back to Home</a>
    </div>
  </div>

  <div class="two-col-row">
    <div class="section-box">
      <h2>🕐 Activity Timeline</h2>
      <div id="timelineList">
        <div class="timeline-empty">Loading...</div>
      </div>
    </div>

    <div class="section-box">
      <h2>⭐ Ratings from Hosts</h2>
      <?php if (empty($individualRatings)): ?>
        <div class="timeline-empty">You haven't received any ratings yet.</div>
      <?php else: ?>
        <?php foreach ($individualRatings as $r): ?>
          <div class="rating-item">
            <div class="rating-item-head">
              <span class="rating-stars"><?php echo str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']); ?></span>
              <span class="rating-from">from <?php echo htmlspecialchars($r['rater_name']); ?></span>
            </div>
            <?php if (!empty($r['comment'])): ?>
              <div class="rating-comment"><?php echo htmlspecialchars($r['comment']); ?></div>
            <?php endif; ?>
            <div class="rating-date"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  // ===== Change profile picture =====
  document.getElementById('avatarInput').addEventListener('change', function() {
    if (!this.files[0]) return;

    const formData = new FormData();
    formData.append('avatar', this.files[0]);

    fetch('api/upload_avatar.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('avatarDisplay').innerHTML = `<img src="${data.avatar_url}?t=${Date.now()}" alt="Profile picture">`;
      } else {
        alert(data.error || 'Failed to update profile picture.');
      }
    })
    .catch(() => {
      alert('Something went wrong. Please try again.');
    });
  });

  // ===== Load activity timeline =====
  fetch('api/get_profile_timeline.php', { credentials: 'same-origin' })
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('timelineList');
      if (!data.success || !data.events || data.events.length === 0) {
        list.innerHTML = '<div class="timeline-empty">No activity yet.</div>';
        return;
      }
      list.innerHTML = data.events.map(e => `
        <div class="timeline-item">
          <div class="timeline-icon">${e.icon}</div>
          <div>
            <div class="timeline-title">${e.title}</div>
            <div class="timeline-detail">${e.detail}</div>
            <div class="timeline-date">${new Date(e.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</div>
          </div>
        </div>
      `).join('');
    })
    .catch(() => {
      document.getElementById('timelineList').innerHTML = '<div class="timeline-empty">Unable to load activity right now.</div>';
    });
</script>

</body>
</html>