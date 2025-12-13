<?php
session_start();

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
        header('Location: login.php');
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
    // Xoá toàn bộ session
    session_destroy();
    // Redirect về trang đăng nhập chung (user/auth/login.php)
    $baseUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header('Location: ' . $baseUrl . '/user/auth/login.php');
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
