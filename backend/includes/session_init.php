<?php
/**
 * session_init.php
 * -----------------------------------------------------------------
 * BACKEND: Starts the PHP session for pages that are public /
 * guest-accessible (e.g. default.php), where we do NOT force a
 * redirect if the user isn't logged in.
 * -----------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
