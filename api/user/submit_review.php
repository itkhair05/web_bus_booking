<?php
/**
 * Submit Review API
 * Gửi đánh giá cho chuyến đi đã hoàn thành
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Check authentication
if (!isLoggedIn()) {
    jsonError('Vui lòng đăng nhập', 'AUTH_REQUIRED', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

requireCsrfToken();

$userId = getCurrentUserId();

// Get input data
$bookingId = intval($_POST['booking_id'] ?? 0);
$tripId = intval($_POST['trip_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = sanitizeInput($_POST['comment'] ?? '');

// Validate required fields
if ($bookingId <= 0 || $tripId <= 0) {
    jsonError('Thông tin đặt vé không hợp lệ', 'INVALID_INPUT');
}

if ($rating < 1 || $rating > 5) {
    jsonError('Đánh giá phải từ 1 đến 5 sao', 'INVALID_RATING');
}

try {
    // Verify booking belongs to user and is completed
    $stmt = $conn->prepare("
        SELECT b.booking_id, b.status, b.payment_status, t.trip_id, t.departure_time
        FROM bookings b
        JOIN trips t ON b.trip_id = t.trip_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $bookingId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonError('Không tìm thấy đơn đặt vé', 'BOOKING_NOT_FOUND');
    }
    
    $booking = $result->fetch_assoc();
    
    // Check if trip has departed (user can only review after trip)
    if (strtotime($booking['departure_time']) > time()) {
        jsonError('Chỉ có thể đánh giá sau khi chuyến đi đã khởi hành', 'TRIP_NOT_STARTED');
    }
    
    // Check if booking is confirmed/completed
    if (!in_array($booking['status'], ['confirmed', 'completed'])) {
        jsonError('Chỉ có thể đánh giá chuyến đi đã xác nhận hoặc hoàn thành', 'INVALID_BOOKING_STATUS');
    }
    
    // Check if user already reviewed this trip
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND trip_id = ?");
    $stmt->bind_param("ii", $userId, $tripId);
    $stmt->execute();
    $existingReview = $stmt->get_result();
    
    if ($existingReview->num_rows > 0) {
        jsonError('Bạn đã đánh giá chuyến đi này rồi', 'ALREADY_REVIEWED');
    }
    
    // Insert review
    $stmt = $conn->prepare("
        INSERT INTO reviews (user_id, trip_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiis", $userId, $tripId, $rating, $comment);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể lưu đánh giá: ' . $stmt->error);
    }
    
    $reviewId = $stmt->insert_id;
    
    // Create notification for partner
    $stmt = $conn->prepare("
        SELECT p.partner_id, r.start_point, r.end_point
        FROM trips t
        JOIN partners p ON t.partner_id = p.partner_id
        JOIN routes r ON t.route_id = r.route_id
        WHERE t.trip_id = ?
    ");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $tripInfo = $stmt->get_result()->fetch_assoc();
    
    if ($tripInfo) {
        $route = $tripInfo['start_point'] . ' - ' . $tripInfo['end_point'];
        $stars = str_repeat('⭐', $rating);
        
        // Insert notification for partner (into notifications table with partner_id)
        $notifTitle = "Đánh giá mới: {$rating} sao";
        $notifMessage = "Khách hàng đã đánh giá {$stars} cho tuyến {$route}";
        if ($comment) {
            $notifMessage .= ": \"{$comment}\"";
        }
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (partner_id, title, message, type, created_at)
            VALUES (?, ?, ?, 'review', NOW())
        ");
        $stmt->bind_param("iss", $tripInfo['partner_id'], $notifTitle, $notifMessage);
        $stmt->execute();
    }
    
    jsonResponse(true, [
        'review_id' => $reviewId,
        'rating' => $rating
    ], 'Cảm ơn bạn đã đánh giá!');
    
} catch (Exception $e) {
    error_log('Submit review error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra. Vui lòng thử lại!', 'SERVER_ERROR', 500);
}

