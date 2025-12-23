<?php
// Set timezone for Vietnam (Asia/Ho_Chi_Minh = UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();

// Load helpers (for appUrl function)
if (!function_exists('appUrl')) {
    require_once __DIR__ . '/../../config/constants.php';
    require_once __DIR__ . '/../../core/helpers.php';
}

// Kiểm tra đăng nhập chung cho các trang dành cho nhà xe
// Cho phép: (1) user_type = partner; hoặc (2) user_type = admin và đã chọn partner context
function checkLogin() {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin' && isset($_SESSION['operator_id'])) {
        return true;
    }
    return checkPartnerLogin();
}

// Kiểm tra đăng nhập nhà xe (partner)
function checkPartnerLogin() {
    if (!isset($_SESSION['user_type']) || !isset($_SESSION['operator_id'])) {
        // Nếu là admin nhưng chưa chọn ngữ cảnh nhà xe, điều hướng đến trang chọn ngữ cảnh
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            header('Location: admin_select_partner.php');
            exit();
        }
        header('Location: login.php');
        exit();
    }
    // Nếu là partner, đảm bảo đúng loại
    if ($_SESSION['user_type'] !== 'partner' && $_SESSION['user_type'] !== 'admin') {
        header('Location: login.php');
        exit();
    }
    return true;
}

// Kiểm tra đăng nhập admin
function checkAdminLogin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['admin_id'])) {
        if (function_exists('appUrl')) {
            header('Location: ' . appUrl('user/auth/login.php'));
        } else {
            header('Location: ../../user/auth/login.php');
        }
        exit();
    }
    return true;
}

// Lấy thông tin nhà xe hiện tại
function getCurrentOperator() {
    if (isset($_SESSION['operator_id'])) {
        return $_SESSION['operator_id'];
    }
    return null;
}

// Lấy thông tin admin hiện tại
function getCurrentAdmin() {
    if (isset($_SESSION['admin_id'])) {
        return $_SESSION['admin_id'];
    }
    return null;
}

// Đăng xuất
function logout() {
    // Lưu user_type trước khi destroy session
    $userType = $_SESSION['user_type'] ?? 'admin'; // Admin mặc định là admin
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect về trang đăng nhập của user
    if (function_exists('appUrl')) {
        $loginUrl = appUrl('user/auth/login.php');
    } else {
        // Fallback nếu appUrl không có
        if (defined('APP_URL')) {
            $baseUrl = APP_URL;
        } else {
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            $scriptPath = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')));
            $basePath = str_replace($docRoot, '', $scriptPath);
            $basePath = str_replace('\\', '/', $basePath);
            $baseUrl = $basePath;
        }
        $loginUrl = rtrim($baseUrl, '/') . '/user/auth/login.php';
    }

    header('Location: ' . $loginUrl);
    exit();
}

// Admin: chọn ngữ cảnh nhà xe để dùng chung trang partner
function adminSetPartnerContext($partnerId, $companyName) {
    $_SESSION['operator_id'] = $partnerId;
    $_SESSION['company_name'] = $companyName;
}

function adminClearPartnerContext() {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        unset($_SESSION['operator_id']);
        // company_name giữ là 'Admin Panel' hoặc tùy biến tại login
    }
}
?>
