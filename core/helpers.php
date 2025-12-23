<?php
/**
 * Helper Functions
 * Các hàm tiện ích dùng chung trong ứng dụng
 */

// Load constants for APP_URL, CSS_URL, IMG_URL, etc.
require_once __DIR__ . '/../config/constants.php';

/**
 * Trả về JSON response chuẩn
 */
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Trả về error response
 */
function jsonError($message, $errorCode = 'ERROR', $httpCode = 400) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $errorCode
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone (Vietnam)
 */
function validatePhone($phone) {
    return preg_match('/^(0|\+84)[0-9]{9}$/', $phone);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate required fields
 */
function validateRequired($fields, $data) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Generate booking code
 */
function generateBookingCode() {
    return 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate ticket code
 */
function generateTicketCode($bookingId, $seatNumber) {
    return 'TK' . $bookingId . $seatNumber . strtoupper(substr(uniqid(), -4));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Get current timestamp
 */
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Check if date is in future
 */
function isFutureDate($date) {
    return strtotime($date) > time();
}

/**
 * Format price (VND)
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

/**
 * Calculate discount
 */
function calculateDiscount($amount, $discountType, $discountValue, $maxDiscount = null) {
    if ($discountType === 'percentage') {
        $discount = ($amount * $discountValue) / 100;
        if ($maxDiscount && $discount > $maxDiscount) {
            $discount = $maxDiscount;
        }
    } else {
        $discount = $discountValue;
    }
    return min($discount, $amount);
}

/**
 * Get flash message and clear it
 */
function getFlashMessage($key) {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * Set flash message
 */
function setFlashMessage($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

/**
 * Get app URL
 */
function appUrl($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Get partner logo URL with fallback
 * Kiểm tra file tồn tại, nếu không có thì dùng logo mặc định local
 * Hỗ trợ cả đường dẫn cũ (uploads/partners/logos/) và mới (uploads/partners/)
 */
function getPartnerLogoUrl($logoPath = null) {
    // Dùng logo mặc định local thay vì placeholder online
    $defaultLogo = appUrl('assets/images/bus-default.png');
    
    if (empty($logoPath)) {
        return $defaultLogo;
    }
    
    // Nếu là URL tuyệt đối (http/https), dùng trực tiếp
    if (preg_match('/^https?:\/\//i', $logoPath)) {
        return $logoPath;
    }
    
    // Chuẩn hóa đường dẫn (loại bỏ / đầu tiên nếu có, thay \ thành /)
    $cleanPath = str_replace('\\', '/', ltrim($logoPath, '/'));
    
    // Danh sách các đường dẫn cần kiểm tra
    $pathsToCheck = [
        $cleanPath,  // Đường dẫn gốc từ database
    ];
    
    // Nếu đường dẫn có subfolder 'logos', thử cả đường dẫn không có subfolder
    if (strpos($cleanPath, 'uploads/partners/logos/') === 0) {
        $filename = basename($cleanPath);
        $pathsToCheck[] = 'uploads/partners/' . $filename;
    }
    // Nếu đường dẫn không có subfolder, thử cả đường dẫn có subfolder
    elseif (strpos($cleanPath, 'uploads/partners/') === 0 && strpos($cleanPath, 'uploads/partners/logos/') !== 0) {
        $filename = basename($cleanPath);
        $pathsToCheck[] = 'uploads/partners/logos/' . $filename;
    }
    // Nếu chỉ có tên file, thử cả 2 đường dẫn
    elseif (strpos($cleanPath, 'uploads/') !== 0) {
        $pathsToCheck[] = 'uploads/partners/' . $cleanPath;
        $pathsToCheck[] = 'uploads/partners/logos/' . $cleanPath;
    }
    
    // Kiểm tra từng đường dẫn
    foreach ($pathsToCheck as $path) {
        $filePath = BASE_PATH . '/' . $path;
        if (file_exists($filePath)) {
            return appUrl($path);
        }
    }
    
    // File không tồn tại, dùng logo mặc định
    return $defaultLogo;
}

/**
 * Get asset URL
 */
function assetUrl($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Include view file
 */
function view($viewPath, $data = []) {
    extract($data);
    $filePath = BASE_PATH . '/user/' . $viewPath . '.php';
    if (file_exists($filePath)) {
        include $filePath;
    } else {
        die("View not found: $viewPath");
    }
}

/**
 * Escape HTML output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Calculate trip duration in hours
 */
function calculateDuration($departureTime, $arrivalTime) {
    $start = strtotime($departureTime);
    $end = strtotime($arrivalTime);
    $duration = ($end - $start) / 3600;
    
    $hours = floor($duration);
    $minutes = ($duration - $hours) * 60;
    
    if ($hours > 0 && $minutes > 0) {
        return $hours . 'h' . round($minutes) . 'p';
    } elseif ($hours > 0) {
        return $hours . 'h';
    } else {
        return round($minutes) . 'p';
    }
}

/**
 * Get day of week in Vietnamese
 */
function getVietnameseDayOfWeek($date) {
    $days = [
        'Sunday' => 'Chủ nhật',
        'Monday' => 'Thứ 2',
        'Tuesday' => 'Thứ 3',
        'Wednesday' => 'Thứ 4',
        'Thursday' => 'Thứ 5',
        'Friday' => 'Thứ 6',
        'Saturday' => 'Thứ 7'
    ];
    
    $dayName = date('l', strtotime($date));
    return $days[$dayName] ?? '';
}

/**
 * Log user activity
 */
function logActivity($conn, $userId, $action, $tableName, $recordId, $details = null) {
    try {
        $ipAddress = getClientIP();
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $userId, $action, $tableName, $recordId, $ipAddress, $details);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log('Log activity error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create notification for user
 */
function createNotification($conn, $userId, $title, $message, $type = 'info', $relatedId = null) {
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $userId, $title, $message, $type, $relatedId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log('Create notification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param array $context Additional context data
 * @return void
 */
function logError($message, $context = []) {
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    
    // Don't log sensitive data (passwords, tokens, secrets)
    $sanitizedMessage = preg_replace('/(password|token|secret|key|api_key|access_token)\s*[:=]\s*[^\s,}]+/i', '$1: [REDACTED]', $message);
    
    $logEntry = "[{$timestamp}] {$sanitizedMessage}{$contextStr}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Log info message
 * 
 * @param string $message Info message
 * @param array $context Additional context data
 * @return void
 */
function logInfo($message, $context = []) {
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/info.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    
    $logEntry = "[{$timestamp}] {$message}{$contextStr}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Log warning message
 * 
 * @param string $message Warning message
 * @param array $context Additional context data
 * @return void
 */
function logWarning($message, $context = []) {
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/warning.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    
    $logEntry = "[{$timestamp}] {$message}{$contextStr}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Render Trip Card Component
 * Template cố định cho hiển thị chuyến xe
 * 
 * @param array $trip - Thông tin chuyến xe
 * @return string HTML
 */
function renderTripCard($trip) {
    // Extract data
    $tripId = $trip['trip_id'];
    $partnerName = e($trip['partner_name']);
    $busType = e($trip['bus_type']);
    $seatCount = $trip['total_seats'];
    $rating = number_format($trip['rating'] ?? 4.5, 1);
    $reviewCount = $trip['review_count'] ?? 0;
    
    $departureTime = date('H:i', strtotime($trip['departure_time']));
    $arrivalTime = date('H:i', strtotime($trip['arrival_time']));
    $departureStation = e($trip['departure_station']);
    $arrivalStation = e($trip['arrival_station']);
    $duration = $trip['duration'];
    
    $originalPrice = number_format($trip['price_per_seat']);
    $currentPrice = number_format($trip['price_per_seat']);
    $discount = $trip['discount'] ?? 0;
    
    // Calculate discounted price
    if ($discount > 0) {
        $discountedPrice = $trip['price_per_seat'] * (1 - $discount / 100);
        $currentPrice = number_format($discountedPrice);
    }
    
    // Amenities
    $amenities = [];
    if (!empty($trip['has_wifi'])) $amenities[] = '<span class="amenity-badge"><i class="fas fa-wifi"></i> WIFI</span>';
    if (!empty($trip['has_ac'])) $amenities[] = '<span class="amenity-badge"><i class="fas fa-snowflake"></i> Điều hòa</span>';
    if (!empty($trip['has_wc'])) $amenities[] = '<span class="amenity-badge"><i class="fas fa-restroom"></i> WC</span>';
    
    // Warnings
    $warnings = [];
    if (!empty($trip['requires_full_payment'])) {
        $warnings[] = '<span class="warning-badge"><i class="fas fa-exclamation-circle"></i> KHÔNG CẦN THANH TOÁN TRƯỚC</span>';
    }
    if (!empty($trip['has_rest_stops'])) {
        $warnings[] = '<span class="info-badge"><i class="fas fa-clock"></i> THEO DÕI HÀNH TRÌNH</span>';
    }
    
    // Flash sale badge
    $flashSaleBadge = '';
    if ($discount >= 20) {
        $flashSaleBadge = '<div class="flash-sale-badge"><i class="fas fa-bolt"></i> FLASH SALE ' . $discount . '%</div>';
    }
    
    // Build HTML
    ob_start();
    ?>
    
    <!-- Trip Card Template -->
    <div class="trip-card" data-trip-id="<?php echo $tripId; ?>">
        <?php if ($flashSaleBadge): ?>
            <?php echo $flashSaleBadge; ?>
        <?php endif; ?>
        
        <!-- Header: Partner Info -->
        <div class="trip-header">
            <div class="partner-info">
                <img src="<?php echo ASSETS_URL; ?>/images/bus-default.png" alt="<?php echo $partnerName; ?>" class="partner-logo">
                <div class="partner-details">
                    <h3 class="partner-name">
                        <?php echo $partnerName; ?>
                        <?php if ($rating >= 4.0): ?>
                            <span class="rating-badge">
                                <i class="fas fa-star"></i> <?php echo $rating; ?> (<?php echo $reviewCount; ?>)
                            </span>
                        <?php endif; ?>
                    </h3>
                    <p class="bus-type"><?php echo $busType; ?> • <?php echo $seatCount; ?> phòng</p>
                </div>
            </div>
            <div class="price-info">
                <?php if ($discount > 0): ?>
                    <span class="original-price"><?php echo $originalPrice; ?>đ</span>
                    <span class="discount-badge">-<?php echo $discount; ?>%</span>
                <?php endif; ?>
                <span class="current-price"><?php echo $currentPrice; ?>đ</span>
                <?php if ($discount > 0): ?>
                    <p class="seats-left">Chỉ còn <?php echo $trip['available_seats'] ?? $seatCount; ?> chỗ trống</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Body: Trip Timeline -->
        <div class="trip-timeline">
            <div class="timeline-point">
                <span class="time"><?php echo $departureTime; ?></span>
                <span class="station"><?php echo $departureStation; ?></span>
                <span class="station-code">(<?php echo $trip['start_point']; ?>)</span>
            </div>
            
            <div class="timeline-line">
                <span class="duration"><?php echo $duration; ?></span>
            </div>
            
            <div class="timeline-point">
                <span class="time"><?php echo $arrivalTime; ?></span>
                <span class="station"><?php echo $arrivalStation; ?></span>
                <span class="station-code">(<?php echo $trip['end_point']; ?>)</span>
            </div>
        </div>
        
        <!-- Footer: Amenities & Actions -->
        <div class="trip-footer">
            <div class="amenities">
                <?php echo implode(' ', $amenities); ?>
                <?php echo implode(' ', $warnings); ?>
            </div>
            
            <div class="trip-actions">
                <button class="btn-details" onclick="toggleTripDetails(<?php echo $tripId; ?>)">
                    Thông tin chi tiết <i class="fas fa-chevron-down"></i>
                </button>
                <a href="<?php echo appUrl('user/booking/select_seat.php?trip_id=' . $tripId); ?>" class="btn-book">
                    Chọn chuyến
                </a>
            </div>
        </div>
        
        <!-- Trip Details (Collapsible) -->
        <div class="trip-details-collapse" id="trip-details-<?php echo $tripId; ?>" style="display: none;">
            <div class="details-content">
                <p><strong>Chặng đường:</strong> <?php echo $trip['route_name'] ?? $trip['start_point'] . ' - ' . $trip['end_point']; ?></p>
                <?php if (!empty($trip['pickup_points'])): ?>
                    <p><strong>Điểm đón:</strong> <?php echo e($trip['pickup_points']); ?></p>
                <?php endif; ?>
                <?php if (!empty($trip['dropoff_points'])): ?>
                    <p><strong>Điểm trả:</strong> <?php echo e($trip['dropoff_points']); ?></p>
                <?php endif; ?>
                <?php if (!empty($trip['notes'])): ?>
                    <p><strong>Ghi chú:</strong> <?php echo e($trip['notes']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

