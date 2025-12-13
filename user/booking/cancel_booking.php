<?php
/**
 * Cancel Booking
 * Hủy đặt vé
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';

// Allow guest cancellation (no login required)
// requireLogin();

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? 0);

if (empty($bookingId)) {
    redirect(appUrl());
}

// Get booking + trip info (allow guest booking)
$stmt = $conn->prepare("
    SELECT b.*, t.departure_time
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    redirect(appUrl());
}

// Optional: Verify user_id if logged in
if (isLoggedIn()) {
    $userId = function_exists('getCurrentUserId') ? getCurrentUserId() : ($user['user_id'] ?? null);
    if ($userId && $booking['user_id'] != $userId && $booking['user_id'] != 0) {
        redirect(appUrl());
    }
}

// Check if already cancelled
if ($booking['status'] === 'cancelled') {
    redirect(appUrl('user/tickets/my_tickets.php?status=cancelled'));
}

// Rule: chỉ cho hủy trước giờ chạy 5 giờ
$departure = !empty($booking['departure_time']) ? strtotime($booking['departure_time']) : null;
$nowTs = time();
if ($departure && ($departure - $nowTs) < 5 * 3600) {
    $_SESSION['error'] = 'Chỉ được hủy/hoàn vé trước giờ xe chạy ít nhất 5 giờ.';
    redirect(appUrl('user/tickets/my_tickets.php'));
}

$isPaid = $booking['payment_status'] === 'paid';
$refundAmount = $isPaid ? round(((float)$booking['final_price']) * 0.8, 0) : 0;

// Start transaction
$conn->begin_transaction();

try {
    // Update booking status
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled',
            payment_status = CASE WHEN payment_status = 'paid' THEN 'refunded' ELSE 'failed' END,
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    // Nếu đã thanh toán: cập nhật payment + tạo record hoàn (80%)
    if ($isPaid && $refundAmount > 0) {
        $payStmt = $conn->prepare("SELECT payment_id, method FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1");
        $payStmt->bind_param("i", $bookingId);
        $payStmt->execute();
        $payment = $payStmt->get_result()->fetch_assoc();

        if ($payment) {
            $updPay = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE payment_id = ?");
            $updPay->bind_param("i", $payment['payment_id']);
            $updPay->execute();

            try {
                $refundStmt = $conn->prepare("
                    INSERT INTO refunds (booking_id, payment_id, refund_amount, refund_reason, status, created_at)
                    VALUES (?, ?, ?, 'User cancel - refund 80%', 'completed', NOW())
                ");
                $refundStmt->bind_param("iid", $bookingId, $payment['payment_id'], $refundAmount);
                $refundStmt->execute();
            } catch (Exception $e) {
                error_log('Refund insert failed: ' . $e->getMessage());
            }
        }
    }
    
    // Release seats (if tables exist)
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
    
    if ($seatsExists && $bookingSeatsExists && !empty($booking['trip_id'])) {
        try {
            $conn->query("
                UPDATE seats s
                JOIN booking_seats bs ON s.seat_number = bs.seat_number AND s.trip_id = {$booking['trip_id']}
                SET s.status = 'available',
                    s.booked_by = NULL,
                    s.updated_at = NOW()
                WHERE bs.booking_id = $bookingId
            ");
        } catch (Exception $e) {
            error_log('Error releasing seats: ' . $e->getMessage());
        }
    }
    
    // Create notification (only if user is logged in)
    if (isLoggedIn() && !empty($booking['user_id']) && $booking['user_id'] > 0) {
        createNotification(
            $conn,
            $booking['user_id'],
            'Đơn hàng đã hủy',
            "Đơn hàng {$booking['booking_code']} đã được hủy thành công.",
            'info',
            $bookingId
        );
    }
    
    // Commit
    $conn->commit();
    
    // Clear booking session to tránh quay lại trang thanh toán
    unset($_SESSION['booking_id'], $_SESSION['booking_expiry'], $_SESSION['booking_trip_id'], $_SESSION['booking_seats'], $_SESSION['booking_price'], $_SESSION['booking_pickup_id'], $_SESSION['booking_pickup_time'], $_SESSION['booking_pickup_station'], $_SESSION['booking_dropoff_id'], $_SESSION['booking_dropoff_time'], $_SESSION['booking_dropoff_station']);
    
    // Redirect về danh sách vé với thông báo hủy thành công
    $_SESSION['success'] = $isPaid
        ? 'Đã hủy vé. Hoàn 80% sẽ được xử lý.'
        : 'Đã hủy vé thành công.';
    redirect(appUrl('user/tickets/my_tickets.php?status=cancelled'));
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Cancel booking error: ' . $e->getMessage());
    $_SESSION['error'] = 'Không thể hủy đơn hàng. Vui lòng thử lại.';
    redirect(appUrl('user/tickets/my_tickets.php'));
}

