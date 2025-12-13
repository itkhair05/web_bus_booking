<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_promotion':
                $code = trim($_POST['code']);
                $title = trim($_POST['title']);
                $description = $_POST['description'] ?? null;
                $discount_type = in_array($_POST['discount_type'], ['fixed','percentage']) ? $_POST['discount_type'] : 'fixed';
                $discount_value = (float)$_POST['discount_value'];
                $min_order_amount = isset($_POST['min_order_amount']) && $_POST['min_order_amount'] !== '' ? (float)$_POST['min_order_amount'] : 0.0;
                $max_discount_amount = isset($_POST['max_discount_amount']) && $_POST['max_discount_amount'] !== '' ? (float)$_POST['max_discount_amount'] : null;
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $usage_limit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
                $status = in_array($_POST['status'], ['active','expired','inactive']) ? $_POST['status'] : 'active';
                if (!$code || !$title || !$discount_value || !$start_date || !$end_date) throw new Exception('Vui lòng nhập đầy đủ thông tin bắt buộc.');
                $chk = $db->prepare("SELECT COUNT(*) FROM promotions WHERE code = ?");
                $chk->execute([$code]);
                if ($chk->fetchColumn() > 0) throw new Exception('Mã khuyến mãi đã tồn tại.');
                $stmt = $db->prepare("INSERT INTO promotions (code,title,description,discount_type,discount_value,min_order_amount,max_discount_amount,start_date,end_date,usage_limit,status,created_at)
                                      VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW())");
                $stmt->execute([$code,$title,$description,$discount_type,$discount_value,$min_order_amount,$max_discount_amount,$start_date,$end_date,$usage_limit,$status]);
                $message = 'Tạo khuyến mãi thành công!';
                $message_type = 'success';
                break;
            case 'update_promotion':
                $promotion_id = (int)$_POST['promotion_id'];
                $title = trim($_POST['title']);
                $description = $_POST['description'] ?? null;
                $discount_type = in_array($_POST['discount_type'], ['fixed','percentage']) ? $_POST['discount_type'] : 'fixed';
                $discount_value = (float)$_POST['discount_value'];
                $min_order_amount = isset($_POST['min_order_amount']) && $_POST['min_order_amount'] !== '' ? (float)$_POST['min_order_amount'] : 0.0;
                $max_discount_amount = isset($_POST['max_discount_amount']) && $_POST['max_discount_amount'] !== '' ? (float)$_POST['max_discount_amount'] : null;
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $usage_limit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
                $status = in_array($_POST['status'], ['active','expired','inactive']) ? $_POST['status'] : 'active';
                if (!$promotion_id || !$title || !$discount_value || !$start_date || !$end_date) throw new Exception('Thiếu thông tin bắt buộc.');
                $stmt = $db->prepare("UPDATE promotions SET title=?, description=?, discount_type=?, discount_value=?, min_order_amount=?, max_discount_amount=?, start_date=?, end_date=?, usage_limit=?, status=? WHERE promotion_id = ?");
                $stmt->execute([$title,$description,$discount_type,$discount_value,$min_order_amount,$max_discount_amount,$start_date,$end_date,$usage_limit,$status,$promotion_id]);
                $message = 'Cập nhật khuyến mãi thành công!';
                $message_type = 'success';
                break;
            case 'update_status':
                $promotion_id = (int)$_POST['promotion_id'];
                $status = in_array($_POST['status'], ['active','expired','inactive']) ? $_POST['status'] : 'inactive';
                $stmt = $db->prepare("UPDATE promotions SET status = ? WHERE promotion_id = ?");
                $stmt->execute([$status,$promotion_id]);
                $message = 'Cập nhật trạng thái thành công!';
                $message_type = 'success';
                break;
            case 'delete_promotion':
                $promotion_id = (int)$_POST['promotion_id'];
                if (!$promotion_id) throw new Exception('Thiếu thông tin khuyến mãi.');
                $stmt = $db->prepare("DELETE FROM promotions WHERE promotion_id = ?");
                $stmt->execute([$promotion_id]);
                $message = 'Đã xóa khuyến mãi.';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

$promotions = [];
try {
    $stmt = $db->query("SELECT promotion_id, code, title, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status FROM promotions ORDER BY created_at DESC LIMIT 500");
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý khuyến mãi</title>
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

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 280px;
      height: 100vh;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    .brand {
      padding: 1.8rem 1.5rem;
      font-weight: 700;
      font-size: 1.4rem;
      border-bottom: 1px solid rgba(255,255,255,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .nav-link {
      color: rgba(255,255,255,0.85);
      padding: 0.9rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.25s ease;
      border-left: 3px solid transparent;
    }

    .nav-link:hover, .nav-link.active {
      background: rgba(255,255,255,0.15);
      color: white;
      border-left-color: white;
      transform: translateX(4px);
    }

    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    /* Main */
    .main-content {
      margin-left: 280px;
      padding: 2rem;
      transition: all 0.3s ease;
    }

    .page-header {
      background: white;
      padding: 1.5rem 2rem;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .page-title {
      font-weight: 700;
      color: var(--dark);
      font-size: 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Search & Button */
    .search-input {
      max-width: 380px;
      border-radius: 12px;
      padding: 0.65rem 1rem;
      border: 1.5px solid var(--border);
      font-size: 0.95rem;
    }

    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.2);
    }

    .btn-primary {
      background: var(--primary);
      border: none;
      border-radius: 12px;
      padding: 0.65rem 1.3rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(32, 201, 151, 0.3);
    }

    /* Action Buttons */
    .action-btn {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      border: none;
    }

    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-activate { background: #ecfdf5; color: #16a34a; }
    .btn-activate:hover { background: #dcfce7; }

    .btn-deactivate { background: #fef3c7; color: #d97706; }
    .btn-deactivate:hover { background: #fde68a; }

    .btn-edit { background: #e0f2fe; color: #0ea5e9; }
    .btn-edit:hover { background: #bae6fd; }

    /* Table Card */
    .table-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .table-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }

    .table thead {
      background: #f8fafc;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
      color: #64748b;
    }

    .table tbody tr {
      transition: all 0.2s;
    }

    .table tbody tr:hover {
      background-color: #f1f5f9;
      transform: translateY(-1px);
    }

    .badge {
      font-weight: 600;
      padding: 0.4em 0.8em;
      border-radius: 8px;
      font-size: 0.8rem;
    }

    /* Modal */
    .modal-content {
      border-radius: 16px;
      border: none;
      box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    }

    .modal-header {
      background: linear-gradient(135deg, #1f2937, #111827);
      color: white;
      border-radius: 16px 16px 0 0;
    }

    .form-control, .form-select {
      border-radius: 12px;
      border: 1.5px solid var(--border);
      padding: 0.65rem 1rem;
      font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.2);
    }

    .form-label {
      font-weight: 600;
      color: #374151;
    }

    /* Alert */
    .alert {
      border-radius: 12px;
      font-weight: 500;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    /* Animation */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .fade-in-up {
      animation: fadeInUp 0.5s ease-out;
    }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar { width: 80px; }
      .sidebar .brand, .nav-link span { display: none; }
      .nav-link { justify-content: center; }
      .main-content { margin-left: 80px; }
      .action-btn { width: 32px; height: 32px; font-size: 0.8rem; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="brand">
      <i class="fas fa-shield-alt"></i>
      <span>Admin Panel</span>
    </div>
    <nav class="nav flex-column mt-3">
      <a class="nav-link" href="../admin/admin_dashboard.php"><i class="fas fa-gauge-high"></i><span>Tổng quan</span></a>
      <a class="nav-link" href="../admin/admin_users.php"><i class="fas fa-users"></i><span>Quản lý người dùng</span></a>
      <a class="nav-link" href="../admin/admin_partners.php"><i class="fas fa-bus"></i><span>Quản lý nhà xe</span></a>
      <a class="nav-link active" href="../admin/admin_promotions.php"><i class="fas fa-tags"></i><span>Khuyến mãi</span></a>
      <a class="nav-link" href="../admin/admin_reports.php"><i class="fas fa-chart-line"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../admin/admin_operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../admin/admin_feedback.php"><i class="fas fa-headset"></i><span>Hỗ trợ</span></a>
      
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
        <i class="fas fa-tags"></i> Quản lý khuyến mãi
      </h1>
      <div class="d-flex gap-2">
        <input id="promotionSearch" class="form-control search-input" placeholder="Tìm mã, tiêu đề...">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
          <i class="fas fa-plus"></i> Thêm khuyến mãi
        </button>
      </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show fade-in-up" role="alert">
      <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="table-card fade-in-up">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="promotionsTable">
          <thead>
            <tr>
              <th>Mã</th>
              <th>Tiêu đề</th>
              <th>Loại</th>
              <th>Giá trị</th>
              <th>Điều kiện</th>
              <th>Thời gian</th>
              <th>Giới hạn/Đã dùng</th>
              <th>Trạng thái</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($promotions as $pr): ?>
            <tr>
              <td><code><?= htmlspecialchars($pr['code']) ?></code></td>
              <td><strong><?= htmlspecialchars($pr['title']) ?></strong></td>
              <td><?= $pr['discount_type'] === 'fixed' ? 'Cố định' : 'Phần trăm' ?></td>
              <td><?= number_format($pr['discount_value']) ?></td>
              <td>
                <small class="text-muted">Min: <?= number_format($pr['min_order_amount']) ?></small>
                <?php if ($pr['max_discount_amount'] !== null): ?>
                <br><small class="text-muted">Max: <?= number_format($pr['max_discount_amount']) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <small><?= date('d/m/Y H:i', strtotime($pr['start_date'])) ?> - <?= date('d/m/Y H:i', strtotime($pr['end_date'])) ?></small>
              </td>
              <td>
                <small><?= $pr['usage_limit'] ?? '∞' ?> / <?= (int)$pr['used_count'] ?></small>
              </td>
              <td>
                <?php 
                  $badge = $pr['status']==='active' ? 'bg-success' : ($pr['status']==='inactive' ? 'bg-secondary' : 'bg-warning text-dark');
                  $label = $pr['status']==='active' ? 'HOẠT ĐỘNG €' : ($pr['status']==='inactive' ? 'TẮT' : 'HẾT HẠN');
                ?>
                <span class="badge <?= $badge ?> px-3 py-2"><?= $label ?></span>
              </td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <!-- Kích hoạt -->
                  <?php if ($pr['status'] !== 'active'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="promotion_id" value="<?= $pr['promotion_id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="action-btn btn-activate" title="Kích hoạt">
                      <i class="fas fa-play"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Tắt -->
                  <?php if ($pr['status'] !== 'inactive'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="promotion_id" value="<?= $pr['promotion_id'] ?>">
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="action-btn btn-deactivate" title="Tắt">
                      <i class="fas fa-stop"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Sửa -->
                  <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#editPromotionModal"
                          data-id="<?= $pr['promotion_id'] ?>"
                          data-title="<?= htmlspecialchars($pr['title']) ?>"
                          data-description="<?= htmlspecialchars($pr['description'] ?? '') ?>"
                          data-type="<?= $pr['discount_type'] ?>"
                          data-value="<?= $pr['discount_value'] ?>"
                          data-min="<?= $pr['min_order_amount'] ?>"
                          data-max="<?= $pr['max_discount_amount'] ?>"
                          data-start="<?= date('Y-m-d\TH:i', strtotime($pr['start_date'])) ?>"
                          data-end="<?= date('Y-m-d\TH:i', strtotime($pr['end_date'])) ?>"
                          data-limit="<?= $pr['usage_limit'] ?>"
                          data-status="<?= $pr['status'] ?>" title="Chỉnh sửa">
                    <i class="fas fa-edit"></i>
                  </button>

                  <!-- Xóa -->
                  <form method="POST" class="d-inline" onsubmit="return confirm('Xóa khuyến mãi này?');">
                    <input type="hidden" name="action" value="delete_promotion">
                    <input type="hidden" name="promotion_id" value="<?= $pr['promotion_id'] ?>">
                    <button type="submit" class="action-btn btn-delete" title="Xóa">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add Promotion Modal -->
  <div class="modal fade" id="addPromotionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-tag me-2"></i>Thêm khuyến mãi</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_promotion">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Mã *</label><input name="code" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Tiêu đề *</label><input name="title" class="form-control" required></div>
              <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" class="form-control" rows="2"></textarea></div>
              <div class="col-md-4"><label class="form-label">Loại giảm</label>
                <select name="discount_type" class="form-select">
                  <option value="fixed">Cố định</option>
                  <option value="percentage">Phần trăm</option>
                </select>
              </div>
              <div class="col-md-4"><label class="form-label">Giá trị *</label><input type="number" step="0.01" min="0" name="discount_value" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">ĐH tối thiểu</label><input type="number" step="0.01" min="0" name="min_order_amount" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Giảm tối đa</label><input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Bắt đầu *</label><input type="datetime-local" name="start_date" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Kết thúc *</label><input type="datetime-local" name="end_date" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Giới hạn lượt</label><input type="number" min="1" name="usage_limit" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                  <option value="active">Hoạt động</option>
                  <option value="inactive">Tắt</option>
                  <option value="expired">Hết hạn</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Tạo mới</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Promotion Modal -->
  <div class="modal fade" id="editPromotionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh sửa khuyến mãi</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="update_promotion">
            <input type="hidden" name="promotion_id" id="edit_id">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Tiêu đề *</label><input name="title" id="edit_title" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Loại</label>
                <select name="discount_type" id="edit_type" class="form-select">
                  <option value="fixed">Cố định</option>
                  <option value="percentage">Phần trăm</option>
                </select>
              </div>
              <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" id="edit_description" class="form-control" rows="2"></textarea></div>
              <div class="col-md-4"><label class="form-label">Giá trị *</label><input type="number" step="0.01" min="0" name="discount_value" id="edit_value" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">ĐH tối thiểu</label><input type="number" step="0.01" min="0" name="min_order_amount" id="edit_min" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Giảm tối đa</label><input type="number" step="0.01" min="0" name="max_discount_amount" id="edit_max" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Bắt đầu *</label><input type="datetime-local" name="start_date" id="edit_start" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Kết thúc *</label><input type="datetime-local" name="end_date" id="edit_end" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Giới hạn lượt</label><input type="number" min="1" name="usage_limit" id="edit_limit" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Trạng thái</label>
                <select name="status" id="edit_status" class="form-select">
                  <option value="active">Hoạt động</option>
                  <option value="inactive">Tắt</option>
                  <option value="expired">Hết hạn</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Lưu thay đổi</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Populate Edit Modal
    document.getElementById('editPromotionModal')?.addEventListener('show.bs.modal', e => {
      const b = e.relatedTarget;
      document.getElementById('edit_id').value = b.dataset.id;
      document.getElementById('edit_title').value = b.dataset.title;
      document.getElementById('edit_description').value = b.dataset.description || '';
      document.getElementById('edit_type').value = b.dataset.type;
      document.getElementById('edit_value').value = b.dataset.value;
      document.getElementById('edit_min').value = b.dataset.min || '';
      document.getElementById('edit_max').value = b.dataset.max || '';
      document.getElementById('edit_start').value = b.dataset.start;
      document.getElementById('edit_end').value = b.dataset.end;
      document.getElementById('edit_limit').value = b.dataset.limit || '';
      document.getElementById('edit_status').value = b.dataset.status;
    });

    // Live Search
    const searchInput = document.getElementById('promotionSearch');
    const table = document.getElementById('promotionsTable');
    searchInput?.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      table.tBodies[0].querySelectorAll('tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  </script>
</body>
</html>