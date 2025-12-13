<?php
/**
 * Search Trips API
 * Tìm kiếm chuyến xe theo điểm đi, điểm đến, ngày đi
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';

// Get search parameters
$fromCity = sanitizeInput($_GET['from'] ?? '');
$toCity = sanitizeInput($_GET['to'] ?? '');
$date = sanitizeInput($_GET['date'] ?? '');
$sortBy = sanitizeInput($_GET['sort'] ?? 'departure_time'); // departure_time, price, rating
$filterType = sanitizeInput($_GET['type'] ?? ''); // limousine, sleeper, seat

// Validate required fields
if (empty($fromCity) || empty($toCity) || empty($date)) {
    jsonError('Vui lòng nhập đầy đủ thông tin tìm kiếm', 'INVALID_INPUT');
}

// Validate date format
if (!strtotime($date)) {
    jsonError('Ngày không hợp lệ', 'INVALID_DATE');
}

// Check if date is in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    jsonError('Không thể tìm chuyến xe trong quá khứ', 'INVALID_DATE');
}

try {
    // Build query - Sử dụng đúng tên cột theo schema thực tế
    $query = "
        SELECT 
            t.trip_id,
            t.departure_time,
            t.arrival_time,
            COALESCE(t.price, t.base_price, r.base_price, 0) as price,
            t.available_seats,
            t.status,
            r.route_id,
            r.route_name,
            COALESCE(r.origin, r.start_point, r.start_location) as origin,
            COALESCE(r.destination, r.end_point, r.end_location) as destination,
            COALESCE(r.distance_km, r.distance) as distance_km,
            COALESCE(r.duration_hours, r.estimated_duration) as duration_hours,
            p.partner_id,
            COALESCE(p.name, p.company_name) as partner_name,
            p.logo_url as partner_logo,
            p.rating,
            p.total_trips,
            v.vehicle_id,
            v.license_plate,
            v.vehicle_type,
            v.total_seats,
            v.amenities,
            v.images,
            d.driver_id,
            d.fullname as driver_name,
            d.phone as driver_phone,
            d.license_number
        FROM trips t
        INNER JOIN routes r ON t.route_id = r.route_id
        INNER JOIN partners p ON t.partner_id = p.partner_id
        INNER JOIN vehicles v ON t.vehicle_id = v.vehicle_id
        LEFT JOIN drivers d ON t.driver_id = d.driver_id
        WHERE (
            COALESCE(r.origin, r.start_point, r.start_location) LIKE ?
            OR COALESCE(r.origin, r.start_point, r.start_location) LIKE ?
        )
        AND (
            COALESCE(r.destination, r.end_point, r.end_location) LIKE ?
            OR COALESCE(r.destination, r.end_point, r.end_location) LIKE ?
        )
        AND DATE(t.departure_time) = ?
        AND (t.status = 'active' OR t.status = 'scheduled' OR t.status IS NULL)
        AND t.available_seats > 0
        AND (p.status = 'approved' OR p.status IS NULL)
    ";
    
    $searchFrom1 = "%$fromCity%";
    $searchFrom2 = "%" . strtolower($fromCity) . "%";
    $searchTo1 = "%$toCity%";
    $searchTo2 = "%" . strtolower($toCity) . "%";
    $params = [$searchFrom1, $searchFrom2, $searchTo1, $searchTo2, $date];
    $types = "sssss";
    
    // Add vehicle type filter
    if (!empty($filterType)) {
        $query .= " AND v.vehicle_type = ?";
        $params[] = $filterType;
        $types .= "s";
    }
    
    // Add sorting
    switch ($sortBy) {
        case 'price':
            $query .= " ORDER BY COALESCE(t.price, t.base_price, r.base_price, 0) ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY COALESCE(t.price, t.base_price, r.base_price, 0) DESC";
            break;
        case 'rating':
            $query .= " ORDER BY p.rating DESC, t.departure_time ASC";
            break;
        case 'departure_time':
        default:
            $query .= " ORDER BY t.departure_time ASC";
            break;
    }
    
    // Prepare and execute
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        // Parse amenities and images
        $row['amenities'] = !empty($row['amenities']) ? json_decode($row['amenities'], true) : [];
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        
        // Format prices
        $price = $row['price'] ?? $row['base_price'] ?? 0;
        $row['price'] = $price;
        $row['base_price'] = $price;
        $row['price_formatted'] = formatPrice($price);
        $row['base_price_formatted'] = formatPrice($price);
        
        // Format times
        $row['departure_time_formatted'] = date('H:i', strtotime($row['departure_time']));
        $row['arrival_time_formatted'] = date('H:i', strtotime($row['arrival_time']));
        $row['departure_date_formatted'] = formatDate($row['departure_time'], 'd/m/Y');
        
        // Calculate duration
        $duration = $row['duration_hours'] ?? calculateDuration($row['departure_time'], $row['arrival_time']);
        $row['duration'] = $duration;
        $row['duration_hours'] = $duration;
        
        // Calculate seats status
        $row['seats_percentage'] = round(($row['available_seats'] / $row['total_seats']) * 100);
        $row['is_almost_full'] = $row['available_seats'] <= 5;
        
        // Add to results
        $trips[] = $row;
    }
    
    // Return results
    jsonResponse(true, [
        'trips' => $trips,
        'total' => count($trips),
        'search_params' => [
            'from' => $fromCity,
            'to' => $toCity,
            'date' => $date,
            'date_formatted' => formatDate($date, 'd/m/Y'),
            'sort_by' => $sortBy
        ]
    ], count($trips) > 0 ? 'Tìm thấy ' . count($trips) . ' chuyến xe' : 'Không tìm thấy chuyến xe phù hợp');
    
} catch (Exception $e) {
    error_log('Search trips error: ' . $e->getMessage());
    jsonError('Có lỗi xảy ra khi tìm kiếm', 'SERVER_ERROR', 500);
}

