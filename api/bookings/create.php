<?php
/**
 * Create Booking API
 * Xử lý tạo booking mới
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';
require_once '../../core/PromotionService.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Verify CSRF
requireCsrfToken();

// Get data
$tripId = intval($_POST['trip_id'] ?? 0);
$seats = sanitizeInput($_POST['seats'] ?? '');
$contactName = sanitizeInput($_POST['contact_name'] ?? '');
$contactPhone = sanitizeInput($_POST['contact_phone'] ?? '');
$contactEmail = sanitizeInput($_POST['contact_email'] ?? '');
$pickupPoint = sanitizeInput($_POST['pickup_point'] ?? '');
$dropoffPoint = sanitizeInput($_POST['dropoff_point'] ?? '');
$promoCode = sanitizeInput($_POST['promo_code'] ?? '');
$notes = sanitizeInput($_POST['notes'] ?? '');
$passengers = $_POST['passengers'] ?? [];

// Validate required fields
if (empty($tripId) || empty($seats) || empty($contactName) || empty($contactPhone) || empty($contactEmail)) {
    jsonError('Vui lòng điền đầy đủ thông tin', 'INVALID_INPUT');
}

// Validate email
if (!validateEmail($contactEmail)) {
    jsonError('Email không hợp lệ', 'INVALID_EMAIL');
}

// Validate phone
if (!validatePhone($contactPhone)) {
    jsonError('Số điện thoại không hợp lệ', 'INVALID_PHONE');
}

// Parse seats
$seatNumbers = array_map('trim', explode(',', $seats));
$totalSeats = count($seatNumbers);

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get trip details
    $stmt = $conn->prepare("
        SELECT t.*, r.start_location, r.end_location, p.company_name
        FROM trips t
        INNER JOIN routes r ON t.route_id = r.route_id
        INNER JOIN partners p ON t.partner_id = p.partner_id
        WHERE t.trip_id = ? AND t.status = 'active'
        FOR UPDATE
    ");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Chuyến xe không tồn tại');
    }
    
    $trip = $result->fetch_assoc();
    
    // Check available seats
    if ($trip['available_seats'] < $totalSeats) {
        throw new Exception('Không đủ ghế trống');
    }
    
    // Check if seats are already booked
    $placeholders = str_repeat('?,', count($seatNumbers) - 1) . '?';
    $checkSeatsQuery = "
        SELECT COUNT(*) as booked_count
        FROM tickets t
        INNER JOIN bookings b ON t.booking_id = b.booking_id
        WHERE b.trip_id = ? 
        AND t.seat_number IN ($placeholders)
        AND b.status IN ('confirmed', 'pending')
        AND t.status = 'active'
    ";
    
    $stmt = $conn->prepare($checkSeatsQuery);
    $types = str_repeat('s', count($seatNumbers));
    $stmt->bind_param("i" . $types, $tripId, ...$seatNumbers);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['booked_count'] > 0) {
        throw new Exception('Một số ghế đã được đặt');
    }
    
    // Calculate prices
    $pricePerSeat = $trip['base_price'];
    $subtotal = $pricePerSeat * $totalSeats;
    $discountAmount = 0;
    
    // Apply promo code if provided (dùng chung logic với web)
    if (!empty($promoCode)) {
        try {
            $promoResult = PromotionService::applyPromotion($conn, $promoCode, (float)$subtotal);
            $discountAmount = $promoResult['discount'];
        } catch (Exception $e) {
            jsonError($e->getMessage(), 'INVALID_PROMO', 400);
        }
    }
    
    $finalPrice = $subtotal - $discountAmount;
    
    // Generate booking code
    $bookingCode = generateBookingCode();
    
    // Get user ID if logged in
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    // Create booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (
            booking_code, user_id, trip_id, 
            contact_name, contact_phone, contact_email,
            pickup_point, dropoff_point,
            total_seats, total_price, discount_amount, final_price,
            status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    
    $stmt->bind_param(
        "siisssssiidds",
        $bookingCode, $userId, $tripId,
        $contactName, $contactPhone, $contactEmail,
        $pickupPoint, $dropoffPoint,
        $totalSeats, $subtotal, $discountAmount, $finalPrice,
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể tạo booking');
    }
    
    $bookingId = $conn->insert_id;
    
    // Create tickets for each seat
    foreach ($seatNumbers as $index => $seatNumber) {
        $ticketCode = generateTicketCode($bookingId, $seatNumber);
        $passengerName = $passengers[$index]['name'] ?? $contactName;
        $passengerPhone = $passengers[$index]['phone'] ?? $contactPhone;
        
        $stmt = $conn->prepare("
            INSERT INTO tickets (
                booking_id, ticket_code, seat_number,
                passenger_name, passenger_phone, passenger_email,
                price, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->bind_param(
            "isssssd",
            $bookingId, $ticketCode, $seatNumber,
            $passengerName, $passengerPhone, $contactEmail,
            $pricePerSeat
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể tạo vé');
        }
    }
    
    // Update trip available seats
    $stmt = $conn->prepare("
        UPDATE trips 
        SET available_seats = available_seats - ? 
        WHERE trip_id = ?
    ");
    $stmt->bind_param("ii", $totalSeats, $tripId);
    $stmt->execute();
    
    // Việc cộng used_count sẽ thực hiện khi thanh toán thành công (VNPay/COD).
    
    // Log activity
    if ($userId) {
        logActivity($conn, $userId, 'create_booking', 'bookings', $bookingId, json_encode([
            'booking_code' => $bookingCode,
            'seats' => $seatNumbers,
            'amount' => $finalPrice
        ]));
        
        // Create notification
        createNotification(
            $conn,
            $userId,
            'Đặt vé thành công',
            "Bạn đã đặt vé thành công với mã đặt chỗ: $bookingCode. Vui lòng thanh toán trong 15 phút.",
            'booking',
            $bookingId
        );
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    jsonResponse(true, [
        'booking_id' => $bookingId,
        'booking_code' => $bookingCode,
        'total_seats' => $totalSeats,
        'final_price' => $finalPrice,
        'final_price_formatted' => formatPrice($finalPrice)
    ], 'Đặt vé thành công');
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log('Create booking error: ' . $e->getMessage());
    jsonError($e->getMessage(), 'BOOKING_ERROR', 400);
}

