<?php
/**
 * Confirm Payment - Simple version for testing
 * Xác nhận thanh toán và gửi email
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/EmailService.php';
require_once '../../core/PromotionService.php';

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? 0);

if (empty($bookingId)) {
    $_SESSION['error'] = 'Không tìm thấy booking ID';
    redirect(appUrl());
}

// Get booking details for email
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

// Get seat numbers
$seatNumbers = array_column($tickets, 'seat_number');
$passengerName = $tickets[0]['passenger_name'] ?? 'Khách hàng';
$passengerEmail = $tickets[0]['passenger_email'] ?? '';

$conn->begin_transaction();

// Update payment status
$stmt = $conn->prepare("
    UPDATE bookings 
    SET payment_status = 'paid', 
        status = 'confirmed',
        updated_at = NOW()
    WHERE booking_id = ?
");
$stmt->bind_param("i", $bookingId);

if ($stmt->execute()) {
    // Update tickets status
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET status = 'confirmed'
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
                $bookingId,
                (int)($booking['user_id'] ?? 0),
                (float)($booking['discount_amount'] ?? 0)
            );
        }
        unset($_SESSION['applied_promotion']);
    }
    
    // Email feature is DISABLED - Focus on core booking flow
    // TODO: Setup proper SMTP server or use third-party service (SendGrid, Mailgun) for production
    // For now: Booking works without email notification
    error_log("✅ Booking confirmed: {$booking['booking_code']} - Email notification skipped");
    
    $conn->commit();

    // Clear session
    unset($_SESSION['booking_expiry']);
    
    // Redirect to success
    redirect(appUrl('user/booking/success.php?booking_id=' . $bookingId));
} else {
    $conn->rollback();
    $_SESSION['error'] = 'Không thể cập nhật thanh toán';
    redirect(appUrl('user/booking/payment.php'));
}

