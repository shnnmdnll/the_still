<?php
/**
 * listings_controller.php
 * -----------------------------------------------------------------
 * BACKEND: Fetches all available properties for the homepage's
 * "Featured Staycations" grid.
 *
 * Sets, for use by the view:
 *   $properties - all rows from `properties` where status = 'available'
 * -----------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("SELECT * FROM properties WHERE status = 'available' ORDER BY created_at DESC");
$properties = $stmt->fetchAll();