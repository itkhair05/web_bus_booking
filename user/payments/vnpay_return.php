<?php
/**
 * VNPay Return URL Handler
 * Xử lý response từ VNPay sau khi thanh toán
 */

require_once '../../config/session.php';
$conn = require_once '../../config/db.php';
require_once '../../core/auth.php';
require_once '../../core/helpers.php';
require_once '../../config/vnpay.php';
require_once '../../core/VNPayService.php';
require_once '../../core/PromotionService.php';

requireLogin();
$userId = getCurrentUserId();

// Get VNPay response data
$vnpayData = $_GET;

// Validate response signature
$isValid = VNPayService::validateResponse($vnpayData);

if (!$isValid) {
    setFlashMessage('error', 'Chữ ký không hợp lệ. Giao dịch có thể bị giả mạo.');
    redirect(appUrl('user/tickets/my_tickets.php'));
}

// Parse response data
$response = VNPayService::parseResponse($vnpayData);
$responseCode = $response['response_code'];
$txnRef = $response['txn_ref'];
$amount = $response['amount'];
$transactionNo = $response['transaction_no'];

// Extract booking_id from txn_ref (format: bookingId_timestamp)
$bookingId = explode('_', $txnRef)[0];

// Check if payment successful
$isSuccess = VNPayService::isPaymentSuccess($responseCode);
$message = VNPayService::getResponseMessage($responseCode);

// Begin transaction
$conn->begin_transaction();

try {
    // Get booking info
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $bookingId, $userId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Không tìm thấy đơn đặt vé');
    }
    
    if ($isSuccess) {
        // Update booking status
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'paid', 
                status = 'confirmed',
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Cộng lượt dùng khuyến mãi nếu có (kèm kiểm tra giới hạn)
        if (!empty($_SESSION['applied_promotion']) && ($_SESSION['applied_promotion']['booking_id'] ?? 0) == $bookingId) {
            $promoId = (int)($_SESSION['applied_promotion']['promotion_id'] ?? 0);
            if ($promoId) {
                PromotionService::incrementUsage(
                    $conn,
                    $promoId,
                    (int)$bookingId,
                    (int)($booking['user_id'] ?? 0),
                    (float)($booking['discount_amount'] ?? 0)
                );
            }
            unset($_SESSION['applied_promotion']);
        }
        
        // Insert or update payment record
        $paymentData = json_encode($response);
        $stmt = $conn->prepare("
            INSERT INTO payments (booking_id, method, amount, status, transaction_code, payment_data, paid_at, created_at)
            VALUES (?, 'vnpay', ?, 'success', ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = 'success',
                transaction_code = VALUES(transaction_code),
                payment_data = VALUES(payment_data),
                paid_at = NOW()
        ");
        $stmt->bind_param("idss", $bookingId, $amount, $transactionNo, $paymentData);
        $stmt->execute();
        
        // Create success notification
        $notifTitle = 'Thanh toán thành công';
        $notifMessage = "Đơn đặt vé {$booking['booking_code']} đã được thanh toán thành công qua VNPay. Mã GD: {$transactionNo}";
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
            VALUES (?, ?, ?, 'payment', ?, NOW())
        ");
        $stmt->bind_param("issi", $userId, $notifTitle, $notifMessage, $bookingId);
        $stmt->execute();
        
        $conn->commit();
        
        // Set success message
        setFlashMessage('success', 'Thanh toán thành công! Mã giao dịch: ' . $transactionNo);
        
        // Redirect to success page
        redirect(appUrl('user/booking/success.php?booking_id=' . $bookingId));
        
    } else {
        // Payment failed
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'unpaid', 
                status = 'pending',
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Insert failed payment record
        $paymentData = json_encode($response);
        $stmt = $conn->prepare("
            INSERT INTO payments (booking_id, method, amount, status, transaction_code, payment_data, created_at)
            VALUES (?, 'vnpay', ?, 'failed', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                status = 'failed',
                transaction_code = VALUES(transaction_code),
                payment_data = VALUES(payment_data)
        ");
        $stmt->bind_param("idss", $bookingId, $amount, $transactionNo, $paymentData);
        $stmt->execute();
        
        // Create notification
        $notifTitle = 'Thanh toán thất bại';
        $notifMessage = "Thanh toán đơn {$booking['booking_code']} thất bại. Lý do: {$message}";
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
            VALUES (?, ?, ?, 'payment', ?, NOW())
        ");
        $stmt->bind_param("issi", $userId, $notifTitle, $notifMessage, $bookingId);
        $stmt->execute();
        
        $conn->commit();
        
        // Set error message
        setFlashMessage('error', 'Thanh toán thất bại: ' . $message);
        
        // Redirect to payment page to retry
        redirect(appUrl('user/booking/payment.php'));
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('VNPay callback error: ' . $e->getMessage());
    setFlashMessage('error', 'Có lỗi xảy ra khi xử lý thanh toán. Vui lòng liên hệ hỗ trợ.');
    redirect(appUrl('user/tickets/my_tickets.php'));
}

