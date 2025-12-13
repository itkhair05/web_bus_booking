<?php
/**
 * Register API
 * Xử lý đăng ký người dùng mới
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

requireCsrfToken();

// Get input data
$fullname = sanitizeInput($_POST['fullname'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate required fields
if (empty($fullname) || empty($phone) || empty($email) || empty($password) || empty($confirmPassword)) {
    jsonError('Vui lòng nhập đầy đủ thông tin', 'INVALID_INPUT');
}

// Validate email
if (!validateEmail($email)) {
    jsonError('Email không hợp lệ', 'INVALID_EMAIL');
}

// Validate phone
if (!validatePhone($phone)) {
    jsonError('Số điện thoại không hợp lệ', 'INVALID_PHONE');
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
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        jsonError('Email đã được sử dụng', 'EMAIL_EXISTS');
    }
    
    // Check if phone exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        jsonError('Số điện thoại đã được sử dụng', 'PHONE_EXISTS');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'user', 'active')");
    $stmt->bind_param("ssss", $fullname, $email, $phone, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        // Log activity
        logActivity($conn, $userId, 'register', 'users', $userId);
        
        // Create welcome notification
        createNotification(
            $conn,
            $userId,
            'Chào mừng bạn đến với BusBooking!',
            'Cảm ơn bạn đã đăng ký. Chúc bạn có những chuyến đi vui vẻ!',
            'system'
        );
        
        jsonResponse(true, [
            'user_id' => $userId
        ], 'Đăng ký thành công');
    } else {
        jsonError('Đăng ký thất bại', 'REGISTER_FAILED', 500);
    }
    
} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra. Vui lòng thử lại!', 'SERVER_ERROR', 500);
}

