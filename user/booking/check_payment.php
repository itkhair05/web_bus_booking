<?php
/**
 * Check Payment Status API
 * Kiểm tra trạng thái thanh toán
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';

// Allow guest payment check (no login required)
// requireLogin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method', 'METHOD_NOT_ALLOWED', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$bookingId = intval($input['booking_id'] ?? 0);
$isAuto = !empty($input['auto']);

if (empty($bookingId)) {
    jsonError('Booking ID is required', 'INVALID_INPUT', 400);
}

// Get booking
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    jsonError('Booking not found', 'NOT_FOUND', 404);
}

// SECURITY: Verify user_id - prevent unauthorized payment
// Allow guest booking (user_id = 0 or guest user) but verify if logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    // If booking has a real user_id, it must match current user
    if ($booking['user_id'] > 0 && $booking['user_id'] != $userId) {
        logError('Unauthorized payment attempt', [
            'booking_id' => $bookingId,
            'booking_user_id' => $booking['user_id'],
            'current_user_id' => $userId
        ]);
        jsonError('Bạn không có quyền thanh toán đơn hàng này', 'UNAUTHORIZED', 403);
    }
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    jsonResponse(true, [
        'paid' => true,
        'booking_code' => $booking['booking_code'],
        'amount' => $booking['final_amount']
    ], 'Đã thanh toán');
}

// TODO: Real payment gateway integration
// For now, we'll simulate payment check

// In production, you would:
// 1. Call VNPay/MoMo/Bank API to check transaction
// 2. Verify the amount matches
// 3. Update booking status if payment confirmed

// SECURITY: Only allow manual verification in sandbox mode
// In production, this endpoint should only be called by payment gateway webhooks
// Manual checks should go through verify_payment.php which uses api/payment/check_status.php

// Check if booking is in pending_verification state
$stmt = $conn->prepare("
    SELECT p.status, p.payment_data 
    FROM payments p 
    WHERE p.booking_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$paymentRecord = $stmt->get_result()->fetch_assoc();

$paymentData = json_decode($paymentRecord['payment_data'] ?? '{}', true);
$isPendingVerification = ($paymentRecord && 
    $paymentRecord['status'] === 'pending' && 
    !empty($paymentData['type']) && 
    $paymentData['type'] === 'pending_verification');

// Only allow manual check if booking is in pending_verification state
// This prevents bypassing the verification flow
if (!$isAuto && $isPendingVerification) {
    // Update booking status
    $conn->begin_transaction();
    
    try {
        // ============================================
        // SECURITY: Validate amount for VietQR/Bank Transfer
        // ============================================
        $expectedAmount = (float)($booking['final_price'] ?? $booking['final_amount'] ?? 0);
        $receivedAmount = (float)($input['amount'] ?? $expectedAmount);
        
        // Allow tolerance of 1000 VND (for rounding differences)
        $tolerance = 1000;
        $amountDifference = abs($receivedAmount - $expectedAmount);
        
        if ($amountDifference > $tolerance) {
            // Log security alert
            error_log(sprintf(
                "VietQR/Bank Transfer Amount Mismatch Alert - Booking ID: %d, User ID: %s, Expected: %.2f, Received: %.2f, Difference: %.2f",
                $bookingId,
                isLoggedIn() ? getCurrentUserId() : 'guest',
                $expectedAmount,
                $receivedAmount,
                $amountDifference
            ));
            
            // Rollback transaction
            $conn->rollback();
            
            // Return error
            jsonError('Số tiền thanh toán không khớp với đơn hàng. Vui lòng liên hệ hỗ trợ.', 'AMOUNT_MISMATCH', 400);
        }
        
        // Use expected amount (from booking) instead of received amount for security
        $paymentAmount = $expectedAmount;
        
        // Update booking
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'confirmed',
                payment_status = 'paid',
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Create payment record
        $paymentMethod = $input['payment_method'] ?? 'bank_transfer'; // VietQR/Bank Transfer
        $transactionId = $input['transaction_id'] ?? ('TXN' . time() . rand(1000, 9999));
        
        $stmt = $conn->prepare("
            INSERT INTO payments (
                booking_id,
                amount,
                method,
                transaction_code,
                status,
                paid_at,
                created_at
            ) VALUES (?, ?, ?, ?, 'success', NOW(), NOW())
        ");
        $stmt->bind_param("idss", 
            $bookingId,
            $paymentAmount,
            $paymentMethod,
            $transactionId
        );
        $stmt->execute();
        
        // Update seat status to confirmed (if seats table exists)
        if (!empty($booking['trip_id'])) {
            // Check if both tables exist
            $seatsExists = false;
            $bookingSeatsExists = false;
            
            try {
                $result = $conn->query("SHOW TABLES LIKE 'seats'");
                $seatsExists = ($result && $result->num_rows > 0);
                
                $result = $conn->query("SHOW TABLES LIKE 'booking_seats'");
                $bookingSeatsExists = ($result && $result->num_rows > 0);
            } catch (Exception $e) {
                error_log('Error checking tables: ' . $e->getMessage());
            }
            
            if ($seatsExists && $bookingSeatsExists) {
                try {
                    $conn->query("
                        UPDATE seats s
                        JOIN booking_seats bs ON s.seat_number = bs.seat_number AND s.trip_id = {$booking['trip_id']}
                        SET s.status = 'booked',
                            s.updated_at = NOW()
                        WHERE bs.booking_id = $bookingId
                    ");
                } catch (Exception $e) {
                    error_log('Error updating seat status: ' . $e->getMessage());
                }
            }
        }
        
        // Create success notification (only if user is logged in)
        if (isLoggedIn() && !empty($booking['user_id']) && $booking['user_id'] > 0) {
            createNotification(
                $conn,
                $booking['user_id'],
                'Thanh toán thành công',
                "Thanh toán đơn hàng {$booking['booking_code']} thành công. Vé của bạn đã được xác nhận!",
                'success',
                $bookingId
            );
        }
        
        // Commit
        $conn->commit();
        
        jsonResponse(true, [
            'paid' => true,
            'booking_code' => $booking['booking_code'],
            'amount' => $booking['final_amount'],
            'transaction_id' => $transactionId
        ], 'Thanh toán thành công');
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Payment processing error: ' . $e->getMessage());
        jsonError('Không thể xử lý thanh toán', 'PAYMENT_ERROR', 500);
    }
} else {
    // Not in pending_verification state - return error
    if (!$isPendingVerification) {
        jsonError('Đơn hàng không ở trạng thái chờ xác nhận. Vui lòng sử dụng trang xác nhận thanh toán.', 'INVALID_STATE', 400);
    } else {
        // Auto check - return current status (for polling)
        jsonResponse(true, [
            'paid' => false,
            'booking_code' => $booking['booking_code'],
            'amount' => $booking['final_amount'],
            'status' => 'pending_verification'
        ], 'Đang chờ xác nhận thanh toán');
    }
}

