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
    $row['origin'] = resolveFirstValue($row, array_column($originColumns, 'alias'), $fromCity);
    $row['destination'] = resolveFirstValue($row, array_column($destinationColumns, 'alias'), $toCity);
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
    $promoSql = "
        SELECT code, title, discount_type, discount_value, max_discount_amount, start_date, end_date
        FROM promotions
        WHERE status = 'active'
          AND start_date <= NOW()
          AND end_date >= NOW()
        ORDER BY discount_type = 'percentage' DESC, discount_value DESC
        LIMIT 3
    ";
    if ($promoResult = $conn->query($promoSql)) {
        while ($p = $promoResult->fetch_assoc()) {
            $activePromos[] = $p;
        }
    }
} catch (Exception $e) { /* ignore promotions if table missing */ }

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
        min-width: 220px;
        height: 90px;
        border-radius: 8px;
        padding: 12px;
        color: #fff;
        cursor: pointer;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    
    /* Trip Card */
    .trip-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #E4E7EC;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 10px 24px rgba(15,23,42,0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .trip-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(15,23,42,0.12);
    }
    
    .flash-sale-bar {
        background: #0B59FF;
        color: #fff;
        font-weight: 700;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        letter-spacing: 0.3px;
    }
    
    .flash-sale-bar span {
        font-size: 12px;
        font-weight: 500;
        opacity: 0.9;
    }
    
    .trip-body {
        display: flex;
        gap: 18px;
        padding: 22px;
    }
    
    .trip-left {
        flex: 1;
        display: flex;
        gap: 16px;
    }
    
    .trip-logo {
        width: 96px;
        height: 96px;
        border-radius: 10px;
        overflow: hidden;
        background: #EEF2FF;
    }
    
    .trip-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .trip-info {
        flex: 1;
    }
    
    .trip-info h3 {
        margin: 0;
        font-size: 18px;
        color: #0F172A;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .rating-pill {
        background: #2563EB;
        color: #fff;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }
    
    .vehicle-type {
        font-size: 13px;
        color: #6B7280;
        margin-top: 4px;
    }
    
    .time-row {
        display: grid;
        grid-template-columns: 1fr 60px 1fr;
        gap: 12px;
        margin-top: 16px;
        align-items: center;
    }
    
    .time-block .time {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
    }
    
    .time-block .place {
        font-size: 14px;
        color: #4B5563;
        margin-top: 4px;
    }
    
    .time-block .note {
        font-size: 12px;
        color: #94A3B8;
        margin-top: 2px;
    }
    
    .arrow {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        color: #475569;
    }
    
    .badge-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }
    
    .badge-row span {
        font-size: 11px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 999px;
    }
    
    .badge-green { background: #DCFCE7; color: #15803D; }
    .badge-blue { background: #E0F2FE; color: #0369A1; }
    .badge-red { background: #FEE2E2; color: #B91C1C; }
    
    .detail-link {
        margin-top: 12px;
        font-size: 13px;
        font-weight: 600;
        color: #2563EB;
        cursor: pointer;
    }
    
    .trip-right {
        width: 210px;
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 6px;
        justify-content: center;
    }
    
    .trip-right .original-price {
        text-decoration: line-through;
        color: #94A3B8;
        font-size: 13px;
    }
    
    .trip-right .current-price {
        font-size: 28px;
        font-weight: 800;
        color: #F97316;
    }
    
    .discount-tag {
        align-self: flex-end;
        background: #FEE2E2;
        color: #B91C1C;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    
    .seat-status {
        font-size: 13px;
        font-weight: 600;
        color: #DC2626;
    }
    
    .btn-select {
        background: #FFB703;
        border: none;
        color: #111;
        font-size: 14px;
        font-weight: 700;
        border-radius: 10px;
        padding: 10px 0;
        cursor: pointer;
        margin-top: 8px;
    }
    
    .btn-select:hover {
        background: #F59E0B;
    }
    
    .trip-note {
        padding: 10px 22px 18px;
        border-top: 1px dashed #E4E7EC;
        font-size: 13px;
        color: #475569;
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
        
        .time-row {
            grid-template-columns: 1fr;
        }
        
        .trip-right {
            width: 100%;
            text-align: left;
            margin-top: 12px;
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
                            $valueText = $promo['discount_type'] === 'percentage'
                                ? ('Giảm ' . rtrim(rtrim($promo['discount_value'], '0'), '.') . '%'
                                   . ($promo['max_discount_amount'] !== null ? ' (tối đa ' . number_format($promo['max_discount_amount']) . 'đ)' : ''))
                                : 'Giảm ' . number_format($promo['discount_value']) . 'đ';
                            $dateText = date('d/m', strtotime($promo['start_date'])) . ' - ' . date('d/m', strtotime($promo['end_date']));
                        ?>
                        <div class="promo-banner" style="background: <?php echo $g; ?>;">
                            <div style="font-weight:700;"><?php echo htmlspecialchars($promo['title'] ?: $promo['code']); ?></div>
                            <div style="font-size:13px; opacity:0.9;"><?php echo htmlspecialchars($valueText); ?></div>
                            <div style="font-size:12px; opacity:0.85;">Hiệu lực: <?php echo $dateText; ?></div>
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
                        $rating = $trip['rating'] ?? 0;
                        
                        // Check if Flash Sale
                        $isFlashSale = false; // TODO: Add flash sale logic
                        $originalPrice = null;
                        $discount = null;
                        ?>
                        
                        <?php
                            $durationText = $duration !== 'N/A' ? $duration . 'h' : null;
                            $originalPrice = $trip['price'] ? ceil($trip['price'] * 1.15 / 1000) * 1000 : null;
                            $discountPercent = ($originalPrice && $trip['price']) ? max(1, round(100 - ($trip['price'] / $originalPrice * 100))) : null;
                            $countdownSeconds = max(0, strtotime($trip['departure_time']) - time());
                            $countdownText = sprintf(
                                'Bắt đầu sau %02d:%02d:%02d',
                                floor($countdownSeconds / 3600),
                                floor(($countdownSeconds % 3600) / 60),
                                $countdownSeconds % 60
                            );
                        ?>
                        <?php
                            $saleLabel = 'FLASH SALE ' . date('d.m', strtotime($trip['departure_time']));
                            $arrivalDifferentDay = date('Y-m-d', strtotime($trip['arrival_time'])) !== date('Y-m-d', strtotime($trip['departure_time']));
                        ?>
                        <div class="trip-card">
                            <div class="flash-sale-bar">
                                ⚡ <?php echo $saleLabel; ?>
                                <span><?php echo $countdownText; ?></span>
                            </div>
                            <div class="trip-body">
                                <div class="trip-left">
                                    <div class="trip-logo">
                                        <?php
                                            $logoSrc = getPartnerLogoUrl($trip['partner_logo'] ?? null);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="<?php echo htmlspecialchars($trip['partner_name']); ?>">
                                    </div>
                                    <div class="trip-info">
                                        <h3>
                                            <?php echo htmlspecialchars($trip['partner_name']); ?>
                                            <?php if ($rating): ?>
                                                <span class="rating-pill"><i class="fas fa-star"></i> <?php echo number_format($rating, 1); ?></span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="vehicle-type"><?php echo htmlspecialchars($trip['vehicle_type'] ?: 'Xe tiêu chuẩn'); ?></div>
                                        
                                        <div class="time-row">
                                            <div class="time-block">
                                                <div class="time"><?php echo $departureTime; ?></div>
                                                <div class="place"><?php echo htmlspecialchars($trip['origin']); ?></div>
                                                <?php if ($durationText): ?>
                                                    <div class="note"><?php echo $durationText; ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="arrow">→</div>
                                            <div class="time-block">
                                                <div class="time"><?php echo $arrivalTime; ?></div>
                                                <div class="place"><?php echo htmlspecialchars($trip['destination']); ?></div>
                                                <?php if ($arrivalDifferentDay): ?>
                                                    <div class="note">(<?php echo $arrivalDate; ?>)</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="badge-row">
                                            <span class="badge-green">TRẢ TẬN NƠI</span>
                                            <span class="badge-blue">THEO DÕI HÀNH TRÌNH XE</span>
                                            <span class="badge-red">KHÔNG CẦN THANH TOÁN TRƯỚC</span>
                                        </div>
                                        
                                        <div class="detail-link">Thông tin chi tiết ▼</div>
                                    </div>
                                </div>
                                
                                <div class="trip-right">
                                    <?php if ($originalPrice): ?>
                                        <div class="original-price"><?php echo number_format($originalPrice, 0, ',', '.'); ?>đ</div>
                                    <?php endif; ?>
                                    <div class="current-price"><?php echo $price; ?>đ</div>
                                    <?php if ($discountPercent): ?>
                                        <span class="discount-tag">-<?php echo $discountPercent; ?>%</span>
                                    <?php endif; ?>
                                    <div class="seat-status">
                                        <?php echo $availableSeats <= 10 ? "Chỉ còn {$availableSeats} chỗ trống" : "Còn {$availableSeats} chỗ trống"; ?>
                                    </div>
                                    <button class="btn-select" type="button" onclick="selectTrip(<?php echo $trip['trip_id']; ?>)">Chọn chuyến</button>
                                </div>
                            </div>
                            <div class="trip-note">
                                * Vé chặng trước chuyến <?php echo date('H:i d/m/Y', strtotime($trip['departure_time'])); ?> <?php echo htmlspecialchars($trip['origin']); ?> - <?php echo htmlspecialchars($trip['destination']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
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

let pendingTripId = null;
let tripPolicies = {};

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
