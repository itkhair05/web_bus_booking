<?php
/**
 * Authentication Functions
 * Quản lý xác thực và phân quyền
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $userId = getCurrentUserId();
    
    $stmt = $conn->prepare("SELECT user_id, name, email, phone, address, avatar, role, status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Require login
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        if (isAjax()) {
            jsonError('Vui lòng đăng nhập', 'AUTH_REQUIRED', 401);
        } else {
            $redirectUrl = $redirectUrl ?? appUrl('user/auth/login.php');
            setFlashMessage('error', 'Vui lòng đăng nhập để tiếp tục');
            redirect($redirectUrl);
        }
    }
}

/**
 * Require role
 */
function requireRole($role) {
    requireLogin();
    if (getCurrentUserRole() !== $role) {
        if (isAjax()) {
            jsonError('Bạn không có quyền truy cập', 'FORBIDDEN', 403);
        } else {
            redirect(appUrl());
        }
    }
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

/**
 * Check if user is regular user
 */
function isUser() {
    return getCurrentUserRole() === 'user';
}

/**
 * Login user
 */
function loginUser($userId, $role, $name = '', $email = '') {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Check if user account is active
 */
function isAccountActive($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['status'] === 'active';
    }
    
    return false;
}

/**
 * Require active account
 */
function requireActiveAccount() {
    requireLogin();
    
    $userId = getCurrentUserId();
    if (!isAccountActive($userId)) {
        logoutUser();
        if (isAjax()) {
            jsonError('Tài khoản của bạn đã bị khóa', 'ACCOUNT_LOCKED', 403);
        } else {
            setFlashMessage('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ admin.');
            redirect(appUrl('user/auth/login.php'));
        }
    }
}

