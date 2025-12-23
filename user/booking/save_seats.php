<?php
/**
 * Save Selected Seats to Session
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method', 'METHOD_NOT_ALLOWED', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$tripId = intval($input['trip_id'] ?? 0);
$seats = $input['seats'] ?? '';

if (empty($tripId) || empty($seats)) {
    jsonError('Vui lòng chọn ghế', 'INVALID_INPUT', 400);
}

// Parse seats
$selectedSeats = array_filter(array_map('trim', explode(',', $seats)));

if (empty($selectedSeats)) {
    jsonError('Vui lòng chọn ít nhất một ghế', 'INVALID_INPUT', 400);
}

// SECURITY: Get price from database, not from client
$conn = require_once '../../config/db.php';

$stmt = $conn->prepare("SELECT price FROM trips WHERE trip_id = ?");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    jsonError('Chuyến xe không tồn tại', 'TRIP_NOT_FOUND', 404);
}

// Calculate price on backend
$pricePerSeat = floatval($trip['price']);
$totalPrice = $pricePerSeat * count($selectedSeats);

// Save to session (price is calculated on backend for security)
$_SESSION['booking_trip_id'] = $tripId;
$_SESSION['booking_seats'] = $selectedSeats;
$_SESSION['booking_price'] = $totalPrice; // This is now calculated on backend

jsonResponse(true, [
    'trip_id' => $tripId,
    'seats' => $selectedSeats,
    'total_price' => $totalPrice,
    'price_per_seat' => $pricePerSeat
], 'Đã lưu thông tin ghế');


