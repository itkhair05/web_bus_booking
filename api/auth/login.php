<?php
/**
 * Login API
 * Xử lý đăng nhập người dùng
 */

// Load dependencies
require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Verify CSRF token
requireCsrfToken();

// Get input data
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($email) || empty($password)) {
    jsonError('Vui lòng nhập đầy đủ thông tin', 'INVALID_INPUT');
}

// Validate email format
if (!validateEmail($email)) {
    jsonError('Email không hợp lệ', 'INVALID_EMAIL');
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, name, email, phone, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonError('Email chưa được đăng ký', 'USER_NOT_FOUND');
    }
    
    $user = $result->fetch_assoc();
    
    // Check if account is locked
    if ($user['status'] === 'locked') {
        jsonError('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ admin.', 'ACCOUNT_LOCKED');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        jsonError('Mật khẩu không đúng', 'INVALID_PASSWORD');
    }
    
    // Login successful - Set session
    loginUser($user['user_id'], $user['role'], $user['name'], $user['email']);
    
    // Set additional session variables for admin/partner compatibility
    if ($user['role'] === 'admin') {
        $_SESSION['user_type'] = 'admin';
        $_SESSION['admin_id'] = $user['user_id'];
    } elseif ($user['role'] === 'partner') {
        $_SESSION['user_type'] = 'partner';
        // Get partner_id from partners table
        $partnerStmt = $conn->prepare("SELECT partner_id, name FROM partners WHERE email = ?");
        $partnerStmt->bind_param("s", $user['email']);
        $partnerStmt->execute();
        $partnerResult = $partnerStmt->get_result();
        if ($partnerResult->num_rows > 0) {
            $partner = $partnerResult->fetch_assoc();
            $_SESSION['operator_id'] = $partner['partner_id'];
            $_SESSION['company_name'] = $partner['name'];
        }
        $partnerStmt->close();
    }
    
    // Log activity
    logActivity($conn, $user['user_id'], 'login', 'users', $user['user_id']);
    
    // Create notification
    createNotification(
        $conn,
        $user['user_id'],
        'Đăng nhập thành công',
        'Bạn vừa đăng nhập vào hệ thống lúc ' . date('H:i d/m/Y'),
        'system'
    );
    
    // Determine redirect URL based on role
    $redirectUrl = appUrl(); // Default: homepage
    
    switch ($user['role']) {
        case 'admin':
            $redirectUrl = appUrl('admin/admin_dashboard.php');
            break;
        case 'partner':
            $redirectUrl = appUrl('partner/dashboard.php');
            break;
        case 'user':
        default:
            $redirectUrl = appUrl(); // Homepage for users
            break;
    }
    
    // Return success with redirect URL
    jsonResponse(true, [
        'user_id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'redirect' => $redirectUrl
    ], 'Đăng nhập thành công');
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra. Vui lòng thử lại!', 'SERVER_ERROR', 500);
}

