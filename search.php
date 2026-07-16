<?php
// search.php - VIEW ONLY. All data comes from the backend controller.
require_once __DIR__ . '/backend/controllers/search_controller.php';
// $results is now set by the controller above.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Pahingahan By The Still</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/search.css">
</head>
<body>
    <nav class="navbar">
        <a href="default.php" class="logo">pahingahan<span>.</span></a>
        <a href="default.php" style="color: #667eea; text-decoration: none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Back</a>
    </nav>
    <div class="container">
        <div class="results-header">
            <h1>🔍 Search Results</h1>
            <p>Found <?php echo count($results); ?> properties</p>
        </div>
        <div class="property-grid">
            <?php if (count($results) > 0): ?>
                <?php foreach ($results as $property): ?>
                    <div class="property-card" onclick="window.location.href='property-detail.php?id=<?php echo $property['id']; ?>'">
                        <div class="card-image">
                            <?php if (!empty($property['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($property['image_url']); ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-home"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="card-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?></div>
                            <div class="card-title"><?php echo htmlspecialchars($property['name']); ?></div>
                            <div class="card-price">₱<?php echo number_format($property['price'], 2); ?> <span>/ night</span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No properties found</h3>
                    <p>Try adjusting your search criteria</p>
                    <a href="default.php" style="color: #667eea; font-weight: 600; display: inline-block; margin-top: 12px;">← Back to Home</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>