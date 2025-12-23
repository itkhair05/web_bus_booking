<?php
/**
 * Search Results Page - Giống VeXeRe
 * Hiển thị danh sách chuyến xe với sidebar filters
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Get search parameters
$fromCity = sanitizeInput($_GET['from'] ?? '');
$toCity = sanitizeInput($_GET['to'] ?? '');
$date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

// Redirect if no search params
if (empty($fromCity) || empty($toCity)) {
    redirect(appUrl());
}

/**
 * Helpers để tương thích nhiều schema khác nhau
 */
function getTableColumns(mysqli $conn, string $table): array {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $columns = [];
    if ($result = $conn->query("SHOW COLUMNS FROM `$table`")) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        $result->free();
    }

    return $cache[$table] = $columns;
}

function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    return in_array($column, getTableColumns($conn, $table), true);
}

function resolveFirstValue(array $row, array $aliases, $default = null) {
    foreach ($aliases as $alias) {
        if (isset($row[$alias]) && $row[$alias] !== '' && $row[$alias] !== null) {
            return $row[$alias];
        }
    }
    return $default;
}

function normalizeSearchInput(string $value): string {
    $value = trim($value);
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

// Xây SELECT động để tránh lỗi cột không tồn tại
$selectParts = [
    't.trip_id',
    't.departure_time',
    't.arrival_time',
    't.available_seats',
    't.status',
    'r.route_id',
    'p.partner_id',
    'p.logo_url as partner_logo',
    'v.vehicle_id',
    'v.total_seats',
    'v.license_plate'
];

if (tableHasColumn($conn, 'partners', 'rating')) {
    $selectParts[] = 'p.rating';
} else {
    $selectParts[] = 'NULL as rating';
}

// Vehicle type column (vehicle_type vs type)
if (tableHasColumn($conn, 'vehicles', 'vehicle_type')) {
    $selectParts[] = 'v.vehicle_type';
} elseif (tableHasColumn($conn, 'vehicles', 'type')) {
    $selectParts[] = 'v.type as vehicle_type';
} else {
    $selectParts[] = "'' as vehicle_type";
}

// Partner name columns
$partnerNameAliases = [];
if (tableHasColumn($conn, 'partners', 'name')) {
    $selectParts[] = 'p.name as partner_name_primary';
    $partnerNameAliases[] = 'partner_name_primary';
}
if (tableHasColumn($conn, 'partners', 'company_name')) {
    $selectParts[] = 'p.company_name as partner_name_secondary';
    $partnerNameAliases[] = 'partner_name_secondary';
}
if (empty($partnerNameAliases)) {
    $selectParts[] = "'' as partner_name_primary";
    $partnerNameAliases[] = 'partner_name_primary';
}

// Route name (nếu có)
if (tableHasColumn($conn, 'routes', 'route_name')) {
    $selectParts[] = 'r.route_name';
}

// Origin / Destination columns
$originColumns = [];
$destinationColumns = [];
$originAliasIndex = 1;
$destinationAliasIndex = 1;
$routeOriginCandidates = ['origin', 'start_point', 'start_location', 'from_city'];
$routeDestinationCandidates = ['destination', 'end_point', 'end_location', 'to_city'];

foreach ($routeOriginCandidates as $column) {
    if (tableHasColumn($conn, 'routes', $column)) {
        $alias = "origin_variant_$originAliasIndex";
        $selectParts[] = "r.$column as $alias";
        $originColumns[] = ['column' => $column, 'alias' => $alias];
        $originAliasIndex++;
    }
}

foreach ($routeDestinationCandidates as $column) {
    if (tableHasColumn($conn, 'routes', $column)) {
        $alias = "destination_variant_$destinationAliasIndex";
        $selectParts[] = "r.$column as $alias";
        $destinationColumns[] = ['column' => $column, 'alias' => $alias];
        $destinationAliasIndex++;
    }
}

// Distance + duration columns
$distanceAliases = [];
foreach (['distance_km', 'distance', 'distance_in_km'] as $column) {
    if (tableHasColumn($conn, 'routes', $column)) {
        $alias = 'distance_variant_' . (count($distanceAliases) + 1);
        $selectParts[] = "r.$column as $alias";
        $distanceAliases[] = $alias;
    }
}
if (empty($distanceAliases)) {
    $selectParts[] = "NULL as distance_variant_1";
    $distanceAliases[] = 'distance_variant_1';
}

$durationAliases = [];
foreach (['duration_hours', 'estimated_duration', 'duration'] as $column) {
    if (tableHasColumn($conn, 'routes', $column)) {
        $alias = 'duration_variant_' . (count($durationAliases) + 1);
        $selectParts[] = "r.$column as $alias";
        $durationAliases[] = $alias;
    }
}
if (empty($durationAliases)) {
    $selectParts[] = "NULL as duration_variant_1";
    $durationAliases[] = 'duration_variant_1';
}

// Average rating from reviews (subquery)
$selectParts[] = '(SELECT AVG(rv.rating) FROM reviews rv WHERE rv.trip_id = t.trip_id) as avg_review_rating';
$selectParts[] = '(SELECT COUNT(rv.review_id) FROM reviews rv WHERE rv.trip_id = t.trip_id) as review_count';

// Price fallbacks
$priceAliases = [];
$selectParts[] = 't.price as trip_price';
$priceAliases[] = 'trip_price';
if (tableHasColumn($conn, 'trips', 'base_price')) {
    $selectParts[] = 't.base_price as trip_base_price';
    $priceAliases[] = 'trip_base_price';
}
foreach (['base_price', 'price', 'default_price'] as $column) {
    if (tableHasColumn($conn, 'routes', $column)) {
        $alias = "route_price_$column";
        $selectParts[] = "r.$column as $alias";
        $priceAliases[] = $alias;
    }
}

$selectClause = implode(",\n        ", $selectParts);

// WHERE clauses động cho điểm đi/đến
if (empty($originColumns)) {
    throw new RuntimeException('Routes table must have at least one origin/start column');
}
if (empty($destinationColumns)) {
    throw new RuntimeException('Routes table must have at least one destination/end column');
}

$originClauses = [];
foreach ($originColumns as $originCol) {
    $originClauses[] = "LOWER(r.{$originCol['column']}) LIKE LOWER(?)";
}

$destinationClauses = [];
foreach ($destinationColumns as $destinationCol) {
    $destinationClauses[] = "LOWER(r.{$destinationCol['column']}) LIKE LOWER(?)";
}

$query = "
    SELECT 
        $selectClause
    FROM trips t
    INNER JOIN routes r ON t.route_id = r.route_id
    INNER JOIN partners p ON t.partner_id = p.partner_id
    INNER JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    WHERE
        (" . implode(' OR ', $originClauses) . ")
        AND (" . implode(' OR ', $destinationClauses) . ")
        AND DATE(t.departure_time) = ?
        AND (
            t.status = 'active'
            OR t.status = 'scheduled'
            OR t.status = 'open'
            OR t.status = ''
            OR t.status IS NULL
        )
        AND t.available_seats > 0
        AND (p.status = 'approved' OR p.status IS NULL)
    ORDER BY t.departure_time ASC
";

$searchFrom = '%'.normalizeSearchInput($fromCity).'%';
$searchTo = '%'.normalizeSearchInput($toCity).'%';

$bindTypes = str_repeat('s', count($originClauses) + count($destinationClauses)) . 's';
$bindParams = [];
foreach ($originClauses as $_) {
    $bindParams[] = $searchFrom;
}
foreach ($destinationClauses as $_) {
    $bindParams[] = $searchTo;
}
$bindParams[] = $date;

$stmt = $conn->prepare($query);
$bindValues = [];
foreach ($bindParams as $key => $value) {
    $bindValues[$key] = &$bindParams[$key];
}
array_unshift($bindValues, $bindTypes);
call_user_func_array([$stmt, 'bind_param'], $bindValues);
$stmt->execute();
$result = $stmt->get_result();

if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo '<pre>';
    echo "Bind types: $bindTypes\n";
    echo "Bind params:\n";
    print_r($bindParams);
    echo "Total rows: " . $result->num_rows . "\n";
    echo '</pre>';
}

$trips = [];
while ($row = $result->fetch_assoc()) {
    $row['partner_name'] = resolveFirstValue($row, $partnerNameAliases, 'Nhà xe');
    
    // Ưu tiên lấy từ database, chỉ fallback về input nếu database rỗng
    $originFromDB = resolveFirstValue($row, array_column($originColumns, 'alias'), null);
    $destinationFromDB = resolveFirstValue($row, array_column($destinationColumns, 'alias'), null);
    
    // Chỉ dùng input của user nếu database thực sự rỗng
    $row['origin'] = !empty($originFromDB) ? $originFromDB : $fromCity;
    $row['destination'] = !empty($destinationFromDB) ? $destinationFromDB : $toCity;
    
    $row['distance_km'] = resolveFirstValue($row, $distanceAliases);
    $row['duration_hours'] = resolveFirstValue($row, $durationAliases);
    $row['price'] = resolveFirstValue($row, $priceAliases, 0);
    $trips[] = $row;
}

// Tính ghế còn trống theo thực tế (tickets/booking)
$tripIds = array_column($trips, 'trip_id');
$bookedMap = [];
if (!empty($tripIds)) {
    $placeholders = implode(',', array_fill(0, count($tripIds), '?'));
    $typesBooked = str_repeat('i', count($tripIds));
    $sqlBooked = "
        SELECT b.trip_id, COUNT(*) AS booked_count
        FROM tickets tk
        JOIN bookings b ON tk.booking_id = b.booking_id
        WHERE b.trip_id IN ($placeholders)
          AND b.status <> 'cancelled'
          AND tk.status <> 'cancelled'
        GROUP BY b.trip_id
    ";
    $stmtBooked = $conn->prepare($sqlBooked);
    if ($stmtBooked) {
        $bindValuesBooked = [];
        foreach ($tripIds as $k => $v) {
            $bindValuesBooked[$k] = &$tripIds[$k];
        }
        array_unshift($bindValuesBooked, $typesBooked);
        call_user_func_array([$stmtBooked, 'bind_param'], $bindValuesBooked);
        $stmtBooked->execute();
        $rsBooked = $stmtBooked->get_result();
        while ($bk = $rsBooked->fetch_assoc()) {
            $bookedMap[$bk['trip_id']] = (int)$bk['booked_count'];
        }
    }
}

foreach ($trips as &$trip) {
    $booked = $bookedMap[$trip['trip_id']] ?? 0;
    $totalSeats = (int)($trip['total_seats'] ?? 0);
    $trip['available_seats'] = max(0, $totalSeats - $booked);
}
unset($trip);

$totalTrips = count($trips);

// ----- FILTERS & SORT -----
$sort = $_GET['sort'] ?? 'default';
$selectedTimes = isset($_GET['time']) ? (array)$_GET['time'] : [];
$selectedPrices = isset($_GET['price']) ? (array)$_GET['price'] : [];
$selectedCompanies = isset($_GET['company']) ? (array)$_GET['company'] : [];

// Build partner options from result data
$partnerOptions = [];
foreach ($trips as $trip) {
    $partnerOptions[$trip['partner_name']] = true;
}
$partnerOptions = array_keys($partnerOptions);

// Time slot helper
function inSelectedTime(array $selectedTimes, string $departure): bool {
    if (empty($selectedTimes)) {
        return true;
    }
    $hour = (int)date('G', strtotime($departure));
    foreach ($selectedTimes as $slot) {
        if ($slot === 'early' && $hour >= 0 && $hour < 6) return true;
        if ($slot === 'morning' && $hour >= 6 && $hour < 12) return true;
        if ($slot === 'afternoon' && $hour >= 12 && $hour < 18) return true;
        if ($slot === 'night' && $hour >= 18 && $hour <= 23) return true;
    }
    return false;
}

// Price slot helper
function inSelectedPrice(array $selectedPrices, $price): bool {
    if (empty($selectedPrices)) {
        return true;
    }
    foreach ($selectedPrices as $slot) {
        switch ($slot) {
            case 'p1':
                if ($price >= 0 && $price <= 200000) return true;
                break;
            case 'p2':
                if ($price > 200000 && $price <= 400000) return true;
                break;
            case 'p3':
                if ($price > 400000 && $price <= 600000) return true;
                break;
            case 'p4':
                if ($price > 600000) return true;
                break;
        }
    }
    return false;
}

// Filter trips
$normalizeLower = function ($value) {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
};

$filteredTrips = array_filter($trips, function ($trip) use ($selectedTimes, $selectedPrices, $selectedCompanies, $normalizeLower) {
    // time filter
    if (!inSelectedTime($selectedTimes, $trip['departure_time'])) {
        return false;
    }
    // price filter
    if (!inSelectedPrice($selectedPrices, (float)$trip['price'])) {
        return false;
    }
    // company filter
    if (!empty($selectedCompanies)) {
        $name = $normalizeLower($trip['partner_name']);
        $matched = false;
        foreach ($selectedCompanies as $c) {
            if ($name === $normalizeLower($c)) {
                $matched = true;
                break;
            }
        }
        if (!$matched) return false;
    }
    return true;
});

// Sort trips
$sort = in_array($sort, ['default','time_asc','time_desc','rating_desc','price_asc','price_desc'], true) ? $sort : 'default';
if ($sort !== 'default') {
    usort($filteredTrips, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'time_asc':
                return strtotime($a['departure_time']) <=> strtotime($b['departure_time']);
            case 'time_desc':
                return strtotime($b['departure_time']) <=> strtotime($a['departure_time']);
            case 'rating_desc':
                return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
            case 'price_asc':
                return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
            case 'price_desc':
                return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        }
        return 0;
    });
}

// Replace original list with filtered & sorted
$trips = array_values($filteredTrips);
$totalTrips = count($trips);

// Lấy các khuyến mãi đang hoạt động để hiển thị thẻ giảm giá
$activePromos = [];
try {
    // Kiểm tra tên cột trong bảng promotions
    $columns = [];
    $colResult = $conn->query("SHOW COLUMNS FROM promotions");
    while ($col = $colResult->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    
    // Xác định tên cột cho date
    $startDateCol = in_array('start_date', $columns) ? 'start_date' : (in_array('valid_from', $columns) ? 'valid_from' : null);
    $endDateCol = in_array('end_date', $columns) ? 'end_date' : (in_array('valid_to', $columns) ? 'valid_to' : null);
    $maxDiscountCol = in_array('max_discount_amount', $columns) ? 'max_discount_amount' : (in_array('max_discount', $columns) ? 'max_discount' : null);
    $minAmountCol = in_array('min_order_amount', $columns) ? 'min_order_amount' : (in_array('min_amount', $columns) ? 'min_amount' : null);
    
    if ($startDateCol && $endDateCol) {
        $selectFields = ['code', 'title', 'discount_type', 'discount_value'];
        if ($maxDiscountCol) $selectFields[] = $maxDiscountCol . ' as max_discount_amount';
        if ($minAmountCol) $selectFields[] = $minAmountCol . ' as min_order_amount';
        $selectFields[] = $startDateCol . ' as start_date';
        $selectFields[] = $endDateCol . ' as end_date';
        
        $promoSql = "
            SELECT " . implode(', ', $selectFields) . "
            FROM promotions
            WHERE status = 'active'
              AND {$startDateCol} <= NOW()
              AND {$endDateCol} >= NOW()
            ORDER BY discount_type = 'percentage' DESC, discount_value DESC
            LIMIT 3
        ";
        
        if ($promoResult = $conn->query($promoSql)) {
            while ($p = $promoResult->fetch_assoc()) {
                $activePromos[] = $p;
            }
        }
    }
} catch (Exception $e) { 
    error_log("Error loading promotions: " . $e->getMessage());
}

$pageTitle = "Kết quả: Tìm xe $fromCity - $toCity";
?>

<?php include '../../includes/header_user.php'; ?>

<style>
    body {
        background: #F3F4F6;
    }
    
    .search-results-page {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .results-layout {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 20px;
    }
    
    /* Sidebar */
    .sidebar-filters {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        height: fit-content;
        position: sticky;
        top: 80px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        /* Cho phép thanh bộ lọc cuộn độc lập với danh sách chuyến xe */
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    
    .filter-section {
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #E5E7EB;
    }
    
    .filter-section:last-child {
        border: none;
        margin-bottom: 0;
    }
    
    .filter-title {
        font-size: 15px;
        font-weight: 700;
        color: #333;
        margin-bottom: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .clear-filter {
        color: #007bff;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
    }
    
    .filter-option {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        cursor: pointer;
    }
    
    .filter-option input {
        width: 16px;
        height: 16px;
    }
    
    .filter-option label {
        font-size: 14px;
        color: #666;
        cursor: pointer;
    }
    
    /* Main Content */
    .main-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .results-header {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin-bottom: 12px;
    }
    
    /* Promo Banners */
    .promo-banners {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        overflow-x: auto;
    }
    
    .promo-banner {
        min-width: 280px;
        flex: 1;
        border-radius: 12px;
        padding: 16px;
        color: #fff;
        cursor: pointer;
        background-size: cover;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .promo-banner:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    
    .promo-banner-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .promo-banner-details {
        font-size: 13px;
        opacity: 0.95;
        margin-bottom: 4px;
        line-height: 1.4;
    }
    
    .promo-banner-date {
        font-size: 12px;
        opacity: 0.85;
        margin-top: 4px;
    }
    
    /* Trip Card - VeXeRe Style */
    .trip-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #E5E7EB;
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
    }
    
    .trip-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-color: #3B82F6;
    }
    
    .flash-sale-bar {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: #fff;
        font-weight: 700;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        letter-spacing: 0.3px;
    }
    
    .flash-sale-bar.sale-urgent {
        background: linear-gradient(135deg, #B91C1C 0%, #991B1B 100%);
    }
    
    .flash-sale-bar span {
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .trip-body {
        display: flex;
        gap: 20px;
        padding: 20px;
    }
    
    .trip-left {
        flex: 1;
        display: flex;
        gap: 16px;
    }
    
    .trip-logo {
        width: 140px;
        min-width: 140px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        background: #F3F4F6;
        position: relative;
    }
    
    .trip-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .trip-logo .instant-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: #fff;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
    }
    
    .trip-info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .trip-info h3 {
        margin: 0 0 4px 0;
        font-size: 18px;
        font-weight: 700;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .rating-pill {
        background: #2563EB;
        color: #fff;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .rating-pill i {
        font-size: 10px;
        color: #FCD34D;
    }
    
    .vehicle-type {
        font-size: 14px;
        color: #6B7280;
        margin-bottom: 12px;
    }
    
    /* Vertical Timeline */
    .trip-timeline-vertical {
        display: flex;
        flex-direction: column;
        gap: 0;
        margin: 8px 0;
    }
    
    .timeline-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .timeline-dot {
        width: 12px;
        min-width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 3px solid #3B82F6;
        background: #fff;
        margin-top: 4px;
        position: relative;
        z-index: 2;
    }
    
    .timeline-dot.destination {
        background: #3B82F6;
    }
    
    .timeline-content {
        flex: 1;
        padding-bottom: 8px;
    }
    
    .timeline-content .time {
        font-size: 16px;
        font-weight: 700;
        color: #1F2937;
        display: inline;
    }
    
    .timeline-content .separator {
        color: #9CA3AF;
        margin: 0 6px;
    }
    
    .timeline-content .station {
        font-size: 14px;
        color: #4B5563;
        display: inline;
    }
    
    .timeline-content .date-badge {
        display: inline-block;
        background: #F3F4F6;
        color: #6B7280;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        margin-left: 8px;
    }
    
    .timeline-duration {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 4px 0;
    }
    
    .timeline-line {
        width: 12px;
        min-width: 12px;
        display: flex;
        justify-content: center;
        position: relative;
    }
    
    .timeline-line::before {
        content: '';
        width: 2px;
        height: 100%;
        min-height: 28px;
        background: linear-gradient(180deg, #3B82F6 0%, #93C5FD 100%);
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }
    
    .duration-text {
        font-size: 13px;
        color: #6B7280;
        font-weight: 500;
    }
    
    /* Feature Tags */
    .feature-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed #E5E7EB;
    }
    
    .feature-tag {
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .feature-tag.blue { color: #2563EB; }
    .feature-tag.red { color: #DC2626; }
    .feature-tag.green { color: #16A34A; }
    
    .detail-link {
        font-size: 13px;
        font-weight: 600;
        color: #3B82F6;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
        transition: color 0.2s;
    }
    
    .detail-link:hover {
        color: #1D4ED8;
    }
    
    .trip-right {
        width: 200px;
        min-width: 200px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        text-align: right;
    }
    
    .price-section {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
    }
    
    .trip-right .original-price {
        text-decoration: line-through;
        color: #9CA3AF;
        font-size: 14px;
    }
    
    .price-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .trip-right .current-price {
        font-size: 24px;
        font-weight: 800;
        color: #EA580C;
    }
    
    .discount-tag {
        background: #FEE2E2;
        color: #DC2626;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
    }
    
    /* Promo Tags */
    .promo-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
        margin: 8px 0;
    }
    
    .promo-tag {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .promo-tag:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    .promo-tag.green {
        background: #DCFCE7;
        color: #166534;
        border: 1px solid #86EFAC;
    }
    
    .promo-tag.green:hover {
        background: #BBF7D0;
    }
    
    .promo-tag.yellow {
        background: #FEF3C7;
        color: #92400E;
        border: 1px solid #FCD34D;
    }
    
    .promo-tag.yellow:hover {
        background: #FDE68A;
    }
    
    .promo-tag.view-all {
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #93C5FD;
    }
    
    .promo-tag.view-all:hover {
        background: #DBEAFE;
    }
    
    /* All Promos Modal */
    .all-promos-modal {
        background: #fff;
        border-radius: 16px;
        width: 100%;
        max-width: 600px;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .all-promos-header {
        background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
        color: #fff;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .all-promos-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .all-promos-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    
    .promo-card {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        gap: 16px;
        align-items: center;
    }
    
    .promo-card:hover {
        background: #F3F4F6;
        border-color: #3B82F6;
        transform: translateX(4px);
    }
    
    .promo-card:last-child {
        margin-bottom: 0;
    }
    
    .promo-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .promo-card-icon.fixed {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: #fff;
    }
    
    .promo-card-icon.percentage {
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        color: #fff;
    }
    
    .promo-card-content {
        flex: 1;
        min-width: 0;
    }
    
    .promo-card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1F2937;
        margin-bottom: 4px;
    }
    
    .promo-card-desc {
        font-size: 13px;
        color: #6B7280;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .promo-card-meta {
        display: flex;
        gap: 12px;
        font-size: 12px;
        color: #9CA3AF;
    }
    
    .promo-card-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .promo-card-code {
        background: #E5E7EB;
        color: #374151;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 1px;
        flex-shrink: 0;
    }
    
    /* Promo Detail Modal */
    .promo-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 3000;
        padding: 20px;
        backdrop-filter: blur(4px);
    }
    
    .promo-modal-overlay.active {
        display: flex;
    }
    
    .promo-modal {
        background: #fff;
        border-radius: 16px;
        width: 100%;
        max-width: 420px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .promo-modal-header {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: #fff;
        padding: 20px;
        text-align: center;
        position: relative;
    }
    
    .promo-modal-header h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 700;
    }
    
    .promo-modal-header .discount-value {
        font-size: 28px;
        font-weight: 800;
    }
    
    .promo-modal-close {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .promo-modal-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .promo-modal-body {
        padding: 24px;
    }
    
    .promo-code-box {
        background: #F3F4F6;
        border: 2px dashed #D1D5DB;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .promo-code-label {
        font-size: 12px;
        color: #6B7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .promo-code-value {
        font-size: 24px;
        font-weight: 800;
        color: #1F2937;
        letter-spacing: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    
    .promo-code-copy {
        background: #3B82F6;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .promo-code-copy:hover {
        background: #2563EB;
    }
    
    .promo-code-copy.copied {
        background: #10B981;
    }
    
    .promo-info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .promo-info-list li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 14px;
        color: #4B5563;
    }
    
    .promo-info-list li:last-child {
        border-bottom: none;
    }
    
    .promo-info-list li i {
        color: #10B981;
        margin-top: 2px;
        width: 16px;
    }
    
    .promo-modal-footer {
        padding: 16px 24px 24px;
    }
    
    .promo-apply-btn {
        width: 100%;
        background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%);
        border: none;
        color: #78350F;
        padding: 14px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .promo-apply-btn:hover {
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
    }
    
    .seat-status {
        font-size: 14px;
        font-weight: 600;
        color: #16A34A;
        margin: 8px 0;
    }
    
    .seat-status.urgent {
        color: #DC2626;
    }
    
    .trip-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        margin-top: 8px;
    }
    
    .btn-detail {
        background: #fff;
        border: 1px solid #E5E7EB;
        color: #4B5563;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .btn-detail:hover {
        background: #F9FAFB;
        border-color: #3B82F6;
        color: #3B82F6;
    }
    
    .btn-select {
        background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%);
        border: none;
        color: #78350F;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
    }
    
    .btn-select:hover {
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.5);
        transform: translateY(-1px);
    }
    
    .trip-note {
        padding: 12px 20px;
        background: #F9FAFB;
        border-top: 1px solid #E5E7EB;
        font-size: 13px;
        color: #DC2626;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    /* Responsive */
    @media (max-width: 968px) {
        .results-layout {
            grid-template-columns: 1fr;
        }
        
        .sidebar-filters {
            position: static;
        }
        
        .trip-body {
            flex-direction: column;
        }
        
        .trip-left {
            flex-direction: column;
        }
        
        .trip-logo {
            width: 100%;
            height: 160px;
        }
        
        .trip-right {
            width: 100%;
            min-width: unset;
            align-items: flex-start;
            text-align: left;
            border-top: 1px dashed #E5E7EB;
            padding-top: 16px;
            margin-top: 8px;
        }
        
        .price-section {
            align-items: flex-start;
        }
        
        .promo-tags {
            justify-content: flex-start;
        }
        
        .trip-actions {
            flex-direction: row;
        }
        
        .btn-detail, .btn-select {
            flex: 1;
        }
    }
    
    @media (max-width: 600px) {
        .trip-body {
            padding: 16px;
        }
        
        .trip-info h3 {
            font-size: 16px;
        }
        
        .trip-right .current-price {
            font-size: 20px;
        }
        
        .timeline-content .time {
            font-size: 15px;
        }
        
        .timeline-content .station {
            font-size: 13px;
        }
        
        .feature-tags {
            gap: 8px;
        }
        
        .feature-tag {
            font-size: 11px;
        }
        
        .trip-actions {
            flex-direction: column;
        }
        
        .promo-tags {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    
    @media (max-width: 400px) {
        .flash-sale-bar {
            flex-direction: column;
            gap: 4px;
            text-align: center;
            padding: 8px 16px;
        }
        
        .trip-logo {
            height: 120px;
        }
    }
    .policy-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }
    
    .policy-modal {
        width: 100%;
        max-width: 520px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }
    
    .policy-header {
        padding: 24px;
        text-align: center;
        border-bottom: 1px solid #F3F4F6;
    }
    
    .policy-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #0F172A;
    }
    
    .policy-body {
        padding: 16px 24px;
        margin: 16px 24px;
        border: 1px solid #F3F4F6;
        border-radius: 12px;
        background: #FDFDFD;
        max-height: 320px;
        overflow-y: auto;
        font-size: 14px;
        color: #374151;
        line-height: 1.5;
    }
    
    .policy-body p {
        margin-bottom: 12px;
    }
    
    .policy-body ul {
        padding-left: 18px;
        margin-bottom: 12px;
    }
    
    .policy-body li {
        margin-bottom: 6px;
    }
    
    .policy-footer {
        padding: 0 24px 24px;
    }
    
    .policy-footer button {
        width: 100%;
        background: #FCD34D;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        color: #111;
    }
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }

    .policy-modal {
        width: 100%;
        max-width: 520px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }

    .policy-header {
        padding: 24px;
        text-align: center;
        border-bottom: 1px solid #F3F4F6;
    }

    .policy-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #0F172A;
    }

    .policy-body {
        padding: 16px 24px;
        margin: 16px 24px;
        border: 1px solid #F3F4F6;
        border-radius: 12px;
        background: #FDFDFD;
        max-height: 320px;
        overflow-y: auto;
        font-size: 14px;
        color: #374151;
        line-height: 1.5;
    }

    .policy-body p {
        margin-bottom: 12px;
    }

    .policy-body ul {
        padding-left: 20px;
        margin-bottom: 12px;
    }

    .policy-body li {
        margin-bottom: 6px;
    }

    .policy-footer {
        padding: 0 24px 24px;
    }

    .policy-footer button {
        width: 100%;
        background: #FCD34D;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        color: #111;
    }
</style>

<div class="search-results-page">
    <div class="results-layout">
        <!-- Sidebar Filters -->
        <aside class="sidebar-filters">
            <form method="GET" class="filter-form" id="filtersForm">
                <input type="hidden" name="from" value="<?php echo e($fromCity); ?>">
                <input type="hidden" name="to" value="<?php echo e($toCity); ?>">
                <input type="hidden" name="date" value="<?php echo e($date); ?>">
                
                <!-- Sắp xếp -->
                <div class="filter-section">
                    <div class="filter-title">Sắp xếp</div>
                    <div class="filter-option">
                        <input type="radio" id="sort1" name="sort" value="default" <?php echo $sort === 'default' ? 'checked' : ''; ?>>
                        <label for="sort1">Mặc định</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" id="sort2" name="sort" value="time_asc" <?php echo $sort === 'time_asc' ? 'checked' : ''; ?>>
                        <label for="sort2">Giờ đi sớm nhất</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" id="sort3" name="sort" value="time_desc" <?php echo $sort === 'time_desc' ? 'checked' : ''; ?>>
                        <label for="sort3">Giờ đi muộn nhất</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" id="sort4" name="sort" value="rating_desc" <?php echo $sort === 'rating_desc' ? 'checked' : ''; ?>>
                        <label for="sort4">Đánh giá cao nhất</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" id="sort5" name="sort" value="price_asc" <?php echo $sort === 'price_asc' ? 'checked' : ''; ?>>
                        <label for="sort5">Giá tăng dần</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" id="sort6" name="sort" value="price_desc" <?php echo $sort === 'price_desc' ? 'checked' : ''; ?>>
                        <label for="sort6">Giá giảm dần</label>
                    </div>
                </div>
                
                <!-- Lọc -->
                <div class="filter-section">
                    <div class="filter-title">
                        Lọc
                        <a href="<?php echo appUrl('user/search/results.php?from=' . urlencode($fromCity) . '&to=' . urlencode($toCity) . '&date=' . urlencode($date)); ?>" class="clear-filter">Xóa lọc</a>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter1" disabled>
                        <label for="filter1" style="color:#9ca3af; cursor:not-allowed;">Trả khách tận nơi (sắp có)</label>
                    </div>
                </div>
                
                <!-- Giờ đi -->
                <div class="filter-section">
                    <div class="filter-title">Giờ đi</div>
                    <div class="filter-option">
                        <input type="checkbox" id="time1" name="time[]" value="early" <?php echo in_array('early', $selectedTimes) ? 'checked' : ''; ?>>
                        <label for="time1">Sáng sớm 00:00 - 06:00</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="time2" name="time[]" value="morning" <?php echo in_array('morning', $selectedTimes) ? 'checked' : ''; ?>>
                        <label for="time2">Buổi sáng 06:00 - 12:00</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="time3" name="time[]" value="afternoon" <?php echo in_array('afternoon', $selectedTimes) ? 'checked' : ''; ?>>
                        <label for="time3">Buổi chiều 12:00 - 18:00</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="time4" name="time[]" value="night" <?php echo in_array('night', $selectedTimes) ? 'checked' : ''; ?>>
                        <label for="time4">Buổi tối 18:00 - 24:00</label>
                    </div>
                </div>
                
                <!-- Nhà xe -->
                <div class="filter-section">
                    <div class="filter-title">Nhà xe</div>
                    <?php if (empty($partnerOptions)): ?>
                        <div class="filter-option"><span style="color:#9ca3af;">Không có nhà xe</span></div>
                    <?php else: ?>
                        <?php foreach ($partnerOptions as $idx => $company): ?>
                        <div class="filter-option">
                            <input type="checkbox" id="company<?php echo $idx; ?>" name="company[]" value="<?php echo e($company); ?>" <?php echo in_array($company, $selectedCompanies) ? 'checked' : ''; ?>>
                            <label for="company<?php echo $idx; ?>"><?php echo e($company); ?></label>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Giá vé -->
                <div class="filter-section">
                    <div class="filter-title">Giá vé</div>
                    <div class="filter-option">
                        <input type="checkbox" id="price1" name="price[]" value="p1" <?php echo in_array('p1', $selectedPrices) ? 'checked' : ''; ?>>
                        <label for="price1">0đ - 200.000đ</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="price2" name="price[]" value="p2" <?php echo in_array('p2', $selectedPrices) ? 'checked' : ''; ?>>
                        <label for="price2">200.000đ - 400.000đ</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="price3" name="price[]" value="p3" <?php echo in_array('p3', $selectedPrices) ? 'checked' : ''; ?>>
                        <label for="price3">400.000đ - 600.000đ</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="price4" name="price[]" value="p4" <?php echo in_array('p4', $selectedPrices) ? 'checked' : ''; ?>>
                        <label for="price4">600.000đ+</label>
                    </div>
                </div>
                
                <!-- Chính sách vé -->
                <div class="policy-box">
                    <div class="policy-header">
                        <span>Chính sách vé</span>
                        <span class="policy-badge">Siêu thuận tiện</span>
                    </div>
                    <div class="policy-body">
                        <div class="policy-row">
                            <div class="policy-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="policy-text">
                                Đổi trả vé linh hoạt trong 24h
                            </div>
                        </div>
                        <div class="policy-row">
                            <div class="policy-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="policy-text">
                                Hoàn tiền nếu nhà xe không phục vụ
                            </div>
                        </div>
                        <div class="policy-row">
                            <div class="policy-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="policy-text">
                                Hỗ trợ 24/7
                            </div>
                        </div>
                    </div>
                    <div class="policy-footer">
                        <button type="button">Xem chi tiết</button>
                    </div>
                </div>
            </form>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <h1 class="results-header">Kết quả: <?php echo $totalTrips; ?> chuyến</h1>
            
            <!-- Promo Banners -->
            <div class="promo-banners">
                <?php if (!empty($activePromos)): ?>
                    <?php
                        $gradients = [
                            'linear-gradient(135deg, #FF6B35, #FFA500)',
                            'linear-gradient(135deg, #4F46E5, #7C3AED)',
                            'linear-gradient(135deg, #10B981, #059669)',
                            'linear-gradient(135deg, #f43f5e, #e11d48)',
                        ];
                    ?>
                    <?php foreach ($activePromos as $idx => $promo): ?>
                        <?php
                            $g = $gradients[$idx % count($gradients)];
                            
                            // Format discount text
                            $discountValue = floatval($promo['discount_value']);
                            if ($promo['discount_type'] === 'percentage') {
                                $valueText = 'Giảm ' . number_format($discountValue, 0, ',', '') . '%';
                                if (!empty($promo['max_discount_amount'])) {
                                    $valueText .= ' (tối đa ' . number_format($promo['max_discount_amount'], 0, ',', '.') . '₫)';
                                }
                            } else {
                                $valueText = 'Giảm ' . number_format($discountValue, 0, ',', '.') . '₫';
                            }
                            
                            // Thêm điều kiện đơn hàng tối thiểu nếu có
                            if (!empty($promo['min_order_amount']) && floatval($promo['min_order_amount']) > 0) {
                                $valueText .= ' (cho đơn từ ' . number_format($promo['min_order_amount'], 0, ',', '.') . '₫)';
                            }
                            
                            // Format date
                            $startDate = strtotime($promo['start_date']);
                            $endDate = strtotime($promo['end_date']);
                            $dateText = date('d/m', $startDate) . ' - ' . date('d/m', $endDate);
                            
                            // Title
                            $title = !empty($promo['title']) ? $promo['title'] : $promo['code'];
                        ?>
                        <div class="promo-banner" style="background: <?php echo $g; ?>;">
                            <div class="promo-banner-title"><?php echo htmlspecialchars($title); ?></div>
                            <div>
                                <div class="promo-banner-details"><?php echo htmlspecialchars($valueText); ?></div>
                                <div class="promo-banner-date">Hiệu lực: <?php echo $dateText; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="promo-banner" style="background: linear-gradient(135deg, #4F46E5, #7C3AED);">
                        Khuyến mãi sẽ được cập nhật
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Trip Cards -->
            <div id="tripsList">
                <?php if (empty($trips)): ?>
                    <!-- No Results -->
                    <div style="background: #fff; padding: 60px 20px; text-align: center; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <i class="fas fa-bus" style="font-size: 64px; color: #D1D5DB; margin-bottom: 20px;"></i>
                        <h2 style="font-size: 24px; color: #333; margin-bottom: 12px;">Không tìm thấy chuyến xe phù hợp</h2>
                        <p style="color: #666; font-size: 16px; margin-bottom: 24px;">
                            Không có chuyến xe nào từ <strong><?php echo htmlspecialchars($fromCity); ?></strong> 
                            đến <strong><?php echo htmlspecialchars($toCity); ?></strong> 
                            vào ngày <strong><?php echo date('d/m/Y', strtotime($date)); ?></strong>
                        </p>
                        <p style="color: #666; font-size: 14px; margin-bottom: 24px;">
                            Hãy thử:
                        </p>
                        <ul style="list-style: none; padding: 0; color: #666; font-size: 14px; text-align: left; max-width: 400px; margin: 0 auto;">
                            <li style="margin-bottom: 8px;">✓ Kiểm tra lại điểm đi và điểm đến</li>
                            <li style="margin-bottom: 8px;">✓ Thử ngày khác</li>
                            <li style="margin-bottom: 8px;">✓ Thay đổi địa điểm tìm kiếm</li>
                        </ul>
                        <a href="<?php echo appUrl(); ?>" class="btn-select" style="display: inline-block; margin-top: 24px; text-decoration: none;">
                            <i class="fas fa-search"></i> Tìm kiếm lại
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($trips as $trip): ?>
                        <?php
                        // Format data for display
                        $departureTime = date('H:i', strtotime($trip['departure_time']));
                        $arrivalTime = date('H:i', strtotime($trip['arrival_time']));
                        $arrivalDate = date('d/m', strtotime($trip['arrival_time']));
                        $price = number_format($trip['price'] ?? 0, 0, ',', '.');
                        $availableSeats = $trip['available_seats'] ?? 0;
                        $duration = $trip['duration_hours'] ?? 'N/A';
                        // Sử dụng review rating từ bảng reviews, fallback về partner rating
                        $rating = !empty($trip['avg_review_rating']) ? $trip['avg_review_rating'] : ($trip['rating'] ?? 0);
                        $reviewCount = $trip['review_count'] ?? 0;
                        
                        // Check if Flash Sale
                        $isFlashSale = false; // TODO: Add flash sale logic
                        $originalPrice = null;
                        $discount = null;
                        ?>
                        
                        <?php
                            // Tính thời gian di chuyển thực tế từ departure và arrival
                            $departureTimestamp = strtotime($trip['departure_time']);
                            $arrivalTimestamp = strtotime($trip['arrival_time']);
                            $durationSeconds = $arrivalTimestamp - $departureTimestamp;
                            
                            // Format duration: Xh hoặc XhYm
                            if ($durationSeconds > 0) {
                                $durationHours = floor($durationSeconds / 3600);
                                $durationMinutes = floor(($durationSeconds % 3600) / 60);
                                if ($durationMinutes > 0) {
                                    $durationText = $durationHours . 'h' . $durationMinutes . 'm';
                                } else {
                                    $durationText = $durationHours . 'h';
                                }
                            } else {
                                $durationText = $duration !== 'N/A' ? $duration . 'h' : '~5h';
                            }
                            
                            $originalPrice = $trip['price'] ? ceil($trip['price'] * 1.15 / 1000) * 1000 : null;
                            $discountPercent = ($originalPrice && $trip['price']) ? max(1, round(100 - ($trip['price'] / $originalPrice * 100))) : null;
                            
                            // Tính thời gian còn lại đến giờ khởi hành
                            $countdownSeconds = max(0, $departureTimestamp - time());
                            
                            // Chỉ hiển thị "Ưu đãi giờ chót" khi còn <= 3 tiếng (10800 giây)
                            $showFlashSale = $countdownSeconds > 0 && $countdownSeconds <= 10800;
                            
                            $arrivalDifferentDay = date('Y-m-d', $arrivalTimestamp) !== date('Y-m-d', $departureTimestamp);
                        ?>
                        <div class="trip-card" data-trip-id="<?php echo $trip['trip_id']; ?>">
                            <?php if ($showFlashSale): ?>
                            <!-- Flash Sale / Promo Header - Chỉ hiện khi còn <= 3 tiếng -->
                            <div class="flash-sale-bar<?php echo $countdownSeconds < 3600 ? ' sale-urgent' : ''; ?>">
                                <span>⚡ ƯU ĐÃI GIỜ CHÓT</span>
                                <span>Kết thúc sau <?php echo sprintf('%02d:%02d:%02d', floor($countdownSeconds / 3600), floor(($countdownSeconds % 3600) / 60), $countdownSeconds % 60); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="trip-body">
                                <!-- Left: Logo + Info -->
                                <div class="trip-left">
                                    <div class="trip-logo">
                                        <?php $logoSrc = getPartnerLogoUrl($trip['partner_logo'] ?? null); ?>
                                        <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="<?php echo htmlspecialchars($trip['partner_name']); ?>">
                                        <span class="instant-badge"><i class="fas fa-bolt"></i> Xác nhận tức thì</span>
                                    </div>
                                    
                                    <div class="trip-info">
                                        <h3>
                                            <?php echo htmlspecialchars($trip['partner_name']); ?>
                                            <?php if ($rating): ?>
                                                <span class="rating-pill">
                                                    <i class="fas fa-star"></i> 
                                                    <?php echo number_format($rating, 1); ?>
                                                    <?php if ($reviewCount > 0): ?>
                                                        <span class="review-count">(<?php echo $reviewCount; ?> đánh giá)</span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="vehicle-type"><?php echo htmlspecialchars($trip['vehicle_type'] ?: 'Limousine'); ?> <?php echo $trip['total_seats'] ?? 34; ?> phòng</div>
                                        
                                        <!-- Vertical Timeline -->
                                        <div class="trip-timeline-vertical">
                                            <!-- Departure -->
                                            <div class="timeline-row">
                                                <div class="timeline-dot"></div>
                                                <div class="timeline-content">
                                                    <span class="time"><?php echo $departureTime; ?></span>
                                                    <span class="separator">•</span>
                                                    <span class="station"><?php echo htmlspecialchars($trip['origin']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Duration - Tính từ giờ khởi hành và giờ đến -->
                                            <div class="timeline-duration">
                                                <div class="timeline-line"></div>
                                                <span class="duration-text"><?php echo $durationText; ?></span>
                                            </div>
                                            
                                            <!-- Arrival -->
                                            <div class="timeline-row">
                                                <div class="timeline-dot destination"></div>
                                                <div class="timeline-content">
                                                    <span class="time"><?php echo $arrivalTime; ?></span>
                                                    <span class="separator">•</span>
                                                    <span class="station"><?php echo htmlspecialchars($trip['destination']); ?></span>
                                                    <?php if ($arrivalDifferentDay): ?>
                                                        <span class="date-badge">(<?php echo $arrivalDate; ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Feature Tags -->
                                        <div class="feature-tags">
                                            <span class="feature-tag blue"><i class="fas fa-map-marker-alt"></i> THEO DÕI HÀNH TRÌNH XE</span>
                                            <span class="feature-tag red">KHÔNG CẦN THANH TOÁN TRƯỚC</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right: Price + Actions -->
                                <div class="trip-right">
                                    <div class="price-section">
                                        <?php if ($originalPrice): ?>
                                            <div class="original-price"><?php echo number_format($originalPrice, 0, ',', '.'); ?>đ</div>
                                        <?php endif; ?>
                                        <div class="price-row">
                                            <span class="current-price"><?php echo $price; ?>đ</span>
                                            <?php if ($discountPercent): ?>
                                                <span class="discount-tag">-<?php echo $discountPercent; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Promo Tags from Database -->
                                    <div class="promo-tags">
                                        <?php 
                                        // Hiển thị tối đa 2 khuyến mãi đang hoạt động
                                        $promoCount = 0;
                                        foreach ($activePromos as $promo): 
                                            if ($promoCount >= 2) break;
                                            
                                            // Kiểm tra min_order_amount
                                            $minAmount = floatval($promo['min_order_amount'] ?? 0);
                                            if ($minAmount > 0 && $trip['price'] < $minAmount) continue;
                                            
                                            $promoCount++;
                                            $promoCode = htmlspecialchars($promo['code']);
                                            $promoTitle = htmlspecialchars($promo['title'] ?? $promo['code']);
                                            $promoDesc = htmlspecialchars($promo['description'] ?? '');
                                            $promoMinOrder = floatval($promo['min_order_amount'] ?? 0);
                                            $promoEndDate = date('d/m/Y', strtotime($promo['end_date']));
                                            
                                            if ($promo['discount_type'] === 'fixed'):
                                                // Giảm cố định (ví dụ: Giảm 50.000đ)
                                                $discountAmount = floatval($promo['discount_value']);
                                                $discountText = 'Giảm ' . number_format($discountAmount, 0, ',', '.') . 'đ';
                                        ?>
                                            <span class="promo-tag green" onclick="showPromoDetail('<?php echo $promoCode; ?>', '<?php echo $promoTitle; ?>', '<?php echo $discountText; ?>', '<?php echo $promoDesc; ?>', <?php echo $promoMinOrder; ?>, '<?php echo $promoEndDate; ?>')">
                                                <i class="fas fa-ticket-alt"></i> 
                                                <?php echo $discountText; ?>
                                            </span>
                                        <?php else: 
                                                // Giảm theo phần trăm
                                                $discountPct = floatval($promo['discount_value']);
                                                $maxDiscount = floatval($promo['max_discount_amount'] ?? 0);
                                                $discountText = 'Giảm ' . number_format($discountPct, 0) . '%';
                                                if ($maxDiscount > 0) {
                                                    $discountText .= ', tối đa ' . number_format($maxDiscount, 0, ',', '.') . 'đ';
                                                }
                                        ?>
                                            <span class="promo-tag yellow" onclick="showPromoDetail('<?php echo $promoCode; ?>', '<?php echo $promoTitle; ?>', '<?php echo $discountText; ?>', '<?php echo $promoDesc; ?>', <?php echo $promoMinOrder; ?>, '<?php echo $promoEndDate; ?>')">
                                                <i class="fas fa-bolt"></i> 
                                                <?php echo $discountText; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($activePromos) > 0): ?>
                                        <!-- Nút xem tất cả mã -->
                                        <span class="promo-tag view-all" onclick="showAllPromos()">
                                            <i class="fas fa-tags"></i> 
                                            Xem tất cả (<?php echo count($activePromos); ?>)
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="seat-status<?php echo $availableSeats <= 10 ? ' urgent' : ''; ?>">
                                        Còn <?php echo $availableSeats; ?> chỗ trống
                                    </div>
                                    
                                    <div class="trip-actions">
                                        <button class="btn-detail" type="button" onclick="toggleDetails(<?php echo $trip['trip_id']; ?>)">
                                            Thông tin chi tiết <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <button class="btn-select" type="button" onclick="selectTrip(<?php echo $trip['trip_id']; ?>)">
                                            Chọn chuyến
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer Note -->
                            <div class="trip-note">
                                Vé chặng thuộc chuyến <?php echo date('H:i d-m-Y', strtotime($trip['departure_time'])); ?> <?php echo htmlspecialchars($trip['origin']); ?> - <?php echo htmlspecialchars($trip['destination']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- All Promos Modal -->
<div class="promo-modal-overlay" id="allPromosModal">
    <div class="all-promos-modal">
        <div class="all-promos-header">
            <h3><i class="fas fa-tags"></i> Tất cả mã khuyến mãi</h3>
            <button class="promo-modal-close" onclick="closeAllPromosModal()">&times;</button>
        </div>
        <div class="all-promos-body">
            <?php foreach ($activePromos as $promo): 
                $promoCode = htmlspecialchars($promo['code']);
                $promoTitle = htmlspecialchars($promo['title'] ?? $promo['code']);
                $promoDesc = htmlspecialchars($promo['description'] ?? '');
                $promoMinOrder = floatval($promo['min_order_amount'] ?? 0);
                $promoEndDate = date('d/m/Y', strtotime($promo['end_date']));
                $isFixed = $promo['discount_type'] === 'fixed';
                
                if ($isFixed) {
                    $discountAmount = floatval($promo['discount_value']);
                    $discountText = 'Giảm ' . number_format($discountAmount, 0, ',', '.') . 'đ';
                } else {
                    $discountPct = floatval($promo['discount_value']);
                    $maxDiscount = floatval($promo['max_discount_amount'] ?? 0);
                    $discountText = 'Giảm ' . number_format($discountPct, 0) . '%';
                    if ($maxDiscount > 0) {
                        $discountText .= ', tối đa ' . number_format($maxDiscount, 0, ',', '.') . 'đ';
                    }
                }
            ?>
            <div class="promo-card" onclick="showPromoDetail('<?php echo $promoCode; ?>', '<?php echo $promoTitle; ?>', '<?php echo $discountText; ?>', '<?php echo $promoDesc; ?>', <?php echo $promoMinOrder; ?>, '<?php echo $promoEndDate; ?>'); closeAllPromosModal();">
                <div class="promo-card-icon <?php echo $isFixed ? 'fixed' : 'percentage'; ?>">
                    <i class="fas fa-<?php echo $isFixed ? 'ticket-alt' : 'percent'; ?>"></i>
                </div>
                <div class="promo-card-content">
                    <div class="promo-card-title"><?php echo $promoTitle; ?></div>
                    <div class="promo-card-desc"><?php echo $discountText; ?></div>
                    <div class="promo-card-meta">
                        <?php if ($promoMinOrder > 0): ?>
                        <span><i class="fas fa-shopping-cart"></i> Từ <?php echo number_format($promoMinOrder, 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                        <span><i class="fas fa-clock"></i> HSD: <?php echo $promoEndDate; ?></span>
                    </div>
                </div>
                <div class="promo-card-code"><?php echo $promoCode; ?></div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($activePromos)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #9CA3AF;">
                <i class="fas fa-ticket-alt" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>Hiện không có mã khuyến mãi nào</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Promo Detail Modal -->
<div class="promo-modal-overlay" id="promoModal">
    <div class="promo-modal">
        <div class="promo-modal-header">
            <button class="promo-modal-close" onclick="closePromoModal()">&times;</button>
            <h3 id="promoModalTitle">Khuyến mãi</h3>
            <div class="discount-value" id="promoModalDiscount">Giảm 50.000đ</div>
        </div>
        <div class="promo-modal-body">
            <div class="promo-code-box">
                <div class="promo-code-label">Mã khuyến mãi</div>
                <div class="promo-code-value">
                    <span id="promoModalCode">SAVE50K</span>
                    <button class="promo-code-copy" onclick="copyPromoCode()">
                        <i class="fas fa-copy"></i> Sao chép
                    </button>
                </div>
            </div>
            <ul class="promo-info-list">
                <li id="promoModalDesc">
                    <i class="fas fa-info-circle"></i>
                    <span>Mô tả khuyến mãi</span>
                </li>
                <li id="promoModalMinOrder" style="display: none;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Áp dụng cho đơn hàng từ <strong>0đ</strong></span>
                </li>
                <li id="promoModalExpiry">
                    <i class="fas fa-clock"></i>
                    <span>Hết hạn: <strong>31/12/2025</strong></span>
                </li>
            </ul>
        </div>
        <div class="promo-modal-footer">
            <button class="promo-apply-btn" onclick="closePromoModal()">
                <i class="fas fa-check"></i> Đã hiểu
            </button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="policyModal" style="display:none;">
    <div class="policy-modal">
        <div class="policy-header">
            <h3>Quy Định Nhà Xe</h3>
            <button type="button" class="policy-close" onclick="closePolicyModal()">&times;</button>
        </div>
        <div class="policy-body" id="policyContent">
            <p>Đang tải quy định...</p>
        </div>
        <div class="policy-footer">
            <button type="button" onclick="agreePolicy()">Tôi đã đọc và đồng ý</button>
        </div>
    </div>
</div>

<script>
// Auto-submit filters on change
const filterForm = document.getElementById('filtersForm');
if (filterForm) {
    filterForm.querySelectorAll('input').forEach(el => {
        el.addEventListener('change', () => filterForm.submit());
    });
}

// Toggle trip details
function toggleDetails(tripId) {
    const card = document.querySelector(`.trip-card[data-trip-id="${tripId}"]`);
    if (!card) return;
    
    let detailsPanel = card.querySelector('.trip-details-panel');
    
    if (!detailsPanel) {
        // Create details panel if not exists
        detailsPanel = document.createElement('div');
        detailsPanel.className = 'trip-details-panel';
        detailsPanel.innerHTML = `
            <div style="padding: 20px; background: #F9FAFB; border-top: 1px solid #E5E7EB;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h4 style="font-size: 14px; font-weight: 700; color: #1F2937; margin: 0 0 12px 0;">
                            <i class="fas fa-bus" style="color: #3B82F6; margin-right: 8px;"></i>Tiện ích
                        </h4>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="font-size: 13px; color: #4B5563; padding: 6px 0;"><i class="fas fa-check" style="color: #10B981; margin-right: 8px;"></i>Wifi miễn phí</li>
                            <li style="font-size: 13px; color: #4B5563; padding: 6px 0;"><i class="fas fa-check" style="color: #10B981; margin-right: 8px;"></i>Nước uống</li>
                            <li style="font-size: 13px; color: #4B5563; padding: 6px 0;"><i class="fas fa-check" style="color: #10B981; margin-right: 8px;"></i>Điều hòa</li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-size: 14px; font-weight: 700; color: #1F2937; margin: 0 0 12px 0;">
                            <i class="fas fa-info-circle" style="color: #3B82F6; margin-right: 8px;"></i>Chính sách
                        </h4>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="font-size: 13px; color: #4B5563; padding: 6px 0;"><i class="fas fa-check" style="color: #10B981; margin-right: 8px;"></i>Đổi/trả vé linh hoạt</li>
                            <li style="font-size: 13px; color: #4B5563; padding: 6px 0;"><i class="fas fa-check" style="color: #10B981; margin-right: 8px;"></i>Hỗ trợ 24/7</li>
                            <li style="font-size: 13px; color: #4B5563; padding: 6px 0;"><i class="fas fa-check" style="color: #10B981; margin-right: 8px;"></i>Hoàn tiền nếu nhà xe không phục vụ</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        // Insert before trip-note
        const tripNote = card.querySelector('.trip-note');
        if (tripNote) {
            card.insertBefore(detailsPanel, tripNote);
        } else {
            card.appendChild(detailsPanel);
        }
    }
    
    // Toggle visibility
    detailsPanel.classList.toggle('active');
    
    // Update button icon
    const btn = card.querySelector('.btn-detail i');
    if (btn) {
        btn.classList.toggle('fa-chevron-down');
        btn.classList.toggle('fa-chevron-up');
    }
}

// Trip details panel style
const style = document.createElement('style');
style.textContent = `
    .trip-details-panel {
        display: none;
        animation: slideDown 0.3s ease-out;
    }
    .trip-details-panel.active {
        display: block;
    }
    @keyframes slideDown {
        from { opacity: 0; max-height: 0; }
        to { opacity: 1; max-height: 500px; }
    }
`;
document.head.appendChild(style);

let pendingTripId = null;
let tripPolicies = {};

// All Promos Modal Functions
function showAllPromos() {
    document.getElementById('allPromosModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAllPromosModal() {
    document.getElementById('allPromosModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close all promos modal when clicking outside
document.getElementById('allPromosModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAllPromosModal();
    }
});

// Promo Modal Functions
function showPromoDetail(code, title, discount, description, minOrder, endDate) {
    document.getElementById('promoModalTitle').textContent = title;
    document.getElementById('promoModalDiscount').textContent = discount;
    document.getElementById('promoModalCode').textContent = code;
    
    // Description
    const descEl = document.getElementById('promoModalDesc');
    if (description) {
        descEl.innerHTML = '<i class="fas fa-info-circle"></i><span>' + description + '</span>';
        descEl.style.display = 'flex';
    } else {
        descEl.style.display = 'none';
    }
    
    // Min order amount
    const minOrderEl = document.getElementById('promoModalMinOrder');
    if (minOrder > 0) {
        minOrderEl.innerHTML = '<i class="fas fa-shopping-cart"></i><span>Áp dụng cho đơn hàng từ <strong>' + formatNumber(minOrder) + 'đ</strong></span>';
        minOrderEl.style.display = 'flex';
    } else {
        minOrderEl.style.display = 'none';
    }
    
    // Expiry date
    document.getElementById('promoModalExpiry').innerHTML = '<i class="fas fa-clock"></i><span>Hết hạn: <strong>' + endDate + '</strong></span>';
    
    // Show modal
    document.getElementById('promoModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePromoModal() {
    document.getElementById('promoModal').classList.remove('active');
    document.body.style.overflow = '';
}

function copyPromoCode() {
    const code = document.getElementById('promoModalCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.promo-code-copy');
        btn.innerHTML = '<i class="fas fa-check"></i> Đã sao chép';
        btn.classList.add('copied');
        
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy"></i> Sao chép';
            btn.classList.remove('copied');
        }, 2000);
    });
}

function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

// Close promo modal when clicking outside
document.getElementById('promoModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePromoModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePromoModal();
        closeAllPromosModal();
        closePolicyModal();
    }
});

// Load policies for all trips
<?php
foreach ($trips as $trip) {
    $partnerId = $trip['partner_id'] ?? null;
    if ($partnerId) {
        // Get policy from database
        $policyQuery = "SELECT policy FROM partners WHERE partner_id = ?";
        $policyStmt = $conn->prepare($policyQuery);
        $policyStmt->bind_param("i", $partnerId);
        $policyStmt->execute();
        $policyResult = $policyStmt->get_result();
        $policyRow = $policyResult->fetch_assoc();
        $policy = $policyRow['policy'] ?? '';
        
        // Format policy for display
        // Convert newlines to <br>, preserve formatting, and handle bullet points
        $formattedPolicy = htmlspecialchars($policy);
        // Convert newlines to <br>
        $formattedPolicy = nl2br($formattedPolicy);
        // Wrap lines starting with * in <p> tags if not already wrapped
        $formattedPolicy = preg_replace('/^(\*[^<]*)$/m', '<p>$1</p>', $formattedPolicy);
        // If empty, show default message
        if (empty(trim($policy))) {
            $formattedPolicy = '<p class="text-muted">Nhà xe chưa cập nhật quy định.</p>';
        }
        
        echo "tripPolicies[{$trip['trip_id']}] = " . json_encode($formattedPolicy) . ";\n";
    }
}
?>

function selectTrip(tripId) {
    pendingTripId = tripId;
    const policy = tripPolicies[tripId] || '<p>Nhà xe chưa cập nhật quy định.</p>';
    document.getElementById('policyContent').innerHTML = policy;
    document.getElementById('policyModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePolicyModal() {
    document.getElementById('policyModal').style.display = 'none';
    document.body.style.overflow = '';
    pendingTripId = null;
}

function agreePolicy() {
    if (!pendingTripId) return;
    const tripId = pendingTripId;
    closePolicyModal();
    window.location.href = `<?php echo appUrl('user/booking/select_seat.php'); ?>?trip_id=${tripId}`;
}
</script>

<?php include '../../includes/footer_user.php'; ?>
