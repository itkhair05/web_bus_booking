<?php
/**
 * EXAMPLE USAGE - TRIP CARD TEMPLATE
 * File này demo cách sử dụng renderTripCard() trong các pages khác nhau
 */

// ========================================
// EXAMPLE 1: SEARCH RESULTS PAGE
// ========================================
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm - BusBooking</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Trip Card CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/trip-card.css">
</head>
<body>
    <div class="page-container">
        <h1>Kết quả: 155 chuyến</h1>
        
        <!-- Filter Bar (Optional) -->
        <div class="filter-bar">
            <button onclick="filterTrips('price-low')">Giá thấp nhất</button>
            <button onclick="filterTrips('time-early')">Giờ đi sớm nhất</button>
            <button onclick="filterTrips('rating')">Đánh giá cao nhất</button>
            <button onclick="resetFilters()">Reset</button>
        </div>
        
        <!-- Trips Container -->
        <div class="trips-container">
            <?php
            // Include helpers
            require_once 'core/helpers.php';
            require_once 'config/database.php';
            
            // Get search parameters
            $from = $_GET['from'] ?? 'Sài Gòn';
            $to = $_GET['to'] ?? 'Quảng Ngãi';
            $date = $_GET['date'] ?? date('Y-m-d');
            
            // Query trips
            $stmt = $conn->prepare("
                SELECT 
                    t.trip_id,
                    t.departure_time,
                    t.arrival_time,
                    t.price_per_seat,
                    t.duration,
                    t.status,
                    r.start_point,
                    r.end_point,
                    r.route_name,
                    p.company_name as partner_name,
                    p.rating,
                    p.review_count,
                    b.bus_type,
                    b.total_seats,
                    b.has_wifi,
                    b.has_ac,
                    b.has_wc,
                    (SELECT departure_station 
                     FROM trip_schedules 
                     WHERE trip_id = t.trip_id 
                     ORDER BY stop_order ASC 
                     LIMIT 1) as departure_station,
                    (SELECT arrival_station 
                     FROM trip_schedules 
                     WHERE trip_id = t.trip_id 
                     ORDER BY stop_order DESC 
                     LIMIT 1) as arrival_station,
                    -- Calculate discount if flash sale
                    (SELECT discount_percentage 
                     FROM promotions 
                     WHERE CURDATE() BETWEEN start_date AND end_date 
                     AND status = 'active'
                     LIMIT 1) as discount,
                    -- Available seats
                    (b.total_seats - (
                        SELECT COUNT(*) 
                        FROM bookings bk 
                        WHERE bk.trip_id = t.trip_id 
                        AND bk.status IN ('confirmed', 'pending')
                    )) as available_seats
                FROM trips t
                JOIN routes r ON t.route_id = r.route_id
                JOIN partners p ON t.partner_id = p.partner_id
                JOIN buses b ON t.bus_id = b.bus_id
                WHERE r.start_point LIKE ?
                AND r.end_point LIKE ?
                AND DATE(t.departure_time) = ?
                AND t.status = 'scheduled'
                ORDER BY t.departure_time ASC
            ");
            
            $fromPattern = "%$from%";
            $toPattern = "%$to%";
            $stmt->bind_param("sss", $fromPattern, $toPattern, $date);
            $stmt->execute();
            $trips = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Render trip cards
            if (empty($trips)) {
                echo '<p class="no-results">Không tìm thấy chuyến xe phù hợp</p>';
            } else {
                foreach ($trips as $trip) {
                    // ⭐ MAGIC: Chỉ cần gọi 1 function!
                    echo renderTripCard($trip);
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Trip Card JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/trip-card.js"></script>
</body>
</html>

<?php
// ========================================
// EXAMPLE 2: HOMEPAGE - FEATURED TRIPS
// ========================================
?>

<!-- In index.php -->
<section class="featured-trips">
    <div class="container">
        <h2 class="section-title">🔥 Flash Sale Hôm Nay</h2>
        
        <div class="trips-container">
            <?php
            // Get flash sale trips
            $flashSaleTrips = $conn->query("
                SELECT 
                    t.*,
                    p.company_name as partner_name,
                    b.bus_type,
                    b.total_seats,
                    r.start_point,
                    r.end_point,
                    20 as discount -- Flash sale 20%
                FROM trips t
                JOIN partners p ON t.partner_id = p.partner_id
                JOIN buses b ON t.bus_id = b.bus_id
                JOIN routes r ON t.route_id = r.route_id
                WHERE DATE(t.departure_time) = CURDATE()
                ORDER BY RAND()
                LIMIT 3
            ")->fetch_all(MYSQLI_ASSOC);
            
            foreach ($flashSaleTrips as $trip) {
                echo renderTripCard($trip);
            }
            ?>
        </div>
    </div>
</section>

<?php
// ========================================
// EXAMPLE 3: PARTNER DASHBOARD - MY TRIPS
// ========================================
?>

<!-- In partner/trips/list.php -->
<div class="dashboard-content">
    <h2>Chuyến xe của tôi</h2>
    
    <div class="trips-container">
        <?php
        $partnerId = $_SESSION['partner_id'];
        
        $myTrips = $conn->query("
            SELECT 
                t.*,
                p.company_name as partner_name,
                b.bus_type,
                b.total_seats,
                r.start_point,
                r.end_point,
                r.route_name
            FROM trips t
            JOIN partners p ON t.partner_id = p.partner_id
            JOIN buses b ON t.bus_id = b.bus_id
            JOIN routes r ON t.route_id = r.route_id
            WHERE t.partner_id = $partnerId
            ORDER BY t.departure_time DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        foreach ($myTrips as $trip) {
            echo renderTripCard($trip);
        }
        ?>
    </div>
</div>

<?php
// ========================================
// EXAMPLE 4: USER PROFILE - BOOKING HISTORY
// ========================================
?>

<!-- In user/profile/bookings.php -->
<div class="booking-history">
    <h2>Lịch sử đặt vé</h2>
    
    <div class="trips-container">
        <?php
        $userId = $_SESSION['user_id'];
        
        $bookedTrips = $conn->query("
            SELECT 
                t.*,
                p.company_name as partner_name,
                b.bus_type,
                b.total_seats,
                r.start_point,
                r.end_point,
                bk.booking_id,
                bk.total_price,
                bk.booking_status
            FROM bookings bk
            JOIN trips t ON bk.trip_id = t.trip_id
            JOIN partners p ON t.partner_id = p.partner_id
            JOIN buses b ON t.bus_id = b.bus_id
            JOIN routes r ON t.route_id = r.route_id
            WHERE bk.user_id = $userId
            ORDER BY bk.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        foreach ($bookedTrips as $trip) {
            echo renderTripCard($trip);
            // Thêm booking info
            echo "<div class='booking-info'>";
            echo "<p>Mã đặt vé: <strong>{$trip['booking_id']}</strong></p>";
            echo "<p>Trạng thái: <span class='badge badge-{$trip['booking_status']}'>{$trip['booking_status']}</span></p>";
            echo "</div>";
        }
        ?>
    </div>
</div>

<?php
// ========================================
// EXAMPLE 5: ADMIN DASHBOARD - ALL TRIPS
// ========================================
?>

<!-- In admin/trips/manage.php -->
<div class="admin-trips-management">
    <h2>Quản lý tất cả chuyến xe</h2>
    
    <div class="trips-container">
        <?php
        $allTrips = $conn->query("
            SELECT 
                t.*,
                p.company_name as partner_name,
                b.bus_type,
                b.total_seats,
                r.start_point,
                r.end_point
            FROM trips t
            JOIN partners p ON t.partner_id = p.partner_id
            JOIN buses b ON t.bus_id = b.bus_id
            JOIN routes r ON t.route_id = r.route_id
            ORDER BY t.created_at DESC
            LIMIT 50
        ")->fetch_all(MYSQLI_ASSOC);
        
        foreach ($allTrips as $trip) {
            echo renderTripCard($trip);
            // Add admin actions
            echo "<div class='admin-actions'>";
            echo "<button onclick='editTrip({$trip['trip_id']})'>Sửa</button>";
            echo "<button onclick='deleteTrip({$trip['trip_id']})'>Xóa</button>";
            echo "</div>";
        }
        ?>
    </div>
</div>

<?php
// ========================================
// EXAMPLE 6: API RESPONSE (MOBILE APP)
// ========================================

// In api/trips/search.php
header('Content-Type: application/json');

$trips = [/* ... query trips ... */];

// Convert trips to HTML
$tripsHtml = array_map(function($trip) {
    return renderTripCard($trip);
}, $trips);

// Or return raw data for mobile to render
echo json_encode([
    'success' => true,
    'count' => count($trips),
    'data' => $trips,
    'html' => $tripsHtml // Optional: for WebView
]);

// ========================================
// EXAMPLE 7: CUSTOM FILTERS
// ========================================
?>

<div class="advanced-filters">
    <!-- Price Range -->
    <div class="filter-group">
        <label>Khoảng giá:</label>
        <input type="range" id="minPrice" min="0" max="1000000" step="50000">
        <input type="range" id="maxPrice" min="0" max="1000000" step="50000">
        <button onclick="filterByPriceRange(
            document.getElementById('minPrice').value,
            document.getElementById('maxPrice').value
        )">Áp dụng</button>
    </div>
    
    <!-- Time Range -->
    <div class="filter-group">
        <label>Giờ khởi hành:</label>
        <select onchange="filterByTimeRange(this.value)">
            <option value="">Tất cả</option>
            <option value="morning">Sáng (00:00 - 12:00)</option>
            <option value="afternoon">Chiều (12:00 - 18:00)</option>
            <option value="evening">Tối (18:00 - 22:00)</option>
            <option value="night">Đêm (22:00 - 24:00)</option>
        </select>
    </div>
    
    <!-- Amenities -->
    <div class="filter-group">
        <label>Tiện ích:</label>
        <label><input type="checkbox" value="wifi"> WiFi</label>
        <label><input type="checkbox" value="ac"> Điều hòa</label>
        <label><input type="checkbox" value="wc"> WC</label>
        <button onclick="filterByAmenities([
            ...document.querySelectorAll('input[type=checkbox]:checked')
        ].map(cb => cb.value))">Lọc</button>
    </div>
    
    <!-- Search -->
    <div class="filter-group">
        <input type="text" placeholder="Tìm nhà xe, loại xe..." 
               oninput="searchTrips(this.value)">
    </div>
</div>

<?php
// ========================================
// EXAMPLE 8: LAZY LOADING (INFINITE SCROLL)
// ========================================
?>

<div class="trips-container" id="tripsContainer">
    <?php
    // Initial load - first 10 trips
    $page = 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    $initialTrips = $conn->query("
        SELECT * FROM trips 
        LIMIT $perPage OFFSET $offset
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($initialTrips as $trip) {
        echo renderTripCard($trip);
    }
    ?>
</div>

<div class="loading" id="loading" style="display: none;">
    <i class="fas fa-spinner fa-spin"></i> Đang tải...
</div>

<script>
let currentPage = 1;
let isLoading = false;

window.addEventListener('scroll', function() {
    if (isLoading) return;
    
    const scrollPosition = window.innerHeight + window.scrollY;
    const threshold = document.body.offsetHeight - 500;
    
    if (scrollPosition >= threshold) {
        loadMoreTrips();
    }
});

async function loadMoreTrips() {
    isLoading = true;
    document.getElementById('loading').style.display = 'block';
    
    currentPage++;
    
    try {
        const response = await fetch(`/api/trips/load.php?page=${currentPage}`);
        const data = await response.json();
        
        if (data.success && data.html) {
            document.getElementById('tripsContainer').insertAdjacentHTML('beforeend', data.html);
        }
    } catch (error) {
        console.error('Load more error:', error);
    } finally {
        isLoading = false;
        document.getElementById('loading').style.display = 'none';
    }
}
</script>

<?php
// ========================================
// SUMMARY: KEY POINTS
// ========================================
/**
 * 1. IMPORT HELPERS:
 *    require_once 'core/helpers.php';
 * 
 * 2. QUERY DATA:
 *    Đảm bảo query đủ fields required (xem TRIP_CARD_TEMPLATE.md)
 * 
 * 3. RENDER:
 *    echo renderTripCard($trip);
 * 
 * 4. INCLUDE CSS/JS:
 *    <link href="assets/css/trip-card.css">
 *    <script src="assets/js/trip-card.js"></script>
 * 
 * 5. CUSTOMIZE:
 *    - Sửa function renderTripCard() trong helpers.php
 *    - Update CSS trong trip-card.css
 *    - Thêm interactions trong trip-card.js
 * 
 * 6. BENEFITS:
 *    ✅ Consistent UI across all pages
 *    ✅ Easy maintenance (1 place to update)
 *    ✅ Reusable component
 *    ✅ Auto-format data
 *    ✅ Responsive & accessible
 */
?>

