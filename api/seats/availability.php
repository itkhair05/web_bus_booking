<?php
/**
 * Get Seats Availability API
 * Lấy thông tin ghế trống/đã đặt cho một chuyến xe
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';

// Get trip ID
$tripId = intval($_GET['trip_id'] ?? 0);

if (empty($tripId)) {
    jsonError('Vui lòng cung cấp trip_id', 'INVALID_INPUT');
}

try {
    // Get trip details with vehicle info
    $query = "
        SELECT 
            t.trip_id,
            t.departure_time,
            t.arrival_time,
            t.base_price,
            t.available_seats,
            t.status,
            r.route_id,
            r.start_location,
            r.end_location,
            r.distance,
            p.partner_id,
            p.company_name,
            p.rating,
            v.vehicle_id,
            v.type as vehicle_type,
            v.total_seats,
            v.seat_layout,
            v.amenities
        FROM trips t
        INNER JOIN routes r ON t.route_id = r.route_id
        INNER JOIN partners p ON t.partner_id = p.partner_id
        INNER JOIN vehicles v ON t.vehicle_id = v.vehicle_id
        WHERE t.trip_id = ? AND t.status = 'active'
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonError('Không tìm thấy chuyến xe', 'TRIP_NOT_FOUND', 404);
    }
    
    $trip = $result->fetch_assoc();
    
    // Parse seat layout and amenities
    $seatLayout = !empty($trip['seat_layout']) ? json_decode($trip['seat_layout'], true) : null;
    $trip['amenities'] = !empty($trip['amenities']) ? json_decode($trip['amenities'], true) : [];
    
    // Get booked seats for this trip
    $bookedQuery = "
        SELECT DISTINCT t.seat_number
        FROM tickets t
        INNER JOIN bookings b ON t.booking_id = b.booking_id
        WHERE b.trip_id = ? 
        AND b.status IN ('confirmed', 'pending')
        AND t.status = 'active'
    ";
    
    $stmt = $conn->prepare($bookedQuery);
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $bookedResult = $stmt->get_result();
    
    $bookedSeats = [];
    while ($row = $bookedResult->fetch_assoc()) {
        $bookedSeats[] = $row['seat_number'];
    }
    
    // Generate seat map if not exists in vehicle
    if (empty($seatLayout)) {
        $seatLayout = generateDefaultSeatLayout($trip['vehicle_type'], $trip['total_seats']);
    }
    
    // Mark booked seats in layout
    foreach ($seatLayout as &$row) {
        foreach ($row as &$seat) {
            if ($seat && in_array($seat, $bookedSeats)) {
                // Mark as booked
                $seat = [
                    'number' => $seat,
                    'status' => 'booked'
                ];
            } elseif ($seat) {
                // Available
                $seat = [
                    'number' => $seat,
                    'status' => 'available'
                ];
            }
        }
    }
    
    // Format trip data
    $trip['departure_time_formatted'] = formatDate($trip['departure_time'], 'H:i - d/m/Y');
    $trip['arrival_time_formatted'] = formatDate($trip['arrival_time'], 'H:i - d/m/Y');
    $trip['duration'] = calculateDuration($trip['departure_time'], $trip['arrival_time']);
    $trip['base_price_formatted'] = formatPrice($trip['base_price']);
    
    // Return response
    jsonResponse(true, [
        'trip' => $trip,
        'seat_layout' => $seatLayout,
        'booked_seats' => $bookedSeats,
        'available_count' => $trip['available_seats'],
        'total_seats' => $trip['total_seats']
    ]);
    
} catch (Exception $e) {
    error_log('Get seats error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra', 'SERVER_ERROR', 500);
}

/**
 * Generate default seat layout based on vehicle type
 */
function generateDefaultSeatLayout($type, $totalSeats) {
    $layout = [];
    
    switch ($type) {
        case 'limousine':
            // Limousine 9 chỗ: 3 hàng x 3 ghế
            $layout = [
                ['A1', 'A2', 'A3'],
                ['B1', 'B2', 'B3'],
                ['C1', 'C2', 'C3']
            ];
            break;
            
        case 'sleeper':
            // Giường nằm 40 chỗ: 2 tầng x 2 bên
            $rows = ceil($totalSeats / 4);
            for ($i = 1; $i <= $rows; $i++) {
                $layout[] = [
                    'A' . $i,
                    'B' . $i,
                    null, // lối đi
                    'C' . $i,
                    'D' . $i
                ];
            }
            break;
            
        case 'seat':
        default:
            // Ghế ngồi 45 chỗ: 2 ghế - lối đi - 3 ghế
            $rows = ceil($totalSeats / 5);
            for ($i = 1; $i <= $rows; $i++) {
                $layout[] = [
                    'A' . $i,
                    'B' . $i,
                    null, // lối đi
                    'C' . $i,
                    'D' . $i,
                    'E' . $i
                ];
            }
            break;
    }
    
    return $layout;
}

