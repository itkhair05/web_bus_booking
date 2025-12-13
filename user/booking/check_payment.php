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

// Get booking (allow guest booking - no user_id check)
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    jsonError('Booking not found', 'NOT_FOUND', 404);
}

// Optional: Verify user_id if logged in
if (isLoggedIn()) {
    $userId = getUserId();
    if ($booking['user_id'] != $userId && $booking['user_id'] != 0) {
        jsonError('Unauthorized access', 'UNAUTHORIZED', 403);
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

// SIMULATION: If manual check (not auto), mark as paid
if (!$isAuto) {
    // Update booking status
    $conn->begin_transaction();
    
    try {
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
        $paymentMethod = 'bank_transfer'; // or from input
        $transactionId = 'TXN' . time() . rand(1000, 9999);
        
        $stmt = $conn->prepare("
            INSERT INTO payments (
                booking_id,
                amount,
                payment_method,
                transaction_id,
                payment_status,
                paid_at,
                created_at
            ) VALUES (?, ?, ?, ?, 'completed', NOW(), NOW())
        ");
        $stmt->bind_param("idss", 
            $bookingId,
            $booking['final_amount'],
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
    // Auto-check: Return not paid yet
    jsonResponse(true, [
        'paid' => false
    ], 'Chưa nhận được thanh toán');
}

