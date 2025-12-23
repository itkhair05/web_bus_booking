<?php
/**
 * Session Configuration
 * Quản lý session cho Bus Booking System
 */

// Set timezone for Vietnam (Asia/Ho_Chi_Minh = UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load environment variables if not already loaded
if (!function_exists('env')) {
    require_once __DIR__ . '/env.php';
}

// Session security settings
// Set secure cookie parameters before starting session
// Note: Using 'Lax' instead of 'Strict' to allow session cookie to be sent when redirecting from payment gateways (VNPay)
// 'Lax' still provides CSRF protection for POST requests while allowing cookies in top-level navigation
session_set_cookie_params([
    'lifetime' => 0, // Until browser closes
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Only over HTTPS
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Lax' // CSRF protection - allows cookies in top-level navigation (needed for payment gateway redirects)
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize session creation time if not set
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
}

// Set session timeout from .env (default 30 minutes = 1800 seconds)
$session_timeout = env('SESSION_TIMEOUT', 1800);

// Check if session has expired (only for logged-in users)
// Don't expire session if user is not logged in (guest booking flow)
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    $timeSinceLastActivity = time() - $_SESSION['last_activity'];
    
    if ($timeSinceLastActivity > $session_timeout) {
        // Session expired for logged-in user - logout user
        // Don't preserve booking data because bookings are saved to database with user_id
        session_unset();
        session_destroy();
        session_start();
        
        // Set new session creation time
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
    } else {
        // Regenerate session ID periodically (every 30 minutes) to prevent session fixation
        if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
} else {
    // User not logged in (guest booking) - preserve booking data, don't expire session
    // Preserve booking data in case session needs to be regenerated
    $bookingData = [
        'booking_id' => $_SESSION['booking_id'] ?? null,
        'booking_trip_id' => $_SESSION['booking_trip_id'] ?? null,
        'booking_seats' => $_SESSION['booking_seats'] ?? null,
        'booking_price' => $_SESSION['booking_price'] ?? null,
        'booking_pickup_id' => $_SESSION['booking_pickup_id'] ?? null,
        'booking_dropoff_id' => $_SESSION['booking_dropoff_id'] ?? null,
        'booking_pickup_time' => $_SESSION['booking_pickup_time'] ?? null,
        'booking_pickup_station' => $_SESSION['booking_pickup_station'] ?? null,
        'booking_dropoff_time' => $_SESSION['booking_dropoff_time'] ?? null,
        'booking_dropoff_station' => $_SESSION['booking_dropoff_station'] ?? null,
    ];
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically even for guests (every 30 minutes)
    if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        // Preserve booking data during regeneration
        session_regenerate_id(true);
        $_SESSION['created'] = time();
        
        // Restore booking data after regeneration
        foreach ($bookingData as $key => $value) {
            if ($value !== null) {
                $_SESSION[$key] = $value;
            }
        }
    }
}

