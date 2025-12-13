<?php
/**
 * CSRF Protection
 * Bảo vệ chống tấn công CSRF
 */

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token
 */
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? generateCsrfToken();
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require CSRF token (for POST requests)
 */
function requireCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!verifyCsrfToken($token)) {
            if (isAjax()) {
                jsonError('Token không hợp lệ', 'CSRF_INVALID', 403);
            } else {
                setFlashMessage('error', 'Yêu cầu không hợp lệ. Vui lòng thử lại.');
                redirect($_SERVER['HTTP_REFERER'] ?? appUrl());
            }
        }
    }
}

/**
 * Generate CSRF input field
 */
function csrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Get CSRF meta tag
 */
function csrfMetaTag() {
    $token = getCsrfToken();
    return '<meta name="csrf-token" content="' . $token . '">';
}

