<?php
/**
 * Check Payment Status API
 * API kiểm tra trạng thái thanh toán - Polling endpoint
 * 
 * SANDBOX MODE: Auto-verifies payment after scheduled delay
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/PromotionService.php';

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? 0);

if (empty($bookingId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing booking_id'
    ]);
    exit;
}

// SANDBOX MODE: Check if auto-verify should trigger
// Check từ database thay vì chỉ session để đảm bảo hoạt động đúng cách
$sandboxTriggered = false;

// First, get payment record to check sandbox verification schedule
$stmt = $conn->prepare("
    SELECT payment_data, status 
    FROM payments 
    WHERE booking_id = ? AND method = 'bank_transfer'
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$paymentRecord = $stmt->get_result()->fetch_assoc();

$shouldTriggerSandbox = false;

if ($paymentRecord && $paymentRecord['status'] === 'pending') {
    $paymentData = json_decode($paymentRecord['payment_data'] ?? '{}', true);
    
    // Check if in pending_verification state
    if (!empty($paymentData['type']) && $paymentData['type'] === 'pending_verification') {
        // Check if sandbox verification time has arrived
        if (!empty($paymentData['sandbox_verify_at']) && time() >= (int)$paymentData['sandbox_verify_at']) {
            $shouldTriggerSandbox = true;
        }
        
        // Fallback: Check session if database doesn't have verify_at
        if (!$shouldTriggerSandbox && !empty($_SESSION['sandbox_verify']) && 
            $_SESSION['sandbox_verify']['booking_id'] == $bookingId &&
            time() >= $_SESSION['sandbox_verify']['verify_at']) {
            $shouldTriggerSandbox = true;
        }
    }
}

if ($shouldTriggerSandbox) {
    // Time to auto-verify!
    $sandboxTriggered = processSandboxVerification($conn, $bookingId);
    unset($_SESSION['sandbox_verify']);
}

// Get booking details
$stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.booking_code,
        b.payment_status,
        b.status,
        b.final_price,
        b.user_id,
        b.discount_amount,
        p.transaction_code,
        p.status as payment_record_status,
        p.paid_at,
        p.payment_data
    FROM bookings b
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.booking_id = ?
    ORDER BY p.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found'
    ]);
    exit;
}

// Determine effective payment status
// Check payment_data for pending_verification state
$effectiveStatus = $booking['payment_status'];
if ($booking['payment_status'] === 'unpaid' && 
    $booking['payment_record_status'] === 'pending') {
    // Check if in verification mode
    $paymentData = json_decode($booking['payment_data'] ?? '{}', true);
    if (!empty($paymentData['type']) && $paymentData['type'] === 'pending_verification') {
        $effectiveStatus = 'pending_verification';
    }
}

// Return current status
$response = [
    'success' => true,
    'booking_id' => $booking['booking_id'],
    'booking_code' => $booking['booking_code'],
    'status' => $effectiveStatus,
    'booking_status' => $booking['status'],
    'amount' => (float)$booking['final_price'],
    'transaction_code' => $booking['transaction_code'] ?? null,
    'paid_at' => $booking['paid_at'] ?? null,
    'message' => getStatusMessage($effectiveStatus),
    'sandbox_triggered' => $sandboxTriggered
];

echo json_encode($response);

/**
 * Process sandbox auto-verification
 */
function processSandboxVerification($conn, $bookingId) {
    global $_SESSION;
    
    $conn->begin_transaction();
    
    try {
        // Get booking with payment info
        $stmt = $conn->prepare("
            SELECT b.*, p.status as payment_status_record, p.payment_data
            FROM bookings b
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            WHERE b.booking_id = ? AND b.payment_status = 'unpaid'
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        // Check if in pending verification state
        if (!$booking) {
            return false;
        }
        
        $paymentData = json_decode($booking['payment_data'] ?? '{}', true);
        if (empty($paymentData['type']) || $paymentData['type'] !== 'pending_verification') {
            return false;
        }
        
        // Generate transaction code
        $transactionCode = 'SANDBOX_' . strtoupper(substr(md5($bookingId . time()), 0, 8));
        $paymentAmount = (float)$booking['final_price'];
        
        // Update booking
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'paid', 
                status = 'confirmed',
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Update tickets
        $stmt = $conn->prepare("UPDATE tickets SET status = 'confirmed' WHERE booking_id = ?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Update payment record
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'success',
                transaction_code = ?,
                paid_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("si", $transactionCode, $bookingId);
        $stmt->execute();
        
        // Increment promotion usage if applicable
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
        
        // Create notification
        if (!empty($booking['user_id'])) {
            $notifTitle = 'Thanh toán thành công';
            $notifMessage = "Đơn đặt vé {$booking['booking_code']} đã được thanh toán thành công. Mã GD: {$transactionCode}";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
                VALUES (?, ?, ?, 'payment', ?, NOW())
            ");
            $stmt->bind_param("issi", $booking['user_id'], $notifTitle, $notifMessage, $bookingId);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Send payment confirmation email (after commit to ensure data is saved)
        sendPaymentConfirmationEmail($conn, $bookingId, $transactionCode);
        
        error_log("✅ [SANDBOX] Auto-verified payment for booking {$booking['booking_code']}, TXN: {$transactionCode}");
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Sandbox auto-verify error: ' . $e->getMessage());
        return false;
    }
}

function getStatusMessage($status) {
    switch ($status) {
        case 'paid':
            return 'Thanh toán thành công';
        case 'pending':
            return 'Đang chờ thanh toán';
        case 'pending_verification':
            return 'Đang xác nhận thanh toán';
        case 'failed':
            return 'Thanh toán thất bại';
        case 'cancelled':
            return 'Đã hủy';
        case 'refunded':
            return 'Đã hoàn tiền';
        default:
            return 'Chưa thanh toán';
    }
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($conn, $bookingId, $transactionCode) {
    try {
        // Load EmailService if not already loaded
        require_once __DIR__ . '/../../core/EmailService.php';
        
        // Get full booking details
        $stmt = $conn->prepare("
            SELECT 
                b.*,
                t.departure_time,
                t.arrival_time,
                r.origin,
                r.destination,
                r.route_name,
                p.name as partner_name,
                v.vehicle_type,
                tk.passenger_name,
                tk.passenger_email,
                tk.passenger_phone
            FROM bookings b
            LEFT JOIN trips t ON b.trip_id = t.trip_id
            LEFT JOIN routes r ON t.route_id = r.route_id
            LEFT JOIN partners p ON t.partner_id = p.partner_id
            LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id
            LEFT JOIN tickets tk ON b.booking_id = tk.booking_id
            WHERE b.booking_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        if (!$booking) {
            error_log("Cannot send email: Booking not found - ID: {$bookingId}");
            return false;
        }
        
        $email = $booking['passenger_email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Cannot send email: Invalid email for booking {$booking['booking_code']}");
            return false;
        }
        
        // Get all seat numbers
        $stmt = $conn->prepare("SELECT seat_number FROM tickets WHERE booking_id = ? ORDER BY seat_number");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $seats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $seatNumbers = array_column($seats, 'seat_number');
        
        // Prepare email content
        $passengerName = $booking['passenger_name'] ?? 'Quý khách';
        $route = ($booking['origin'] ?? '') . ' → ' . ($booking['destination'] ?? '');
        $departureTime = date('H:i - d/m/Y', strtotime($booking['departure_time']));
        $seatsStr = implode(', ', $seatNumbers);
        $totalPrice = number_format($booking['final_price']) . 'đ';
        
        $tripDetails = [
            'booking_id' => $bookingId,
            'route' => $route,
            'departure_time' => $departureTime,
            'seats' => $seatsStr,
            'total_price' => $totalPrice,
            'partner_name' => $booking['partner_name'] ?? '',
            'vehicle_type' => $booking['vehicle_type'] ?? '',
            'transaction_code' => $transactionCode
        ];
        
        // Send email
        $sent = EmailService::sendPaymentConfirmation(
            $email,
            $passengerName,
            $booking['booking_code'],
            $tripDetails
        );
        
        if ($sent) {
            error_log("✅ Payment confirmation email sent to {$email} for booking {$booking['booking_code']}");
        } else {
            error_log("❌ Failed to send payment confirmation email to {$email}");
        }
        
        return $sent;
        
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

