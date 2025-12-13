<?php
/**
 * Process Booking
 * Lưu booking vào database
 */

// Enable error display for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';
require_once '../../core/EmailService.php';

// Allow guest booking (no login required)
// requireLogin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method', 'METHOD_NOT_ALLOWED', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    jsonError('Invalid CSRF token', 'CSRF_ERROR', 403);
}

// Get data from session
$tripId = intval($_SESSION['booking_trip_id'] ?? 0);
$selectedSeats = $_SESSION['booking_seats'] ?? [];
$totalPrice = floatval($_SESSION['booking_price'] ?? 0);
$pickupId = $_SESSION['booking_pickup_id'] ?? '';
$dropoffId = $_SESSION['booking_dropoff_id'] ?? '';
$pickupTime = $_SESSION['booking_pickup_time'] ?? '';
$pickupStation = $_SESSION['booking_pickup_station'] ?? '';
$dropoffTime = $_SESSION['booking_dropoff_time'] ?? '';
$dropoffStation = $_SESSION['booking_dropoff_station'] ?? '';

// Validate session data
if (empty($tripId)) {
    jsonError('Thiếu thông tin chuyến xe. Vui lòng chọn lại chuyến.', 'INVALID_SESSION', 400);
}

if (empty($selectedSeats) || !is_array($selectedSeats) || count($selectedSeats) === 0) {
    jsonError('Thiếu thông tin ghế. Vui lòng chọn lại ghế.', 'INVALID_SESSION', 400);
}

if (empty($totalPrice) || $totalPrice <= 0) {
    jsonError('Thiếu thông tin giá. Vui lòng chọn lại chuyến.', 'INVALID_SESSION', 400);
}

// Pickup/Dropoff are optional - not all systems require them
// if (empty($pickupId)) {
//     jsonError('Thiếu thông tin điểm đón. Vui lòng chọn lại điểm đón.', 'INVALID_SESSION', 400);
// }

// if (empty($dropoffId)) {
//     jsonError('Thiếu thông tin điểm trả. Vui lòng chọn lại điểm trả.', 'INVALID_SESSION', 400);
// }

// Validate input
$passengerName = trim($input['passenger_name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$hasInsurance = !empty($input['insurance']);

if (empty($passengerName) || empty($phone) || empty($email)) {
    jsonError('Vui lòng điền đầy đủ thông tin', 'INVALID_INPUT', 400);
}

if (!validateEmail($email)) {
    jsonError('Email không hợp lệ', 'INVALID_EMAIL', 400);
}

// Helper function to check if column exists
function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    static $cache = [];
    $key = "$table.$column";
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    try {
        // First check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return $cache[$key] = false;
        }
        
        // Then check if column exists
        $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $cache[$key] = ($result && $result->num_rows > 0);
    } catch (Exception $e) {
        error_log("Error checking column $table.$column: " . $e->getMessage());
        return $cache[$key] = false;
    } catch (Error $e) {
        error_log("Fatal error checking column $table.$column: " . $e->getMessage());
        return $cache[$key] = false;
    }
}

// Get user ID - create or use guest user if not logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
} else {
    // Check if guest user exists (email = 'guest@system.local')
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = 'guest@system.local' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $guestUser = $result->fetch_assoc();
        $userId = $guestUser['user_id'];
    } else {
        // Create guest user BEFORE transaction (so it won't be rolled back)
        $guestName = 'Guest User';
        $guestEmail = 'guest@system.local';
        $guestPhone = '0000000000';
        $guestPassword = password_hash('guest_' . time() . rand(1000, 9999), PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, phone, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'user', 'active', NOW())
        ");
        $stmt->bind_param("ssss", $guestName, $guestEmail, $guestPhone, $guestPassword);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            error_log('Created guest user with ID: ' . $userId);
        } else {
            // If insert fails, we cannot proceed
            error_log('Could not create guest user: ' . $stmt->error);
            jsonError('Không thể tạo tài khoản khách. Vui lòng đăng nhập hoặc thử lại.', 'GUEST_USER_ERROR', 500);
            exit;
        }
    }
}

// Get trip details for email (before transaction)
$tripDetails = null;
try {
    $routeOriginCol = tableHasColumn($conn, 'routes', 'origin') ? 'r.origin' : 
                      (tableHasColumn($conn, 'routes', 'start_point') ? 'r.start_point' : 'r.start_point');
    $routeDestCol = tableHasColumn($conn, 'routes', 'destination') ? 'r.destination' : 
                    (tableHasColumn($conn, 'routes', 'end_point') ? 'r.end_point' : 'r.end_point');
    
    $partnerNameCol = tableHasColumn($conn, 'partners', 'name') ? 'p.name' : 
                      (tableHasColumn($conn, 'partners', 'company_name') ? 'p.company_name' : 'p.name');
    $vehicleTypeCol = tableHasColumn($conn, 'vehicles', 'vehicle_type') ? 'v.vehicle_type' : 
                      (tableHasColumn($conn, 'vehicles', 'type') ? 'v.type' : "''");
    
    $stmt = $conn->prepare("
        SELECT 
            t.*,
            $routeOriginCol as origin,
            $routeDestCol as destination,
            $partnerNameCol as partner_name,
            $vehicleTypeCol as vehicle_type
        FROM trips t
        JOIN routes r ON t.route_id = r.route_id
        JOIN partners p ON t.partner_id = p.partner_id
        JOIN vehicles v ON t.vehicle_id = v.vehicle_id
        WHERE t.trip_id = ?
    ");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $tripDetails = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log('Error fetching trip details for email: ' . $e->getMessage());
    // Continue without trip details - email will have limited info
}

// Start transaction
$conn->begin_transaction();

try {
    // Calculate total amount
    $insuranceAmount = 0;
    if ($hasInsurance) {
        $insuranceAmount = 20000 * count($selectedSeats);
    }
    $finalAmount = $totalPrice + $insuranceAmount;
    
    // Generate booking code
    $bookingCode = 'BK' . date('ymd') . strtoupper(substr(uniqid(), -6));
    
    // Build INSERT query for bookings table
    // Based on actual schema: user_id, trip_id, booking_code, total_price, discount_amount, final_price, status, payment_status
    $columns = ['user_id', 'trip_id', 'booking_code', 'total_price', 'discount_amount', 'final_price', 'status', 'payment_status'];
    $values = ['?', '?', '?', '?', '?', '?', "'pending'", "'unpaid'"];
    $params = [$userId, $tripId, $bookingCode, $totalPrice, 0.00, $finalAmount];
    $types = 'iisddd';
    
    // Build and execute INSERT
    $columnsStr = implode(', ', $columns);
    $valuesStr = implode(', ', $values);
    
    $sql = "INSERT INTO bookings ($columnsStr) VALUES ($valuesStr)";
    
    // Count number of placeholders (?) in values
    $placeholderCount = substr_count($valuesStr, '?');
    
    // Debug: Log SQL for troubleshooting
    error_log('Booking SQL: ' . $sql);
    error_log('Types length: ' . strlen($types));
    error_log('Params count: ' . count($params));
    error_log('Placeholder count: ' . $placeholderCount);
    
    // Verify counts match
    if ($placeholderCount !== count($params)) {
        error_log('Placeholder mismatch: placeholders=' . $placeholderCount . ', params=' . count($params));
        error_log('SQL: ' . $sql);
        throw new Exception('Lỗi tham số không khớp. Vui lòng thử lại.');
    }
    
    if (strlen($types) !== count($params)) {
        error_log('Type mismatch: types=' . strlen($types) . ', params=' . count($params));
        error_log('SQL: ' . $sql);
        throw new Exception('Lỗi kiểu dữ liệu không khớp. Vui lòng thử lại.');
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log('Prepare error: ' . $conn->error);
        throw new Exception('Không thể prepare SQL: ' . $conn->error);
    }
    
    // Bind parameters
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log('Execute error: ' . $stmt->error);
        throw new Exception('Không thể tạo booking: ' . $stmt->error);
    }
    
    $bookingId = $conn->insert_id;
    
    if (!$bookingId) {
        throw new Exception('Không thể lấy booking_id sau khi insert');
    }
    
    // Insert booking seats (if table exists) - non-critical
    // Check if table exists first
    $bookingSeatsExists = false;
    try {
        $result = $conn->query("SHOW TABLES LIKE 'booking_seats'");
        $bookingSeatsExists = ($result && $result->num_rows > 0);
    } catch (Exception $e) {
        error_log('Error checking booking_seats table: ' . $e->getMessage());
    }
    
    if ($bookingSeatsExists && tableHasColumn($conn, 'booking_seats', 'booking_id')) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO booking_seats (booking_id, seat_number, price, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if ($stmt) {
                foreach ($selectedSeats as $seatNumber) {
                    $seatPrice = $totalPrice / count($selectedSeats);
                    $stmt->bind_param("isd", $bookingId, $seatNumber, $seatPrice);
                    if (!$stmt->execute()) {
                        error_log('Could not save booking seat: ' . $stmt->error);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error inserting booking_seats: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Fatal error inserting booking_seats: ' . $e->getMessage());
        }
    } else {
        error_log('booking_seats table does not exist, skipping...');
    }
    
    // Save passenger info to tickets table - non-critical but important
    // Check if table exists first
    $ticketsExists = false;
    try {
        $result = $conn->query("SHOW TABLES LIKE 'tickets'");
        $ticketsExists = ($result && $result->num_rows > 0);
    } catch (Exception $e) {
        error_log('Error checking tickets table: ' . $e->getMessage());
    }
    
    if ($ticketsExists && tableHasColumn($conn, 'tickets', 'booking_id')) {
        try {
            $ticketCodePrefix = 'TKT' . date('ymd');
            
            // Check required columns
            $hasTripId = tableHasColumn($conn, 'tickets', 'trip_id');
            $hasTicketCode = tableHasColumn($conn, 'tickets', 'ticket_code');
            
            if ($hasTripId && $hasTicketCode) {
                $stmt = $conn->prepare("
                    INSERT INTO tickets (
                        booking_id, trip_id, seat_number, 
                        passenger_name, passenger_phone, passenger_email,
                        ticket_code, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                
                if ($stmt) {
                    foreach ($selectedSeats as $index => $seatNumber) {
                        $ticketCode = $ticketCodePrefix . strtoupper(substr(uniqid(), -6)) . $index;
                        $stmt->bind_param("iisssss", $bookingId, $tripId, $seatNumber, $passengerName, $phone, $email, $ticketCode);
                        if (!$stmt->execute()) {
                            error_log('Could not save ticket: ' . $stmt->error);
                        }
                    }
                }
            } elseif ($hasTicketCode) {
                // Without trip_id
                $stmt = $conn->prepare("
                    INSERT INTO tickets (
                        booking_id, seat_number, 
                        passenger_name, passenger_phone, passenger_email,
                        ticket_code, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                
                if ($stmt) {
                    foreach ($selectedSeats as $index => $seatNumber) {
                        $ticketCode = $ticketCodePrefix . strtoupper(substr(uniqid(), -6)) . $index;
                        $stmt->bind_param("isssss", $bookingId, $seatNumber, $passengerName, $phone, $email, $ticketCode);
                        if (!$stmt->execute()) {
                            error_log('Could not save ticket: ' . $stmt->error);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error inserting tickets: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Fatal error inserting tickets: ' . $e->getMessage());
        }
    } else {
        error_log('tickets table does not exist or missing booking_id column, skipping...');
    }
    
    // Không khóa ghế tại bước tạo booking. Ghế sẽ giữ dựa trên tickets/booking khi thanh toán thành công hoặc khi hết hạn sẽ tự mở.
    
    // Create notification (only if user is logged in and function exists)
    if ($userId && function_exists('createNotification')) {
        try {
            createNotification(
                $conn,
                $userId,
                'Đặt vé thành công',
                "Đơn đặt vé $bookingCode đã được tạo. Vui lòng thanh toán trong 15 phút.",
                'booking',
                $bookingId
            );
        } catch (Exception $e) {
            // Non-critical error, just log it
            error_log('Could not create notification: ' . $e->getMessage());
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Clear booking session data (keep booking_id for payment)
    $_SESSION['booking_id'] = $bookingId;
    unset($_SESSION['booking_trip_id']);
    unset($_SESSION['booking_seats']);
    unset($_SESSION['booking_price']);
    unset($_SESSION['booking_pickup_id']);
    unset($_SESSION['booking_dropoff_id']);
    
    // Log success for debugging
    error_log('Booking created successfully: booking_id=' . $bookingId . ', booking_code=' . $bookingCode);
    
    // Send confirmation email (non-blocking - don't fail if email fails)
    try {
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Prepare trip details for email
            $route = '';
            if ($tripDetails) {
                $origin = $tripDetails['origin'] ?? '';
                $destination = $tripDetails['destination'] ?? '';
                $route = $origin && $destination ? $origin . ' → ' . $destination : 'N/A';
            }
            
            $departureTime = '';
            if ($tripDetails && isset($tripDetails['departure_time'])) {
                $departureTime = date('H:i - d/m/Y', strtotime($tripDetails['departure_time']));
            }
            
            $seatsStr = implode(', ', $selectedSeats);
            $totalPriceFormatted = number_format($finalAmount) . 'đ';
            
            $emailTripDetails = [
                'booking_id' => $bookingId,
                'route' => $route,
                'departure_time' => $departureTime,
                'seats' => $seatsStr,
                'total_price' => $totalPriceFormatted,
                'partner_name' => $tripDetails['partner_name'] ?? '',
                'vehicle_type' => $tripDetails['vehicle_type'] ?? '',
                'pickup_station' => $pickupStation,
                'pickup_time' => $pickupTime ? date('H:i', strtotime($pickupTime)) : '',
                'dropoff_station' => $dropoffStation,
                'dropoff_time' => $dropoffTime ? date('H:i', strtotime($dropoffTime)) : ''
            ];
            
            $emailSent = EmailService::sendBookingConfirmation(
                $email,
                $passengerName,
                $bookingCode,
                $emailTripDetails
            );
            
            if ($emailSent) {
                error_log('Confirmation email sent successfully to: ' . $email);
            } else {
                error_log('Failed to send confirmation email to: ' . $email);
            }
        } else {
            error_log('Invalid email address, skipping email: ' . ($email ?? 'empty'));
        }
    } catch (Exception $e) {
        // Don't fail the booking if email fails
        error_log('Error sending confirmation email: ' . $e->getMessage());
    }
    
    // Return success
    jsonResponse(true, [
        'booking_id' => $bookingId,
        'booking_code' => $bookingCode,
        'final_amount' => $finalAmount
    ], 'Đặt vé thành công');
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    
    $errorDetails = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log('Booking error: ' . json_encode($errorDetails, JSON_UNESCAPED_UNICODE));
    
    // Return error with details for debugging
    $errorMessage = 'Không thể đặt vé. Vui lòng thử lại.';
    // Always show details in development
    $errorMessage .= ' Lỗi: ' . $e->getMessage();
    
    jsonError($errorMessage, 'BOOKING_ERROR', 500);
} catch (Error $e) {
    // Catch fatal errors
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    
    error_log('Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    jsonError('Có lỗi nghiêm trọng xảy ra. Vui lòng thử lại sau.', 'FATAL_ERROR', 500);
}

