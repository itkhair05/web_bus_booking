<?php
/**
 * Booking Information Page
 * Điền thông tin liên hệ và chọn tiện ích - Final Step
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Get data from session
$tripId = $_SESSION['booking_trip_id'] ?? 0;
$selectedSeats = $_SESSION['booking_seats'] ?? [];
$totalPrice = $_SESSION['booking_price'] ?? 0;
$pickupId = $_SESSION['booking_pickup_id'] ?? '';
$pickupTime = $_SESSION['booking_pickup_time'] ?? '';
$pickupStation = $_SESSION['booking_pickup_station'] ?? '';
$dropoffId = $_SESSION['booking_dropoff_id'] ?? '';
$dropoffTime = $_SESSION['booking_dropoff_time'] ?? '';
$dropoffStation = $_SESSION['booking_dropoff_station'] ?? '';

if (empty($tripId) || empty($selectedSeats) || empty($pickupId) || empty($dropoffId)) {
    redirect(appUrl('user/booking/select_seat.php?trip_id=' . $tripId));
}

// Helper function to check if column exists
function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    static $cache = [];
    $key = "$table.$column";
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $cache[$key] = ($result && $result->num_rows > 0);
}

// Get trip details with dynamic column handling
$routeOriginCol = tableHasColumn($conn, 'routes', 'origin') ? 'r.origin' : 
                  (tableHasColumn($conn, 'routes', 'start_point') ? 'r.start_point' : 'r.start_point');
$routeDestCol = tableHasColumn($conn, 'routes', 'destination') ? 'r.destination' : 
                (tableHasColumn($conn, 'routes', 'end_point') ? 'r.end_point' : 'r.end_point');

$partnerNameCol = tableHasColumn($conn, 'partners', 'name') ? 'p.name' : 
                  (tableHasColumn($conn, 'partners', 'company_name') ? 'p.company_name' : 'p.name');
$vehicleTypeCol = tableHasColumn($conn, 'vehicles', 'vehicle_type') ? 'v.vehicle_type' : 
                  (tableHasColumn($conn, 'vehicles', 'type') ? 'v.type' : "''");

$stmt = $conn->prepare("
    SELECT 
        t.*,
        $routeOriginCol as origin,
        $routeDestCol as destination,
        $partnerNameCol as partner_name,
        $vehicleTypeCol as vehicle_type,
        v.license_plate,
        v.total_seats,
        p.logo_url as partner_logo
    FROM trips t
    JOIN routes r ON t.route_id = r.route_id
    JOIN partners p ON t.partner_id = p.partner_id
    JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    WHERE t.trip_id = ?
");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    redirect(appUrl());
}

// Get partner logo URL
$partnerLogoUrl = getPartnerLogoUrl($trip['partner_logo'] ?? null);

// Get user info if logged in
$user = isLoggedIn() ? getCurrentUser() : [];

// Get pickup/dropoff from session
$pickupPoint = [
    'schedule_id' => $pickupId,
    'departure_time' => $pickupTime,
    'departure_station' => $pickupStation
];

$dropoffPoint = [
    'schedule_id' => $dropoffId,
    'arrival_time' => $dropoffTime,
    'arrival_station' => $dropoffStation
];

$pageTitle = 'Thông tin đặt vé - BusBooking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
    .booking-info-page {
        background: #f3f4f6;
        min-height: 100vh;
        padding: 30px 0;
    }
    
    .container-bi {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Back Button */
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #475569;
        text-decoration: none;
        font-size: 14px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }
    
    .back-btn:hover {
        color: #1976d2;
    }
    
    /* Layout */
    .booking-layout {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 24px;
    }
    
    /* Left Column */
    .booking-form-section {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    /* Card */
    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    
    .info-card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
    }
    
    /* Login Prompt */
    .login-prompt {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .login-prompt-text {
        font-size: 14px;
        color: #0d47a1;
    }
    
    .btn-login-prompt {
        background: #1976d2;
        color: #fff;
        padding: 8px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .btn-login-prompt:hover {
        background: #1565c0;
    }
    
    /* Form Group */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-label .required {
        color: #ef4444;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .form-input:focus {
        border-color: #1976d2;
        outline: none;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }
    
    .phone-input-group {
        display: flex;
        gap: 10px;
    }
    
    .country-code {
        width: 120px;
    }
    
    /* Success Alert */
    .success-alert {
        background: #d1fae5;
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
    }
    
    .success-alert i {
        color: #059669;
        font-size: 18px;
    }
    
    .success-alert p {
        margin: 0;
        color: #047857;
        font-size: 14px;
    }
    
    /* Utilities Section */
    .utilities-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .utility-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        gap: 16px;
        align-items: flex-start;
        transition: all 0.3s ease;
    }
    
    .utility-item:hover {
        border-color: #1976d2;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.1);
    }
    
    .utility-checkbox {
        width: 20px;
        height: 20px;
        accent-color: #1976d2;
        cursor: pointer;
        flex-shrink: 0;
    }
    
    .utility-content {
        flex: 1;
    }
    
    .utility-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .utility-title {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
    }
    
    .utility-price {
        font-size: 15px;
        font-weight: 700;
        color: #1976d2;
    }
    
    .utility-desc {
        font-size: 13px;
        color: #64748b;
        line-height: 1.5;
        margin: 0;
    }
    
    .utility-badge {
        display: inline-block;
        background: #fef3c7;
        color: #92400e;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .utility-details {
        margin-top: 12px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 6px;
        border-left: 3px solid #1976d2;
    }
    
    .utility-details p {
        margin: 0;
        font-size: 13px;
        color: #475569;
        line-height: 1.6;
    }
    
    .utility-link {
        color: #1976d2;
        text-decoration: none;
        font-weight: 600;
    }
    
    /* Right Column - Trip Summary */
    .trip-summary {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        height: fit-content;
        position: sticky;
        top: 20px;
    }
    
    .summary-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
    }
    
    .summary-price {
        font-size: 28px;
        font-weight: 700;
        color: #ef4444;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .price-dropdown {
        font-size: 14px;
        color: #64748b;
        cursor: pointer;
    }
    
    /* Trip Info */
    .trip-info-section {
        margin-bottom: 20px;
    }
    
    .trip-info-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }
    
    .trip-date {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background: #f1f5f9;
        border-radius: 8px;
        margin-bottom: 16px;
    }
    
    .trip-date i {
        color: #1976d2;
    }
    
    .trip-date span {
        font-size: 14px;
        color: #1e293b;
        font-weight: 600;
    }
    
    .trip-date a {
        margin-left: auto;
        color: #1976d2;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }
    
    /* Bus Card */
    .bus-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .bus-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .bus-logo {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
    }
    
    .bus-info h4 {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px 0;
    }
    
    .bus-info p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    
    /* Route Timeline */
    .route-timeline {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .route-point {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    
    .route-time {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        min-width: 50px;
    }
    
    .route-date {
        font-size: 12px;
        color: #64748b;
        display: block;
    }
    
    .route-location {
        flex: 1;
        font-size: 13px;
        color: #475569;
        line-height: 1.5;
    }
    
    .route-location strong {
        color: #1e293b;
        display: block;
        margin-bottom: 4px;
    }
    
    .route-location a {
        color: #1976d2;
        text-decoration: none;
        font-size: 12px;
    }
    
    .route-divider {
        height: 30px;
        width: 2px;
        background: #e5e7eb;
        margin-left: 24px;
    }
    
    /* Warning Box */
    .warning-box {
        background: #fef3c7;
        border: 1px solid #fde047;
        border-radius: 8px;
        padding: 12px;
        margin-top: 20px;
    }
    
    .warning-box p {
        margin: 0;
        font-size: 12px;
        color: #92400e;
        line-height: 1.5;
    }
    
    .warning-box a {
        color: #92400e;
        font-weight: 600;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 24px;
    }
    
    .btn-primary {
        background: #1e40af;
        color: #fff;
        padding: 14px 24px;
        border-radius: 8px;
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background: #1e3a8a;
    }
    
    .btn-secondary {
        background: #fbbf24;
        color: #78350f;
        padding: 14px 24px;
        border-radius: 8px;
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: #f59e0b;
    }
    
    .promo-text {
        text-align: center;
        font-size: 13px;
        color: #64748b;
        margin-top: 12px;
    }
    
    .promo-text a {
        color: #1976d2;
        text-decoration: none;
        font-weight: 600;
    }
    
    /* Terms */
    .terms-text {
        font-size: 12px;
        color: #64748b;
        text-align: center;
        margin-top: 16px;
        line-height: 1.6;
    }
    
    .terms-text a {
        color: #1976d2;
        text-decoration: none;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .booking-layout {
            grid-template-columns: 1fr;
        }
        
        .trip-summary {
            position: static;
        }
    }
    
    @media (max-width: 768px) {
        .phone-input-group {
            flex-direction: column;
        }
        
        .country-code {
            width: 100%;
        }
        
        .bus-header {
            flex-direction: column;
            text-align: center;
        }
    }
    
    /* Login Required Modal Styles */
    .login-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        animation: fadeIn 0.3s ease;
    }
    
    .login-modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to { 
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .login-modal {
        background: #fff;
        border-radius: 20px;
        width: 90%;
        max-width: 450px;
        padding: 0;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: slideUp 0.3s ease;
        overflow: hidden;
    }
    
    .login-modal-header {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        padding: 30px;
        text-align: center;
        position: relative;
    }
    
    .login-modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    
    .login-modal-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        z-index: 1;
    }
    
    .login-modal-icon i {
        font-size: 36px;
        color: #fff;
    }
    
    .login-modal-header h3 {
        color: #fff;
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 8px;
        position: relative;
        z-index: 1;
    }
    
    .login-modal-header p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .login-modal-body {
        padding: 30px;
    }
    
    .login-modal-benefits {
        margin-bottom: 25px;
    }
    
    .benefit-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .benefit-item:last-child {
        border-bottom: none;
    }
    
    .benefit-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .benefit-icon.green {
        background: #d1fae5;
        color: #059669;
    }
    
    .benefit-icon.blue {
        background: #dbeafe;
        color: #2563eb;
    }
    
    .benefit-icon.yellow {
        background: #fef3c7;
        color: #d97706;
    }
    
    .benefit-text {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
    }
    
    .login-modal-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .btn-modal-login {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: #fff;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
    }
    
    .btn-modal-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(30, 64, 175, 0.4);
        color: #fff;
    }
    
    .btn-modal-register {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #f8fafc;
        color: #1e40af;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
    }
    
    .btn-modal-register:hover {
        background: #1e40af;
        color: #fff;
        border-color: #1e40af;
    }
    
    .login-modal-divider {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 20px 0;
    }
    
    .login-modal-divider::before,
    .login-modal-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }
    
    .login-modal-divider span {
        font-size: 13px;
        color: #9ca3af;
        font-weight: 500;
    }
    
    .btn-modal-guest {
        display: block;
        text-align: center;
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-modal-guest:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .login-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 2;
    }
    
    .login-modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
</style>

<div class="booking-info-page">
    <div class="container-bi">
        <a href="pickup_dropoff.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        
        <div class="booking-layout">
            <!-- Left Column: Form -->
            <div class="booking-form-section">
                <!-- Contact Info Card -->
                <div class="info-card">
                    <h3 class="info-card-title">Thông tin liên hệ</h3>
                    
                    <?php if (!isLoggedIn()): ?>
                    <div class="login-prompt">
                        <span class="login-prompt-text">Đăng nhập để điền thông tin và nhận điểm khi đặt vé</span>
                        <a href="<?php echo appUrl('user/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="btn-login-prompt">Đăng nhập</a>
                    </div>
                    <?php endif; ?>
                    
                    <form id="bookingForm">
                        <div class="form-group">
                            <label class="form-label">
                                Tên người đi <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="passenger_name" 
                                class="form-input" 
                                placeholder="Họ và tên"
                                value="<?php echo e($user['name'] ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Số điện thoại <span class="required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                name="phone" 
                                class="form-input" 
                                placeholder="Nhập số điện thoại"
                                value="<?php echo e($user['phone'] ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Email để nhận thông tin đặt chỗ <span class="required">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="nvt7040@gmail.com"
                                value="<?php echo e($user['email'] ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="success-alert">
                            <i class="fas fa-check-circle"></i>
                            <p>Thông tin đơn hàng sẽ được gửi qua số điện thoại và email bạn cung cấp.</p>
                        </div>
                    </form>
                </div>
                
                <!-- Utilities Card -->
                <div class="info-card">
                    <h3 class="info-card-title">Tiện ích</h3>
                    
                    <div class="utilities-list">
                        <!-- Insurance -->
                        <div class="utility-item">
                            <input 
                                type="checkbox" 
                                id="insurance" 
                                name="insurance" 
                                class="utility-checkbox"
                                data-price="20000"
                            >
                            <label for="insurance" class="utility-content">
                                <div class="utility-header">
                                    <span class="utility-title">Bảo hiểm chuyến đi (≈20,000đ/ghế)</span>
                                    <span class="utility-price">≈20,000đ/ghế</span>
                                </div>
                                <p class="utility-desc">
                                    Được bồi thường lên đến 240,000,000 đ/ghế.<br>
                                    Cung cấp bởi: <strong>MoMo</strong> & <strong>SafeTrip</strong>
                                </p>
                                <div class="utility-badge">
                                    Hỗ trợ viện phí lên đến 25 triệu đồng nếu cần ký túc tại nạn.
                                </div>
                            </label>
                        </div>
                        
                        <!-- Cancellation Policy -->
                        <div class="utility-item">
                            <div style="width: 20px;"></div>
                            <div class="utility-content">
                                <div class="utility-header">
                                    <span class="utility-title">Bảo hiểm tai nạn</span>
                                </div>
                                <p class="utility-desc">
                                    Hỗ trợ viện phí lên đến 25 triệu đồng nếu khách hàng bị tai nạn.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Refund Policy -->
                        <div class="utility-item">
                            <div style="width: 20px;"></div>
                            <div class="utility-content">
                                <div class="utility-header">
                                    <span class="utility-title">Chính sách Hoàn Hủy chuyến đi</span>
                                </div>
                                <p class="utility-desc">
                                    Hoàn 100% tiền vé nếu hủy chuyến đi thành công 24h trước giờ khởi hành chờ cho kịch hợp quy hoạc kế thế không thắng kể mức thuộc.
                                </p>
                                <div class="utility-details">
                                    <p>
                                        <i class="fas fa-info-circle" style="color: #1976d2;"></i>
                                        CHỉ áp dụng với ngân hàng Việt Nam 
                                        <a href="#" class="utility-link">Chi tiết</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Notice -->
                    <div style="margin-top: 20px; padding: 16px; background: #d1fae5; border-radius: 8px;">
                        <p style="margin: 0; font-size: 13px; color: #047857; line-height: 1.6;">
                            <i class="fas fa-check-circle"></i>
                            Bằng thường tức tuyến nhanh không, tất áp đọng và tốt với 
                            <a href="#" style="color: #047857; font-weight: 600;">Chính sách bảo mật thanh toán</a> và 
                            <a href="#" style="color: #047857; font-weight: 600;">Quy chế</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Trip Summary -->
            <div class="trip-summary">
                <h3 class="summary-title">Tóm tắt</h3>
                
                <div class="summary-price">
                    <?php echo number_format($totalPrice); ?>đ
                    <i class="fas fa-chevron-down price-dropdown"></i>
                </div>
                
                <!-- Trip Info -->
                <div class="trip-info-section">
                    <h4 class="trip-info-title">Thông tin chuyến đi</h4>
                    
                    <div class="trip-date">
                        <i class="far fa-calendar-alt"></i>
                        <?php
                        $departureDate = date('d/m/Y', strtotime($trip['departure_time']));
                        $dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                        $dayOfWeek = $dayNames[date('w', strtotime($trip['departure_time']))];
                        ?>
                        <span><?php echo $dayOfWeek . ', ' . $departureDate; ?></span>
                        <a href="#">Chi tiết</a>
                    </div>
                    
                    <!-- Bus Info -->
                    <div class="bus-card">
                        <div class="bus-header">
                            <img src="<?php echo htmlspecialchars($partnerLogoUrl); ?>" alt="<?php echo e($trip['partner_name']); ?>" class="bus-logo">
                            <div class="bus-info">
                                <h4><?php echo e($trip['partner_name']); ?> <?php echo $trip['total_seats']; ?> phòng</h4>
                                <p><i class="fas fa-user"></i> <?php echo count($selectedSeats); ?> <i class="fas fa-chair"></i> <?php echo implode(', ', $selectedSeats); ?></p>
                            </div>
                        </div>
                        
                        <!-- Route -->
                        <div class="route-timeline">
                            <div class="route-point">
                                <div class="route-time">
                                    <?php 
                                    preg_match('/(\d{2}:\d{2})/', $pickupTime, $timeMatch);
                                    $pickupTimeDisplay = $timeMatch[1] ?? date('H:i', strtotime($trip['departure_time']));
                                    preg_match('/\((\d{2}\/\d{2})\)/', $pickupTime, $dateMatch);
                                    $pickupDateDisplay = $dateMatch[1] ?? date('d/m', strtotime($trip['departure_time']));
                                    ?>
                                    <?php echo $pickupTimeDisplay; ?>
                                    <span class="route-date">(<?php echo $pickupDateDisplay; ?>)</span>
                                </div>
                                <div class="route-location">
                                    <strong><?php echo e($pickupStation); ?></strong>
                                    <a href="pickup_dropoff.php" style="color: #1976d2; text-decoration: none; font-size: 12px; font-weight: 600;">Thay đổi</a>
                                </div>
                            </div>
                            
                            <div class="route-divider"></div>
                            
                            <div class="route-point">
                                <div class="route-time">
                                    <?php echo date('H:i', strtotime($dropoffPoint['arrival_time'])); ?>
                                    <span class="route-date">(<?php echo date('d/m', strtotime($dropoffPoint['arrival_time'])); ?>)</span>
                                </div>
                                <div class="route-location">
                                    <strong><?php echo e($dropoffPoint['arrival_station']); ?></strong>
                                    <a href="pickup_dropoff.php" style="color: #1976d2; text-decoration: none; font-size: 12px; font-weight: 600;">Thay đổi</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cancellation Note -->
                    <div style="margin-top: 12px; padding: 10px; background: #f8fafc; border-radius: 6px; font-size: 12px; color: #64748b;">
                        <i class="fas fa-info-circle"></i> 
                        Hủy miễn phí trước <?php echo date('H:i', strtotime($trip['departure_time'] . ' -6 hours')); ?>. <?php echo $dayOfWeek . ', ' . $departureDate; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn-primary" onclick="submitBooking(this)">
                        Tiếp tục đặt vé
                    </button>
                </div>
                
                <!-- Terms -->
                <p class="terms-text">
                    Bằng việc tiếp tục, bạn đồng ý với 
                    <a href="#">Chính sách bảo mật thanh toán</a> và 
                    <a href="#">Quy chế</a>
                </p>
                
            </div>
        </div>
    </div>
</div>

<script>
// Check if user is logged in
const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;

// Prevent double submit
let isSubmittingBooking = false;

// Calculate total with utilities
function updateTotal() {
    const basePrice = <?php echo $totalPrice; ?>;
    let total = basePrice;
    
    // Add insurance if checked
    const insurance = document.getElementById('insurance');
    if (insurance && insurance.checked) {
        const insurancePrice = parseInt(insurance.dataset.price) * <?php echo count($selectedSeats); ?>;
        total += insurancePrice;
    }
    
    // Update display
    document.querySelector('.summary-price').innerHTML = 
        number_format(total) + 'đ <i class="fas fa-chevron-down price-dropdown"></i>';
}

// Number format helper
function number_format(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

// Listen to utility checkboxes
document.querySelectorAll('.utility-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateTotal);
});

// Login Modal Functions
function showLoginModal() {
    document.getElementById('loginModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLoginModal();
    }
});

// Continue as guest - proceed with booking without login
function continueAsGuest() {
    closeLoginModal();
    const btn = document.querySelector('.btn-primary');
    if (!btn) return;
    // Proceed with actual booking submission
    submitBooking(btn);
}

// Submit booking
function submitBooking(btnElement) {
    const form = document.getElementById('bookingForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Avoid double click / double request
    if (isSubmittingBooking) {
        return;
    }
    isSubmittingBooking = true;
    
    // Disable button immediately to tránh click nhanh
    const btn = btnElement || document.querySelector('.btn-primary');
    if (btn) {
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.textContent = 'Đang xử lý...';
    }
    
    // Check if user is logged in
    if (!isLoggedIn) {
        // Show login modal
        showLoginModal();
        // Nếu user chưa chọn tiếp tục với tư cách khách thì cho phép thử lại
        isSubmittingBooking = false;
        if (btn) {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || 'Tiếp tục đặt vé';
        }
        return;
    }
    
    // User is logged in, proceed with booking
    processBookingSubmission(btn);
}

// Process the actual booking submission
function processBookingSubmission(btn) {
    const form = document.getElementById('bookingForm');
    const formData = new FormData(form);
    
    const data = {
        passenger_name: formData.get('passenger_name'),
        phone: formData.get('country_code') + formData.get('phone'),
        email: formData.get('email'),
        insurance: document.getElementById('insurance') ? document.getElementById('insurance').checked : false,
        csrf_token: '<?php echo generateCsrfToken(); ?>'
    };
    
    // Submit
    fetch('process_booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Server error response:', text);
                let errorData;
                try {
                    errorData = JSON.parse(text);
                } catch (e) {
                    errorData = { error: text };
                }
                throw new Error(errorData.error || errorData.message || 'Server error: ' + response.status);
            });
        }
        
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        });
    })
    .then(result => {
        console.log('Parsed response:', result);
        
        if (result.success && result.data && result.data.booking_id) {
            console.log('Redirecting to payment with booking_id:', result.data.booking_id);
            // Redirect to payment
            window.location.href = 'payment.php?booking_id=' + result.data.booking_id;
        } else {
            const errorMsg = result.error || result.message || 'Có lỗi xảy ra. Vui lòng thử lại.';
            console.error('Booking failed:', errorMsg);
            alert(errorMsg);
            isSubmittingBooking = false;
            if (btn) {
                btn.disabled = false;
                btn.textContent = btn.dataset.originalText || 'Tiếp tục đặt vé';
            }
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        if (error && typeof error.message === 'string' && error.message.includes('Thiếu thông tin chuyến xe')) {
            // Trường hợp session chuyến xe không còn hợp lệ (ví dụ: quay lại trang cũ, reload nhiều lần)
            alert('Phiên đặt vé đã hết hạn hoặc thiếu thông tin chuyến xe. Vui lòng chọn lại chuyến.');
            window.location.href = '<?php echo appUrl("user/search"); ?>';
        } else {
            alert('Có lỗi xảy ra trong quá trình đặt vé. Vui lòng thử lại sau.');
        }
        isSubmittingBooking = false;
        if (btn) {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || 'Tiếp tục đặt vé';
        }
    });
}
</script>

<!-- Login Required Modal -->
<div class="login-modal-overlay" id="loginModal">
    <div class="login-modal">
        <div class="login-modal-header">
            <button type="button" class="login-modal-close" onclick="closeLoginModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="login-modal-icon">
                <i class="fas fa-user-lock"></i>
            </div>
            <h3>Đăng nhập để tiếp tục</h3>
            <p>Vui lòng đăng nhập hoặc đăng ký để hoàn tất đặt vé</p>
        </div>
        <div class="login-modal-body">
            <div class="login-modal-benefits">
                <div class="benefit-item">
                    <div class="benefit-icon green">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <span class="benefit-text">Quản lý vé đã đặt dễ dàng</span>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon blue">
                        <i class="fas fa-history"></i>
                    </div>
                    <span class="benefit-text">Xem lịch sử chuyến đi</span>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon yellow">
                        <i class="fas fa-gift"></i>
                    </div>
                    <span class="benefit-text">Nhận ưu đãi và tích điểm thành viên</span>
                </div>
            </div>
            
            <div class="login-modal-actions">
                <a href="<?php echo appUrl('user/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="btn-modal-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Đăng nhập
                </a>
                <a href="<?php echo appUrl('user/auth/register.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="btn-modal-register">
                    <i class="fas fa-user-plus"></i>
                    Đăng ký tài khoản mới
                </a>
            </div>
            
            <div class="login-modal-divider">
                <span>hoặc</span>
            </div>
            
            <div class="btn-modal-guest" onclick="continueAsGuest()">
                Tiếp tục đặt vé với tư cách khách <i class="fas fa-arrow-right"></i>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer_user.php'; ?>

