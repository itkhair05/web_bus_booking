<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();
$operator_id = getCurrentOperator();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// === LẤY DỮ LIỆU LỌC (nếu có) ===
$filter_route_id = (int)($_GET['route_id'] ?? 0);
$filter_status = $_GET['status'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';

// === XỬ LÝ AJAX: THÊM TUYẾN ĐƯỜNG MỚI ===
if (isset($_POST['action']) && $_POST['action'] === 'add_route_ajax') {
    header('Content-Type: application/json');
    $start = trim($_POST['start_point'] ?? '');
    $end = trim($_POST['end_point'] ?? '');
    if (!$start || !$end) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập điểm đi và điểm đến.']);
        exit;
    }
    try {
        // Kiểm tra route đã tồn tại (check start_point/end_point)
        // Thử check với origin/destination nếu có, nếu không thì chỉ check start_point/end_point
        try {
            $stmt = $db->prepare("SELECT route_id FROM routes WHERE (start_point=? AND end_point=?) OR (origin=? AND destination=?)");
            $stmt->execute([$start, $end, $start, $end]);
        } catch (PDOException $e) {
            // Nếu lỗi (có thể do thiếu cột origin), chỉ check start_point/end_point
            $stmt = $db->prepare("SELECT route_id FROM routes WHERE start_point=? AND end_point=?");
            $stmt->execute([$start, $end]);
        }
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            echo json_encode([
                'success' => true,
                'route_id' => $existing['route_id'],
                'label' => htmlspecialchars("$start → $end")
            ]);
            exit;
        }

        // Tạo route_name từ start và end
        $route_name = "$start - $end";
        
        // Insert đầy đủ các cột bắt buộc vào routes
        // Thử insert với đầy đủ cột, nếu lỗi thì fallback về cột cơ bản
        try {
            // Thử insert với origin và destination (nếu có)
            $stmt = $db->prepare("INSERT INTO routes (route_name, origin, destination, start_point, end_point, base_price, status) VALUES (?, ?, ?, ?, ?, 0, 'active')");
            $stmt->execute([$route_name, $start, $end, $start, $end]);
        } catch (PDOException $e) {
            // Nếu lỗi (có thể do thiếu cột), thử insert chỉ với start_point và end_point
            try {
                $stmt = $db->prepare("INSERT INTO routes (start_point, end_point, base_price, status) VALUES (?, ?, 0, 'active')");
                $stmt->execute([$start, $end]);
            } catch (PDOException $e2) {
                // Nếu vẫn lỗi, throw exception
                throw new Exception("Không thể thêm tuyến đường vào database: " . $e2->getMessage());
            }
        }
        
        $route_id = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'route_id' => $route_id,
            'label' => htmlspecialchars("$start → $end")
        ]);
        exit;
    } catch (Exception $e) {
        // Log lỗi để debug
        error_log("Error adding route: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Lỗi hệ thống: ' . ($e->getMessage() ?? 'Không thể thêm tuyến đường')
        ]);
        exit;
    }
}

// === XỬ LÝ THÊM CHUYẾN ===
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $route_id = (int)($_POST['route_id'] ?? 0);
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $driver_id = (int)($_POST['driver_id'] ?? 0);
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $price = (int)($_POST['price'] ?? 0);

    if (!$route_id || !$vehicle_id || !$driver_id || !$departure_time || !$arrival_time || !$price) {
        $message = 'Vui lòng điền đầy đủ thông tin.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $db->prepare("SELECT total_seats FROM vehicles WHERE vehicle_id = ? AND partner_id = ?");
            $stmt->execute([$vehicle_id, $operator_id]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$vehicle) throw new Exception('Xe không hợp lệ.');

            if (strtotime($arrival_time) <= strtotime($departure_time)) {
                throw new Exception('Giờ đến phải sau giờ khởi hành.');
            }

            $stmt = $db->prepare("INSERT INTO trips 
                (partner_id, route_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$operator_id, $route_id, $vehicle_id, $driver_id, $departure_time, $arrival_time, $price, $vehicle['total_seats']]);

            $message = 'Thêm chuyến xe thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// === CẬP NHẬT TRẠNG THÁI ===
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $trip_id = (int)($_POST['trip_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['scheduled', 'open', 'completed', 'cancelled'];
    if (!$trip_id || !in_array($status, $allowed)) {
        $message = 'Dữ liệu không hợp lệ.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $db->prepare("UPDATE trips SET status = ? WHERE trip_id = ? AND partner_id = ?");
            $stmt->execute([$status, $trip_id, $operator_id]);
            $message = 'Cập nhật trạng thái thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// === XÓA CHUYẾN ===
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $trip_id = (int)($_POST['trip_id'] ?? 0);
    if (!$trip_id) {
        $message = 'Dữ liệu không hợp lệ.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as sold FROM tickets WHERE trip_id = ? AND status IN ('active','checked_in','used')");
            $stmt->execute([$trip_id]);
            $sold = (int)$stmt->fetch(PDO::FETCH_ASSOC)['sold'];

            if ($sold > 0) {
                throw new Exception("Không thể xóa vì đã có $sold vé được bán.");
            }

            $stmt = $db->prepare("DELETE FROM trips WHERE trip_id = ? AND partner_id = ?");
            $stmt->execute([$trip_id, $operator_id]);

            $message = 'Xóa chuyến xe thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// === SỬA CHUYẾN ===
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $trip_id = (int)($_POST['trip_id'] ?? 0);
    $route_id = (int)($_POST['route_id'] ?? 0);
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $driver_id = (int)($_POST['driver_id'] ?? 0);
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $price = (int)($_POST['price'] ?? 0);

    if (!$trip_id || !$route_id || !$vehicle_id || !$driver_id || !$departure_time || !$arrival_time || !$price) {
        $message = 'Vui lòng điền đầy đủ.';
        $message_type = 'danger';
    } else {
        try {
            if (strtotime($arrival_time) <= strtotime($departure_time)) {
                throw new Exception('Giờ đến phải sau giờ đi.');
            }

            $stmt = $db->prepare("SELECT COUNT(*) as sold FROM tickets WHERE trip_id = ? AND status IN ('active','checked_in','used')");
            $stmt->execute([$trip_id]);
            $sold = (int)$stmt->fetch(PDO::FETCH_ASSOC)['sold'];

            $stmt = $db->prepare("SELECT total_seats FROM vehicles WHERE vehicle_id = ? AND partner_id = ?");
            $stmt->execute([$vehicle_id, $operator_id]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$vehicle) throw new Exception('Xe không hợp lệ.');
            $available = $vehicle['total_seats'] - $sold;
            if ($available < 0) throw new Exception("Đã bán $sold vé, xe mới chỉ có {$vehicle['total_seats']} ghế.");

            $stmt = $db->prepare("UPDATE trips SET route_id=?, vehicle_id=?, driver_id=?, departure_time=?, arrival_time=?, price=?, available_seats=? WHERE trip_id=? AND partner_id=?");
            $stmt->execute([$route_id, $vehicle_id, $driver_id, $departure_time, $arrival_time, $price, $available, $trip_id, $operator_id]);

            $message = 'Cập nhật thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// === LẤY DỮ LIỆU (CÓ LỌC) ===
$sql = "
    SELECT t.*, r.start_point, r.end_point, v.license_plate, v.type AS vehicle_type, v.total_seats, d.name AS driver_name
    FROM trips t
    JOIN routes r ON t.route_id = r.route_id
    JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    LEFT JOIN drivers d ON t.driver_id = d.driver_id
    WHERE t.partner_id = ?
";
$params = [$operator_id];

if ($filter_route_id) {
    $sql .= " AND t.route_id = ?";
    $params[] = $filter_route_id;
}
if ($filter_status) {
    $sql .= " AND t.status = ?";
    $params[] = $filter_status;
}
if ($filter_from) {
    $sql .= " AND DATE(t.departure_time) >= ?";
    $params[] = $filter_from;
}
if ($filter_to) {
    $sql .= " AND DATE(t.departure_time) <= ?";
    $params[] = $filter_to;
}
$sql .= " ORDER BY t.departure_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === LẤY DANH SÁCH TUYẾN ĐƯỜNG ===
$routes = $db->query("SELECT * FROM routes ORDER BY start_point, end_point")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM vehicles WHERE partner_id = ? ORDER BY license_plate");
$stmt->execute([$operator_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM drivers WHERE partner_id = ? ORDER BY name");
$stmt->execute([$operator_id]);
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý chuyến xe - <?= htmlspecialchars($_SESSION['company_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1E90FF;
      --primary-hover: #1873CC;
      --secondary: #17a2b8;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --info: #0dcaf0;
      --dark: #1f2937;
      --light: #f8fafc;
      --gray: #94a3b8;
      --border: #e2e8f0;
    }

    * { font-family: 'Inter', sans-serif; }
    body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; }

    .sidebar {
      position: fixed; top: 0; left: 0; width: 280px; height: 100vh;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white; z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,0.1);
    }
    .brand { padding: 1.8rem 1.5rem; font-weight: 700; font-size: 1.4rem; border-bottom: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 12px; }
    .nav-link { color: rgba(255,255,255,0.85); padding: 0.9rem 1.5rem; display: flex; align-items: center; gap: 12px; transition: all 0.25s ease; border-left: 3px solid transparent; }
    .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.15); color: white; border-left-color: white; transform: translateX(4px); }
    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    .main-content { margin-left: 280px; padding: 2rem; }
    .page-header { background: white; padding: 1.5rem 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-weight: 700; color: var(--dark); font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }

    .filter-card { background: white; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 1.2rem; margin-bottom: 1.5rem; }
    .filter-title { font-weight: 600; color: var(--dark); margin-bottom: 0.8rem; font-size: 1rem; }

    .table-card { background: white; border-radius: 16px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); overflow: hidden; }
    .table thead { background: #f8fafc; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; color: #64748b; }
    .table tbody tr { transition: all 0.2s; }
    .table tbody tr:hover { background-color: #f1f5f9; transform: translateY(-1px); }

    .badge { font-weight: 600; padding: 0.4em 0.8em; border-radius: 8px; font-size: 0.8rem; }
    .progress { height: 6px; background: #e2e8f0; border-radius: 3px; }
    .progress-bar { background: linear-gradient(90deg, var(--primary), var(--secondary)); }

    .action-btn {
      padding: 0.35rem 0.65rem; font-size: 0.8rem; border-radius: 8px; margin: 0 2px;
      min-width: 36px; display: inline-flex; align-items: center; justify-content: center;
      gap: 4px; transition: all 0.2s ease;
    }
    .action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }

    .modal-content { border-radius: 16px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
    .modal-header { background: linear-gradient(135deg, #1f2937, #111827); color: white; border-radius: 16px 16px 0 0; }
    .form-label { font-weight: 600; color: #374151; }
    .form-control, .form-select { border-radius: 12px; border: 1.5px solid var(--border); padding: 0.65rem 1rem; font-size: 0.95rem; }
    .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.2); }

    .input-group-text { background: var(--light); border-color: var(--border); }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in-up { animation: fadeInUp 0.6s ease-out; }

    @media (max-width: 992px) {
      .sidebar { width: 80px; }
      .sidebar .brand, .nav-link span { display: none; }
      .nav-link { justify-content: center; }
      .main-content { margin-left: 80px; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="brand">
      <i class="fas fa-bus"></i>
      <span><?= htmlspecialchars($_SESSION['company_name']) ?></span>
    </div>
    <nav class="nav flex-column mt-3">
      <a class="nav-link" href="../partner/dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a>
      <a class="nav-link active" href="../partner/trips.php"><i class="fas fa-route"></i><span>Chuyến xe</span></a>
      <a class="nav-link" href="../partner/tickets.php"><i class="fas fa-ticket-alt"></i><span>Đặt vé</span></a>
      <a class="nav-link" href="../partner/operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../partner/reports.php"><i class="fas fa-chart-bar"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../partner/feedback.php"><i class="fas fa-star"></i><span>Phản hồi</span></a>
      <a class="nav-link" href="../partner/notifications.php"><i class="fas fa-bell"></i><span>Thông báo</span></a>
      <a class="nav-link" href="../partner/settings.php"><i class="fas fa-cog"></i><span>Cài đặt</span></a>
      
      <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
      <div style="padding: 0 15px; margin-bottom: 10px;">
        <small style="color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Chuyển giao diện</small>
      </div>
      <a class="nav-link" href="../index.php" style="background: rgba(59, 130, 246, 0.1);"><i class="fas fa-home"></i><span>Giao diện User</span></a>
      
      <a class="nav-link" href="<?php echo appUrl('partner/auth/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="page-header fade-in-up">
      <div>
        <h1 class="page-title">Quản lý chuyến xe</h1>
        <p class="text-muted mb-0">Thêm, sửa, xóa, lọc và quản lý trạng thái</p>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTripModal">
        Thêm chuyến mới
      </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show fade-in-up" role="alert">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Bộ lọc -->
    <div class="filter-card fade-in-up">
      <div class="filter-title">Lọc chuyến xe</div>
      <form method="GET" id="filterForm" class="row g-3">
        <div class="col-md-3">
          <select name="route_id" class="form-select">
            <option value="">Tất cả tuyến đường</option>
            <?php foreach ($routes as $r): ?>
              <option value="<?= $r['route_id'] ?>" <?= $filter_route_id == $r['route_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['start_point'] . ' → ' . $r['end_point']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">Tất cả trạng thái</option>
            <option value="scheduled" <?= $filter_status === 'scheduled' ? 'selected' : '' ?>>Đã lên lịch</option>
            <option value="open" <?= $filter_status === 'open' ? 'selected' : '' ?>>Mở bán</option>
            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
            <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filter_from) ?>" placeholder="Từ ngày">
        </div>
        <div class="col-md-2">
          <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filter_to) ?>" placeholder="Đến ngày">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">
            Lọc
          </button>
          <a href="trips.php" class="btn btn-outline-secondary">
            Xóa lọc
          </a>
        </div>
      </form>
    </div>

    <!-- Bảng chuyến xe -->
    <div class="table-card fade-in-up">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Tuyến đường</th>
              <th>Xe</th>
              <th>Tài xế</th>
              <th>Giờ khởi hành</th>
              <th>Giá vé</th>
              <th>Ghế</th>
              <th>Trạng thái</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($trips): ?>
              <?php foreach ($trips as $t): ?>
              <tr data-trip-id="<?= $t['trip_id'] ?>">
                <td><div class="fw-bold"><?= htmlspecialchars($t['start_point'] . ' → ' . $t['end_point']) ?></div></td>
                <td><div><?= htmlspecialchars($t['license_plate']) ?></div><small class="text-muted"><?= htmlspecialchars($t['vehicle_type']) ?></small></td>
                <td><?= htmlspecialchars($t['driver_name'] ?? 'Chưa có') ?></td>
                <td><div><?= date('d/m/Y', strtotime($t['departure_time'])) ?></div><small class="text-muted"><?= date('H:i', strtotime($t['departure_time'])) ?></small></td>
                <td><strong><?= number_format($t['price']) ?>đ</strong></td>
                <td>
                  <div><?= $t['available_seats'] . '/' . $t['total_seats'] ?></div>
                  <div class="progress mt-1"><div class="progress-bar" style="width: <?= $t['total_seats'] > 0 ? (($t['total_seats'] - $t['available_seats']) / $t['total_seats'] * 100) : 0 ?>%"></div></div>
                </td>
                <td>
                  <?php
                    $statusMap = [
                      'scheduled' => ['bg-secondary', 'Đã lên lịch'],
                      'open' => ['bg-success', 'Mở bán'],
                      'cancelled' => ['bg-danger', 'Đã hủy'],
                      'completed' => ['bg-info text-dark', 'Hoàn thành']
                    ];
                    $s = $statusMap[$t['status']] ?? ['bg-dark', 'Không rõ'];
                  ?>
                  <span class="badge <?= $s[0] ?>"><?= $s[1] ?></span>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1 flex-wrap">
                    <button class="btn btn-outline-primary action-btn" onclick="editTrip(<?= $t['trip_id'] ?>)" title="Sửa">
                      <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($t['status'] !== 'open'): ?>
                      <button class="btn btn-success action-btn" onclick="updateStatus(<?= $t['trip_id'] ?>, 'open')" title="Mở bán">
                        <i class="fas fa-play"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ($t['status'] !== 'cancelled'): ?>
                      <button class="btn btn-danger action-btn" onclick="updateStatus(<?= $t['trip_id'] ?>, 'cancelled')" title="Hủy">
                        <i class="fas fa-times"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ($t['status'] !== 'completed'): ?>
                      <button class="btn btn-info action-btn text-white" onclick="updateStatus(<?= $t['trip_id'] ?>, 'completed')" title="Hoàn thành">
                        <i class="fas fa-check"></i>
                      </button>
                    <?php endif; ?>
                    <button class="btn btn-outline-danger action-btn" onclick="deleteTrip(<?= $t['trip_id'] ?>)" title="Xóa">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center text-muted py-5">Không tìm thấy chuyến xe nào</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Các Modal (giữ nguyên như cũ) -->
  <!-- Add Modal, Edit Modal, Forms... -->

  <!-- Add Modal -->
  <div class="modal fade" id="addTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Thêm chuyến xe mới</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" id="addForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="route_id" id="add_route_id" value="">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Tuyến đường *</label>
                <div class="input-group">
                  <select class="form-select" id="add_route_select">
                    <option value="">Chọn tuyến có sẵn</option>
                    <?php foreach ($routes as $r): ?>
                      <option value="<?= $r['route_id'] ?>"><?= htmlspecialchars($r['start_point'] . ' → ' . $r['end_point']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline-secondary" type="button" id="add_toggle_new">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
                <div id="add_new_route" class="mt-2" style="display: none;">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Hà Nội" id="start_point">
                    <span class="input-group-text">→</span>
                    <input type="text" class="form-control" placeholder="TP.HCM" id="end_point">
                    <button class="btn btn-primary" type="button" id="add_route_btn">Thêm</button>
                  </div>
                  <small class="text-muted">Hoặc nhập: <code>Hà Nội → TP.HCM</code></small>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Xe *</label>
                <select class="form-select" name="vehicle_id" required>
                  <option value="">Chọn xe</option>
                  <?php foreach ($vehicles as $v): ?>
                    <option value="<?= $v['vehicle_id'] ?>"><?= htmlspecialchars($v['license_plate'] . ' - ' . $v['type']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tài xế *</label>
                <select class="form-select" name="driver_id" required>
                  <option value="">Chọn tài xế</option>
                  <?php foreach ($drivers as $d): ?>
                    <option value="<?= $d['driver_id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Giá vé (VND) *</label>
                <input type="number" class="form-control" name="price" min="0" step="1000" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Giờ khởi hành *</label>
                <input type="datetime-local" class="form-control" name="departure_time" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Giờ đến *</label>
                <input type="datetime-local" class="form-control" name="arrival_time" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit">Thêm chuyến</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Chỉnh sửa chuyến xe</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" id="editForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="trip_id" id="edit_trip_id">
            <input type="hidden" name="route_id" id="edit_route_id" value="">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Tuyến đường *</label>
                <div class="input-group">
                  <select class="form-select" id="edit_route_select">
                    <option value="">Chọn tuyến</option>
                    <?php foreach ($routes as $r): ?>
                      <option value="<?= $r['route_id'] ?>"><?= htmlspecialchars($r['start_point'] . ' → ' . $r['end_point']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline-secondary" type="button" id="edit_toggle_new">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
                <div id="edit_new_route" class="mt-2" style="display: none;">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Hà Nội" id="edit_start">
                    <span class="input-group-text">→</span>
                    <input type="text" class="form-control" placeholder="TP.HCM" id="edit_end">
                    <button class="btn btn-primary" type="button" id="edit_route_btn">Thêm</button>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Xe *</label>
                <select class="form-select" name="vehicle_id" id="edit_vehicle_id" required>
                  <?php foreach ($vehicles as $v): ?>
                    <option value="<?= $v['vehicle_id'] ?>"><?= htmlspecialchars($v['license_plate'] . ' - ' . $v['type']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tài xế *</label>
                <select class="form-select" name="driver_id" id="edit_driver_id" required>
                  <?php foreach ($drivers as $d): ?>
                    <option value="<?= $d['driver_id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Giá vé *</label>
                <input type="number" class="form-control" name="price" id="edit_price" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Khởi hành *</label>
                <input type="datetime-local" class="form-control" name="departure_time" id="edit_departure_time" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Đến nơi *</label>
                <input type="datetime-local" class="form-control" name="arrival_time" id="edit_arrival_time" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit">Cập nhật</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Forms ẩn -->
  <form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="trip_id" id="status_trip_id">
    <input type="hidden" name="status" id="status_value">
  </form>
  <form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="trip_id" id="delete_trip_id">
  </form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const trips = <?= json_encode($trips) ?>;

    // Toggle new route
    document.getElementById('add_toggle_new').addEventListener('click', () => {
      const div = document.getElementById('add_new_route');
      div.style.display = div.style.display === 'none' ? 'block' : 'none';
    });
    document.getElementById('edit_toggle_new').addEventListener('click', () => {
      const div = document.getElementById('edit_new_route');
      div.style.display = div.style.display === 'none' ? 'block' : 'none';
    });

    // Đồng bộ khi chọn tuyến có sẵn
    document.getElementById('add_route_select').addEventListener('change', function() {
      document.getElementById('add_route_id').value = this.value;
      document.getElementById('add_new_route').style.display = 'none';
    });

    // Thêm tuyến mới (AJAX)
    document.getElementById('add_route_btn').addEventListener('click', async () => {
      const start = document.getElementById('start_point').value.trim();
      const end = document.getElementById('end_point').value.trim();
      if (!start || !end) return alert('Nhập điểm đi và điểm đến.');

      const res = await fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_route_ajax&start_point=${encodeURIComponent(start)}&end_point=${encodeURIComponent(end)}`
      });
      const data = await res.json();
      if (data.success) {
        const routeId = data.route_id;
        document.getElementById('add_route_id').value = routeId;

        const select = document.getElementById('add_route_select');
        const option = new Option(data.label, routeId, true, true);
        select.add(option);

        document.getElementById('start_point').value = '';
        document.getElementById('end_point').value = '';
        document.getElementById('add_new_route').style.display = 'none';
      } else {
        alert(data.message);
      }
    });

    // Edit route AJAX
    document.getElementById('edit_route_btn').addEventListener('click', async () => {
      const start = document.getElementById('edit_start').value.trim();
      const end = document.getElementById('edit_end').value.trim();
      if (!start || !end) return alert('Nhập điểm đi và điểm đến.');

      const res = await fetch('', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
        body: `action=add_route_ajax&start_point=${encodeURIComponent(start)}&end_point=${encodeURIComponent(end)}` 
      });
      const data = await res.json();
      if (data.success) {
        document.getElementById('edit_route_id').value = data.route_id;
        const select = document.getElementById('edit_route_select');
        const option = new Option(data.label, data.route_id, true, true);
        select.add(option);
        document.getElementById('edit_new_route').style.display = 'none';
      } else {
        alert(data.message);
      }
    });

    // Edit trip
    function editTrip(tripId) {
      const trip = trips.find(t => t.trip_id == tripId);
      if (!trip) return alert('Không tìm thấy.');

      document.getElementById('edit_trip_id').value = trip.trip_id;
      document.getElementById('edit_route_id').value = trip.route_id;
      document.getElementById('edit_route_select').value = trip.route_id;
      document.getElementById('edit_vehicle_id').value = trip.vehicle_id;
      document.getElementById('edit_driver_id').value = trip.driver_id;
      document.getElementById('edit_price').value = trip.price;
      document.getElementById('edit_departure_time').value = new Date(trip.departure_time).toISOString().slice(0, 16);
      document.getElementById('edit_arrival_time').value = new Date(trip.arrival_time).toISOString().slice(0, 16);

      document.getElementById('edit_new_route').style.display = 'none';
      new bootstrap.Modal(document.getElementById('editTripModal')).show();
    }

    function updateStatus(tripId, status) {
      if (confirm('Xác nhận thay đổi trạng thái?')) {
        document.getElementById('status_trip_id').value = tripId;
        document.getElementById('status_value').value = status;
        document.getElementById('statusForm').submit();
      }
    }

    function deleteTrip(tripId) {
      if (confirm('Bạn có chắc chắn muốn XÓA chuyến xe này?\n\nCảnh báo: Không thể xóa nếu đã có vé bán!')) {
        document.getElementById('delete_trip_id').value = tripId;
        document.getElementById('deleteForm').submit();
      }
    }
  </script>
</body>
</html>