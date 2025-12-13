<?php
/**
 * Seat Selection Page
 * Chọn ghế và xem sơ đồ ghế
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Get trip ID
$tripId = intval($_GET['trip_id'] ?? 0);

if (empty($tripId)) {
    redirect(appUrl());
}

// Helper function to check if column exists
function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    static $cache = [];
    $key = "$table.$column";
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $cache[$key] = ($result && $result->num_rows > 0);
}

// Get trip details with dynamic column handling
$routeOriginCol = tableHasColumn($conn, 'routes', 'origin') ? 'r.origin' : 
                  (tableHasColumn($conn, 'routes', 'start_point') ? 'r.start_point' : 'r.start_point');
$routeDestCol = tableHasColumn($conn, 'routes', 'destination') ? 'r.destination' : 
                (tableHasColumn($conn, 'routes', 'end_point') ? 'r.end_point' : 'r.end_point');

$partnerNameCol = tableHasColumn($conn, 'partners', 'name') ? 'p.name' : 
                  (tableHasColumn($conn, 'partners', 'company_name') ? 'p.company_name' : 'p.name');
$vehicleTypeCol = tableHasColumn($conn, 'vehicles', 'vehicle_type') ? 'v.vehicle_type' : 
                  (tableHasColumn($conn, 'vehicles', 'type') ? 'v.type' : "''");
$partnerRatingCol = tableHasColumn($conn, 'partners', 'rating') ? 'COALESCE(p.rating, 0) as rating' : '0 as rating';

$stmt = $conn->prepare("
    SELECT 
        t.*,
        $routeOriginCol as origin,
        $routeDestCol as destination,
        $partnerNameCol as partner_name,
        $vehicleTypeCol as vehicle_type,
        v.total_seats,
        p.logo_url,
        $partnerRatingCol,
        p.partner_id
    FROM trips t
    JOIN routes r ON t.route_id = r.route_id
    JOIN partners p ON t.partner_id = p.partner_id
    JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    WHERE t.trip_id = ?
");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    redirect(appUrl());
}

// Get booked seats for this trip
$bookedSeatsQuery = "
    SELECT DISTINCT tk.seat_number
    FROM tickets tk
    INNER JOIN bookings b ON tk.booking_id = b.booking_id
    WHERE b.trip_id = ? 
    AND b.status IN ('confirmed', 'pending')
    AND tk.status = 'active'
";
$bookedStmt = $conn->prepare($bookedSeatsQuery);
$bookedStmt->bind_param("i", $tripId);
$bookedStmt->execute();
$bookedResult = $bookedStmt->get_result();
$bookedSeats = [];
while ($row = $bookedResult->fetch_assoc()) {
    $bookedSeats[] = $row['seat_number'];
}

// Format dates and times
$departureTime = date('H:i', strtotime($trip['departure_time']));
$departureDate = date('d/m', strtotime($trip['departure_time']));
$arrivalTime = date('H:i', strtotime($trip['arrival_time']));
$arrivalDate = date('d/m', strtotime($trip['arrival_time']));

// Calculate duration
$departureTimestamp = strtotime($trip['departure_time']);
$arrivalTimestamp = strtotime($trip['arrival_time']);
$durationSeconds = $arrivalTimestamp - $departureTimestamp;
$hours = floor($durationSeconds / 3600);
$minutes = floor(($durationSeconds % 3600) / 60);
$durationText = $hours . 'h' . $minutes . 'm';

// Get partner logo URL with file existence check
$logoUrl = getPartnerLogoUrl($trip['logo_url'] ?? null);

// Get rating
$rating = $trip['rating'] ?? null;
$ratingDisplay = $rating ? number_format($rating, 1) : 'N/A';
$ratingCount = 0; // TODO: Get actual rating count from reviews table if exists

// Get price
$price = $trip['price'] ?? 0;
$priceFormatted = number_format($price, 0, ',', '.') . 'đ';

// Get pickup and dropoff points
$pickupPoints = [];
$dropoffPoints = [];

// Default pickup point - origin station
$pickupPoints[] = [
    'schedule_id' => 'pickup_1',
    'departure_time' => $trip['departure_time'],
    'departure_station' => $trip['origin'] . ' - Bến xe chính'
];

// Additional pickup points
$additionalPickups = [
    ['time' => date('H:i', strtotime($trip['departure_time'] . ' -30 minutes')), 'station' => $trip['origin'] . ' - Điểm đón trung tâm'],
    ['time' => date('H:i', strtotime($trip['departure_time'] . ' -15 minutes')), 'station' => $trip['origin'] . ' - Điểm đón phụ']
];

foreach ($additionalPickups as $idx => $pickup) {
    $pickupTime = date('Y-m-d H:i:s', strtotime($trip['departure_time'] . ' -' . (30 - $idx * 15) . ' minutes'));
    $pickupPoints[] = [
        'schedule_id' => 'pickup_' . ($idx + 2),
        'departure_time' => $pickupTime,
        'departure_station' => $pickup['station']
    ];
}

// Default dropoff point - destination station
$dropoffPoints[] = [
    'schedule_id' => 'dropoff_1',
    'arrival_time' => $trip['arrival_time'],
    'arrival_station' => $trip['destination'] . ' - Bến xe chính'
];

// Additional dropoff points
$additionalDropoffs = [
    ['time' => date('H:i', strtotime($trip['arrival_time'] . ' +15 minutes')), 'station' => $trip['destination'] . ' - Điểm trả trung tâm'],
    ['time' => date('H:i', strtotime($trip['arrival_time'] . ' +30 minutes')), 'station' => $trip['destination'] . ' - Điểm trả phụ']
];

foreach ($additionalDropoffs as $idx => $dropoff) {
    $dropoffTime = date('Y-m-d H:i:s', strtotime($trip['arrival_time'] . ' +' . (($idx + 1) * 15) . ' minutes'));
    $dropoffPoints[] = [
        'schedule_id' => 'dropoff_' . ($idx + 2),
        'arrival_time' => $dropoffTime,
        'arrival_station' => $dropoff['station']
    ];
}

$pageTitle = 'Chọn ghế - BusBooking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
.seat-selection-page {
        background: #F3F4F6;
        min-height: 100vh;
        padding: 20px 0;
    }
    
    .seat-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Main Content */
    .main-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    /* Trip Info Card */
    .trip-info-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .trip-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #E5E7EB;
    }
    
    .company-info {
        display: flex;
        gap: 16px;
    }
    
    .company-logo {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .company-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .company-details h2 {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
    }
    
    .company-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #666;
    }
    
    .rating-badge {
        background: #FFA500;
        color: #fff;
        padding: 4px 10px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .vehicle-badge {
        background: #E8F4F8;
        color: #0D5F2B;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .trip-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-bottom: 20px;
    }
    
    .detail-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .detail-icon {
        width: 40px;
        height: 40px;
        background: #FFF5EB;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF6B35;
        font-size: 18px;
    }
    
    .detail-content label {
        font-size: 12px;
        color: #999;
        display: block;
        margin-bottom: 4px;
    }
    
    .detail-content strong {
        font-size: 16px;
        color: #333;
    }
    
    .badges-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .badge-flash {
        background: #EEF2FF;
        color: #4F46E5;
    }
    
    .badge-pickup {
        background: #DCFCE7;
        color: #16A34A;
    }
    
    .badge-policy {
        background: #FEE2E2;
        color: #DC2626;
    }
    
    /* Promo Cards */
    .promo-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 20px;
    }
    
    .promo-card {
        background: linear-gradient(135deg, #4F46E5, #7C3AED);
        color: #fff;
        padding: 16px;
        border-radius: 8px;
        position: relative;
        overflow: hidden;
    }
    
    .promo-card::before {
        content: '⚡';
        position: absolute;
        right: 10px;
        top: 10px;
        font-size: 32px;
        opacity: 0.3;
    }
    
    .promo-title {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .promo-desc {
        font-size: 12px;
        opacity: 0.9;
        margin-bottom: 8px;
    }
    
    .promo-validity {
        font-size: 11px;
        opacity: 0.8;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Seat Map Section */
    .seat-map-section {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .seat-map-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .seat-map-title {
        font-size: 18px;
        font-weight: 700;
        color: #333;
    }
    
    .floor-tabs {
        display: flex;
        gap: 8px;
    }
    
    .floor-tab {
        padding: 8px 20px;
        border: 2px solid #E5E7EB;
        background: #fff;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .floor-tab.active {
        background: #FF6B35;
        color: #fff;
        border-color: #FF6B35;
    }
    
    /* Legend */
    .seat-legend {
        display: flex;
        gap: 24px;
        margin-bottom: 24px;
        padding: 16px;
        background: #F9FAFB;
        border-radius: 8px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    
    .legend-seat {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
    }
    
    .legend-seat.available {
        background: #DCFCE7;
        border: 2px solid #16A34A;
        color: #16A34A;
    }
    
    .legend-seat.selected {
        background: #FF6B35;
        border: 2px solid #E55A2B;
        color: #fff;
    }
    
    .legend-seat.booked {
        background: #F3F4F6;
        border: 2px solid #9CA3AF;
        color: #9CA3AF;
    }
    
    /* Seat Map */
    .bus-layout {
        max-width: 600px;
        margin: 0 auto;
        padding: 24px;
        background: linear-gradient(180deg, #FFF9F5 0%, #FFFFFF 100%);
        border: 2px solid #E5E7EB;
        border-radius: 16px;
        position: relative;
    }
    
    .driver-section {
        text-align: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px dashed #E5E7EB;
    }
    
    .driver-icon {
        width: 48px;
        height: 48px;
        background: #4F46E5;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 24px;
        margin-bottom: 8px;
    }
    
    .driver-text {
        font-size: 12px;
        color: #666;
        font-weight: 600;
    }
    
    .seats-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .seat-row {
        display: flex;
        justify-content: center;
        gap: 12px;
    }
    
    .seat {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid;
    }
    
    .seat.available {
        background: #DCFCE7;
        border-color: #16A34A;
        color: #16A34A;
    }
    
    .seat.available:hover {
        background: #BBF7D0;
        transform: scale(1.1);
    }
    
    .seat.selected {
        background: #FF6B35;
        border-color: #E55A2B;
        color: #fff;
        transform: scale(1.05);
    }
    
    .seat.booked {
        background: #F3F4F6;
        border-color: #9CA3AF;
        color: #9CA3AF;
        cursor: not-allowed;
    }
    
    .seat.aisle {
        background: transparent;
        border: none;
        cursor: default;
    }
    
    /* Summary Bar */
    .summary-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        padding: 20px;
        box-shadow: 0 -4px 16px rgba(0,0,0,0.1);
        z-index: 1000;
    }
    
    .summary-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .summary-left {
        display: flex;
        gap: 24px;
        align-items: center;
    }
    
    .selected-seats {
        font-size: 14px;
        color: #666;
    }
    
    .selected-seats strong {
        color: #FF6B35;
        font-size: 16px;
    }
    
    .total-price {
        font-size: 14px;
        color: #666;
    }
    
    .total-price strong {
        font-size: 24px;
        color: #FF6B35;
        margin-left: 8px;
    }
    
    .btn-continue {
        background: #FF6B35;
        color: #fff;
        padding: 14px 48px;
        border-radius: 8px;
        border: none;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-continue:hover {
        background: #E55A2B;
        transform: translateY(-2px);
    }
    
    .btn-continue:disabled {
        background: #D1D5DB;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Pickup/Dropoff Section - Hidden initially */
    .pickup-dropoff-section {
        display: none;
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-top: 24px;
    }
    
    .pickup-dropoff-section.active {
        display: block;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
    }
    
    .alert-box {
        background: #e8f5e9;
        border: 1px solid #a5d6a7;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .alert-box i {
        color: #2e7d32;
        font-size: 20px;
    }
    
    .alert-box p {
        margin: 0;
        color: #2e7d32;
        font-size: 14px;
    }
    
    .tabs-header {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .tab {
        flex: 1;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
        color: #757575;
        background: #fafafa;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }
    
    .tab.active {
        color: #1976d2;
        background: #fff;
        border-bottom-color: #1976d2;
    }
    
    .tab-content {
        display: none;
        padding: 30px;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 45px 12px 20px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .search-box i {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9e9e9e;
    }
    
    .point-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .point-item {
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .point-item:hover {
        border-color: #1976d2;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.15);
    }
    
    .point-item.selected {
        border-color: #1976d2;
        background: #e3f2fd;
    }
    
    .point-item input[type="radio"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #1976d2;
    }
    
    .point-info {
        flex: 1;
    }
    
    .point-time {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .point-time .date {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
        margin-left: 8px;
    }
    
    .point-address {
        font-size: 14px;
        color: #475569;
        line-height: 1.5;
    }
    
    .btn-map {
        background: #fff;
        border: 1px solid #1976d2;
        color: #1976d2;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }
    
    .btn-map:hover {
        background: #1976d2;
        color: #fff;
    }
    
    /* Responsive */
    @media (max-width: 968px) {
        .trip-details {
            grid-template-columns: 1fr;
        }
        
        .promo-cards {
            grid-template-columns: 1fr;
        }
        
        .summary-content {
            flex-direction: column;
            gap: 16px;
        }
    }
</style>

<div class="seat-selection-page">
    <div class="seat-container">
        <main class="main-content">
            <!-- Trip Info Card -->
            <div class="trip-info-card">
                <div class="trip-header">
                    <div class="company-info">
                        <div class="company-logo">
                            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($trip['partner_name']); ?>">
                        </div>
                        <div class="company-details">
                            <h2><?php echo htmlspecialchars($trip['partner_name']); ?></h2>
                            <?php if ($rating): ?>
                            <div class="company-rating">
                                <span class="rating-badge">
                                    <i class="fas fa-star"></i> <?php echo $ratingDisplay; ?><?php if ($ratingCount > 0): ?> (<?php echo $ratingCount; ?>)<?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div style="margin-top: 8px; font-size: 14px; color: #666;">
                                <?php echo htmlspecialchars($trip['vehicle_type'] ?: 'Xe tiêu chuẩn'); ?> <?php echo $trip['total_seats']; ?> chỗ
                            </div>
                        </div>
                    </div>
                    <div class="vehicle-badge"><?php echo $priceFormatted; ?></div>
                </div>
                
                <div class="trip-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="far fa-clock"></i>
                        </div>
                        <div class="detail-content">
                            <label>Khởi hành</label>
                            <strong><?php echo $departureTime; ?> - <?php echo htmlspecialchars($trip['origin']); ?></strong>
                            <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                <?php echo $durationText; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="far fa-clock"></i>
                        </div>
                        <div class="detail-content">
                            <label>Đến nơi</label>
                            <strong><?php echo $arrivalTime; ?> - <?php echo htmlspecialchars($trip['destination']); ?></strong>
                            <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                (<?php echo $arrivalDate; ?>)
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="detail-content">
                            <label>Thông tin</label>
                            <div style="display: flex; gap: 8px; margin-top: 4px;">
                                <span class="badge badge-flash">
                                    <i class="fas fa-bolt"></i> FLASH SALE 28.10
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="badges-row">
                    <span class="badge badge-pickup">
                        <i class="fas fa-bus"></i> TRẢ TẬN NƠI
                    </span>
                    <span class="badge badge-flash">
                        <i class="fas fa-tag"></i> THEO DÕI HÀNH TRÌNH XE
                    </span>
                    <span class="badge badge-policy">
                        <i class="fas fa-shield-alt"></i> KHÔNG CẦN THANH TOÁN TRƯỚC
                    </span>
                </div>
                
                <!-- Promo Cards - TODO: Load from promotions table if exists -->
                <?php
                // Get promotions for this partner/trip if promotions table exists
                $promotions = [];
                try {
                    $promoCheck = $conn->query("SHOW TABLES LIKE 'promotions'");
                    if ($promoCheck && $promoCheck->num_rows > 0) {
                        $promoQuery = "SELECT * FROM promotions WHERE partner_id = ? AND status = 'active' AND (start_date <= NOW() AND end_date >= NOW()) LIMIT 3";
                        $promoStmt = $conn->prepare($promoQuery);
                        $promoStmt->bind_param("i", $trip['partner_id']);
                        $promoStmt->execute();
                        $promoResult = $promoStmt->get_result();
                        while ($promo = $promoResult->fetch_assoc()) {
                            $promotions[] = $promo;
                        }
                    }
                } catch (Exception $e) {
                    // Promotions table doesn't exist, skip
                }
                
                if (!empty($promotions)):
                ?>
                <div class="promo-cards">
                    <?php foreach ($promotions as $promo): ?>
                    <div class="promo-card" style="background: linear-gradient(135deg, #4F46E5, #7C3AED);">
                        <div class="promo-title"><?php echo htmlspecialchars($promo['title'] ?? 'Khuyến mãi'); ?></div>
                        <div class="promo-desc"><?php echo htmlspecialchars($promo['description'] ?? ''); ?></div>
                        <div class="promo-validity">
                            <i class="far fa-clock"></i>
                            <span>Hiệu lực: <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Seat Map Section -->
            <div class="seat-map-section">
                <div class="seat-map-header">
                    <h3 class="seat-map-title">Chọn ghế</h3>
                    <div class="floor-tabs">
                        <div class="floor-tab active" style="cursor: default;">
                            Ghế số lẻ: tầng dưới · Ghế số chẵn: tầng trên
                        </div>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="legend-seat available">A1</div>
                        <span>Còn trống</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat selected">A2</div>
                        <span>Đang chọn</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat booked">A3</div>
                        <span>Đã đặt</span>
                    </div>
                </div>
                
                <!-- Bus Layout -->
                <div class="bus-layout">
                    <div class="driver-section">
                        <div class="driver-icon">
                            <i class="fas fa-steering-wheel"></i>
                        </div>
                        <div class="driver-text">Tài xế</div>
                    </div>
                    
                    <div class="seats-grid" id="seatsGrid">
                        <?php
                        // Generate seat layout with prefixes:
                        // - Trái: A (cột 1: A odd, cột 2: A even)
                        // - Phải: B (cột 1: B odd, cột 2: B even)
                        // Ghế lẻ: tầng dưới, ghế chẵn: tầng trên (hiển thị bằng số trong label)
                        $totalSeats = (int)$trip['total_seats'];
                        $seatsA = (int)ceil($totalSeats / 2); // chia đều cho A/B
                        $seatsB = $totalSeats - $seatsA;

                        // Hàm kiểm tra ghế đã đặt (so sánh cả nhãn và số)
                        $bookedSeatsNormalized = array_map(function($s) { return is_numeric($s) ? (int)$s : $s; }, $bookedSeats);
                        $isSeatBooked = function($label) use ($bookedSeatsNormalized) {
                            if (in_array($label, $bookedSeatsNormalized, true)) return true;
                            $num = preg_replace('/\D/', '', (string)$label);
                            if ($num !== '') {
                                $numInt = (int)$num;
                                return in_array($numInt, $bookedSeatsNormalized, true) || in_array((string)$numInt, $bookedSeatsNormalized, true);
                            }
                            return false;
                        };

                        // Tạo danh sách ghế
                        $A_odds = $A_evens = $B_odds = $B_evens = [];
                        for ($i = 1; $i <= $seatsA; $i++) {
                            if ($i % 2 === 1) $A_odds[] = "A{$i}";
                            else $A_evens[] = "A{$i}";
                        }
                        for ($i = 1; $i <= $seatsB; $i++) {
                            if ($i % 2 === 1) $B_odds[] = "B{$i}";
                            else $B_evens[] = "B{$i}";
                        }

                        // Số hàng cần hiển thị
                        $rows = max(
                            count($A_odds),
                            count($A_evens),
                            count($B_odds),
                            count($B_evens)
                        );
                        $rows = (int)max($rows, 1);

                        // Con trỏ từng cột
                        $ai = $ae = $bi = $be = 0;

                        for ($r = 0; $r < $rows; $r++) {
                            echo '<div class="seat-row">';

                            // Trái cột 1: A odd
                            if (isset($A_odds[$ai])) {
                                $label = $A_odds[$ai++];
                                $booked = $isSeatBooked($label);
                                $cls = $booked ? 'booked' : 'available';
                                $onclick = $booked ? '' : "onclick=\"selectSeat(this, '{$label}')\"";
                                echo "<div class=\"seat {$cls}\" {$onclick}>{$label}</div>";
                            } else {
                                echo '<div class="seat aisle"></div>';
                            }

                            // Trái cột 2: A even
                            if (isset($A_evens[$ae])) {
                                $label = $A_evens[$ae++];
                                $booked = $isSeatBooked($label);
                                $cls = $booked ? 'booked' : 'available';
                                $onclick = $booked ? '' : "onclick=\"selectSeat(this, '{$label}')\"";
                                echo "<div class=\"seat {$cls}\" {$onclick}>{$label}</div>";
                            } else {
                                echo '<div class="seat aisle"></div>';
                            }

                            // Aisle
                            echo '<div class="seat aisle"></div>';

                            // Phải cột 1: B odd
                            if (isset($B_odds[$bi])) {
                                $label = $B_odds[$bi++];
                                $booked = $isSeatBooked($label);
                                $cls = $booked ? 'booked' : 'available';
                                $onclick = $booked ? '' : "onclick=\"selectSeat(this, '{$label}')\"";
                                echo "<div class=\"seat {$cls}\" {$onclick}>{$label}</div>";
                            } else {
                                echo '<div class="seat aisle\"></div>';
                            }

                            // Phải cột 2: B even
                            if (isset($B_evens[$be])) {
                                $label = $B_evens[$be++];
                                $booked = $isSeatBooked($label);
                                $cls = $booked ? 'booked' : 'available';
                                $onclick = $booked ? '' : "onclick=\"selectSeat(this, '{$label}')\"";
                                echo "<div class=\"seat {$cls}\" {$onclick}>{$label}</div>";
                            } else {
                                echo '<div class="seat aisle\"></div>';
                            }

                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Pickup/Dropoff Section (Hidden initially) -->
            <div class="pickup-dropoff-section" id="pickupDropoffSection">
                <h3 class="section-title">Chọn điểm đón và điểm trả</h3>
                
                <!-- Alert Box -->
                <div class="alert-box">
                    <i class="fas fa-check-circle"></i>
                    <p>An tâm được đón đúng nơi, trả đúng chỗ đã chọn vé và đang thay đổi khi cần.</p>
                </div>
                
                <!-- Main Card -->
                <div class="pickup-dropoff-card">
                    <!-- Tabs Header -->
                    <div class="tabs-header">
                        <div class="tab active" data-tab="pickup">
                            <span>Điểm đón</span>
                        </div>
                        <div class="tab" data-tab="dropoff">
                            <span>Điểm trả</span>
                        </div>
                    </div>
                    
                    <!-- Pickup Tab -->
                    <div class="tab-content active" id="pickup-content">
                        <div class="search-box">
                            <input type="text" placeholder="Tìm trong danh sách" id="pickup-search">
                            <i class="fas fa-search"></i>
                        </div>
                        
                        <div class="point-list" id="pickup-list">
                            <?php foreach ($pickupPoints as $index => $point): ?>
                            <label class="point-item" for="pickup-<?php echo $point['schedule_id']; ?>">
                                <input 
                                    type="radio" 
                                    name="pickup_point" 
                                    id="pickup-<?php echo $point['schedule_id']; ?>" 
                                    value="<?php echo $point['schedule_id']; ?>"
                                    <?php echo $index === 0 ? 'checked' : ''; ?>
                                >
                                <div class="point-info">
                                    <div class="point-time">
                                        <?php echo date('H:i', strtotime($point['departure_time'])); ?>
                                        <span class="date">(<?php echo date('d/m', strtotime($point['departure_time'])); ?>)</span>
                                    </div>
                                    <div class="point-address">
                                        <?php echo e($point['departure_station']); ?>
                                    </div>
                                </div>
                                <div class="point-actions">
                                    <button type="button" class="btn-map" onclick="event.preventDefault(); showMap('<?php echo e($point['departure_station']); ?>')">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Bản đồ
                                    </button>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Dropoff Tab -->
                    <div class="tab-content" id="dropoff-content">
                        <div class="search-box">
                            <input type="text" placeholder="Tìm trong danh sách" id="dropoff-search">
                            <i class="fas fa-search"></i>
                        </div>
                        
                        <div class="point-list" id="dropoff-list">
                            <?php foreach ($dropoffPoints as $index => $point): ?>
                            <label class="point-item" for="dropoff-<?php echo $point['schedule_id']; ?>">
                                <input 
                                    type="radio" 
                                    name="dropoff_point" 
                                    id="dropoff-<?php echo $point['schedule_id']; ?>" 
                                    value="<?php echo $point['schedule_id']; ?>"
                                    <?php echo $index === 0 ? 'checked' : ''; ?>
                                >
                                <div class="point-info">
                                    <div class="point-time">
                                        <?php echo date('H:i', strtotime($point['arrival_time'])); ?>
                                        <span class="date">(<?php echo date('d/m', strtotime($point['arrival_time'])); ?>)</span>
                                    </div>
                                    <div class="point-address">
                                        <?php echo e($point['arrival_station']); ?>
                                    </div>
                                </div>
                                <div class="point-actions">
                                    <button type="button" class="btn-map" onclick="event.preventDefault(); showMap('<?php echo e($point['arrival_station']); ?>')">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Bản đồ
                                    </button>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Summary Bar -->
<div class="summary-bar">
    <div class="summary-content">
        <div class="summary-left">
            <div class="selected-seats">
                Ghế đã chọn: <strong id="selectedSeatsText">Chưa chọn</strong>
            </div>
            <div class="total-price">
                Tổng cộng: <strong id="totalPriceText">0đ</strong>
            </div>
        </div>
        <button class="btn-continue" id="btnContinue" onclick="continueToBooking()" disabled>
            Tiếp tục
        </button>
    </div>
</div>

<script>
const selectedSeats = [];
const seatPrice = <?php echo $price; ?>;
const bookedSeats = <?php echo json_encode($bookedSeats); ?>;
const totalSeats = <?php echo $trip['total_seats']; ?>;

function selectSeat(element, seatNumber) {
    const seatStr = seatNumber.toString();
    
    // Check if seat is booked
    if (bookedSeats.includes(seatStr) || bookedSeats.includes(parseInt(seatNumber))) {
        alert('Ghế này đã được đặt. Vui lòng chọn ghế khác.');
        return;
    }
    
    if (element.classList.contains('booked')) return;
    
    if (element.classList.contains('selected')) {
        // Deselect
        element.classList.remove('selected');
        element.classList.add('available');
        const index = selectedSeats.indexOf(seatStr);
        if (index > -1) {
            selectedSeats.splice(index, 1);
        }
    } else {
        // Select
        element.classList.remove('available');
        element.classList.add('selected');
        selectedSeats.push(seatStr);
    }
    
    updateSummary();
}

function updateSummary() {
    const selectedSeatsText = document.getElementById('selectedSeatsText');
    const totalPriceText = document.getElementById('totalPriceText');
    const btnContinue = document.getElementById('btnContinue');
    
    if (selectedSeats.length === 0) {
        selectedSeatsText.textContent = 'Chưa chọn';
        totalPriceText.textContent = '0đ';
        btnContinue.disabled = true;
    } else {
        selectedSeatsText.textContent = selectedSeats.join(', ');
        const total = selectedSeats.length * seatPrice;
        totalPriceText.textContent = total.toLocaleString('vi-VN') + 'đ';
        btnContinue.disabled = false;
    }
}

let pickupDropoffSelected = false;

function continueToBooking() {
    if (selectedSeats.length === 0) {
        alert('Vui lòng chọn ít nhất một ghế');
        return;
    }
    
    // If pickup/dropoff section is not visible, show it
    const pickupDropoffSection = document.getElementById('pickupDropoffSection');
    if (!pickupDropoffSection.classList.contains('active')) {
        // Save seats to session
        const tripId = <?php echo $tripId; ?>;
        const seats = selectedSeats.join(',');
        const totalPrice = selectedSeats.length * seatPrice;
        
        // Save to session via AJAX
        fetch('<?php echo appUrl("user/booking/save_seats.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                trip_id: tripId,
                seats: seats,
                total_price: totalPrice
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show pickup/dropoff section
                pickupDropoffSection.classList.add('active');
                pickupDropoffSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Update button text
                document.getElementById('btnContinue').textContent = 'Tiếp tục đến thông tin đặt vé';
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Still show the section even if save fails
            pickupDropoffSection.classList.add('active');
            pickupDropoffSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.getElementById('btnContinue').textContent = 'Tiếp tục đến thông tin đặt vé';
        });
        
        return;
    }
    
    // If pickup/dropoff section is visible, proceed to booking info
    const pickupPoint = document.querySelector('input[name="pickup_point"]:checked');
    const dropoffPoint = document.querySelector('input[name="dropoff_point"]:checked');
    
    if (!pickupPoint || !dropoffPoint) {
        alert('Vui lòng chọn điểm đón và điểm trả');
        return;
    }
    
    // Get pickup/dropoff details from DOM
    const pickupItem = pickupPoint.closest('.point-item');
    const dropoffItem = dropoffPoint.closest('.point-item');
    
    const pickupTime = pickupItem.querySelector('.point-time').textContent.trim();
    const pickupStation = pickupItem.querySelector('.point-address').textContent.trim();
    
    const dropoffTime = dropoffItem.querySelector('.point-time').textContent.trim();
    const dropoffStation = dropoffItem.querySelector('.point-address').textContent.trim();
    
    // Save to session via AJAX
    fetch('<?php echo appUrl("user/booking/save_points.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            pickup_id: pickupPoint.value,
            pickup_time: pickupTime,
            pickup_station: pickupStation,
            dropoff_id: dropoffPoint.value,
            dropoff_time: dropoffTime,
            dropoff_station: dropoffStation
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?php echo appUrl("user/booking/booking_info.php"); ?>';
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại');
    });
}

// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetTab = this.dataset.tab;
        
        // Update tab active state
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Update content active state
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(targetTab + '-content').classList.add('active');
    });
});

// Point selection - highlight selected
document.querySelectorAll('.point-item input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Remove selected class from all items in the same group
        const group = this.name;
        document.querySelectorAll(`input[name="${group}"]`).forEach(r => {
            r.closest('.point-item').classList.remove('selected');
        });
        
        // Add selected class to current item
        if (this.checked) {
            this.closest('.point-item').classList.add('selected');
        }
    });
    
    // Set initial selected state
    if (radio.checked) {
        radio.closest('.point-item').classList.add('selected');
    }
});

// Search functionality
function setupSearch(searchId, listId) {
    const searchInput = document.getElementById(searchId);
    if (!searchInput) return;
    const list = document.getElementById(listId);
    if (!list) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const items = list.querySelectorAll('.point-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Setup search when section is shown
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.target.classList.contains('active')) {
            setupSearch('pickup-search', 'pickup-list');
            setupSearch('dropoff-search', 'dropoff-list');
        }
    });
});

const pickupDropoffSection = document.getElementById('pickupDropoffSection');
if (pickupDropoffSection) {
    observer.observe(pickupDropoffSection, { attributes: true, attributeFilter: ['class'] });
}

// Show map (open Google Maps)
function showMap(address) {
    const encodedAddress = encodeURIComponent(address);
    window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank');
}
</script>

<?php include '../../includes/footer_user.php'; ?>


