<?php
/**
 * Payment Success Page
 * Trang thanh toán thành công
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Allow guest booking - no login required
// requireLogin();

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? 0);

if (empty($bookingId)) {
    redirect(appUrl());
}

// Get booking details
$stmt = $conn->prepare("
    SELECT 
        b.*,
        t.departure_time,
        t.arrival_time,
        r.route_name,
        r.origin,
        r.destination,
        p.name as partner_name,
        p.phone as partner_phone,
        v.vehicle_type,
        v.license_plate
    FROM bookings b
    JOIN trips t ON b.trip_id = t.trip_id
    JOIN routes r ON t.route_id = r.route_id
    JOIN partners p ON t.partner_id = p.partner_id
    JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    redirect(appUrl());
}

// Get seats - try multiple sources
$seatNumbers = [];

// Try booking_seats table first
$bookingSeatsExists = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'booking_seats'");
    $bookingSeatsExists = ($result && $result->num_rows > 0);
} catch (Exception $e) {
    error_log('Error checking booking_seats table: ' . $e->getMessage());
}

if ($bookingSeatsExists) {
    try {
        $seats = $conn->query("SELECT seat_number FROM booking_seats WHERE booking_id = $bookingId")->fetch_all(MYSQLI_ASSOC);
        $seatNumbers = array_column($seats, 'seat_number');
    } catch (Exception $e) {
        error_log('Error getting seats from booking_seats: ' . $e->getMessage());
    }
}

// If no seats from booking_seats, try tickets table
if (empty($seatNumbers)) {
    try {
        $result = $conn->query("SHOW TABLES LIKE 'tickets'");
        $ticketsExists = ($result && $result->num_rows > 0);
        
        if ($ticketsExists) {
            $tickets = $conn->query("SELECT seat_number FROM tickets WHERE booking_id = $bookingId")->fetch_all(MYSQLI_ASSOC);
            $seatNumbers = array_column($tickets, 'seat_number');
        }
    } catch (Exception $e) {
        error_log('Error getting seats from tickets: ' . $e->getMessage());
    }
}

// If still empty, use default
if (empty($seatNumbers)) {
    $seatNumbers = ['N/A'];
}

// Get passenger info from first ticket
$passengerName = 'N/A';
$passengerPhone = 'N/A';
$passengerEmail = 'N/A';

try {
    $stmt = $conn->prepare("SELECT passenger_name, passenger_phone, passenger_email FROM tickets WHERE booking_id = ? LIMIT 1");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $passenger = $stmt->get_result()->fetch_assoc();
    
    if ($passenger) {
        $passengerName = $passenger['passenger_name'] ?? 'N/A';
        $passengerPhone = $passenger['passenger_phone'] ?? 'N/A';
        $passengerEmail = $passenger['passenger_email'] ?? 'N/A';
    }
} catch (Exception $e) {
    error_log('Error getting passenger info: ' . $e->getMessage());
}

$pageTitle = 'Thanh toán thành công - BusBooking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
    .success-page {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        min-height: 100vh;
        padding: 60px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .success-container {
        max-width: 700px;
        width: 100%;
    }
    
    /* Success Icon */
    .success-icon {
        text-align: center;
        margin-bottom: 30px;
        animation: scaleIn 0.5s ease;
    }
    
    .success-icon-circle {
        width: 120px;
        height: 120px;
        background: #fff;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    
    .success-icon-circle i {
        font-size: 60px;
        color: #10b981;
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    /* Success Card */
    .success-card {
        background: #fff;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.5s ease 0.2s both;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .success-title {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }
    
    .success-subtitle {
        text-align: center;
        font-size: 16px;
        color: #64748b;
        margin-bottom: 30px;
    }
    
    /* Booking Info */
    .booking-code-section {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .booking-code-label {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 8px;
    }
    
    .booking-code-value {
        font-size: 28px;
        font-weight: 700;
        color: #3b82f6;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }
    
    /* Details */
    .booking-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .detail-item {
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
    }
    
    .detail-label {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
    }
    
    .detail-value {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
    }
    
    .detail-value.highlight {
        color: #3b82f6;
        font-size: 18px;
    }
    
    /* Actions */
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 30px;
    }
    
    .btn {
        flex: 1;
        padding: 16px 24px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary:hover {
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #fff;
        color: #475569;
        border: 2px solid #e5e7eb;
    }
    
    .btn-secondary:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }
    
    /* Info Box */
    .info-box {
        background: #fef3c7;
        border: 2px solid #fbbf24;
        border-radius: 12px;
        padding: 16px;
        margin-top: 20px;
        display: flex;
        gap: 12px;
    }
    
    .info-box i {
        color: #f59e0b;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .info-box-content {
        flex: 1;
    }
    
    .info-box-content p {
        margin: 0;
        font-size: 14px;
        color: #78350f;
        line-height: 1.6;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .success-card {
            padding: 30px 20px;
        }
        
        .success-title {
            font-size: 24px;
        }
        
        .booking-code-value {
            font-size: 22px;
        }
        
        .booking-details-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="success-page">
    <div class="success-container">
        <!-- Success Icon -->
        <div class="success-icon">
            <div class="success-icon-circle">
                <i class="fas fa-check"></i>
            </div>
        </div>
        
        <!-- Success Card -->
        <div class="success-card">
            <h1 class="success-title">🎉 Đặt vé thành công!</h1>
            <p class="success-subtitle">
                Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của BusBooking
            </p>
            
            <!-- Booking Code -->
            <div class="booking-code-section">
                <div class="booking-code-label">Mã đặt vé của bạn</div>
                <div class="booking-code-value"><?php echo e($booking['booking_code']); ?></div>
            </div>
            
            <!-- Booking Details -->
            <div class="booking-details-grid">
                <div class="detail-item">
                    <div class="detail-label">Tuyến đường</div>
                    <div class="detail-value"><?php echo e($booking['route_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Nhà xe</div>
                    <div class="detail-value"><?php echo e($booking['partner_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Loại xe</div>
                    <div class="detail-value"><?php echo ucfirst($booking['vehicle_type']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Biển số xe</div>
                    <div class="detail-value"><?php echo e($booking['license_plate']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Khởi hành</div>
                    <div class="detail-value">
                        <?php echo date('H:i', strtotime($booking['departure_time'])); ?><br>
                        <small style="font-size: 13px; color: #64748b;">
                            <?php echo date('d/m/Y', strtotime($booking['departure_time'])); ?>
                        </small>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Ghế đã đặt</div>
                    <div class="detail-value highlight"><?php echo implode(', ', $seatNumbers); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Hành khách</div>
                    <div class="detail-value"><?php echo e($passengerName); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Tổng tiền</div>
                    <div class="detail-value highlight"><?php echo number_format($booking['final_price'] ?? 0); ?>đ</div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="action-buttons">
                <a href="<?php echo appUrl('user/tickets/eticket.php?booking_id=' . $bookingId); ?>" class="btn btn-primary">
                    <i class="fas fa-ticket-alt"></i> Xem vé điện tử
                </a>
                <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Danh sách vé
                </a>
                <a href="<?php echo appUrl(); ?>" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div class="info-box-content">
                    <p>
                        <strong>Lưu ý quan trọng:</strong><br>
                        • Vui lòng có mặt tại điểm đón trước giờ khởi hành <strong>15 phút</strong><br>
                        • Mang theo CMND/CCCD để đối chiếu thông tin<br>
                        • Thông tin chi tiết đã được gửi qua email: <strong><?php echo e($passengerEmail); ?></strong><br>
                        • Liên hệ hotline nhà xe: <strong><?php echo e($booking['partner_phone'] ?? '1900-xxxx'); ?></strong> nếu cần hỗ trợ
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Confetti animation (optional)
function createConfetti() {
    const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
    const confettiCount = 50;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            top: -20px;
            left: ${Math.random() * 100}vw;
            opacity: ${Math.random()};
            animation: fall ${2 + Math.random() * 3}s linear;
            z-index: 9999;
        `;
        document.body.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 5000);
    }
}

// Add keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes fall {
        to {
            transform: translateY(100vh) rotate(360deg);
        }
    }
`;
document.head.appendChild(style);

// Trigger confetti
createConfetti();
</script>

<?php include '../../includes/footer_user.php'; ?>

