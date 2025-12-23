<?php
/**
 * Payment Verification Page
 * Trang chờ xác nhận thanh toán - Polling for payment status
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? $_SESSION['booking_id'] ?? 0);

if (empty($bookingId)) {
    $_SESSION['error'] = 'Không tìm thấy booking ID';
    redirect(appUrl());
}

// Get booking details
$stmt = $conn->prepare("
    SELECT 
        b.*,
        t.departure_time,
        r.origin,
        r.destination,
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
    $_SESSION['error'] = 'Không tìm thấy booking';
    redirect(appUrl());
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    redirect(appUrl('user/booking/success.php?booking_id=' . $bookingId));
}

// Check payment record for verification state
$stmt = $conn->prepare("SELECT status, payment_data FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$paymentRecord = $stmt->get_result()->fetch_assoc();

$paymentData = json_decode($paymentRecord['payment_data'] ?? '{}', true);
$isPendingVerification = ($paymentRecord && 
    $paymentRecord['status'] === 'pending' && 
    !empty($paymentData['type']) && 
    $paymentData['type'] === 'pending_verification');

// If not in verification mode, redirect to payment
if (!$isPendingVerification && $booking['payment_status'] !== 'paid') {
    redirect(appUrl('user/booking/payment.php'));
}

// Calculate timeout (5 minutes for verification)
$verifyTimeout = 300; // 5 minutes
$startTime = time();

$pageTitle = 'Xác nhận thanh toán - Bus Booking';
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
        :root {
            --primary: #1E90FF;
            --primary-dark: #1873CC;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .verify-container {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .verify-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--warning));
            animation: gradient-slide 2s linear infinite;
        }
        
        @keyframes gradient-slide {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        
        .spinner-container {
            margin-bottom: 30px;
        }
        
        .spinner {
            width: 100px;
            height: 100px;
            border: 6px solid #f3f3f3;
            border-top: 6px solid var(--primary);
            border-right: 6px solid var(--success);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .verify-title {
            font-size: 26px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .verify-subtitle {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .booking-info {
            background: #f9fafb;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }
        
        .amount-highlight {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            margin: 20px 0;
        }
        
        .status-message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }
        
        .status-checking {
            background: #DBEAFE;
            color: #1E40AF;
        }
        
        .status-success {
            background: #D1FAE5;
            color: #065F46;
        }
        
        .status-failed {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .timer-display {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .timer-display span {
            font-weight: 700;
            color: var(--warning);
        }
        
        .btn-action {
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .sandbox-note {
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            border-radius: 12px;
            padding: 15px 20px;
            margin-top: 25px;
            font-size: 13px;
            color: #92400E;
            text-align: left;
        }
        
        .sandbox-note strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .pulse-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: var(--warning);
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        /* Success Animation */
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--success), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: success-bounce 0.6s ease;
        }
        
        .success-icon i {
            font-size: 50px;
            color: white;
        }
        
        @keyframes success-bounce {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        /* Failed Animation */
        .failed-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--danger), #DC2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: shake 0.5s ease;
        }
        
        .failed-icon i {
            font-size: 50px;
            color: white;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        @media (max-width: 480px) {
            .verify-container {
                padding: 40px 25px;
            }
            
            .verify-title {
                font-size: 22px;
            }
            
            .amount-highlight {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container" id="verifyContainer">
        <!-- Initial Pending State -->
        <div id="pendingState">
            <div class="spinner-container">
                <div class="spinner"></div>
            </div>
            
            <h1 class="verify-title">Đang xác nhận thanh toán</h1>
            <p class="verify-subtitle">
                Hệ thống đang kiểm tra giao dịch của bạn.<br>
                Vui lòng đợi trong giây lát...
            </p>
            
            <div class="status-message status-checking" id="statusMessage">
                <span class="pulse-dot"></span>
                <span id="statusText">Đang kiểm tra giao dịch...</span>
            </div>
            
            <div class="booking-info">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-barcode"></i> Mã đặt vé</span>
                    <span class="info-value"><?php echo e($booking['booking_code']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-route"></i> Tuyến đường</span>
                    <span class="info-value"><?php echo e($booking['origin'] ?? 'N/A'); ?> → <?php echo e($booking['destination'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-building"></i> Nhà xe</span>
                    <span class="info-value"><?php echo e($booking['partner_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
            
            <div class="amount-highlight">
                <i class="fas fa-money-bill-wave"></i>
                <?php echo number_format($booking['final_price']); ?>đ
            </div>
            
            <div class="timer-display">
                Thời gian chờ: <span id="timerDisplay">05:00</span>
            </div>
            
            <div class="actions">
                <button onclick="retryCheck()" class="btn-action btn-primary" id="retryBtn" disabled>
                    <i class="fas fa-sync-alt"></i> Kiểm tra lại
                </button>
                <a href="<?php echo appUrl('user/booking/payment.php'); ?>" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại thanh toán
                </a>
            </div>
            
            <div class="sandbox-note">
                <strong><i class="fas fa-flask"></i> Chế độ Sandbox</strong>
                Đây là môi trường thử nghiệm. Thanh toán sẽ được xác nhận tự động sau 5-15 giây để mô phỏng webhook từ ngân hàng.
            </div>
        </div>
        
        <!-- Success State (hidden by default) -->
        <div id="successState" style="display: none;">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="verify-title" style="color: #065F46;">Thanh toán thành công!</h1>
            <p class="verify-subtitle">
                Giao dịch của bạn đã được xác nhận.<br>
                Cảm ơn bạn đã sử dụng dịch vụ!
            </p>
            
            <div class="status-message status-success">
                <i class="fas fa-check-circle"></i>
                <span>Xác nhận thành công</span>
            </div>
            
            <div class="booking-info">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-barcode"></i> Mã đặt vé</span>
                    <span class="info-value"><?php echo e($booking['booking_code']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-receipt"></i> Mã giao dịch</span>
                    <span class="info-value" id="transactionCode">-</span>
                </div>
            </div>
            
            <div class="amount-highlight" style="color: #10B981;">
                <i class="fas fa-check-circle"></i>
                <?php echo number_format($booking['final_price']); ?>đ
            </div>
            
            <div class="actions">
                <a href="<?php echo appUrl('user/booking/success.php?booking_id=' . $bookingId); ?>" class="btn-action btn-success">
                    <i class="fas fa-ticket-alt"></i> Xem vé của tôi
                </a>
                <a href="<?php echo appUrl('index.php'); ?>" class="btn-action btn-secondary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
        </div>
        
        <!-- Failed/Timeout State (hidden by default) -->
        <div id="failedState" style="display: none;">
            <div class="failed-icon">
                <i class="fas fa-times"></i>
            </div>
            
            <h1 class="verify-title" style="color: #991B1B;">Chưa nhận được thanh toán</h1>
            <p class="verify-subtitle" id="failedMessage">
                Hệ thống chưa nhận được xác nhận thanh toán từ ngân hàng.<br>
                Vui lòng kiểm tra lại hoặc thử thanh toán phương thức khác.
            </p>
            
            <div class="status-message status-failed">
                <i class="fas fa-exclamation-circle"></i>
                <span>Chưa xác nhận được thanh toán</span>
            </div>
            
            <div class="actions">
                <a href="<?php echo appUrl('user/booking/payment.php'); ?>" class="btn-action btn-primary">
                    <i class="fas fa-redo"></i> Thử lại
                </a>
                <a href="<?php echo appUrl('user/booking/choose_payment.php'); ?>" class="btn-action btn-secondary">
                    <i class="fas fa-exchange-alt"></i> Đổi phương thức
                </a>
                <a href="<?php echo appUrl('index.php'); ?>" class="btn-action btn-secondary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
        </div>
    </div>
    
    <script>
        const bookingId = <?php echo $bookingId; ?>;
        const verifyTimeout = <?php echo $verifyTimeout; ?>;
        let remainingSeconds = verifyTimeout;
        let checkInterval;
        let timerInterval;
        let checkCount = 0;
        const maxChecks = 60; // Max 60 checks (5 minutes with 5-second intervals)
        
        // Update timer display
        function updateTimer() {
            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                clearInterval(checkInterval);
                showFailedState('Hết thời gian chờ. Vui lòng thử lại.');
                return;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            document.getElementById('timerDisplay').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            remainingSeconds--;
        }
        
        // Check payment status via API
        async function checkPaymentStatus() {
            checkCount++;
            
            // Update status text
            const statusEl = document.getElementById('statusText');
            const messages = [
                'Đang kiểm tra giao dịch...',
                'Đang liên hệ ngân hàng...',
                'Đang xác minh thanh toán...',
                'Vui lòng đợi thêm...'
            ];
            statusEl.textContent = messages[checkCount % messages.length];
            
            try {
                const response = await fetch(`<?php echo appUrl('api/payment/check_status.php'); ?>?booking_id=${bookingId}&_t=${Date.now()}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.status === 'paid') {
                        // Payment confirmed!
                        clearInterval(checkInterval);
                        clearInterval(timerInterval);
                        showSuccessState(data.transaction_code || 'N/A');
                    } else if (data.status === 'failed' || data.status === 'cancelled') {
                        clearInterval(checkInterval);
                        clearInterval(timerInterval);
                        showFailedState(data.message || 'Thanh toán thất bại');
                    }
                    // If still pending, continue checking
                }
            } catch (error) {
                console.log('Check error:', error);
                // Continue checking even on error
            }
            
            if (checkCount >= maxChecks) {
                clearInterval(checkInterval);
                clearInterval(timerInterval);
                showFailedState('Không nhận được xác nhận thanh toán sau nhiều lần kiểm tra.');
            }
            
            // Enable retry button after a few checks
            if (checkCount >= 2) {
                document.getElementById('retryBtn').disabled = false;
            }
        }
        
        // Show success state
        function showSuccessState(transactionCode) {
            document.getElementById('pendingState').style.display = 'none';
            document.getElementById('failedState').style.display = 'none';
            document.getElementById('successState').style.display = 'block';
            document.getElementById('transactionCode').textContent = transactionCode;
            
            // Auto redirect after 3 seconds
            setTimeout(() => {
                window.location.href = '<?php echo appUrl('user/booking/success.php?booking_id=' . $bookingId); ?>';
            }, 3000);
        }
        
        // Show failed state
        function showFailedState(message) {
            document.getElementById('pendingState').style.display = 'none';
            document.getElementById('successState').style.display = 'none';
            document.getElementById('failedState').style.display = 'block';
            if (message) {
                document.getElementById('failedMessage').innerHTML = message + '<br>Vui lòng thử lại hoặc chọn phương thức khác.';
            }
        }
        
        // Manual retry check
        function retryCheck() {
            const btn = document.getElementById('retryBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
            
            checkPaymentStatus().then(() => {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Kiểm tra lại';
                }, 2000);
            });
        }
        
        // Start checking
        function startVerification() {
            // Initial check
            checkPaymentStatus();
            
            // Start timer
            timerInterval = setInterval(updateTimer, 1000);
            
            // Check every 5 seconds
            checkInterval = setInterval(checkPaymentStatus, 5000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', startVerification);
    </script>
</body>
</html>

