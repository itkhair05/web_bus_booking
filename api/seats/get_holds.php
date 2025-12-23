<?php
/**
 * Get Seat Holds API
 * Lấy danh sách ghế đang được giữ cho một chuyến xe
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';

$tripId = intval($_GET['trip_id'] ?? 0);

if (empty($tripId)) {
    jsonError('Thiếu thông tin trip_id', 'INVALID_INPUT', 400);
}

try {
    // First, release expired holds
    $releaseStmt = $conn->prepare("
        UPDATE seat_holds
        SET status = 'expired', updated_at = NOW()
        WHERE trip_id = ?
        AND status = 'holding'
        AND expired_at < NOW()
    ");
    $releaseStmt->bind_param("i", $tripId);
    $releaseStmt->execute();
    
    // Get active holds
    $stmt = $conn->prepare("
        SELECT hold_id, seat_number, user_id, session_id, expired_at, created_at
        FROM seat_holds
        WHERE trip_id = ?
        AND status = 'holding'
        AND expired_at > NOW()
        ORDER BY seat_number
    ");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $holds = [];
    while ($row = $result->fetch_assoc()) {
        $holds[] = [
            'hold_id' => $row['hold_id'],
            'seat_number' => $row['seat_number'],
            'expired_at' => $row['expired_at'],
            'expires_in_seconds' => max(0, strtotime($row['expired_at']) - time())
        ];
    }
    
    jsonResponse(true, [
        'trip_id' => $tripId,
        'holds' => $holds,
        'count' => count($holds)
    ]);
    
} catch (Exception $e) {
    logError('Get seat holds error', [
        'trip_id' => $tripId,
        'error' => $e->getMessage()
    ]);
    jsonError($e->getMessage(), 'GET_HOLDS_ERROR', 400);
}

