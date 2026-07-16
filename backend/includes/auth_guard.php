<?php
/**
 * auth_guard.php
 * -----------------------------------------------------------------
 * BACKEND: Access-control logic shared by every page that requires
 * a logged-in user (homepage, hosting, contact, discover, ...).
 *
 * Usage (top of a protected page):
 *     require_once __DIR__ . '/../backend/includes/auth_guard.php';
 *
 * What it does:
 *  1. Starts the PHP session if one isn't already running.
 *  2. Redirects guests back to default.php if they aren't logged in.
 *  3. Exposes $currentUserName for the view to use.
 * -----------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in'])) {
    header('Location: default.php');
    exit;
}

function pahingahan_first_name(string $fullName): string {
    $fullName = trim($fullName);
    if ($fullName === '') {
        return 'Guest';
    }

    // "Last, First Middle" -> keep only the part after the comma
    if (strpos($fullName, ',') !== false) {
        $parts = explode(',', $fullName, 2);
        $fullName = trim($parts[1] ?? $parts[0]);
    }

    $words = preg_split('/\s+/', $fullName);
    $firstName = $words[0] ?? $fullName;

    // Normalize casing (handles ALL CAPS Google names) with UTF-8 safety
    return mb_convert_case($firstName, MB_CASE_TITLE, 'UTF-8');
}

$currentUserName = pahingahan_first_name($_SESSION['user_name'] ?? 'Guest');