<?php
/**
 * Reset Password API
 * Xử lý đặt lại mật khẩu mới
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

requireCsrfToken();

// Get input data
$token = sanitizeInput($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate required fields
if (empty($token) || empty($password) || empty($confirmPassword)) {
    jsonError('Vui lòng nhập đầy đủ thông tin', 'INVALID_INPUT');
}

// Validate password length
if (strlen($password) < 6) {
    jsonError('Mật khẩu phải có ít nhất 6 ký tự', 'PASSWORD_TOO_SHORT');
}

// Check password match
if ($password !== $confirmPassword) {
    jsonError('Mật khẩu xác nhận không khớp', 'PASSWORD_MISMATCH');
}

try {
    // Check if token exists and not expired
    // Use PHP time instead of MySQL NOW() to avoid timezone mismatch
    $currentTime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        SELECT pr.reset_id, pr.user_id, u.email, u.fullname
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.user_id
        WHERE pr.token = ? AND pr.expires_at > ?
    ");
    $stmt->bind_param("ss", $token, $currentTime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonError('Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn', 'INVALID_TOKEN');
    }
    
    $reset = $result->fetch_assoc();
    
    // Hash new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user password
    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("si", $hashedPassword, $reset['user_id']);
    $stmt->execute();
    
    // Delete used token
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE reset_id = ?");
    $stmt->bind_param("i", $reset['reset_id']);
    $stmt->execute();
    
    // Create notification
    createNotification(
        $conn,
        $reset['user_id'],
        'Mật khẩu đã được đổi',
        'Mật khẩu của bạn đã được thay đổi thành công lúc ' . date('H:i d/m/Y'),
        'system'
    );
    
    jsonResponse(true, null, 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');
    
} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra. Vui lòng thử lại!', 'SERVER_ERROR', 500);
}

