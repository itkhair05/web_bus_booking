<?php
/**
 * Forgot Password API
 * Xử lý quên mật khẩu và gửi email reset
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

requireCsrfToken();

// Get input data
$email = sanitizeInput($_POST['email'] ?? '');

// Validate required fields
if (empty($email)) {
    jsonError('Vui lòng nhập email', 'INVALID_INPUT');
}

// Validate email
if (!validateEmail($email)) {
    jsonError('Email không hợp lệ', 'INVALID_EMAIL');
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, fullname, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Don't reveal if email exists or not for security
        jsonResponse(true, null, 'Nếu email tồn tại, link đặt lại mật khẩu đã được gửi đến email của bạn');
    }
    
    $user = $result->fetch_assoc();
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete old tokens for this user
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    
    // Insert new reset token
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        jsonError('Lỗi database. Vui lòng thử lại!', 'DB_ERROR', 500);
    }
    $stmt->bind_param("iss", $user['user_id'], $token, $expiresAt);
    
    if (!$stmt->execute()) {
        error_log("Insert token failed: " . $stmt->error);
        jsonError('Không thể tạo token. Vui lòng thử lại!', 'INSERT_ERROR', 500);
    }
    
    // Verify token was inserted
    $insertedId = $stmt->insert_id;
    error_log("Token inserted with ID: {$insertedId} for user: {$user['user_id']}");
    
    // Generate reset link
    $resetLink = APP_URL . "/user/auth/reset_password.php?token=" . $token;
    
    // Send email with reset link
    $emailSent = EmailService::sendPasswordReset(
        $user['email'],
        $user['fullname'],
        $resetLink,
        60 // 60 minutes
    );
    
    // Log for debugging
    error_log("Password reset requested for {$email} - Email sent: " . ($emailSent ? 'Yes' : 'No'));
    
    // Always show success message (don't reveal if email exists)
    jsonResponse(true, null, 'Nếu email tồn tại, link đặt lại mật khẩu đã được gửi đến email của bạn');
    
} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra. Vui lòng thử lại!', 'SERVER_ERROR', 500);
}

