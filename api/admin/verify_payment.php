<?php
/**
 * Admin Manual Payment Verification API
 * API cho admin xác nhận thanh toán thủ công
 */

header('Content-Type: application/json');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/PromotionService.php';

// Check admin authentication
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Admin only'
    ]);
    exit;
}

// Get request data
$bookingId = intval($_POST['booking_id'] ?? $_GET['booking_id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? 'verify';
$transactionCode = trim($_POST['transaction_code'] ?? '');
$note = trim($_POST['note'] ?? '');

if (empty($bookingId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing booking_id'
    ]);
    exit;
}

// Get booking details
$stmt = $conn->prepare("
    SELECT 
        b.*,
        u.name as user_name,
        u.email as user_email
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found'
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $adminId = getCurrentUserId();
    $paymentAmount = (float)$booking['final_price'];
    
    if ($action === 'verify' || $action === 'confirm') {
        // Verify/Confirm payment
        
        // Generate transaction code if not provided
        if (empty($transactionCode)) {
            $transactionCode = 'ADMIN_' . strtoupper(substr(md5($bookingId . time()), 0, 8));
        }
        
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
        $paymentNote = "Xác nhận bởi Admin ID: {$adminId}";
        if (!empty($note)) {
            $paymentNote .= " - " . $note;
        }
        
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'success',
                transaction_code = ?,
                paid_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("si", $transactionCode, $bookingId);
        $stmt->execute();
        
        // If no payment record exists, create one
        if ($conn->affected_rows === 0) {
            $stmt = $conn->prepare("
                INSERT INTO payments (booking_id, method, amount, transaction_code, status, paid_at, created_at)
                VALUES (?, 'bank_transfer', ?, ?, 'success', NOW(), NOW())
            ");
            $stmt->bind_param("ids", $bookingId, $paymentAmount, $transactionCode);
            $stmt->execute();
        }
        
        // Create notification for user
        if (!empty($booking['user_id'])) {
            $notifTitle = 'Thanh toán đã được xác nhận';
            $notifMessage = "Đơn đặt vé {$booking['booking_code']} đã được xác nhận thanh toán thành công. Mã GD: {$transactionCode}";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
                VALUES (?, ?, ?, 'payment', ?, NOW())
            ");
            $stmt->bind_param("issi", $booking['user_id'], $notifTitle, $notifMessage, $bookingId);
            $stmt->execute();
        }
        
        // Log admin action
        error_log("✅ [ADMIN] Payment verified for booking {$booking['booking_code']} by Admin #{$adminId}, TXN: {$transactionCode}");
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thanh toán đã được xác nhận thành công',
            'status' => 'paid',
            'transaction_code' => $transactionCode,
            'booking_code' => $booking['booking_code']
        ]);
        
    } elseif ($action === 'reject' || $action === 'fail') {
        // Reject payment
        
        // Update booking
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'failed',
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Update payment record
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'failed'
            WHERE booking_id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Create notification for user
        if (!empty($booking['user_id'])) {
            $notifTitle = 'Thanh toán không thành công';
            $notifMessage = "Đơn đặt vé {$booking['booking_code']} không xác nhận được thanh toán.";
            if (!empty($note)) {
                $notifMessage .= " Lý do: " . $note;
            }
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
                VALUES (?, ?, ?, 'payment', ?, NOW())
            ");
            $stmt->bind_param("issi", $booking['user_id'], $notifTitle, $notifMessage, $bookingId);
            $stmt->execute();
        }
        
        // Log admin action
        error_log("❌ [ADMIN] Payment rejected for booking {$booking['booking_code']} by Admin #{$adminId}. Note: {$note}");
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thanh toán đã bị từ chối',
            'status' => 'failed',
            'booking_code' => $booking['booking_code']
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Admin verify payment error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

