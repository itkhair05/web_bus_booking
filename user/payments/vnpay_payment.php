<?php
/**
 * VNPay Payment Gateway
 * Trang thanh toán VNPay với UI chuyên nghiệp
 */

require_once '../../config/session.php';
$conn = require_once '../../config/db.php';
require_once '../../core/auth.php';
require_once '../../core/helpers.php';
require_once '../../config/vnpay.php';
require_once '../../core/VNPayService.php';

requireLogin();
$userId = getCurrentUserId();

// Get booking ID
$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$bookingId) {
    setFlashMessage('error', 'Không tìm thấy thông tin đặt vé.');
    redirect(appUrl('user/tickets/my_tickets.php'));
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, 
           t.departure_time, t.arrival_time,
           r.start_point, r.end_point, r.distance, r.duration,
           p.name as partner_name, p.phone as partner_phone
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    LEFT JOIN routes r ON t.route_id = r.route_id
    LEFT JOIN partners p ON t.partner_id = p.partner_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    setFlashMessage('error', 'Không tìm thấy thông tin đặt vé.');
    redirect(appUrl('user/tickets/my_tickets.php'));
}

// Get seat numbers
$stmt = $conn->prepare("SELECT seat_number FROM tickets WHERE booking_id = ? ORDER BY seat_number");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$seatNumbers = [];
while ($row = $result->fetch_assoc()) {
    $seatNumbers[] = $row['seat_number'];
}

$pageTitle = 'Thanh toán VNPay';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f3f4f6;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .payment-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .vnpay-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .vnpay-logo {
            height: 50px;
            margin-bottom: 15px;
        }
        
        .badge-secure {
            background: #d1fae5;
            color: #065f46;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .booking-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 600;
        }
        
        .price-breakdown {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        
        .total-price {
            font-size: 32px;
            font-weight: 800;
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 15px;
            margin-top: 10px;
        }
        
        .payment-methods {
            margin-bottom: 25px;
        }
        
        .method-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .method-card.selected {
            border-color: #FF3838;
            background: #fff5f5;
        }
        
        .method-card:hover {
            border-color: #FF3838;
            transform: translateX(5px);
        }
        
        .method-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .method-icon img {
            max-width: 50px;
            max-height: 50px;
        }
        
        .method-info h6 {
            margin-bottom: 5px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .method-info p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .payment-button {
            background: linear-gradient(135deg, #FF3838 0%, #FF1744 100%);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 56, 56, 0.4);
        }
        
        .payment-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .security-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .security-badge i {
            color: #10b981;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .payment-container {
                padding: 0 15px;
            }
            
            .payment-card {
                padding: 25px 20px;
            }
            
            .method-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../../includes/header_user.php'; ?>
    
    <div class="payment-container">
        <!-- VNPay Header -->
        <div class="vnpay-header">
            <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay" class="vnpay-logo">
            <h1 style="color: #FF3838; font-size: 28px; font-weight: 800; margin-bottom: 10px;">
                Thanh Toán An Toàn
            </h1>
            <p class="text-muted mb-3">Được bảo mật bởi VNPay - Cổng thanh toán hàng đầu Việt Nam</p>
            <span class="badge-secure">
                <i class="fas fa-shield-alt"></i> Bảo mật SSL 256-bit
            </span>
        </div>
        
        <div class="row">
            <!-- Left: Payment Details -->
            <div class="col-lg-7 mb-4">
                <div class="payment-card">
                    <h5 class="section-title">
                        <i class="fas fa-file-invoice"></i>
                        Thông tin đặt vé
                    </h5>
                    
                    <div class="booking-info">
                        <div class="info-row">
                            <span class="info-label">Mã đặt vé</span>
                            <span class="info-value"><?php echo e($booking['booking_code']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tuyến đường</span>
                            <span class="info-value">
                                <?php echo e($booking['start_point']); ?> → <?php echo e($booking['end_point']); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nhà xe</span>
                            <span class="info-value"><?php echo e($booking['partner_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Khởi hành</span>
                            <span class="info-value">
                                <?php echo date('H:i, d/m/Y', strtotime($booking['departure_time'])); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ghế</span>
                            <span class="info-value"><?php echo implode(', ', $seatNumbers); ?></span>
                        </div>
                    </div>
                    
                    <h5 class="section-title mt-4">
                        <i class="fas fa-credit-card"></i>
                        Phương thức thanh toán
                    </h5>
                    
                    <div class="payment-methods">
                        <div class="method-card selected" onclick="selectMethod(this)">
                            <div class="method-icon">
                                <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay">
                            </div>
                            <div class="method-info flex-grow-1">
                                <h6>VNPay QR Code</h6>
                                <p>Quét mã QR qua app ngân hàng</p>
                            </div>
                            <i class="fas fa-check-circle text-success" style="font-size: 24px;"></i>
                        </div>
                        
                        <div class="method-card" onclick="selectMethod(this)">
                            <div class="method-icon">
                                <i class="fas fa-credit-card" style="font-size: 28px; color: #2196F3;"></i>
                            </div>
                            <div class="method-info flex-grow-1">
                                <h6>Thẻ ATM / Internet Banking</h6>
                                <p>Thanh toán qua thẻ ngân hàng nội địa</p>
                            </div>
                            <i class="fas fa-circle text-muted" style="font-size: 24px;"></i>
                        </div>
                        
                        <div class="method-card" onclick="selectMethod(this)">
                            <div class="method-icon">
                                <i class="fas fa-globe" style="font-size: 28px; color: #4CAF50;"></i>
                            </div>
                            <div class="method-info flex-grow-1">
                                <h6>Thẻ quốc tế</h6>
                                <p>Visa, Mastercard, JCB, AMEX</p>
                            </div>
                            <i class="fas fa-circle text-muted" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right: Price Summary -->
            <div class="col-lg-5">
                <div class="payment-card">
                    <h5 class="section-title">
                        <i class="fas fa-receipt"></i>
                        Chi tiết thanh toán
                    </h5>
                    
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>Giá vé (<?php echo count($seatNumbers); ?> ghế)</span>
                            <span><?php echo number_format($booking['total_price']); ?>đ</span>
                        </div>
                        <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="price-row">
                            <span>Giảm giá</span>
                            <span>-<?php echo number_format($booking['discount_amount']); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <div class="price-row total-price">
                            <span>Tổng thanh toán</span>
                            <span><?php echo number_format($booking['final_price']); ?>đ</span>
                        </div>
                    </div>
                    
                    <form action="" method="POST" id="paymentForm">
                        <button type="submit" class="payment-button" id="payButton">
                            <i class="fas fa-lock"></i> Thanh Toán Ngay
                        </button>
                    </form>
                    
                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Bảo mật</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-clock"></i>
                            <span>Nhanh chóng</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-check-circle"></i>
                            <span>Tin cậy</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Sau khi thanh toán thành công, vé điện tử sẽ được gửi về email của bạn.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function selectMethod(element) {
        // Remove selected class from all
        document.querySelectorAll('.method-card').forEach(card => {
            card.classList.remove('selected');
            card.querySelector('i:last-child').classList.remove('fa-check-circle', 'text-success');
            card.querySelector('i:last-child').classList.add('fa-circle', 'text-muted');
        });
        
        // Add selected class to clicked
        element.classList.add('selected');
        element.querySelector('i:last-child').classList.remove('fa-circle', 'text-muted');
        element.querySelector('i:last-child').classList.add('fa-check-circle', 'text-success');
    }
    
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const payButton = document.getElementById('payButton');
        payButton.disabled = true;
        payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        // Create VNPay payment URL
        <?php
        $amount = intval($booking['final_price']);
        $orderInfo = "Thanh toan ve xe " . $booking['booking_code'];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        try {
            $paymentUrl = VNPayService::createPaymentUrl($bookingId, $amount, $orderInfo, $ipAddress);
            echo "window.location.href = '" . $paymentUrl . "';";
        } catch (Exception $e) {
            echo "alert('Có lỗi xảy ra: " . addslashes($e->getMessage()) . "'); payButton.disabled = false; payButton.innerHTML = '<i class=\"fas fa-lock\"></i> Thanh Toán Ngay';";
        }
        ?>
    });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

