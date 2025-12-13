<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();
$partner_id = getCurrentOperator();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';
$company_name = $_SESSION['company_name'] ?? 'Nhà xe';

// === XỬ LÝ POST (THÊM / SỬA) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_vehicle':
                $license_plate = trim($_POST['license_plate'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $total_seats = (int)($_POST['total_seats'] ?? 0);

                if (!$license_plate || !$type || $total_seats < 16 || $total_seats > 60) {
                    throw new Exception('Vui lòng điền đầy đủ: Biển số, loại xe, số ghế (16-60).');
                }

                $stmt = $db->prepare("INSERT INTO vehicles (partner_id, license_plate, type, total_seats, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$partner_id, $license_plate, $type, $total_seats]);
                $message = 'Thêm xe thành công!';
                $message_type = 'success';
                break;

            case 'edit_vehicle':
                $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
                $license_plate = trim($_POST['license_plate'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $total_seats = (int)($_POST['total_seats'] ?? 0);

                if ($vehicle_id <= 0 || !$license_plate || !$type || $total_seats < 16 || $total_seats > 60) {
                    throw new Exception('Thông tin xe không hợp lệ.');
                }

                $stmt = $db->prepare("UPDATE vehicles SET license_plate = ?, type = ?, total_seats = ? WHERE vehicle_id = ? AND partner_id = ?");
                $stmt->execute([$license_plate, $type, $total_seats, $vehicle_id, $partner_id]);
                $message = 'Cập nhật xe thành công!';
                $message_type = 'success';
                break;

            case 'add_driver':
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $license_number = trim($_POST['license_number'] ?? '');

                if (!$name) {
                    throw new Exception('Vui lòng nhập họ tên tài xế.');
                }
                if ($phone && !preg_match('/^0[3|5|7|8|9]\d{8}$/', $phone)) {
                    throw new Exception('Số điện thoại không hợp lệ (10 số, bắt đầu 03,05,07,08,09).');
                }

                $stmt = $db->prepare("INSERT INTO drivers (partner_id, name, phone, license_number, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$partner_id, $name, $phone, $license_number]);
                $message = 'Thêm tài xế thành công!';
                $message_type = 'success';
                break;

            case 'edit_driver':
                $driver_id = (int)($_POST['driver_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $license_number = trim($_POST['license_number'] ?? '');

                if ($driver_id <= 0 || !$name) {
                    throw new Exception('Thông tin tài xế không hợp lệ.');
                }
                if ($phone && !preg_match('/^0[3|5|7|8|9]\d{8}$/', $phone)) {
                    throw new Exception('Số điện thoại không hợp lệ.');
                }

                $stmt = $db->prepare("UPDATE drivers SET name = ?, phone = ?, license_number = ? WHERE driver_id = ? AND partner_id = ?");
                $stmt->execute([$name, $phone, $license_number, $driver_id, $partner_id]);
                $message = 'Cập nhật tài xế thành công!';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// === XÓA XE / TÀI XẾ ===
if (isset($_GET['delete_vehicle']) && is_numeric($_GET['delete_vehicle'])) {
    $vehicle_id = (int)$_GET['delete_vehicle'];
    try {
        $stmt = $db->prepare("DELETE FROM vehicles WHERE vehicle_id = ? AND partner_id = ?");
        $stmt->execute([$vehicle_id, $partner_id]);
        $message = 'Xóa xe thành công!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Không thể xóa xe đang có chuyến.';
        $message_type = 'danger';
    }
}

if (isset($_GET['delete_driver']) && is_numeric($_GET['delete_driver'])) {
    $driver_id = (int)$_GET['delete_driver'];
    try {
        $stmt = $db->prepare("DELETE FROM drivers WHERE driver_id = ? AND partner_id = ?");
        $stmt->execute([$driver_id, $partner_id]);
        $message = 'Xóa tài xế thành công!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Không thể xóa tài xế đang có chuyến.';
        $message_type = 'danger';
    }
}

// === LẤY DỮ LIỆU ===
$vehicles = [];
$drivers = [];

try {
    $stmt = $db->prepare("SELECT vehicle_id, license_plate, type, total_seats, created_at FROM vehicles WHERE partner_id = ? ORDER BY created_at DESC");
    $stmt->execute([$partner_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT driver_id, name, phone, license_number, created_at FROM drivers WHERE partner_id = ? ORDER BY created_at DESC");
    $stmt->execute([$partner_id]);
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý vận hành - <?= htmlspecialchars($company_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1E90FF;
      --primary-hover: #1873CC; 
      --secondary: #17a2b8;
      --success: #10b981; --danger: #ef4444; --info: #0dcaf0; --dark: #1f2937;
      --light: #f8fafc; --gray: #94a3b8; --border: #e2e8f0;
    }
    * { font-family: 'Inter', sans-serif; }
    body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; }

    .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100vh; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,0.1); transition: all 0.3s ease; }
    .brand { padding: 1.8rem 1.5rem; font-weight: 700; font-size: 1.4rem; border-bottom: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 12px; }
    .nav-link { color: rgba(255,255,255,0.85); padding: 0.9rem 1.5rem; display: flex; align-items: center; gap: 12px; transition: all 0.25s ease; border-left: 3px solid transparent; }
    .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.15); color: white; border-left-color: white; transform: translateX(4px); }
    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    .main-content { margin-left: 280px; padding: 2rem; transition: all 0.3s ease; }
    .page-header { background: white; padding: 1.5rem 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-weight: 700; color: var(--dark); font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }

    .filter-card { background: white; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 1.5rem; margin-bottom: 1.5rem; text-align: center; }
    .table-card { background: white; border-radius: 16px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s ease; }
    .table-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.12); }
    .card-header { background: #f8fafc; border-bottom: 1px solid var(--border); font-weight: 600; color: var(--dark); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .btn-add { background: var(--primary); border: none; border-radius: 12px; padding: 0.5rem 1rem; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
    .btn-add:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(32, 201, 151, 0.3); }

    .table thead { background: #f8fafc; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; color: #64748b; }
    .table tbody tr { transition: all 0.2s; }
    .table tbody tr:hover { background-color: #f1f5f9; transform: translateY(-1px); }
    .badge { font-weight: 600; padding: 0.4em 0.8em; border-radius: 8px; font-size: 0.8rem; }

    .btn-sm { padding: 0.35rem 0.7rem; font-size: 0.85rem; border-radius: 8px; }
    .btn-outline-primary { border-color: #94a3b8; color: #64748b; }
    .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); color: white; }
    .btn-outline-danger { border-color: #fca5a5; color: #dc2626; }
    .btn-outline-danger:hover { background: var(--danger); border-color: var(--danger); color: white; }

    .modal-content { border-radius: 16px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
    .modal-header { background: linear-gradient(135deg, #1f2937, #111827); color: white; border-radius: 16px 16px 0 0; }
    .form-control, .form-select { border-radius: 12px; border: 1.5px solid var(--border); padding: 0.65rem 1rem; font-size: 0.95rem; }
    .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.2); }
    .form-label { font-weight: 600; color: #374151; }

    .alert { border-radius: 12px; font-weight: 500; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

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
      <span><?= htmlspecialchars($company_name) ?></span>
    </div>
    <nav class="nav flex-column mt-3">
      <a class="nav-link" href="../partner/dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a>
      <a class="nav-link" href="../partner/trips.php"><i class="fas fa-route"></i><span>Chuyến xe</span></a>
      <a class="nav-link" href="../partner/tickets.php"><i class="fas fa-ticket-alt"></i><span>Đặt vé</span></a>
      <a class="nav-link active" href="../partner/operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../partner/reports.php"><i class="fas fa-chart-bar"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../partner/feedback.php"><i class="fas fa-star"></i><span>Phản hồi</span></a>
      <a class="nav-link" href="../partner/notifications.php"><i class="fas fa-bell"></i><span>Thông báo</span></a>
      <a class="nav-link" href="../partner/settings.php"><i class="fas fa-cog"></i><span>Cài đặt</span></a>
      
      <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
      <div style="padding: 0 15px; margin-bottom: 10px;">
        <small style="color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Chuyển giao diện</small>
      </div>
      <a class="nav-link" href="../index.php" style="background: rgba(59, 130, 246, 0.1);"><i class="fas fa-home"></i><span>Giao diện User</span></a>
      
      <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="page-header fade-in-up">
      <h1 class="page-title">
        <i class="fas fa-cogs"></i> Quản lý vận hành
      </h1>
      <div class="text-muted">
        <small>Quản lý xe buýt & tài xế</small>
      </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show fade-in-up" role="alert">
      <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="filter-card fade-in-up">
      <small class="text-muted">
        Tổng: <strong><?= count($vehicles) ?></strong> xe buýt | 
        <strong><?= count($drivers) ?></strong> tài xế
      </small>
    </div>

    <!-- Vehicles & Drivers -->
    <div class="row g-4">
      <!-- Xe buýt -->
      <div class="col-lg-6 fade-in-up">
        <div class="table-card">
          <div class="card-header">
            <span><i class="fas fa-bus me-2"></i>Danh sách xe buýt</span>
            <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
              <i class="fas fa-plus"></i> Thêm xe
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Biển số</th>
                  <th>Loại xe</th>
                  <th>Số ghế</th>
                  <th>Ngày thêm</th>
                  <th class="text-center">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($vehicles): ?>
                  <?php foreach ($vehicles as $v): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($v['license_plate']) ?></strong></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($v['type']) ?></span></td>
                    <td><i class="fas fa-chair me-1"></i><?= $v['total_seats'] ?></td>
                    <td><small class="text-muted"><?= date('d/m/Y', strtotime($v['created_at'])) ?></small></td>
                    <td class="text-center">
                      <button class="btn btn-outline-primary btn-sm" onclick="editVehicle(<?= $v['vehicle_id'] ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="?delete_vehicle=<?= $v['vehicle_id'] ?>" class="btn btn-outline-danger btn-sm" 
                         onclick="return confirm('Xóa xe này? Dữ liệu không thể khôi phục.')">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                      <i class="fas fa-bus fa-2x mb-3 text-gray"></i><br>
                      Chưa có xe nào
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tài xế -->
      <div class="col-lg-6 fade-in-up">
        <div class="table-card">
          <div class="card-header">
            <span><i class="fas fa-id-card me-2"></i>Danh sách tài xế</span>
            <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addDriverModal">
              <i class="fas fa-plus"></i> Thêm tài xế
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Họ tên</th>
                  <th>SĐT</th>
                  <th>Bằng lái</th>
                  <th>Ngày thêm</th>
                  <th class="text-center">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($drivers): ?>
                  <?php foreach ($drivers as $d): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                    <td>
                      <?php if ($d['phone']): ?>
                        <a href="tel:<?= htmlspecialchars($d['phone']) ?>" class="text-decoration-none">
                          <i class="fas fa-phone me-1"></i><?= htmlspecialchars($d['phone']) ?>
                        </a>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($d['license_number'] ?: '—') ?></code></td>
                    <td><small class="text-muted"><?= date('d/m/Y', strtotime($d['created_at'])) ?></small></td>
                    <td class="text-center">
                      <button class="btn btn-outline-primary btn-sm" onclick="editDriver(<?= $d['driver_id'] ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="?delete_driver=<?= $d['driver_id'] ?>" class="btn btn-outline-danger btn-sm" 
                         onclick="return confirm('Xóa tài xế này? Dữ liệu không thể khôi phục.')">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                      <i class="fas fa-id-card fa-2x mb-3 text-gray"></i><br>
                      Chưa có tài xế nào
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Vehicle Modal -->
  <div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-bus me-2"></i>Thêm xe mới</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_vehicle">
            <div class="mb-3">
              <label class="form-label">Biển số xe *</label>
              <input name="license_plate" class="form-control" placeholder="VD: 51H-12345" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Loại xe *</label>
              <input name="type" class="form-control" placeholder="VD: Ghế ngồi 29 chỗ" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Tổng số ghế (16-60) *</label>
              <input type="number" min="16" max="60" name="total_seats" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Tạo xe</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Vehicle Modal -->
  <div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh sửa xe</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit_vehicle">
            <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
            <div class="mb-3">
              <label class="form-label">Biển số xe *</label>
              <input name="license_plate" id="edit_license_plate" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Loại xe *</label>
              <input name="type" id="edit_type" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Tổng số ghế (16-60) *</label>
              <input type="number" min="16" max="60" name="total_seats" id="edit_total_seats" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Cập nhật</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Driver Modal -->
  <div class="modal fade" id="addDriverModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Thêm tài xế mới</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_driver">
            <div class="mb-3">
              <label class="form-label">Họ và tên *</label>
              <input name="name" class="form-control" placeholder="Nguyễn Văn A" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Số điện thoại</label>
              <input name="phone" class="form-control" placeholder="0901234567">
            </div>
            <div class="mb-3">
              <label class="form-label">Số giấy phép lái xe</label>
              <input name="license_number" class="form-control" placeholder="B2-123456789">
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Tạo tài xế</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Driver Modal -->
  <div class="modal fade" id="editDriverModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Chỉnh sửa tài xế</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit_driver">
            <input type="hidden" name="driver_id" id="edit_driver_id">
            <div class="mb-3">
              <label class="form-label">Họ và tên *</label>
              <input name="name" id="edit_driver_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Số điện thoại</label>
              <input name="phone" id="edit_driver_phone" class="form-control" placeholder="0901234567">
            </div>
            <div class="mb-3">
              <label class="form-label">Số giấy phép lái xe</label>
              <input name="license_number" id="edit_driver_license" class="form-control" placeholder="B2-123456789">
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Cập nhật</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const vehicles = <?= json_encode($vehicles) ?>;
    const drivers = <?= json_encode($drivers) ?>;

    function editVehicle(id) {
      const v = vehicles.find(x => x.vehicle_id == id);
      if (!v) return alert('Không tìm thấy xe.');
      document.getElementById('edit_vehicle_id').value = v.vehicle_id;
      document.getElementById('edit_license_plate').value = v.license_plate;
      document.getElementById('edit_type').value = v.type;
      document.getElementById('edit_total_seats').value = v.total_seats;
      new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
    }

    function editDriver(id) {
      const d = drivers.find(x => x.driver_id == id);
      if (!d) return alert('Không tìm thấy tài xế.');
      document.getElementById('edit_driver_id').value = d.driver_id;
      document.getElementById('edit_driver_name').value = d.name;
      document.getElementById('edit_driver_phone').value = d.phone || '';
      document.getElementById('edit_driver_license').value = d.license_number || '';
      new bootstrap.Modal(document.getElementById('editDriverModal')).show();
    }
  </script>
</body>
</html>