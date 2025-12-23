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

// NOTE: VNPay callback không require login vì session có thể mất khi redirect từ VNPay
// Thay vào đó, chúng ta sẽ verify booking ownership sau khi lấy booking từ database
// IMPORTANT: Session cookie với samesite='Lax' sẽ được gửi trong top-level navigation redirects

// Get VNPay response data
$vnpayData = $_GET;

// Validate response signature FIRST (security)
$isValid = VNPayService::validateResponse($vnpayData);

if (!$isValid) {
    logError('VNPay invalid signature', ['vnpay_data' => $vnpayData]);
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
$bookingId = intval(explode('_', $txnRef)[0]);

if (empty($bookingId)) {
    logError('VNPay invalid booking_id', ['txn_ref' => $txnRef]);
    setFlashMessage('error', 'Mã đơn hàng không hợp lệ.');
    redirect(appUrl());
}

// Check if payment successful
$isSuccess = VNPayService::isPaymentSuccess($responseCode);
$message = VNPayService::getResponseMessage($responseCode);

// Begin transaction
$conn->begin_transaction();

try {
    // Get booking info FIRST (without user_id check - we'll verify after)
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Không tìm thấy đơn đặt vé');
    }
    
    // SECURITY: Verify booking ownership AFTER getting booking
    // If user is logged in, verify user_id matches
    // If user is not logged in, allow if booking is guest booking (user_id = 0 or guest user)
    $userId = null;
    
    // ============================================
    // RESTORE SESSION: Check if we have a restore token from before redirect
    // ============================================
    $restoreUserId = $_SESSION['vnpay_restore_user_id'] ?? null;
    $restoreBookingId = $_SESSION['vnpay_restore_booking_id'] ?? null;
    $restoreTime = $_SESSION['vnpay_restore_time'] ?? 0;
    
    // If we have a restore token and it matches this booking, and user is not logged in
    if ($restoreUserId && $restoreBookingId == $bookingId && !isLoggedIn()) {
        // Check if restore token is still valid (within 30 minutes)
        if (time() - $restoreTime < 1800) {
            // Verify restore user_id matches booking user_id
            if ($booking['user_id'] > 0 && $booking['user_id'] == $restoreUserId) {
                // Restore session
                $userStmt = $conn->prepare("SELECT user_id, name, email, phone, role, status FROM users WHERE user_id = ?");
                $userStmt->bind_param("i", $restoreUserId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                
                if ($user && $user['status'] === 'active') {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['created'] = time(); // Reset session creation time
                    
                    // Regenerate session ID for security after restore
                    session_regenerate_id(true);
                    
                    error_log(sprintf(
                        "VNPay session restored from token - Booking ID: %d, User ID: %d, Session ID: %s",
                        $bookingId,
                        $restoreUserId,
                        session_id()
                    ));
                }
            }
        }
        
        // Clear restore token
        unset($_SESSION['vnpay_restore_user_id'], $_SESSION['vnpay_restore_booking_id'], $_SESSION['vnpay_restore_time']);
    }
    
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        // If booking has a real user_id, it must match current user
        if ($booking['user_id'] > 0 && $booking['user_id'] != $userId) {
            logError('VNPay unauthorized callback', [
                'booking_id' => $bookingId,
                'booking_user_id' => $booking['user_id'],
                'current_user_id' => $userId,
                'transaction_no' => $transactionNo
            ]);
            $conn->rollback();
            setFlashMessage('error', 'Bạn không có quyền xử lý đơn hàng này.');
            redirect(appUrl('user/tickets/my_tickets.php'));
            exit;
        }
        // Use booking's user_id if current user matches
        $userId = $booking['user_id'];
    } else {
        // Guest booking - use booking's user_id
        $userId = $booking['user_id'];
    }
    
    // ============================================
    // SECURITY: Validate amount from VNPay
    // ============================================
    $expectedAmount = (float)$booking['final_price'];
    $receivedAmount = (float)$amount;
    
    // Allow tolerance of 1000 VND (for rounding differences)
    $tolerance = 1000;
    $amountDifference = abs($receivedAmount - $expectedAmount);
    
    if ($amountDifference > $tolerance) {
        // Log security alert
        error_log(sprintf(
            "VNPay Amount Mismatch Alert - Booking ID: %d, User ID: %d, Expected: %.2f, Received: %.2f, Difference: %.2f",
            $bookingId,
            $userId,
            $expectedAmount,
            $receivedAmount,
            $amountDifference
        ));
        
        // Rollback transaction
        $conn->rollback();
        
        // Set error message
        setFlashMessage('error', 'Số tiền thanh toán không khớp với đơn hàng. Vui lòng liên hệ hỗ trợ.');
        
        // Log failed payment attempt
        $paymentData = json_encode(array_merge($response, [
            'amount_mismatch' => true,
            'expected_amount' => $expectedAmount,
            'received_amount' => $receivedAmount
        ]));
        $stmt = $conn->prepare("
            INSERT INTO payments (booking_id, method, amount, status, transaction_code, payment_data, created_at)
            VALUES (?, 'vnpay', ?, 'failed', ?, ?, NOW())
        ");
        $stmt->bind_param("idss", $bookingId, $receivedAmount, $transactionNo, $paymentData);
        $stmt->execute();
        
        // Redirect to tickets page
        redirect(appUrl('user/tickets/my_tickets.php'));
        exit;
    }
    
    if ($isSuccess) {
        // ============================================
        // SECURITY: Idempotency Check (Chống double payment)
        // ============================================
        // Check if payment already processed
        $checkStmt = $conn->prepare("
            SELECT payment_id, status, transaction_code, paid_at
            FROM payments 
            WHERE booking_id = ? AND transaction_code = ?
        ");
        $checkStmt->bind_param("is", $bookingId, $transactionNo);
        $checkStmt->execute();
        $existingPayment = $checkStmt->get_result()->fetch_assoc();
        
        if ($existingPayment && $existingPayment['status'] === 'success') {
            // Payment already processed - idempotent response
            error_log(sprintf(
                "Duplicate VNPay payment attempt (Idempotent) - Booking ID: %d, User ID: %d, Transaction: %s, Already paid at: %s",
                $bookingId,
                $userId,
                $transactionNo,
                $existingPayment['paid_at'] ?? 'N/A'
            ));
            
            // Rollback transaction (we don't need to do anything)
            $conn->rollback();
            
            // Set info message (not error, because payment was successful)
            setFlashMessage('info', 'Giao dịch đã được xử lý trước đó. Mã giao dịch: ' . $transactionNo);
            
            // Redirect to success page (payment was already successful)
            redirect(appUrl('user/booking/success.php?booking_id=' . $bookingId));
            exit;
        }
        
        // Check if booking already paid
        if ($booking['payment_status'] === 'paid' && $booking['status'] === 'confirmed') {
            error_log(sprintf(
                "VNPay callback for already paid booking - Booking ID: %d, User ID: %d, Transaction: %s",
                $bookingId,
                $userId,
                $transactionNo
            ));
            
            $conn->rollback();
            setFlashMessage('info', 'Đơn đặt vé này đã được thanh toán trước đó.');
            redirect(appUrl('user/tickets/my_tickets.php'));
            exit;
        }
        
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
        
        // Create success notification (only if user_id is valid and > 0)
        if ($userId && $userId > 0) {
            $notifTitle = 'Thanh toán thành công';
            $notifMessage = "Đơn đặt vé {$booking['booking_code']} đã được thanh toán thành công qua VNPay. Mã GD: {$transactionNo}";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
                VALUES (?, ?, ?, 'payment', ?, NOW())
            ");
            $stmt->bind_param("issi", $userId, $notifTitle, $notifMessage, $bookingId);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // ============================================
        // RESTORE SESSION: Nếu user đã login trước đó nhưng session bị mất sau redirect từ VNPay
        // ============================================
        // Nếu booking có user_id hợp lệ (> 0) và user chưa login, restore session
        if ($userId && $userId > 0 && !isLoggedIn()) {
            // Lấy thông tin user từ database
            $userStmt = $conn->prepare("SELECT user_id, name, email, phone, role, status FROM users WHERE user_id = ?");
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();
            
            if ($user && $user['status'] === 'active') {
                // Restore session bằng cách set lại session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['last_activity'] = time();
                $_SESSION['created'] = time(); // Reset session creation time
                
                // Regenerate session ID for security after restore
                session_regenerate_id(true);
                
                // Log for debugging
                error_log(sprintf(
                    "VNPay session restored after payment - Booking ID: %d, User ID: %d, Email: %s, Session ID: %s",
                    $bookingId,
                    $userId,
                    $user['email'],
                    session_id()
                ));
            }
        }
        
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
        
        // Create notification (only if user_id is valid and > 0)
        if ($userId && $userId > 0) {
            $notifTitle = 'Thanh toán thất bại';
            $notifMessage = "Thanh toán đơn {$booking['booking_code']} thất bại. Lý do: {$message}";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
                VALUES (?, ?, ?, 'payment', ?, NOW())
            ");
            $stmt->bind_param("issi", $userId, $notifTitle, $notifMessage, $bookingId);
            $stmt->execute();
        }
        
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

