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
 *  3. Syncs $_SESSION['role'] with the database on every load, and
 *     redirects a just-promoted guest (e.g. after a Host Application
 *     approval) straight to their new role's dashboard.
 *  4. Exposes $currentUserName for the view to use.
 * -----------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in'])) {
    header('Location: default.php');
    exit;
}

// ===== I-sync ang role mula sa database sa bawat page load =====
// Dahil pwedeng ma-approve ang isang Host Application habang naka-login
// pa rin ang guest, kailangan nating tignan ang latest na role sa DB
// sa halip na umasa lang sa lumang value na naka-save sa session mula
// noong nag-login siya. Kung na-promote siya, idi-direkta agad siya
// papunta sa tamang dashboard imbes na patuloy na makita ang Guest UI.
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/db.php';

    $roleCheckStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $roleCheckStmt->execute([':id' => $_SESSION['user_id']]);
    $latestRole = $roleCheckStmt->fetchColumn();

    $sessionRole = $_SESSION['role'] ?? null;

    if ($latestRole !== false && $latestRole !== $sessionRole) {
        $_SESSION['role'] = $latestRole;

        // I-redirect papunta sa tamang dashboard kapag nagbago ang role
        // (halimbawa: kaka-approve lang ng Host Application), sa halip
        // na patuloy na ipakita ang page na hiniling niya bilang Guest.
        $dashboardByRole = [
            'host'  => 'host/today.php',
            'owner' => 'owner/dashboard.php',
            'staff' => 'staff/tasks.php',
        ];
        if (isset($dashboardByRole[$latestRole])) {
            header('Location: ' . $dashboardByRole[$latestRole]);
            exit;
        }
    }
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