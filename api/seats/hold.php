<?php
/**
 * Hold Seat API
 * Giữ ghế tạm thời khi user chọn ghế (15 phút)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

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

$tripId = intval($input['trip_id'] ?? 0);
$seatNumber = trim($input['seat_number'] ?? '');
$holdDurationMinutes = intval($input['hold_duration'] ?? 15); // Default 15 minutes

if (empty($tripId) || empty($seatNumber)) {
    jsonError('Thiếu thông tin trip_id hoặc seat_number', 'INVALID_INPUT', 400);
}

// Validate hold duration (max 30 minutes)
if ($holdDurationMinutes < 1 || $holdDurationMinutes > 30) {
    $holdDurationMinutes = 15;
}

// Get user info
$userId = isLoggedIn() ? getCurrentUserId() : null;
$sessionId = session_id();

// Start transaction
$conn->begin_transaction();

try {
    // First, release expired holds for this trip/seat
    $releaseStmt = $conn->prepare("
        UPDATE seat_holds
        SET status = 'expired', updated_at = NOW()
        WHERE trip_id = ? 
        AND seat_number = ?
        AND status = 'holding'
        AND expired_at < NOW()
    ");
    $releaseStmt->bind_param("is", $tripId, $seatNumber);
    $releaseStmt->execute();
    
    // Check if seat is already booked (confirmed tickets)
    $checkBookedStmt = $conn->prepare("
        SELECT COUNT(*) as booked_count
        FROM tickets tk
        INNER JOIN bookings b ON tk.booking_id = b.booking_id
        WHERE b.trip_id = ?
        AND tk.seat_number = ?
        AND b.status IN ('confirmed', 'pending')
        -- Ghế đã thuộc về một vé bất kỳ (trừ vé đã hủy) thì không cho giữ nữa
        AND tk.status <> 'cancelled'
    ");
    $checkBookedStmt->bind_param("is", $tripId, $seatNumber);
    $checkBookedStmt->execute();
    $bookedResult = $checkBookedStmt->get_result();
    $bookedRow = $bookedResult->fetch_assoc();
    
    if ($bookedRow['booked_count'] > 0) {
        throw new Exception('Ghế này đã được đặt');
    }
    
    // Check if seat is already being held by someone else
    $checkHoldStmt = $conn->prepare("
        SELECT hold_id, user_id, session_id, expired_at
        FROM seat_holds
        WHERE trip_id = ?
        AND seat_number = ?
        AND status = 'holding'
        AND expired_at > NOW()
        FOR UPDATE
    ");
    $checkHoldStmt->bind_param("is", $tripId, $seatNumber);
    $checkHoldStmt->execute();
    $holdResult = $checkHoldStmt->get_result();
    $existingHold = $holdResult->fetch_assoc();
    
    if ($existingHold) {
        // Check if it's the same user/session trying to extend hold
        $isSameUser = ($userId && $existingHold['user_id'] == $userId) || 
                      (!$userId && $existingHold['session_id'] == $sessionId);
        
        if (!$isSameUser) {
            throw new Exception('Ghế này đang được người khác giữ');
        }
        
        // Extend hold time
        $expiredAt = date('Y-m-d H:i:s', strtotime("+{$holdDurationMinutes} minutes"));
        $updateStmt = $conn->prepare("
            UPDATE seat_holds
            SET expired_at = ?,
                updated_at = NOW()
            WHERE hold_id = ?
        ");
        $updateStmt->bind_param("si", $expiredAt, $existingHold['hold_id']);
        $updateStmt->execute();
        
        $holdId = $existingHold['hold_id'];
    } else {
        // Create new hold
        $expiredAt = date('Y-m-d H:i:s', strtotime("+{$holdDurationMinutes} minutes"));
        
        $insertStmt = $conn->prepare("
            INSERT INTO seat_holds (trip_id, seat_number, user_id, session_id, status, expired_at)
            VALUES (?, ?, ?, ?, 'holding', ?)
        ");
        $insertStmt->bind_param("isiss", $tripId, $seatNumber, $userId, $sessionId, $expiredAt);
        $insertStmt->execute();
        
        $holdId = $conn->insert_id;
    }
    
    // Commit transaction
    $conn->commit();
    
    jsonResponse(true, [
        'hold_id' => $holdId,
        'trip_id' => $tripId,
        'seat_number' => $seatNumber,
        'expired_at' => $expiredAt,
        'hold_duration_minutes' => $holdDurationMinutes
    ], 'Đã giữ ghế thành công');
    
} catch (Exception $e) {
    $conn->rollback();
    logError('Hold seat error', [
        'trip_id' => $tripId,
        'seat_number' => $seatNumber,
        'error' => $e->getMessage()
    ]);
    jsonError($e->getMessage(), 'HOLD_SEAT_ERROR', 400);
}

