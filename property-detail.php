<?php
// property-detail.php - VIEW ONLY. All data comes from the backend controller.
require_once __DIR__ . '/backend/controllers/property_controller.php';
// $property, $similar_properties and $amenities are now set by the controller above.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['name']); ?> - Pahingahan By The Still</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/property-detail.css">
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar">
        <a href="default.php" class="logo">pahingahan<span>.</span></a>
        <a href="default.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </nav>

    <div class="container">
        <!-- ===== PROPERTY DETAIL ===== -->
        <div class="property-detail">
            <div class="property-image">
                <?php if (!empty($property['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($property['image_url']); ?>" alt="<?php echo htmlspecialchars($property['name']); ?>">
                <?php else: ?>
                    <i class="fas fa-home"></i>
                <?php endif; ?>
            </div>

            <div class="detail-header">
                <div>
                    <h1><?php echo htmlspecialchars($property['name']); ?></h1>
                    <div class="detail-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['location']); ?>
                    </div>
                </div>
                <div class="price-big">
                    ₱<?php echo number_format($property['price'], 2); ?>
                    <span>/ night</span>
                </div>
            </div>

            <div class="detail-info-grid">
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <div class="value"><?php echo $property['max_guests']; ?></div>
                    <div class="label">Max Guests</div>
                </div>
                <div class="info-item">
                    <i class="fas fa-bed"></i>
                    <div class="value"><?php echo $property['bedrooms']; ?></div>
                    <div class="label">Bedrooms</div>
                </div>
                <div class="info-item">
                    <i class="fas fa-bath"></i>
                    <div class="value"><?php echo $property['bathrooms']; ?></div>
                    <div class="label">Bathrooms</div>
                </div>
                <div class="info-item">
                    <i class="fas fa-star"></i>
                    <div class="value">4.9 ★</div>
                    <div class="label">Rating</div>
                </div>
            </div>

            <div class="detail-description">
                <h3>About this property</h3>
                <p><?php echo nl2br(htmlspecialchars($property['description'] ?? 'No description available.')); ?></p>
            </div>

            <?php if (!empty($amenities)): ?>
                <h3>Amenities</h3>
                <div class="amenities-list">
                    <?php foreach ($amenities as $amenity): ?>
                        <span class="amenity-tag"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <button class="btn-book" onclick="alert('Booking functionality coming soon!')">
                <i class="fas fa-calendar-check"></i> Book Now
            </button>
        </div>

        <!-- ===== SIMILAR PROPERTIES ===== -->
        <?php if (count($similar_properties) > 0): ?>
            <div class="similar-section">
                <h2>📍 Similar Properties</h2>
                <div class="similar-grid">
                    <?php foreach ($similar_properties as $similar): ?>
                        <div class="similar-card" onclick="window.location.href='property-detail.php?id=<?php echo $similar['id']; ?>'">
                            <div class="sim-image">
                                <?php if (!empty($similar['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($similar['image_url']); ?>" alt="<?php echo htmlspecialchars($similar['name']); ?>" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-home"></i>
                                <?php endif; ?>
                            </div>
                            <div class="sim-body">
                                <div class="sim-title"><?php echo htmlspecialchars($similar['name']); ?></div>
                                <div class="sim-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($similar['location']); ?></div>
                                <div class="sim-price">₱<?php echo number_format($similar['price'], 2); ?> / night</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>