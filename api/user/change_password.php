<?php
/**
 * Change Password API
 * API đổi mật khẩu
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Require login
if (!isLoggedIn()) {
    jsonError('Vui lòng đăng nhập', 'UNAUTHORIZED', 401);
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid method', 'METHOD_NOT_ALLOWED', 405);
}

// Verify CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError('Invalid CSRF token', 'CSRF_ERROR', 403);
}

// Get data
$userId = getCurrentUserId();
$oldPassword = $_POST['old_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate
if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
    jsonError('Vui lòng điền đầy đủ thông tin', 'MISSING_FIELDS', 400);
}

if (strlen($newPassword) < 6) {
    jsonError('Mật khẩu mới phải có ít nhất 6 ký tự', 'PASSWORD_TOO_SHORT', 400);
}

if ($newPassword !== $confirmPassword) {
    jsonError('Mật khẩu xác nhận không khớp', 'PASSWORD_MISMATCH', 400);
}

if ($oldPassword === $newPassword) {
    jsonError('Mật khẩu mới phải khác mật khẩu cũ', 'SAME_PASSWORD', 400);
}

// Get current user
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    jsonError('Không tìm thấy user', 'USER_NOT_FOUND', 404);
}

// Verify old password
if (!password_verify($oldPassword, $user['password'])) {
    jsonError('Mật khẩu hiện tại không đúng', 'WRONG_PASSWORD', 400);
}

// Hash new password
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Update password
$stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
$stmt->bind_param("si", $hashedPassword, $userId);

if ($stmt->execute()) {
    // Log activity
    error_log("User ID {$userId} changed password successfully");
    
    // Destroy session (will logout user)
    session_destroy();
    
    jsonResponse(true, [], 'Đổi mật khẩu thành công');
} else {
    jsonError('Không thể đổi mật khẩu', 'UPDATE_FAILED', 500);
}

