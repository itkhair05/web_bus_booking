<?php
/**
 * Update Profile API
 * API cập nhật thông tin user
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
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors['name'] = 'Tên phải có ít nhất 2 ký tự';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Email không hợp lệ';
}

if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
    $errors['phone'] = 'Số điện thoại không hợp lệ (10-11 số)';
}

if (!empty($errors)) {
    jsonError('Dữ liệu không hợp lệ', 'VALIDATION_ERROR', 400, $errors);
}

// Check email uniqueness
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    jsonError('Email đã được sử dụng bởi tài khoản khác', 'EMAIL_EXISTS', 400);
}

// Update
$stmt = $conn->prepare("
    UPDATE users 
    SET name = ?, 
        email = ?, 
        phone = ?, 
        address = ?,
        updated_at = NOW()
    WHERE user_id = ?
");

$stmt->bind_param("ssssi", $name, $email, $phone, $address, $userId);

if ($stmt->execute()) {
    // Update session
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    // Get updated user
    $stmt = $conn->prepare("SELECT user_id, name, email, phone, address, avatar FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    jsonResponse(true, ['user' => $user], 'Cập nhật thông tin thành công');
} else {
    jsonError('Không thể cập nhật thông tin', 'UPDATE_FAILED', 500);
}

