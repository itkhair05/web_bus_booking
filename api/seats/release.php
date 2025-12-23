<?php
/**
 * Release Seat Hold API
 * Giải phóng ghế đang được giữ
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
$holdId = intval($input['hold_id'] ?? 0);

if (empty($tripId) || empty($seatNumber)) {
    jsonError('Thiếu thông tin trip_id hoặc seat_number', 'INVALID_INPUT', 400);
}

// Get user info
$userId = isLoggedIn() ? getCurrentUserId() : null;
$sessionId = session_id();

try {
    // Build WHERE clause
    $whereClause = "trip_id = ? AND seat_number = ? AND status = 'holding'";
    $params = [$tripId, $seatNumber];
    $types = "is";
    
    // If hold_id provided, use it
    if ($holdId > 0) {
        $whereClause .= " AND hold_id = ?";
        $params[] = $holdId;
        $types .= "i";
    }
    
    // Verify ownership before release
    if ($userId) {
        $whereClause .= " AND user_id = ?";
        $params[] = $userId;
        $types .= "i";
    } else {
        $whereClause .= " AND session_id = ?";
        $params[] = $sessionId;
        $types .= "s";
    }
    
    // Release hold
    $stmt = $conn->prepare("
        UPDATE seat_holds
        SET status = 'released',
            updated_at = NOW()
        WHERE {$whereClause}
    ");
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        jsonError('Không tìm thấy ghế đang được giữ hoặc bạn không có quyền giải phóng', 'HOLD_NOT_FOUND', 404);
    }
    
    jsonResponse(true, [
        'trip_id' => $tripId,
        'seat_number' => $seatNumber,
        'released' => true
    ], 'Đã giải phóng ghế thành công');
    
} catch (Exception $e) {
    logError('Release seat hold error', [
        'trip_id' => $tripId,
        'seat_number' => $seatNumber,
        'error' => $e->getMessage()
    ]);
    jsonError($e->getMessage(), 'RELEASE_SEAT_ERROR', 400);
}

