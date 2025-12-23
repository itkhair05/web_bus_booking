<?php
/**
 * Sandbox Payment Webhook Simulator
 * Mô phỏng webhook từ ngân hàng trong môi trường sandbox
 * 
 * Cách hoạt động:
 * 1. Khi user nhấn "Đã thanh toán", booking được đặt sang trạng thái pending_verification
 * 2. Script này sẽ tự động xác nhận payment sau 5-15 giây (mô phỏng webhook từ ngân hàng)
 * 3. Trong production, sẽ được thay thế bằng webhook thực từ Casso/SePay/PayOS
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/PromotionService.php';

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? 'auto_verify';

if (empty($bookingId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing booking_id'
    ]);
    exit;
}

// Get booking details
$stmt = $conn->prepare("
    SELECT 
        b.*,
        t.departure_time
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found'
    ]);
    exit;
}

// Check if already processed
if ($booking['payment_status'] === 'paid') {
    echo json_encode([
        'success' => true,
        'message' => 'Payment already confirmed',
        'status' => 'paid'
    ]);
    exit;
}

// Check payment record for pending_verification state
$stmt = $conn->prepare("SELECT status, payment_data FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

$paymentData = json_decode($payment['payment_data'] ?? '{}', true);
$isPendingVerification = ($payment && 
    $payment['status'] === 'pending' && 
    !empty($paymentData['type']) && 
    $paymentData['type'] === 'pending_verification');

// Only process pending_verification status
if (!$isPendingVerification && $action === 'auto_verify') {
    echo json_encode([
        'success' => false,
        'message' => 'Booking is not in pending_verification status',
        'current_status' => $booking['payment_status'],
        'payment_status' => $payment['status'] ?? 'none'
    ]);
    exit;
}

$conn->begin_transaction();

try {
    // Generate mock transaction code
    $transactionCode = 'SANDBOX_' . strtoupper(substr(md5($bookingId . time()), 0, 8));
    $paymentAmount = (float)$booking['final_price'];
    
    // Update booking status to paid
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'paid', 
            status = 'confirmed',
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    // Update tickets status
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET status = 'confirmed'
        WHERE booking_id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    // Check if payment record exists
    $stmt = $conn->prepare("SELECT payment_id FROM payments WHERE booking_id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $existingPayment = $stmt->get_result()->fetch_assoc();
    
    if ($existingPayment) {
        // Update existing payment record
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'success',
                transaction_code = ?,
                paid_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("si", $transactionCode, $bookingId);
    } else {
        // Create new payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (
                booking_id,
                amount,
                method,
                transaction_code,
                status,
                paid_at,
                created_at
            ) VALUES (?, ?, 'bank_transfer', ?, 'success', NOW(), NOW())
        ");
        $stmt->bind_param("ids", $bookingId, $paymentAmount, $transactionCode);
    }
    $stmt->execute();
    
    // Increment promotion usage if applicable
    if (!empty($_SESSION['applied_promotion']) && ($_SESSION['applied_promotion']['booking_id'] ?? 0) == $bookingId) {
        $promoId = (int)($_SESSION['applied_promotion']['promotion_id'] ?? 0);
        if ($promoId) {
            PromotionService::incrementUsage(
                $conn,
                $promoId,
                $bookingId,
                (int)($booking['user_id'] ?? 0),
                (float)($booking['discount_amount'] ?? 0)
            );
        }
        unset($_SESSION['applied_promotion']);
    }
    
    // Create notification
    if (!empty($booking['user_id'])) {
        $notifTitle = 'Thanh toán thành công';
        $notifMessage = "Đơn đặt vé {$booking['booking_code']} đã được thanh toán thành công. Mã GD: {$transactionCode}";
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
            VALUES (?, ?, ?, 'payment', ?, NOW())
        ");
        $stmt->bind_param("issi", $booking['user_id'], $notifTitle, $notifMessage, $bookingId);
        $stmt->execute();
    }
    
    $conn->commit();
    
    // Log success
    error_log("✅ [SANDBOX] Payment confirmed for booking {$booking['booking_code']}, TXN: {$transactionCode}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment confirmed successfully (Sandbox)',
        'status' => 'paid',
        'transaction_code' => $transactionCode,
        'booking_code' => $booking['booking_code'],
        'amount' => $paymentAmount
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Sandbox webhook error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing payment: ' . $e->getMessage()
    ]);
}

