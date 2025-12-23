<?php
/**
 * Payment Page - Modern Design with VietQR
 * Trang thanh toán hiện đại với VietQR
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';
require_once '../../core/PromotionService.php';

// Get booking ID from GET parameter or session
$bookingId = intval($_GET['booking_id'] ?? $_SESSION['booking_id'] ?? 0);

if (empty($bookingId)) {
    $_SESSION['error'] = 'Không tìm thấy thông tin đặt vé. Vui lòng đặt vé lại.';
    redirect(appUrl());
}

// Save booking_id to session for consistency
$_SESSION['booking_id'] = $bookingId;

// Get booking details FIRST (before checking expiry)
$sql = "
    SELECT 
        b.*,
        r.origin,
        r.destination,
        r.route_name,
        t.departure_time,
        t.arrival_time,
        p.name as partner_name,
        p.phone as partner_phone
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    LEFT JOIN routes r ON t.route_id = r.route_id
    LEFT JOIN partners p ON t.partner_id = p.partner_id
    WHERE b.booking_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = 'Không tìm thấy thông tin đặt vé.';
    redirect(appUrl());
}

// SECURITY: Verify user_id - prevent unauthorized payment access
// Allow guest booking (user_id = 0 or guest user) but verify if logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    // If booking has a real user_id, it must match current user
    if ($booking['user_id'] > 0 && $booking['user_id'] != $userId) {
        logError('Unauthorized payment page access', [
            'booking_id' => $bookingId,
            'booking_user_id' => $booking['user_id'],
            'current_user_id' => $userId
        ]);
        $_SESSION['error'] = 'Bạn không có quyền truy cập trang thanh toán này';
        redirect(appUrl('user/tickets/my_tickets.php'));
    }
}

// Set expiry time 30 phút cho từng booking dựa trên created_at
$now = time();
$createdAt = isset($booking['created_at']) ? strtotime($booking['created_at']) : $now;
$expiryTime = $createdAt + (30 * 60); // 30 minutes per booking

// Nếu hết hạn: hủy booking và mở ghế
if ($expiryTime > 0 && $now > $expiryTime) {
    try {
        $conn->begin_transaction();
        
        // Hủy booking
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', payment_status = 'unpaid', updated_at = NOW() WHERE booking_id = ?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();

        // Hủy tickets để trả ghế (nếu có)
        $stmt = $conn->prepare("UPDATE tickets SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Increment available_seats khi booking hết hạn
        if (!empty($booking['trip_id'])) {
            $seatsStmt = $conn->prepare("SELECT COUNT(*) as seat_count FROM tickets WHERE booking_id = ?");
            $seatsStmt->bind_param("i", $bookingId);
            $seatsStmt->execute();
            $seatsResult = $seatsStmt->get_result()->fetch_assoc();
            $seatCount = $seatsResult['seat_count'] ?? 0;
            
            if ($seatCount > 0) {
                $updateSeatsStmt = $conn->prepare("UPDATE trips SET available_seats = available_seats + ? WHERE trip_id = ?");
                $updateSeatsStmt->bind_param("ii", $seatCount, $booking['trip_id']);
                $updateSeatsStmt->execute();
            }
        }
        
        $conn->commit();
    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        error_log('Error cancelling expired booking: ' . $e->getMessage());
        // Bỏ qua lỗi nhưng vẫn chuyển hướng
    }

    // Xóa session booking
    unset($_SESSION['booking_id'], $_SESSION['booking_expiry'], $_SESSION['booking_trip_id'], $_SESSION['booking_seats'], $_SESSION['booking_price'], $_SESSION['booking_pickup_id'], $_SESSION['booking_pickup_time'], $_SESSION['booking_pickup_station'], $_SESSION['booking_dropoff_id'], $_SESSION['booking_dropoff_time'], $_SESSION['booking_dropoff_station']);

    $_SESSION['error'] = 'Đơn hàng đã hết hạn thanh toán (30 phút). Vui lòng đặt lại.';
    redirect(appUrl('user/search'));
}

// Get seat numbers from tickets
$stmt = $conn->prepare("SELECT seat_number FROM tickets WHERE booking_id = ? ORDER BY seat_number");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$seatNumbers = [];
while ($row = $result->fetch_assoc()) {
    $seatNumbers[] = $row['seat_number'];
}

$promoMessage = '';
$promoError = '';

// Handle promotion apply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    $promoCode = trim($_POST['promo_code'] ?? '');
    if ($promoCode === '') {
        $promoError = 'Vui lòng nhập mã khuyến mãi.';
    } else {
        try {
            $result = PromotionService::applyPromotion($conn, $promoCode, (float)$booking['total_price']);
            $promo = $result['promotion'];
            $discount = $result['discount'];
            $final = $result['final'];

            // Lưu vào booking
            $stmt = $conn->prepare("UPDATE bookings SET discount_amount = ?, final_price = ?, updated_at = NOW() WHERE booking_id = ?");
            $stmt->bind_param("ddi", $discount, $final, $bookingId);
            $stmt->execute();

            // Lưu session để cộng used_count khi thanh toán thành công
            $_SESSION['applied_promotion'] = [
                'promotion_id' => $promo['promotion_id'],
                'code' => $promo['code'],
                'booking_id' => $bookingId,
                'discount' => $discount,
            ];

            $promoMessage = "Áp dụng mã {$promo['code']} thành công! Giảm " . number_format($discount) . "đ.";

            // Reload booking data & amount
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            $amount = intval($booking['final_price']);
        } catch (Exception $e) {
            $promoError = $e->getMessage();
        }
    }
}

// Get selected payment method - MẶC ĐỊNH hiển thị 3 options để chọn
$selectedMethod = $_GET['method'] ?? 'choose';

// Handle VNPay payment
if ($selectedMethod === 'vnpay') {
    require_once '../../config/vnpay.php';
    require_once '../../core/VNPayService.php';
    
    $amount = intval($booking['final_price']);
    $orderInfo = "Thanh toan ve xe " . $booking['booking_code'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // ============================================
    // Lưu session restore token trước khi redirect đến VNPay
    // Để có thể restore session sau khi VNPay redirect về
    // ============================================
    if (isLoggedIn()) {
        // Lưu user_id vào session để restore sau khi redirect về
        $_SESSION['vnpay_restore_user_id'] = getCurrentUserId();
        $_SESSION['vnpay_restore_booking_id'] = $bookingId;
        $_SESSION['vnpay_restore_time'] = time();
    }
    
    // Create VNPay payment URL
    $vnpayUrl = VNPayService::createPaymentUrl($bookingId, $amount, $orderInfo, $ipAddress);
    
    // Redirect to VNPay
    header('Location: ' . $vnpayUrl);
    exit;
}

// Handle COD payment - Redirect to confirm_payment.php để xử lý tập trung
if ($selectedMethod === 'cod') {
    // Redirect to confirm_payment.php để xử lý COD payment tập trung
    // Điều này đảm bảo logic xử lý COD chỉ ở một nơi và update tickets đúng cách
    redirect(appUrl('user/booking/confirm_payment.php?booking_id=' . $bookingId . '&method=cod'));
}

// VietQR Configuration (for bank transfer)
$bank_id = "970436"; // VietinBank (có thể đổi)
$account_no = "1036218727"; // Số TK thật
$account_name = "CONG TY BUS BOOKING"; // Tên chủ TK (IN HOA, KHÔNG DẤU)
$amount = intval($booking['final_price']);
$description = $booking['booking_code'];

// Build VietQR URL
$vietqr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png";
$vietqr_url .= "?amount=" . $amount;
$vietqr_url .= "&addInfo=" . urlencode($description);
$vietqr_url .= "&accountName=" . urlencode($account_name);

// Calculate remaining time
$remainingSeconds = max(0, $expiryTime - $now);

$pageTitle = 'Thanh toán - Bus Booking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
/* Modern Payment Page Design */
:root {
    --primary: #1E90FF;
    --primary-dark: #1873CC;
    --success: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --gray-50: #F9FAFB;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --gray-400: #9CA3AF;
    --gray-500: #6B7280;
    --gray-600: #4B5563;
    --gray-700: #374151;
    --gray-800: #1F2937;
    --gray-900: #111827;
}

body {
    background: #f3f4f6;
    min-height: 100vh;
}

.payment-page {
    padding: 40px 20px;
    min-height: calc(100vh - 100px);
}

.payment-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Timer Card */
.timer-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.timer-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--primary), var(--success));
}

.timer-label {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.timer-display {
    font-size: 52px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--success));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-family: 'Courier New', monospace;
    letter-spacing: 3px;
}

.timer-display.warning {
    background: linear-gradient(135deg, var(--warning), var(--danger));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: pulse 1s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.02); }
}

/* Main Payment Card */
.payment-card {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    display: grid;
    grid-template-columns: 1fr 400px;
    min-height: 600px;
}

/* Left - QR Section */
.qr-section {
    padding: 50px 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    position: relative;
}

.qr-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(30, 144, 255, 0.1), transparent);
    border-radius: 50%;
}

.qr-header {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
    z-index: 1;
}

.qr-title {
    font-size: 28px;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 10px;
}

.qr-subtitle {
    font-size: 15px;
    color: var(--gray-600);
}

.qr-wrapper {
    background: white;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 25px;
    position: relative;
    z-index: 1;
}

.qr-code-img {
    width: 320px;
    height: 320px;
    display: block;
    border-radius: 12px;
}

.qr-footer {
    text-align: center;
    position: relative;
    z-index: 1;
}

.qr-note {
    font-size: 14px;
    color: var(--gray-700);
    margin-bottom: 15px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.qr-note i {
    color: var(--primary);
    margin-right: 8px;
}

.booking-code-display {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
    letter-spacing: 2px;
    font-family: 'Courier New', monospace;
}

/* Right - Details Section */
.details-section {
    padding: 50px 40px;
    background: white;
    overflow-y: auto;
}

.details-header {
    margin-bottom: 30px;
}

.details-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.details-subtitle {
    font-size: 14px;
    color: var(--gray-600);
}

/* Info Item */
.info-item {
    padding: 16px 0;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 14px;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-label i {
    width: 20px;
    color: var(--primary);
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
    text-align: right;
    max-width: 200px;
    word-wrap: break-word;
}

.copy-btn {
    background: var(--gray-100);
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    margin-left: 8px;
    color: var(--gray-600);
}

.copy-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

/* Bank Info Card */
.bank-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px;
    border-radius: 16px;
    margin: 25px 0;
    color: white;
}

.bank-card-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bank-item {
    margin-bottom: 15px;
}

.bank-item:last-child {
    margin-bottom: 0;
}

.bank-label {
    font-size: 12px;
    opacity: 0.9;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bank-value {
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bank-value .copy-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.bank-value .copy-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Total Amount */
.total-amount {
    background: var(--gray-50);
    padding: 25px;
    border-radius: 16px;
    margin-top: 25px;
}

.total-label {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 8px;
}

.total-value {
    font-size: 36px;
    font-weight: 800;
    color: var(--primary);
}

/* Action Buttons */
.action-buttons {
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn {
    padding: 16px 24px;
    border-radius: 12px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(30, 144, 255, 0.4);
}

.btn-outline {
    background: white;
    color: var(--gray-700);
    border: 2px solid var(--gray-300);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* Alert */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-top: 20px;
    display: flex;
    align-items: start;
    gap: 12px;
    font-size: 13px;
    line-height: 1.6;
}

.alert-warning {
    background: #FEF3C7;
    color: #92400E;
    border-left: 4px solid var(--warning);
}

.alert i {
    margin-top: 2px;
}

/* Responsive */
@media (max-width: 768px) {
    .payment-card {
        grid-template-columns: 1fr;
    }
    
    .qr-section {
        padding: 40px 20px;
    }
    
    .details-section {
        padding: 40px 20px;
    }
    
    .timer-display {
        font-size: 40px;
    }
    
    .qr-code-img {
        width: 280px;
        height: 280px;
    }
    
    .qr-title {
        font-size: 24px;
    }
    
    .total-value {
        font-size: 28px;
    }
}

/* Loading Animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
</style>

<div class="payment-page">
    <div class="payment-container">
        
        <!-- Timer -->
        <div class="timer-card">
            <div class="timer-label">
                <i class="fas fa-clock"></i> Thời gian còn lại để thanh toán
            </div>
            <div class="timer-display" id="countdown"><?php echo gmdate('i:s', $remainingSeconds); ?></div>
        </div>
        
        <?php if ($selectedMethod === 'choose'): ?>
        <!-- Choose Payment Method -->
        <div class="payment-card" style="padding: 40px; display: block !important; grid-template-columns: none !important;">
            <h2 style="text-align: center; color: #1f2937; margin-bottom: 30px; font-size: 28px; font-weight: 700;">
                <i class="fas fa-credit-card"></i> Chọn phương thức thanh toán
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 20px; width: 100%;">
                <!-- VNPay -->
                <a href="?method=vnpay" style="display: flex; align-items: center; gap: 20px; padding: 25px 30px; border: 2px solid #e5e7eb; border-radius: 15px; text-decoration: none; transition: all 0.3s; cursor: pointer; width: 100%;">
                    <div style="width: 70px; height: 70px; min-width: 70px; display: flex; align-items: center; justify-content: center; background: #fff5f5; border-radius: 12px;">
                        <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay" style="max-width: 60px;">
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h3 style="font-size: 19px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">VNPay</h3>
                        <p style="color: #6b7280; margin: 0 0 8px 0; font-size: 14px;">Thanh toán qua ví điện tử / Thẻ ngân hàng</p>
                        <div>
                            <span style="background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-right: 6px;">⚡ Nhanh chóng</span>
                            <span style="background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">🔒 Bảo mật</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="font-size: 22px; color: #FF3838; min-width: 22px;"></i>
                </a>
                
                <!-- Bank Transfer -->
                <a href="?method=bank_transfer" style="display: flex; align-items: center; gap: 20px; padding: 25px 30px; border: 2px solid #e5e7eb; border-radius: 15px; text-decoration: none; transition: all 0.3s; cursor: pointer; width: 100%;">
                    <div style="width: 70px; height: 70px; min-width: 70px; display: flex; align-items: center; justify-content: center; background: #eff6ff; border-radius: 12px;">
                        <i class="fas fa-university" style="font-size: 42px; color: #2196F3;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h3 style="font-size: 19px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Chuyển khoản ngân hàng</h3>
                        <p style="color: #6b7280; margin: 0 0 8px 0; font-size: 14px;">Quét mã QR hoặc chuyển khoản thủ công</p>
                        <div>
                            <span style="background: #dbeafe; color: #1e40af; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">⭐ Phổ biến</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="font-size: 22px; color: #2196F3; min-width: 22px;"></i>
                </a>
                
                <!-- COD -->
                <a href="?method=cod" style="display: flex; align-items: center; gap: 20px; padding: 25px 30px; border: 2px solid #e5e7eb; border-radius: 15px; text-decoration: none; transition: all 0.3s; cursor: pointer; width: 100%;">
                    <div style="width: 70px; height: 70px; min-width: 70px; display: flex; align-items: center; justify-content: center; background: #f0fdf4; border-radius: 12px;">
                        <i class="fas fa-money-bill-wave" style="font-size: 42px; color: #4CAF50;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h3 style="font-size: 19px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Thanh toán khi lên xe</h3>
                        <p style="color: #6b7280; margin: 0 0 8px 0; font-size: 14px;">Thanh toán tiền mặt khi lên xe</p>
                        <div>
                            <span style="background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">💵 Tiện lợi</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="font-size: 22px; color: #4CAF50; min-width: 22px;"></i>
                </a>
            </div>
            
            <style>
            a[href^="?method"]:hover {
                border-color: var(--primary) !important;
                box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
                transform: translateY(-2px);
            }
            </style>
        </div>
        
        <?php elseif ($selectedMethod === 'bank_transfer'): ?>
        <!-- Main Payment Card - Bank Transfer -->
        <div class="payment-card">
            
            <!-- Left: QR Code -->
            <div class="qr-section">
                <div class="qr-header">
                    <div class="qr-title">
                        <i class="fas fa-qrcode"></i> Quét mã QR
                    </div>
                    <div class="qr-subtitle">
                        Sử dụng app ngân hàng để quét và thanh toán
                    </div>
                </div>
                
                <div class="qr-wrapper">
                    <img src="<?php echo $vietqr_url; ?>" 
                         alt="VietQR Payment" 
                         class="qr-code-img">
                </div>
                
                <div class="qr-footer">
                    <div class="qr-note">
                        <i class="fas fa-mobile-alt"></i>
                        Mở app ngân hàng → Quét QR → Xác nhận thanh toán
                    </div>
                    <div class="booking-code-display">
                        <?php echo e($booking['booking_code']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Right: Booking Details -->
            <div class="details-section">
                <div class="details-header">
                    <div class="details-title">Chi tiết đơn hàng</div>
                    <div class="details-subtitle">Thông tin chuyến đi của bạn</div>
                </div>
                
                <!-- Promo Apply -->
                <form method="POST" class="mb-3 d-flex gap-2 align-items-center flex-wrap">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="apply_promo" value="1">
                    <input type="text" name="promo_code" class="form-control" placeholder="Nhập mã khuyến mãi" style="max-width: 220px;">
                    <button type="submit" class="btn btn-success"><i class="fas fa-tag"></i> Áp dụng</button>
                </form>
                <?php if ($promoMessage): ?>
                    <div class="alert alert-success py-2 px-3" style="border-radius: 10px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($promoMessage); ?>
                    </div>
                <?php elseif ($promoError): ?>
                    <div class="alert alert-danger py-2 px-3" style="border-radius: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($promoError); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Trip Info -->
                <div style="margin-bottom: 25px;">
                    <div class="info-item">
                        <span class="info-label">
                            <i class="fas fa-route"></i> Tuyến đường
                        </span>
                        <span class="info-value">
                            <?php echo e($booking['origin'] ?? 'N/A'); ?> → <?php echo e($booking['destination'] ?? 'N/A'); ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">
                            <i class="fas fa-building"></i> Nhà xe
                        </span>
                        <span class="info-value">
                            <?php echo e($booking['partner_name'] ?? 'N/A'); ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">
                            <i class="fas fa-calendar-alt"></i> Khởi hành
                        </span>
                        <span class="info-value">
                            <?php echo $booking['departure_time'] ? date('H:i, d/m/Y', strtotime($booking['departure_time'])) : 'N/A'; ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">
                            <i class="fas fa-chair"></i> Ghế đã chọn
                        </span>
                        <span class="info-value">
                            <?php echo !empty($seatNumbers) ? implode(', ', $seatNumbers) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Bank Info -->
                <div class="bank-card">
                    <div class="bank-card-title">
                        <i class="fas fa-university"></i>
                        Thông tin chuyển khoản
                    </div>
                    
                    <div class="bank-item">
                        <div class="bank-label">Ngân hàng</div>
                        <div class="bank-value">
                            VietinBank (CTG)
                            <button class="copy-btn" onclick="copyText('VietinBank')">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bank-item">
                        <div class="bank-label">Số tài khoản</div>
                        <div class="bank-value">
                            <?php echo $account_no; ?>
                            <button class="copy-btn" onclick="copyText('<?php echo $account_no; ?>')">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bank-item">
                        <div class="bank-label">Chủ tài khoản</div>
                        <div class="bank-value">
                            <?php echo $account_name; ?>
                            <button class="copy-btn" onclick="copyText('<?php echo $account_name; ?>')">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bank-item">
                        <div class="bank-label">Nội dung chuyển khoản</div>
                        <div class="bank-value">
                            <?php echo e($description); ?>
                            <button class="copy-btn" onclick="copyText('<?php echo $description; ?>')">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Total -->
                <div class="total-amount">
                    <div class="total-label">Tổng thanh toán</div>
                    <div class="total-value"><?php echo number_format($amount); ?>đ</div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button onclick="checkPayment()" class="btn btn-primary" id="confirmBtn">
                        <i class="fas fa-check-circle"></i>
                        Tôi đã chuyển khoản
                    </button>
                    
                    <a href="<?php echo appUrl(); ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Hủy đơn hàng
                    </a>
                </div>
                
                <!-- Verification Info -->
                <div class="alert" style="background: #DBEAFE; color: #1E40AF; border-left: 4px solid #2563EB;">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Quy trình xác nhận:</strong><br>
                        • Sau khi nhấn "Tôi đã chuyển khoản", hệ thống sẽ kiểm tra giao dịch<br>
                        • Thanh toán sẽ được xác nhận tự động trong vài giây đến vài phút<br>
                        • Bạn không cần làm gì thêm, chỉ cần đợi xác nhận
                    </div>
                </div>
                
                <!-- Warning Alert -->
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Lưu ý quan trọng:</strong><br>
                        • Vui lòng thanh toán đúng số tiền và nội dung chuyển khoản<br>
                        • Đơn hàng sẽ tự động hủy sau khi hết thời gian<br>
                        • Liên hệ hotline nếu cần hỗ trợ: <strong><?php echo e($booking['partner_phone'] ?? '1900-xxxx'); ?></strong>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<?php elseif ($selectedMethod === 'cod'): ?>
<!-- COD Payment - Thanh toán khi lên xe -->
<div class="payment-card" style="padding: 40px; text-align: center;">
    <div style="margin-bottom: 30px;">
        <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-check-circle" style="font-size: 60px; color: white;"></i>
        </div>
        <h2 style="color: #1f2937; font-size: 28px; font-weight: 700; margin-bottom: 15px;">
            Đặt vé thành công!
        </h2>
        <p style="color: #6b7280; font-size: 16px; margin-bottom: 30px;">
            Bạn đã chọn thanh toán bằng <strong>tiền mặt khi lên xe</strong>
        </p>
    </div>
    
    <!-- Booking Info -->
    <div style="background: #f9fafb; border-radius: 15px; padding: 30px; margin-bottom: 30px; text-align: left;">
        <h3 style="color: #1f2937; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-ticket-alt"></i> Thông tin vé của bạn
        </h3>
        
        <div style="display: grid; gap: 15px;">
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280; font-weight: 500;"><i class="fas fa-barcode"></i> Mã đặt vé:</span>
                <span style="color: #1f2937; font-weight: 700; font-size: 18px;"><?php echo e($booking['booking_code']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280; font-weight: 500;"><i class="fas fa-route"></i> Tuyến đường:</span>
                <span style="color: #1f2937; font-weight: 600;"><?php echo e($booking['origin'] ?? $booking['route_name']); ?> → <?php echo e($booking['destination'] ?? ''); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280; font-weight: 500;"><i class="fas fa-calendar-alt"></i> Giờ xuất bến:</span>
                <span style="color: #1f2937; font-weight: 600;"><?php echo date('H:i - d/m/Y', strtotime($booking['departure_time'])); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280; font-weight: 500;"><i class="fas fa-chair"></i> Số ghế:</span>
                <span style="color: #1f2937; font-weight: 600;"><?php echo implode(', ', $seatNumbers); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0;">
                <span style="color: #6b7280; font-weight: 500;"><i class="fas fa-money-bill-wave"></i> Tổng tiền:</span>
                <span style="color: #10b981; font-weight: 700; font-size: 24px;"><?php echo number_format($booking['final_price']); ?>đ</span>
            </div>
        </div>
    </div>
    
    <!-- Instructions -->
    <div style="background: #fffbeb; border: 2px solid #fbbf24; border-radius: 15px; padding: 25px; margin-bottom: 30px; text-align: left;">
        <h4 style="color: #92400e; font-size: 18px; font-weight: 700; margin-bottom: 15px;">
            <i class="fas fa-info-circle"></i> Lưu ý quan trọng:
        </h4>
        <ul style="color: #78350f; line-height: 1.8; margin: 0; padding-left: 20px;">
            <li>Vui lòng <strong>đến điểm đón</strong> trước giờ xuất bến <strong>15-20 phút</strong></li>
            <li>Chuẩn bị <strong>đủ tiền mặt</strong> để thanh toán cho tài xế</li>
            <li>Xuất trình <strong>mã đặt vé</strong> (<?php echo e($booking['booking_code']); ?>) cho nhân viên</li>
            <li>Nếu không đến hoặc đến muộn, vé sẽ <strong>bị hủy tự động</strong></li>
            <li>Liên hệ hotline <strong>1900-xxxx</strong> nếu cần hỗ trợ</li>
        </ul>
    </div>
    
    <!-- Actions -->
    <div style="display: flex; gap: 15px; justify-content: center;">
        <a href="<?php echo appUrl('user/tickets/eticket.php?booking_id=' . $bookingId); ?>" class="btn btn-primary" style="padding: 15px 36px; font-size: 16px; font-weight: 700; border-radius: 12px;">
            <i class="fas fa-ticket-alt"></i> Xem vé của tôi
        </a>
        <a href="<?php echo appUrl('index.php'); ?>" class="btn btn-outline-primary" style="padding: 15px 36px; font-size: 16px; font-weight: 600; border-radius: 12px;">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
    </div>
</div>

<?php endif; ?>

</main>

<script>
// Countdown Timer - luôn chạy
let remainingSeconds = <?php echo $remainingSeconds; ?>;
const countdownEl = document.getElementById('countdown');

function updateTimer() {
    if (!countdownEl) return;
    if (remainingSeconds <= 0) {
        clearInterval(timerInterval);
        countdownEl.textContent = '00:00';
        countdownEl.classList.add('warning');
        return;
    }
    const minutes = Math.floor(remainingSeconds / 60);
    const seconds = remainingSeconds % 60;
    countdownEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    if (remainingSeconds < 60) countdownEl.classList.add('warning'); else countdownEl.classList.remove('warning');
    remainingSeconds--;
}
const timerInterval = setInterval(updateTimer, 1000);
updateTimer();

// Copy to clipboard
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: #10B981; color: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 9999; font-weight: 600; animation: slideIn 0.3s ease;';
        toast.innerHTML = '<i class="fas fa-check-circle"></i> Đã sao chép!';
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    });
}

// Check payment - Redirect to confirm_payment with bank_transfer method
function checkPayment() {
    const btn = document.getElementById('confirmBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="loading"></span> Đang xử lý...';
    }
    // Redirect to confirm_payment with bank_transfer method to start verification flow
    window.location.href = 'confirm_payment.php?booking_id=<?php echo $bookingId; ?>&method=bank_transfer';
}

// Animations for toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

