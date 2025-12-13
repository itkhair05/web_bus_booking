<?php
/**
 * Application Constants
 * Các hằng số dùng chung trong ứng dụng
 */

// Debug mode (set to false in production)
define('APP_DEBUG', true);

// Application info
define('APP_NAME', 'BusBooking');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Bus_Booking');

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
define('ITEMS_PER_PAGE', 10);

// Booking settings
define('BOOKING_EXPIRY_MINUTES', 15);
define('CANCEL_BEFORE_HOURS', 24);
define('REFUND_PERCENTAGE', 80);

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

// VeXeRe-style color scheme (Blue)
define('COLOR_PRIMARY', '#1E90FF');
define('COLOR_SECONDARY', '#FFA500');
define('COLOR_SUCCESS', '#4CAF50');
define('COLOR_DANGER', '#F44336');
define('COLOR_WARNING', '#FF9800');
define('COLOR_INFO', '#2196F3');

