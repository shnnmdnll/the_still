<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$userId = $_SESSION['user_id'];
$bookingId = intval($_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: stay_history.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT b.id, b.check_in, b.check_out, un.name, un.location, un.image_url
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    WHERE b.id = :booking_id AND b.user_id = :user_id
      AND (b.status = 'completed' OR b.check_out < CURRENT_DATE)
    LIMIT 1
");
$stmt->execute([':booking_id' => $bookingId, ':user_id' => $userId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: stay_history.php');
    exit();
}

// I-check kung na-review na
$dupStmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = :booking_id");
$dupStmt->execute([':booking_id' => $bookingId]);
if ($dupStmt->fetch()) {
    header('Location: stay_history.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review — Pahingahan</title>
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
            max-width: 480px;
            margin: 0 auto;
        }
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #3c6b41;
            text-decoration: none;
            font-weight: 600;
            font-size: .85rem;
        }
        .review-box {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        .stay-summary {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0ece0;
        }
        .stay-img {
            width: 70px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0ece0;
            flex-shrink: 0;
        }
        .stay-name {
            font-weight: 700;
            font-size: 1rem;
            margin: 0 0 4px;
        }
        .stay-dates {
            color: #8a8266;
            font-size: .82rem;
        }
        h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            color: #3c6b41;
            margin: 0 0 20px;
        }
        .star-rating {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 24px;
        }
        .star {
            font-size: 2.4rem;
            color: #e2ddc9;
            cursor: pointer;
            transition: color 0.15s ease;
            user-select: none;
        }
        .star.active {
            color: #c98a1f;
        }
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2ddc9;
            border-radius: 10px;
            font-family: inherit;
            font-size: .95rem;
            min-height: 120px;
            resize: vertical;
        }
        textarea:focus {
            outline: none;
            border-color: #5c8a3a;
        }
        button[type="submit"] {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #5c8a3a;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background: #4a7130;
        }
        button[type="submit"]:disabled {
            background: #cfc9b0;
            cursor: not-allowed;
        }
        .rating-label {
            text-align: center;
            font-size: .85rem;
            color: #8a8266;
            margin-bottom: 20px;
            min-height: 18px;
        }
    </style>
</head>
<body>

<div class="page">
    <a href="stay_history.php" class="btn-back">← Bumalik sa Stay History</a>

    <div class="review-box">
        <div class="stay-summary">
            <?php if (!empty($booking['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" class="stay-img" alt="">
            <?php else: ?>
                <div class="stay-img"></div>
            <?php endif; ?>
            <div>
                <p class="stay-name"><?php echo htmlspecialchars($booking['name']); ?></p>
                <p class="stay-dates">📍 <?php echo htmlspecialchars($booking['location']); ?></p>
                <p class="stay-dates"><?php echo htmlspecialchars($booking['check_in']); ?> → <?php echo htmlspecialchars($booking['check_out']); ?></p>
            </div>
        </div>

        <h2>⭐ Kumusta ang stay mo?</h2>

        <form id="reviewForm">
            <div class="star-rating" id="starRating">
                <span class="star" data-value="1">★</span>
                <span class="star" data-value="2">★</span>
                <span class="star" data-value="3">★</span>
                <span class="star" data-value="4">★</span>
                <span class="star" data-value="5">★</span>
            </div>
            <div class="rating-label" id="ratingLabel"></div>

            <textarea id="reviewComment" placeholder="Ikwento mo ang karanasan mo sa unit na ito... (optional)"></textarea>

            <button type="submit" id="submitBtn" disabled>Submit Review</button>
        </form>
    </div>
</div>

<script>
    let selectedRating = 0;
    const stars = document.querySelectorAll('.star');
    const ratingLabel = document.getElementById('ratingLabel');
    const submitBtn = document.getElementById('submitBtn');
    const labels = { 1: 'Hindi maganda', 2: 'Pwede na', 3: 'Okay lang', 4: 'Maganda', 5: 'Sobrang ganda!' };

    function paintStars(value) {
        stars.forEach(star => {
            star.classList.toggle('active', Number(star.dataset.value) <= value);
        });
    }

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => paintStars(Number(star.dataset.value)));
        star.addEventListener('mouseleave', () => paintStars(selectedRating));
        star.addEventListener('click', () => {
            selectedRating = Number(star.dataset.value);
            paintStars(selectedRating);
            ratingLabel.textContent = labels[selectedRating];
            submitBtn.disabled = false;
        });
    });

    document.getElementById('reviewForm').addEventListener('submit', (e) => {
        e.preventDefault();

        if (selectedRating === 0) {
            alert('Pumili muna ng rating.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('api/submit_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                booking_id: <?php echo $bookingId; ?>,
                rating: selectedRating,
                comment: document.getElementById('reviewComment').value,
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = 'stay_history.php';
            } else {
                alert(data.error || 'Something went wrong.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
            }
        })
        .catch(() => {
            alert('Something went wrong. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        });
    });
</script>

</body>
</html>