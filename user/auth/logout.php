<?php
/**
 * Logout
 * Đăng xuất người dùng
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';

// Log activity if logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    logActivity($conn, $userId, 'logout', 'users', $userId);
}

// Clear session
logoutUser();

// Redirect to home
redirect(appUrl());
