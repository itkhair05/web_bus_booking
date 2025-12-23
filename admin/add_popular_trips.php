<?php
/**
 * Script thêm chuyến xe cho các tuyến phổ biến
 * Mỗi nhà xe sẽ có 3 chuyến với thời gian rải đều từ 12h đến 20h
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Các tuyến phổ biến từ index.php
$popularRoutes = [
    ['from' => 'Sài Gòn', 'to' => 'Đà Lạt', 'base_price' => 200000, 'duration' => 6.5],
    ['from' => 'Quảng Ngãi', 'to' => 'Đà Nẵng', 'base_price' => 90000, 'duration' => 2.5],
    ['from' => 'Quảng Ngãi', 'to' => 'Sài Gòn', 'base_price' => 160000, 'duration' => 8.0],
    ['from' => 'Sài Gòn', 'to' => 'Vũng Tàu', 'base_price' => 180000, 'duration' => 2.5],
    ['from' => 'Hà Nội', 'to' => 'Sapa', 'base_price' => 300000, 'duration' => 6.0],
    ['from' => 'Hà Nội', 'to' => 'Quảng Ninh', 'base_price' => 250000, 'duration' => 3.5],
];

// Thời gian khởi hành: 12:00, 16:00, 20:00
$departureTimes = ['12:00', '16:00', '20:00'];

$message = '';
$messageType = 'info';
$results = [];

// Chỉ chạy khi có tham số run=1
if (!isset($_GET['run']) || $_GET['run'] != '1') {
    $message = 'Nhấn nút "Chạy script" bên dưới để bắt đầu thêm chuyến xe.';
    $messageType = 'info';
} else {
try {
    // Lấy tất cả nhà xe đã được duyệt
    $stmt = $db->query("SELECT partner_id, name FROM partners WHERE status = 'approved' ORDER BY partner_id");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($partners)) {
        throw new Exception('Không có nhà xe nào được duyệt.');
    }
    
    $totalTripsAdded = 0;
    $date = date('Y-m-d', strtotime('+1 day')); // Ngày mai
    
    foreach ($partners as $partner) {
        $partnerId = $partner['partner_id'];
        $partnerName = $partner['name'];
        
        // Lấy vehicle_id đầu tiên của nhà xe này
        $stmt = $db->prepare("SELECT vehicle_id, total_seats FROM vehicles WHERE partner_id = ? LIMIT 1");
        $stmt->execute([$partnerId]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vehicle) {
            $results[] = [
                'partner' => $partnerName,
                'status' => 'skip',
                'message' => 'Không có xe nào'
            ];
            continue;
        }
        
        $vehicleId = $vehicle['vehicle_id'];
        $totalSeats = $vehicle['total_seats'];
        
        // Lấy driver_id đầu tiên của nhà xe này (nếu có)
        $stmt = $db->prepare("SELECT driver_id FROM drivers WHERE partner_id = ? LIMIT 1");
        $stmt->execute([$partnerId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        $driverId = $driver ? $driver['driver_id'] : null;
        
        $partnerTripsAdded = 0;
        
        // Xử lý từng tuyến phổ biến
        foreach ($popularRoutes as $route) {
            $from = $route['from'];
            $to = $route['to'];
            $basePrice = $route['base_price'];
            $durationHours = $route['duration'];
            
            // Tìm hoặc tạo route
            $stmt = $db->prepare("
                SELECT route_id FROM routes 
                WHERE (start_point = ? AND end_point = ?) 
                   OR (origin = ? AND destination = ?)
                LIMIT 1
            ");
            $stmt->execute([$from, $to, $from, $to]);
            $existingRoute = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingRoute) {
                $routeId = $existingRoute['route_id'];
            } else {
                // Tạo route mới
                try {
                    $stmt = $db->prepare("
                        INSERT INTO routes (route_name, origin, destination, start_point, end_point, base_price, duration_hours, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $routeName = "$from - $to";
                    $stmt->execute([$routeName, $from, $to, $from, $to, $basePrice, $durationHours]);
                    $routeId = $db->lastInsertId();
                } catch (PDOException $e) {
                    // Nếu không có cột origin/destination, thử insert chỉ với start_point/end_point
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO routes (start_point, end_point, base_price, duration_hours, status) 
                            VALUES (?, ?, ?, ?, 'active')
                        ");
                    $stmt->execute([$from, $to, $basePrice, $durationHours]);
                    $routeId = $db->lastInsertId();
                } catch (PDOException $e2) {
                    // Nếu vẫn lỗi, bỏ qua tuyến này
                    continue;
                }
            }
            
            // Lấy duration từ route nếu chưa có
            if (!$durationHours || $durationHours == 0) {
                $stmt = $db->prepare("SELECT duration_hours FROM routes WHERE route_id = ?");
                $stmt->execute([$routeId]);
                $routeData = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($routeData && $routeData['duration_hours']) {
                    $durationHours = $routeData['duration_hours'];
                } else {
                    $durationHours = 6; // Mặc định 6 giờ nếu không có
                }
                }
            }
            
            // Thêm 3 chuyến xe với thời gian rải đều
            foreach ($departureTimes as $depTime) {
                $departureDateTime = "$date $depTime:00";
                $departureTimestamp = strtotime($departureDateTime);
                $arrivalTimestamp = $departureTimestamp + ($durationHours * 3600);
                $arrivalDateTime = date('Y-m-d H:i:s', $arrivalTimestamp);
                
                // Kiểm tra xem chuyến đã tồn tại chưa
                $stmt = $db->prepare("
                    SELECT trip_id FROM trips 
                    WHERE partner_id = ? AND route_id = ? AND departure_time = ?
                    LIMIT 1
                ");
                $stmt->execute([$partnerId, $routeId, $departureDateTime]);
                
                if ($stmt->fetch()) {
                    continue; // Chuyến đã tồn tại, bỏ qua
                }
                
                // Thêm chuyến mới
                $stmt = $db->prepare("
                    INSERT INTO trips 
                    (partner_id, route_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')
                ");
                $stmt->execute([
                    $partnerId,
                    $routeId,
                    $vehicleId,
                    $driverId,
                    $departureDateTime,
                    $arrivalDateTime,
                    $basePrice,
                    $totalSeats
                ]);
                
                $partnerTripsAdded++;
                $totalTripsAdded++;
            }
        }
        
        $results[] = [
            'partner' => $partnerName,
            'status' => 'success',
            'trips' => $partnerTripsAdded,
            'message' => "Đã thêm $partnerTripsAdded chuyến"
        ];
    }
    
    $message = "Hoàn thành! Đã thêm tổng cộng $totalTripsAdded chuyến xe cho " . count($partners) . " nhà xe.";
    $messageType = 'success';
    
} catch (Exception $e) {
    $message = 'Lỗi: ' . $e->getMessage();
    $messageType = 'danger';
}
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm chuyến xe phổ biến</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-bus"></i> Thêm chuyến xe cho các tuyến phổ biến</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h5>Các tuyến phổ biến sẽ được thêm:</h5>
                        <ul>
                            <?php foreach ($popularRoutes as $route): ?>
                                <li><?php echo htmlspecialchars($route['from'] . ' - ' . $route['to']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <p><strong>Thời gian khởi hành:</strong> 12:00, 16:00, 20:00</p>
                        <p><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime('+1 day')); ?></p>
                        
                        <?php if (!empty($results)): ?>
                            <h5 class="mt-4">Kết quả:</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nhà xe</th>
                                            <th>Trạng thái</th>
                                            <th>Số chuyến đã thêm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['partner']); ?></td>
                                                <td>
                                                    <?php if ($result['status'] === 'success'): ?>
                                                        <span class="badge bg-success">Thành công</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Bỏ qua</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $result['trips'] ?? 0; ?>
                                                    <?php if (isset($result['message'])): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($result['message']); ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="admin_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Quay lại Dashboard
                            </a>
                            <?php if (!isset($_GET['run']) || $_GET['run'] != '1'): ?>
                                <a href="?run=1" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Chạy script
                                </a>
                            <?php else: ?>
                                <a href="?" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Chạy lại
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

