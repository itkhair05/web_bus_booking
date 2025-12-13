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
$totalPrice = floatval($input['total_price'] ?? 0);

if (empty($tripId) || empty($seats)) {
    jsonError('Vui lòng chọn ghế', 'INVALID_INPUT', 400);
}

// Parse seats
$selectedSeats = array_filter(array_map('trim', explode(',', $seats)));

if (empty($selectedSeats)) {
    jsonError('Vui lòng chọn ít nhất một ghế', 'INVALID_INPUT', 400);
}

// Save to session
$_SESSION['booking_trip_id'] = $tripId;
$_SESSION['booking_seats'] = $selectedSeats;
$_SESSION['booking_price'] = $totalPrice;

jsonResponse(true, [
    'trip_id' => $tripId,
    'seats' => $selectedSeats,
    'total_price' => $totalPrice
], 'Đã lưu thông tin ghế');


