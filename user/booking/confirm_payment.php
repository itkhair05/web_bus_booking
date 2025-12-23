<?php
/**
 * Confirm Payment - Mark as pending verification
 * Xác nhận thanh toán và chuyển sang trạng thái chờ xác nhận
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/EmailService.php';
require_once '../../core/PromotionService.php';

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? 0);
$method = $_GET['method'] ?? 'bank_transfer';

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
        r.route_name,
        p.name as partner_name,
        v.vehicle_type
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    LEFT JOIN routes r ON t.route_id = r.route_id
    LEFT JOIN partners p ON t.partner_id = p.partner_id
    LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = 'Không tìm thấy booking';
    redirect(appUrl());
}

// SECURITY: Verify user_id - prevent unauthorized payment
// Allow guest booking (user_id = 0 or guest user) but verify if logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    // If booking has a real user_id, it must match current user
    if ($booking['user_id'] > 0 && $booking['user_id'] != $userId) {
        logError('Unauthorized payment confirmation attempt', [
            'booking_id' => $bookingId,
            'booking_user_id' => $booking['user_id'],
            'current_user_id' => $userId
        ]);
        $_SESSION['error'] = 'Bạn không có quyền thanh toán đơn hàng này';
        redirect(appUrl('user/tickets/my_tickets.php'));
    }
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    redirect(appUrl('user/booking/success.php?booking_id=' . $bookingId));
}

// Get passenger info
$stmt = $conn->prepare("
    SELECT passenger_name, passenger_email, passenger_phone, seat_number 
    FROM tickets 
    WHERE booking_id = ?
    ORDER BY seat_number
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$tickets = $result->fetch_all(MYSQLI_ASSOC);

$seatNumbers = array_column($tickets, 'seat_number');
$passengerName = $tickets[0]['passenger_name'] ?? 'Khách hàng';
$passengerEmail = $tickets[0]['passenger_email'] ?? '';

// Check payment method - COD vs Bank Transfer
if ($method === 'cod') {
    // COD Payment - Confirm immediately (no verification needed)
    processCODPayment($conn, $bookingId, $booking);
} else {
    // Bank Transfer / VietQR - Require verification
    processBankTransferPayment($conn, $bookingId, $booking);
}

/**
 * Process COD Payment - Direct confirmation
 */
function processCODPayment($conn, $bookingId, $booking) {
    $conn->begin_transaction();
    
    try {
        $paymentAmount = (float)$booking['final_price'];
        
        // Update booking status to confirmed with pending payment
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'confirmed', 
                payment_status = 'pending',
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Update tickets
        $stmt = $conn->prepare("UPDATE tickets SET status = 'confirmed' WHERE booking_id = ?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Create payment record
        $transactionCode = 'COD_' . strtoupper(substr(md5($bookingId . time()), 0, 8));
        $stmt = $conn->prepare("
            INSERT INTO payments (booking_id, method, amount, transaction_code, status, created_at)
            VALUES (?, 'cod', ?, ?, 'pending', NOW())
            ON DUPLICATE KEY UPDATE
                status = 'pending',
                transaction_code = VALUES(transaction_code)
        ");
        $stmt->bind_param("ids", $bookingId, $paymentAmount, $transactionCode);
        $stmt->execute();
        
        $conn->commit();
        
        // Clear session
        unset($_SESSION['booking_expiry']);
        
        // Redirect to success page
        redirect(appUrl('user/booking/success.php?booking_id=' . $bookingId . '&method=cod'));
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('COD payment error: ' . $e->getMessage());
        $_SESSION['error'] = 'Có lỗi xảy ra khi xử lý đặt vé. Vui lòng thử lại.';
        redirect(appUrl('user/booking/payment.php'));
    }
}

/**
 * Process Bank Transfer Payment - Mark as pending verification
 * 
 * Note: Using 'unpaid' status in bookings table and 'pending' in payments table
 * to track verification state without requiring database schema changes.
 * The payments.payment_data field stores verification metadata.
 */
function processBankTransferPayment($conn, $bookingId, $booking) {
    global $_SESSION;
    
    $conn->begin_transaction();
    
    try {
        $paymentAmount = (float)$booking['final_price'];
        
        // Keep booking payment_status as 'unpaid' until verified
        // The verification state is tracked in payments table
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Create or update payment record as pending with verification metadata
        $transactionCode = 'PENDING_' . strtoupper(substr(md5($bookingId . time()), 0, 8));
        $verifyMeta = json_encode([
            'type' => 'pending_verification',
            'started_at' => date('Y-m-d H:i:s'),
            'method' => 'bank_transfer'
        ]);
        
        // Check if payment record exists
        $stmt = $conn->prepare("SELECT payment_id FROM payments WHERE booking_id = ? AND method = 'bank_transfer'");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $existingPayment = $stmt->get_result()->fetch_assoc();
        
        if ($existingPayment) {
            $stmt = $conn->prepare("
                UPDATE payments 
                SET status = 'pending',
                    transaction_code = ?,
                    payment_data = ?
                WHERE payment_id = ?
            ");
            $stmt->bind_param("ssi", $transactionCode, $verifyMeta, $existingPayment['payment_id']);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO payments (booking_id, method, amount, transaction_code, status, payment_data, created_at)
                VALUES (?, 'bank_transfer', ?, ?, 'pending', ?, NOW())
            ");
            $stmt->bind_param("idss", $bookingId, $paymentAmount, $transactionCode, $verifyMeta);
        }
        $stmt->execute();
        
        $conn->commit();
        
        // Store booking info in session for verification page
        $_SESSION['verify_booking_id'] = $bookingId;
        $_SESSION['verify_started'] = time();
        
        // Log
        error_log("⏳ [PAYMENT] Booking {$booking['booking_code']} awaiting verification");
        
        // SANDBOX MODE: Schedule auto-verification
        // In production, this would be replaced by actual bank webhook
        scheduleSandboxVerification($conn, $bookingId);
        
        // Redirect to verification page
        redirect(appUrl('user/booking/verify_payment.php?booking_id=' . $bookingId));
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Bank transfer payment error: ' . $e->getMessage());
        $_SESSION['error'] = 'Có lỗi xảy ra khi xử lý thanh toán. Vui lòng thử lại.';
        redirect(appUrl('user/booking/payment.php'));
    }
}

/**
 * Schedule sandbox auto-verification
 * In sandbox mode, payment will be auto-verified after 5-15 seconds
 * Lưu thông tin vào database để đảm bảo hoạt động đúng cách
 */
function scheduleSandboxVerification($conn, $bookingId) {
    // Store verification request with random delay
    $delay = rand(5, 15); // 5-15 seconds delay
    $verifyAt = time() + $delay;
    
    // Store in session for sandbox auto-verify (backup)
    $_SESSION['sandbox_verify'] = [
        'booking_id' => $bookingId,
        'verify_at' => $verifyAt,
        'delay' => $delay
    ];
    
    // QUAN TRỌNG: Lưu vào database trong payment_data để đảm bảo hoạt động đúng cách
    // Update payment_data với thông tin sandbox verification
    $stmt = $conn->prepare("
        SELECT payment_id, payment_data 
        FROM payments 
        WHERE booking_id = ? AND method = 'bank_transfer'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        $paymentData = json_decode($payment['payment_data'] ?? '{}', true);
        $paymentData['sandbox_verify_at'] = $verifyAt;
        $paymentData['sandbox_delay'] = $delay;
        $paymentData['sandbox_scheduled_at'] = time();
        
        $updatedPaymentData = json_encode($paymentData);
        $updateStmt = $conn->prepare("
            UPDATE payments 
            SET payment_data = ? 
            WHERE payment_id = ?
        ");
        $updateStmt->bind_param("si", $updatedPaymentData, $payment['payment_id']);
        $updateStmt->execute();
        
        error_log("🔄 [SANDBOX] Scheduled auto-verification for booking {$bookingId} in {$delay} seconds (saved to DB)");
    } else {
        error_log("⚠️ [SANDBOX] Cannot find payment record for booking {$bookingId}");
    }
}
