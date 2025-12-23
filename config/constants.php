<?php
/**
 * Application Constants
 * Các hằng số dùng chung trong ứng dụng
 */

// Set timezone for Vietnam (Asia/Ho_Chi_Minh = UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load environment variables
require_once __DIR__ . '/env.php';

// Debug mode (set to false in production)
define('APP_DEBUG', env('APP_DEBUG', true));

// Application info
define('APP_NAME', env('APP_NAME', 'BusBooking'));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));
define('APP_URL', env('APP_URL', 'http://localhost/Bus_Booking'));

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/storage/uploads');
define('QR_PATH', BASE_PATH . '/storage/qrcodes');
define('LOG_PATH', BASE_PATH . '/storage/logs');

// Asset URLs
define('ASSETS_URL', APP_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMG_URL', ASSETS_URL . '/images');

// Pagination
define('ITEMS_PER_PAGE', env('ITEMS_PER_PAGE', 10));

// Booking settings
define('BOOKING_EXPIRY_MINUTES', env('BOOKING_EXPIRY_MINUTES', 15));
define('CANCEL_BEFORE_HOURS', env('CANCEL_BEFORE_HOURS', 24));
define('REFUND_PERCENTAGE', env('REFUND_PERCENTAGE', 80));

// Payment methods
define('PAYMENT_METHODS', [
    'momo' => 'MoMo',
    'vnpay' => 'VNPay',
    'zalopay' => 'ZaloPay',
    'cod' => 'Thanh toán khi lên xe',
    'bank_transfer' => 'Chuyển khoản ngân hàng'
]);

// Status constants
define('BOOKING_STATUS', [
    'pending' => 'Chờ thanh toán',
    'confirmed' => 'Đã xác nhận',
    'cancelled' => 'Đã hủy',
    'completed' => 'Hoàn thành'
]);

define('PAYMENT_STATUS', [
    'unpaid' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'refunded' => 'Đã hoàn tiền'
]);

define('TRIP_STATUS', [
    'scheduled' => 'Đã lên lịch',
    'open' => 'Đang mở bán',
    'completed' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy'
]);

// Error codes
define('ERROR_CODES', [
    'AUTH_REQUIRED' => 'Vui lòng đăng nhập',
    'INVALID_CREDENTIALS' => 'Email hoặc mật khẩu không đúng',
    'EMAIL_EXISTS' => 'Email đã được sử dụng',
    'PHONE_EXISTS' => 'Số điện thoại đã được sử dụng',
    'TRIP_NOT_FOUND' => 'Không tìm thấy chuyến xe',
    'SEAT_UNAVAILABLE' => 'Ghế không còn trống',
    'BOOKING_NOT_FOUND' => 'Không tìm thấy đơn đặt vé',
    'PAYMENT_FAILED' => 'Thanh toán thất bại',
    'INVALID_INPUT' => 'Dữ liệu không hợp lệ',
    'CSRF_INVALID' => 'Token không hợp lệ'
]);

// Error messages mapping (User-friendly messages)
define('ERROR_MESSAGES', [
    'DB_CONNECTION_ERROR' => 'Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.',
    'INVALID_INPUT' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.',
    'UNAUTHORIZED' => 'Bạn không có quyền thực hiện thao tác này.',
    'NOT_FOUND' => 'Không tìm thấy dữ liệu yêu cầu.',
    'PAYMENT_ERROR' => 'Có lỗi xảy ra trong quá trình thanh toán. Vui lòng thử lại.',
    'BOOKING_ERROR' => 'Không thể đặt vé. Vui lòng thử lại.',
    'GENERIC_ERROR' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
    'NETWORK_ERROR' => 'Lỗi kết nối mạng. Vui lòng kiểm tra kết nối internet.',
    'SERVER_ERROR' => 'Lỗi máy chủ. Vui lòng thử lại sau.',
    'VALIDATION_ERROR' => 'Dữ liệu nhập vào không hợp lệ. Vui lòng kiểm tra lại.',
    'SESSION_EXPIRED' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.',
    'RATE_LIMIT_EXCEEDED' => 'Bạn đã thực hiện quá nhiều yêu cầu. Vui lòng thử lại sau.',
    'MAINTENANCE_MODE' => 'Hệ thống đang bảo trì. Vui lòng quay lại sau.'
]);

/**
 * Get user-friendly error message
 * 
 * @param string $code Error code
 * @param string|null $default Default message if code not found
 * @return string User-friendly error message
 */
function getErrorMessage($code, $default = null) {
    return ERROR_MESSAGES[$code] ?? $default ?? ERROR_MESSAGES['GENERIC_ERROR'];
}

// VeXeRe-style color scheme (Blue)
define('COLOR_PRIMARY', '#1E90FF');
define('COLOR_SECONDARY', '#FFA500');
define('COLOR_SUCCESS', '#4CAF50');
define('COLOR_DANGER', '#F44336');
define('COLOR_WARNING', '#FF9800');
define('COLOR_INFO', '#2196F3');

// SMTP Email Configuration (only if not already defined by email.php)
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', env('SMTP_PORT', 587));
}
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', env('SMTP_USERNAME', '')));
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', APP_NAME));
}
if (!defined('SMTP_ENCRYPTION')) {
    define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));
}
if (!defined('SMTP_REPLY_TO')) {
    define('SMTP_REPLY_TO', env('SMTP_REPLY_TO', env('SMTP_FROM_EMAIL', '')));
}

// Company Info (for emails) - only if not already defined
if (!defined('COMPANY_NAME')) {
    define('COMPANY_NAME', env('COMPANY_NAME', 'BusBooking'));
}
if (!defined('COMPANY_PHONE')) {
    define('COMPANY_PHONE', env('COMPANY_PHONE', '1900-xxxx'));
}
if (!defined('COMPANY_EMAIL')) {
    define('COMPANY_EMAIL', env('COMPANY_EMAIL', env('SMTP_FROM_EMAIL', '')));
}
if (!defined('COMPANY_ADDRESS')) {
    define('COMPANY_ADDRESS', env('COMPANY_ADDRESS', 'Việt Nam'));
}
if (!defined('COMPANY_WEBSITE')) {
    define('COMPANY_WEBSITE', env('COMPANY_WEBSITE', APP_URL));
}
if (!defined('SUPPORT_EMAIL')) {
    define('SUPPORT_EMAIL', env('SUPPORT_EMAIL', env('SMTP_FROM_EMAIL', '')));
}
if (!defined('SUPPORT_PHONE')) {
    define('SUPPORT_PHONE', env('SUPPORT_PHONE', '1900-xxxx'));
}

