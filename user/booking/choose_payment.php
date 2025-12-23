<?php
/**
 * Choose Payment Method Page
 * Trang chọn phương thức thanh toán
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';
require_once '../../core/PromotionService.php';

// Get booking ID from session
$bookingId = intval($_SESSION['booking_id'] ?? 0);

if (empty($bookingId)) {
    $_SESSION['error'] = 'Không tìm thấy thông tin đặt vé. Vui lòng đặt vé lại.';
    redirect(appUrl());
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, 
           t.departure_time, t.arrival_time,
           r.start_point, r.end_point,
           p.name as partner_name
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    LEFT JOIN routes r ON t.route_id = r.route_id
    LEFT JOIN partners p ON t.partner_id = p.partner_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = 'Không tìm thấy thông tin đặt vé.';
    redirect(appUrl());
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

            // Reload booking data
            $stmt = $conn->prepare("
                SELECT b.*, 
                       t.departure_time, t.arrival_time,
                       r.start_point, r.end_point,
                       p.name as partner_name
                FROM bookings b
                LEFT JOIN trips t ON b.trip_id = t.trip_id
                LEFT JOIN routes r ON t.route_id = r.route_id
                LEFT JOIN partners p ON t.partner_id = p.partner_id
                WHERE b.booking_id = ?
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            $promoError = $e->getMessage();
        }
    }
}

// Handle payment method selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method'])) {
    $method = $_POST['method'] ?? '';
    
    if ($method === 'vnpay') {
        redirect(appUrl('user/payments/vnpay_payment.php?booking_id=' . $bookingId));
    } elseif ($method === 'bank_transfer') {
        redirect(appUrl('user/booking/payment.php'));
    } elseif ($method === 'cod') {
        redirect(appUrl('user/booking/confirm_payment.php?booking_id=' . $bookingId . '&method=cod'));
    }
}

$pageTitle = 'Chọn phương thức thanh toán';
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
        
        .choose-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .choose-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .page-title p {
            color: #6b7280;
            font-size: 16px;
        }
        
        .method-card {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 30px;
            border: 3px solid #e5e7eb;
            border-radius: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .method-card:hover {
            border-color: #2563eb;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
            transform: translateY(-3px);
        }
        
        .method-card.vnpay:hover {
            border-color: #FF3838;
            box-shadow: 0 8px 25px rgba(255, 56, 56, 0.15);
        }
        
        .method-card.bank:hover {
            border-color: #2196F3;
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.15);
        }
        
        .method-card.cod:hover {
            border-color: #4CAF50;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.15);
        }
        
        .method-icon {
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            flex-shrink: 0;
        }
        
        .method-icon.vnpay {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe4e6 100%);
        }
        
        .method-icon.bank {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        }
        
        .method-icon.cod {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .method-icon img {
            max-width: 75px;
            max-height: 75px;
        }
        
        .method-icon i {
            font-size: 48px;
        }
        
        .method-info {
            flex: 1;
        }
        
        .method-info h3 {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .method-info p {
            color: #6b7280;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .method-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .badge-custom {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .badge-fast {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-secure {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-popular {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-easy {
            background: #dcfce7;
            color: #166534;
        }
        
        .method-arrow {
            font-size: 28px;
            color: #9ca3af;
            transition: all 0.3s;
        }
        
        .method-card:hover .method-arrow {
            color: #2563eb;
            transform: translateX(8px);
        }
        
        .booking-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 30px;
        }
        
        .booking-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .booking-row:last-child {
            border-bottom: none;
            font-size: 24px;
            font-weight: 800;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid rgba(255,255,255,0.3);
        }
            
            /* Promo Section Styles */
            .promo-section {
                background: rgba(255,255,255,0.15);
                border-radius: 12px;
                padding: 16px 20px;
                margin-bottom: 20px;
                backdrop-filter: blur(10px);
            }
            
            .promo-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-weight: 600;
                margin-bottom: 12px;
                font-size: 15px;
            }
            
            .promo-header i {
                margin-right: 8px;
            }
            
            .view-promos {
                color: #fde68a;
                font-size: 13px;
                text-decoration: none;
                font-weight: 500;
            }
            
            .view-promos:hover {
                color: #fff;
                text-decoration: underline;
            }
            
            .promo-form {
                margin-bottom: 0;
            }
            
            .promo-input-wrapper {
                display: flex;
                gap: 10px;
            }
            
            .promo-input {
                flex: 1;
                padding: 12px 16px;
                border: 2px solid rgba(255,255,255,0.3);
                border-radius: 10px;
                background: rgba(255,255,255,0.95);
                font-size: 15px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #1f2937;
            }
            
            .promo-input:focus {
                outline: none;
                border-color: #fde68a;
                background: #fff;
            }
            
            .promo-input::placeholder {
                text-transform: none;
                letter-spacing: 0;
                font-weight: 400;
                color: #9ca3af;
            }
            
            .promo-btn {
                padding: 12px 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                border: none;
                border-radius: 10px;
                color: #fff;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.3s ease;
                white-space: nowrap;
            }
            
            .promo-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            }
            
            .promo-result {
                margin-top: 12px;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .promo-result.success {
                background: rgba(16, 185, 129, 0.2);
                color: #a7f3d0;
            }
            
            .promo-result.error {
                background: rgba(239, 68, 68, 0.2);
                color: #fecaca;
            }
            
            .promo-suggestions {
                margin-top: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            
            .suggestion-label {
                font-size: 13px;
                opacity: 0.8;
            }
            
            .suggestion-tag {
                background: rgba(255,255,255,0.2);
                border: 1px solid rgba(255,255,255,0.3);
                border-radius: 6px;
                padding: 4px 10px;
                font-size: 12px;
                font-weight: 600;
                color: #fff;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .suggestion-tag:hover {
                background: rgba(255,255,255,0.3);
                transform: scale(1.05);
            }
        
        @media (max-width: 768px) {
            .method-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 25px 20px;
            }
            
            .method-arrow {
                display: none;
            }
            
            .method-badges {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../../includes/header_user.php'; ?>
    
    <div class="choose-container">
        <div class="choose-card">
            <div class="page-title">
                <h1><i class="fas fa-credit-card"></i> Chọn Phương Thức Thanh Toán</h1>
                <p>Vui lòng chọn một trong các phương thức thanh toán bên dưới</p>
            </div>
            
            <!-- Booking Summary -->
            <div class="booking-summary">
                <!-- Promo Code Section -->
                <div class="promo-section">
                    <div class="promo-header">
                        <i class="fas fa-ticket-alt"></i> Mã khuyến mãi
                        <a href="<?php echo appUrl('user/promotions/'); ?>" class="view-promos" target="_blank">Xem ưu đãi <i class="fas fa-external-link-alt"></i></a>
                    </div>
                    <form method="POST" class="promo-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="apply_promo" value="1">
                        <div class="promo-input-wrapper">
                            <input type="text" name="promo_code" class="promo-input" placeholder="Nhập mã giảm giá" value="<?php echo e($_POST['promo_code'] ?? ''); ?>">
                            <button type="submit" class="promo-btn"><i class="fas fa-check"></i> Áp dụng</button>
                        </div>
                    </form>
                    <?php if ($promoMessage): ?>
                        <div class="promo-result success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($promoMessage); ?>
                        </div>
                    <?php elseif ($promoError): ?>
                        <div class="promo-result error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($promoError); ?>
                        </div>
                    <?php else: ?>
                        <div class="promo-suggestions">
                            <span class="suggestion-label">Gợi ý:</span>
                            <button type="button" class="suggestion-tag" onclick="applyPromoCode('4FRIDAY')">4FRIDAY</button>
                            <button type="button" class="suggestion-tag" onclick="applyPromoCode('SINHVIEN10')">SINHVIEN10</button>
                            <button type="button" class="suggestion-tag" onclick="applyPromoCode('EARLYBIRD')">EARLYBIRD</button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="booking-row" style="border-bottom: 2px solid rgba(255,255,255,0.3); padding-bottom: 15px; margin-bottom: 15px;">
                    <div>
                        <div style="font-size: 18px; font-weight: 700;">
                            <?php echo e($booking['start_point']); ?> → <?php echo e($booking['end_point']); ?>
                        </div>
                        <div style="font-size: 14px; opacity: 0.9; margin-top: 5px;">
                            <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($booking['departure_time'])); ?> 
                            | <i class="fas fa-chair"></i> <?php echo implode(', ', $seatNumbers); ?>
                        </div>
                    </div>
                </div>
                <div class="booking-row">
                    <span>Giá vé</span>
                    <span><?php echo number_format($booking['total_price']); ?>đ</span>
                </div>
                <?php if ($booking['discount_amount'] > 0): ?>
                <div class="booking-row">
                    <span>Giảm giá</span>
                    <span>-<?php echo number_format($booking['discount_amount']); ?>đ</span>
                </div>
                <?php endif; ?>
                <div class="booking-row">
                    <span>TỔNG THANH TOÁN</span>
                    <span><?php echo number_format($booking['final_price']); ?>đ</span>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div id="paymentMethods">
                <!-- VNPay -->
                <a href="?method=vnpay" class="method-card vnpay">
                    <div style="display: flex; align-items: center; gap: 25px; width: 100%;">
                        <div class="method-icon vnpay">
                            <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay">
                        </div>
                        <div class="method-info" style="flex: 1; min-width: 0;">
                            <h3 style="margin-bottom: 8px;"><i class="fas fa-bolt text-danger"></i> VNPay</h3>
                            <p style="margin-bottom: 10px;">Thanh toán qua ví điện tử, thẻ ATM, Internet Banking, thẻ quốc tế</p>
                            <div class="method-badges">
                                <span class="badge-custom badge-fast">⚡ Nhanh chóng</span>
                                <span class="badge-custom badge-secure">🔒 Bảo mật cao</span>
                            </div>
                        </div>
                        <div class="method-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </a>
                
                <!-- Bank Transfer -->
                <a href="<?php echo appUrl('user/booking/payment.php'); ?>" class="method-card bank">
                    <div style="display: flex; align-items: center; gap: 25px; width: 100%;">
                        <div class="method-icon bank">
                            <i class="fas fa-qrcode" style="color: #2196F3;"></i>
                        </div>
                        <div class="method-info" style="flex: 1; min-width: 0;">
                            <h3 style="margin-bottom: 8px;"><i class="fas fa-university text-primary"></i> Chuyển khoản ngân hàng</h3>
                            <p style="margin-bottom: 10px;">Quét mã QR VietQR hoặc chuyển khoản thủ công qua app ngân hàng</p>
                            <div class="method-badges">
                                <span class="badge-custom badge-popular">⭐ Phổ biến nhất</span>
                            </div>
                        </div>
                        <div class="method-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </a>
                
                <!-- COD -->
                <a href="?method=cod" class="method-card cod">
                    <div style="display: flex; align-items: center; gap: 25px; width: 100%;">
                        <div class="method-icon cod">
                            <i class="fas fa-money-bill-wave" style="color: #4CAF50;"></i>
                        </div>
                        <div class="method-info" style="flex: 1; min-width: 0;">
                            <h3 style="margin-bottom: 8px;"><i class="fas fa-hand-holding-usd text-success"></i> Thanh toán khi lên xe</h3>
                            <p style="margin-bottom: 10px;">Thanh toán bằng tiền mặt cho lái xe/nhân viên khi lên xe</p>
                            <div class="method-badges">
                                <span class="badge-custom badge-easy">💵 Tiện lợi</span>
                            </div>
                        </div>
                        <div class="method-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="alert alert-info mt-4 mb-0">
                <i class="fas fa-shield-alt"></i>
                <strong>Bảo mật:</strong> Tất cả giao dịch đều được mã hóa và bảo mật theo tiêu chuẩn quốc tế
            </div>
        </div>
    </div>
    
    <?php
    // Handle COD method
    if (isset($_GET['method']) && $_GET['method'] === 'cod'):
    ?>
    <script>
    if (confirm('Bạn chọn thanh toán bằng tiền mặt khi lên xe?\n\nVui lòng mang theo đủ tiền mặt và xuất trình mã đặt vé.')) {
        window.location.href = 'confirm_payment.php?booking_id=<?php echo $bookingId; ?>&method=cod';
    } else {
        window.location.href = 'choose_payment.php';
    }
    </script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function applyPromoCode(code) {
        // Điền mã vào input
        document.querySelector('.promo-input').value = code;
        // Submit form
        document.querySelector('.promo-form').submit();
    }
    </script>
</body>
</html>

