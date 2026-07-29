<?php
/**
 * search_controller.php
 * -----------------------------------------------------------------
 * BACKEND: Data logic for the search-results page.
 * (moved out of search.php, which is now view-only)
 *
 * Reads $_GET['q'], $_GET['location'], $_GET['guests'] and sets:
 *   $results - matching unit rows, newest first
 * -----------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/db.php';

$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$location    = isset($_GET['location']) ? trim($_GET['location']) : '';
$guests      = isset($_GET['guests']) ? intval($_GET['guests']) : 0;

// Build query
$sql = "SELECT * FROM units WHERE status = 'available'";
$params = [];

if (!empty($search_term)) {
    $sql .= " AND (name LIKE :search OR description LIKE :search OR location LIKE :search)";
    $params[':search'] = "%$search_term%";
}

if (!empty($location)) {
    $sql .= " AND location LIKE :location";
    $params[':location'] = "%$location%";
}

if ($guests > 0) {
    $sql .= " AND max_guests >= :guests";
    $params[':guests'] = $guests;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();