<?php
/**
 * Save Pickup & Dropoff Points to Session
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

$pickupId = sanitizeInput($input['pickup_id'] ?? '');
$pickupTime = sanitizeInput($input['pickup_time'] ?? '');
$pickupStation = sanitizeInput($input['pickup_station'] ?? '');

$dropoffId = sanitizeInput($input['dropoff_id'] ?? '');
$dropoffTime = sanitizeInput($input['dropoff_time'] ?? '');
$dropoffStation = sanitizeInput($input['dropoff_station'] ?? '');

if (empty($pickupId) || empty($dropoffId) || empty($pickupStation) || empty($dropoffStation)) {
    jsonError('Vui lòng chọn điểm đón và điểm trả', 'INVALID_INPUT', 400);
}

// Save to session
$_SESSION['booking_pickup_id'] = $pickupId;
$_SESSION['booking_pickup_time'] = $pickupTime;
$_SESSION['booking_pickup_station'] = $pickupStation;

$_SESSION['booking_dropoff_id'] = $dropoffId;
$_SESSION['booking_dropoff_time'] = $dropoffTime;
$_SESSION['booking_dropoff_station'] = $dropoffStation;

jsonResponse(true, [
    'pickup' => [
        'id' => $pickupId,
        'time' => $pickupTime,
        'station' => $pickupStation
    ],
    'dropoff' => [
        'id' => $dropoffId,
        'time' => $dropoffTime,
        'station' => $dropoffStation
    ]
], 'Đã lưu điểm đón và điểm trả');

