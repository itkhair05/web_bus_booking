<?php
/**
 * Session Configuration
 * Quản lý session cho Bus Booking System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout (30 minutes)
$session_timeout = 1800;

// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expired
    session_unset();
    session_destroy();
    session_start();
}

// Update last activity time
$_SESSION['last_activity'] = time();

