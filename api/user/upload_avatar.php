<?php
/**
 * Upload Avatar API
 * API upload ảnh đại diện
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Require login
if (!isLoggedIn()) {
    jsonError('Vui lòng đăng nhập', 'UNAUTHORIZED', 401);
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid method', 'METHOD_NOT_ALLOWED', 405);
}

// Verify CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError('Invalid CSRF token', 'CSRF_ERROR', 403);
}

// Check file
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Vui lòng chọn ảnh', 'NO_FILE', 400);
}

$file = $_FILES['avatar'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    jsonError('Chỉ chấp nhận file JPG, PNG', 'INVALID_TYPE', 400);
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    jsonError('Ảnh không được vượt quá 2MB', 'FILE_TOO_LARGE', 400);
}

// Create upload directory
$uploadDir = '../../uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . getCurrentUserId() . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;
$dbPath = 'uploads/avatars/' . $filename;

// Get old avatar to delete
$userId = getCurrentUserId();
$stmt = $conn->prepare("SELECT avatar FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$oldAvatar = $result['avatar'] ?? '';

// Upload file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    jsonError('Không thể upload ảnh', 'UPLOAD_FAILED', 500);
}

// Update database
$stmt = $conn->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE user_id = ?");
$stmt->bind_param("si", $dbPath, $userId);

if ($stmt->execute()) {
    // Delete old avatar
    if (!empty($oldAvatar) && file_exists('../../' . $oldAvatar)) {
        @unlink('../../' . $oldAvatar);
    }
    
    jsonResponse(true, [
        'avatar_url' => appUrl($dbPath)
    ], 'Cập nhật ảnh đại diện thành công');
} else {
    // Rollback: delete uploaded file
    @unlink($uploadPath);
    jsonError('Không thể cập nhật ảnh', 'UPDATE_FAILED', 500);
}

