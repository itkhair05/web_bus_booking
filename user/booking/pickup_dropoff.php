<?php
/**
 * Pickup & Dropoff Selection Page
 * Chọn điểm đón và điểm trả - Step 2
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Get data (session fallback to query params)
$tripId = $_SESSION['booking_trip_id'] ?? intval($_GET['trip_id'] ?? 0);
$selectedSeats = $_SESSION['booking_seats'] ?? [];

if (empty($selectedSeats) && !empty($_GET['seats'])) {
    $selectedSeats = array_filter(array_map('trim', explode(',', $_GET['seats'])));
}

$totalPrice = $_SESSION['booking_price'] ?? 0;

if (empty($tripId) || empty($selectedSeats)) {
    redirect(appUrl());
}

// Normalize seats
$selectedSeats = array_values(array_unique($selectedSeats));

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

$stmt = $conn->prepare("
    SELECT 
        t.*,
        $routeOriginCol as origin,
        $routeDestCol as destination,
        $partnerNameCol as partner_name,
        $vehicleTypeCol as vehicle_type,
        v.total_seats
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

$seatPrice = $trip['price'] ?? 0;
if ($totalPrice <= 0 && $seatPrice > 0) {
    $totalPrice = count($selectedSeats) * $seatPrice;
}

// Persist data for next steps
$_SESSION['booking_trip_id'] = $tripId;
$_SESSION['booking_seats'] = $selectedSeats;
$_SESSION['booking_price'] = $totalPrice;

// Get pickup and dropoff points
// Since trip_schedules table doesn't exist, we'll create default points from route and trip data
$pickupPoints = [];
$dropoffPoints = [];

// Default pickup point - origin station
$pickupPoints[] = [
    'schedule_id' => 'pickup_1',
    'departure_time' => $trip['departure_time'],
    'departure_station' => $trip['origin'] . ' - Bến xe chính'
];

// Additional pickup points (common locations)
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

$pageTitle = 'Chọn điểm đón trả - BusBooking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
    .pickup-dropoff-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 30px 0;
    }
    
    .container-pd {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Progress Steps */
    .progress-steps {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        position: relative;
    }
    
    .progress-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e0e0e0;
        z-index: 0;
    }
    
    .step {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        padding: 10px 20px;
        border-radius: 50px;
        position: relative;
        z-index: 1;
    }
    
    .step.completed {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .step.active {
        background: #1976d2;
        color: #fff;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
    }
    
    .step.completed .step-number {
        background: #4caf50;
        color: #fff;
    }
    
    .step.active .step-number {
        background: #fff;
        color: #1976d2;
    }
    
    /* Alert Box */
    .alert-box {
        background: #e8f5e9;
        border: 1px solid #a5d6a7;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 30px;
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
    
    /* Main Card */
    .pickup-dropoff-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    /* Tabs */
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
    
    /* Tab Content */
    .tab-content {
        display: none;
        padding: 30px;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Search Box */
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
    
    /* Sort Options */
    .sort-options {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .sort-label {
        font-size: 14px;
        color: #666;
        font-weight: 600;
    }
    
    .sort-options a {
        font-size: 14px;
        color: #1976d2;
        text-decoration: none;
        padding: 4px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .sort-options a:hover {
        background: #e3f2fd;
    }
    
    /* Point List */
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
    
    .point-actions {
        display: flex;
        gap: 10px;
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
    
    /* Notice */
    .notice-text {
        font-size: 13px;
        color: #64748b;
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }
    
    .notice-text a {
        color: #1976d2;
        text-decoration: none;
        font-weight: 600;
    }
    
    /* Footer Actions */
    .footer-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px;
        background: #fff;
        border-top: 2px solid #e9ecef;
        margin-top: 30px;
    }
    
    .btn-back {
        background: #fff;
        border: 1px solid #e0e0e0;
        color: #475569;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
    
    .total-price {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .total-label {
        font-size: 14px;
        color: #64748b;
    }
    
    .total-amount {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .btn-continue {
        background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        color: #fff;
        padding: 14px 32px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
    }
    
    .btn-continue:hover {
        background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
        box-shadow: 0 6px 16px rgba(25, 118, 210, 0.4);
        transform: translateY(-2px);
    }
    
    .btn-continue:disabled {
        background: #e0e0e0;
        color: #9e9e9e;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .progress-steps {
            flex-direction: column;
            gap: 10px;
        }
        
        .progress-steps::before {
            display: none;
        }
        
        .step {
            width: 100%;
            justify-content: center;
        }
        
        .tabs-header {
            flex-direction: column;
        }
        
        .tab-content {
            padding: 20px;
        }
        
        .footer-actions {
            flex-direction: column;
            gap: 16px;
            align-items: stretch;
        }
        
        .total-price {
            flex-direction: column;
            gap: 8px;
            width: 100%;
            text-align: center;
        }
        
        .btn-continue {
            width: 100%;
        }
    }
</style>

<div class="pickup-dropoff-page">
    <div class="container-pd">
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <span>Chọ muốn</span>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <span>Điểm đón trả</span>
            </div>
        </div>
        
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
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" placeholder="Tìm trong danh sách" id="pickup-search">
                    <i class="fas fa-search"></i>
                </div>
                
                <!-- Sort Options -->
                <div class="sort-options">
                    <span class="sort-label">Sắp xếp theo</span>
                    <a href="#" class="sort-link active">Điểm đón nào gần bạn nhất?</a>
                    <a href="#" class="sort-link">Nhập địa chỉ của bạn</a>
                </div>
                
                <!-- Suggestion -->
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 13px; color: #64748b; margin: 0;">
                        <strong>Lưu ý:</strong> Sử dụng địa phương trước cặp nhập
                    </p>
                </div>
                
                <!-- Point List -->
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
                
                <!-- Notice -->
                <div class="notice-text">
                    Sai hoặc thiếu thông tin? <a href="#" onclick="reportError(); return false;">Báo cáo</a>
                </div>
            </div>
            
            <!-- Dropoff Tab -->
            <div class="tab-content" id="dropoff-content">
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" placeholder="Tìm trong danh sách" id="dropoff-search">
                    <i class="fas fa-search"></i>
                </div>
                
                <!-- Sort Options -->
                <div class="sort-options">
                    <span class="sort-label">Sắp xếp theo</span>
                    <a href="#" class="sort-link active">Điểm trả nào thuận tiện nhất?</a>
                    <a href="#" class="sort-link">Nhập địa chỉ của bạn</a>
                </div>
                
                <!-- Point List -->
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
                
                <!-- Notice -->
                <div class="notice-text">
                    Sai hoặc thiếu thông tin? <a href="#" onclick="reportError(); return false;">Báo cáo</a>
                </div>
            </div>
        </div>
        
        <!-- Footer Actions -->
        <div class="footer-actions">
            <button class="btn-back" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Quay lại
            </button>
            
            <div class="total-price">
                <span class="total-label">Tổng cộng:</span>
                <span class="total-amount"><?php echo number_format($totalPrice); ?>đ</span>
            </div>
            
            <button class="btn-continue" onclick="continueToBooking()">
                Tiếp tục
            </button>
        </div>
    </div>
</div>

<script>
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
    const list = document.getElementById(listId);
    
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

setupSearch('pickup-search', 'pickup-list');
setupSearch('dropoff-search', 'dropoff-list');

// Show map (open Google Maps)
function showMap(address) {
    const encodedAddress = encodeURIComponent(address);
    window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank');
}

// Report error
function reportError() {
    alert('Cảm ơn bạn đã báo cáo! Chúng tôi sẽ xem xét và cập nhật thông tin.');
}

// Go back
function goBack() {
    window.history.back();
}

// Continue to booking info
function continueToBooking() {
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
    fetch('save_points.php', {
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
            window.location.href = 'booking_info.php';
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại');
    });
}
</script>

<?php include '../../includes/footer_user.php'; ?>

