<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Helper upload logo
function uploadPartnerLogo(?array $file): ?string {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Tải logo thất bại, vui lòng thử lại.');
    }
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception('Logo phải là JPG, PNG hoặc WebP.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Logo tối đa 2MB.');
    }
    $uploadDir = dirname(__DIR__) . '/uploads/partners/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $filename = 'partner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Không thể lưu logo, vui lòng thử lại.');
    }
    return 'uploads/partners/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_partner':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $password = $_POST['password'];
                $policy = $_POST['policy'] ?? null;
                $status = in_array($_POST['status'] ?? 'approved', ['pending','approved','suspended']) ? $_POST['status'] : 'approved';
                $logoUrl = uploadPartnerLogo($_FILES['logo'] ?? null);
                if (!$name || !$email || !$password) throw new Exception('Thiếu thông tin bắt buộc.');
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO partners (name,email,phone,password,policy,status,logo_url,created_at) VALUES (?,?,?,?,?,?,?, NOW())");
                $stmt->execute([$name,$email,$phone,$hash,$policy,$status,$logoUrl]);
                $message = 'Tạo nhà xe thành công!';
                $message_type = 'success';
                break;
            case 'update_partner_status':
                $partner_id = (int)$_POST['partner_id'];
                $status = in_array($_POST['status'], ['pending','approved','suspended']) ? $_POST['status'] : 'approved';
                $stmt = $db->prepare("UPDATE partners SET status = ? WHERE partner_id = ?");
                $stmt->execute([$status, $partner_id]);
                $message = 'Cập nhật trạng thái thành công!';
                $message_type = 'success';
                break;
            case 'update_partner':
                $partner_id = (int)$_POST['partner_id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $policy = $_POST['policy'] ?? null;
                $logoUrl = uploadPartnerLogo($_FILES['logo'] ?? null);
                if (!$partner_id || !$name || !$email) throw new Exception('Thiếu thông tin bắt buộc.');
                if ($logoUrl) {
                    $stmt = $db->prepare("UPDATE partners SET name = ?, email = ?, phone = ?, policy = ?, logo_url = ? WHERE partner_id = ?");
                    $stmt->execute([$name,$email,$phone,$policy,$logoUrl,$partner_id]);
                } else {
                    $stmt = $db->prepare("UPDATE partners SET name = ?, email = ?, phone = ?, policy = ? WHERE partner_id = ?");
                    $stmt->execute([$name,$email,$phone,$policy,$partner_id]);
                }
                $message = 'Cập nhật thông tin nhà xe thành công!';
                $message_type = 'success';
                break;
            case 'reset_partner_password':
                $partner_id = (int)$_POST['partner_id'];
                $new_password = $_POST['new_password'] ?? '';
                if (!$partner_id || !$new_password) throw new Exception('Thiếu thông tin bắt buộc.');
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE partners SET password = ? WHERE partner_id = ?");
                $stmt->execute([$hash, $partner_id]);
                $message = 'Đổi mật khẩu nhà xe thành công!';
                $message_type = 'success';
                break;
            case 'delete_partner':
                $partner_id = (int)$_POST['partner_id'];
                if (!$partner_id) throw new Exception('Thiếu thông tin nhà xe.');
                try {
                    $del = $db->prepare("DELETE FROM partners WHERE partner_id = ?");
                    $del->execute([$partner_id]);
                    $message = 'Đã xóa nhà xe.';
                    $message_type = 'success';
                } catch (Exception $ex) {
                    throw new Exception('Không thể xóa do đang có dữ liệu liên quan. Hãy tạm khóa thay vì xóa.');
                }
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

$partners = [];
try {
    $stmt = $db->query("SELECT partner_id, name, email, phone, status, created_at, policy, logo_url FROM partners ORDER BY created_at DESC LIMIT 500");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý nhà xe</title>
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

    .btn-approve { background: #ecfdf5; color: #16a34a; }
    .btn-approve:hover { background: #dcfce7; }

    .btn-suspend { background: #fef3c7; color: #d97706; }
    .btn-suspend:hover { background: #fde68a; }

    .btn-pending { background: #fefce8; color: #ca8a04; }
    .btn-pending:hover { background: #fef9c3; }

    .btn-edit { background: #e0f2fe; color: #0ea5e9; }
    .btn-edit:hover { background: #bae6fd; }

    .btn-reset { background: #fef3c7; color: #d97706; }
    .btn-reset:hover { background: #fde68a; }

    .btn-delete { background: #fee2e2; color: #dc2626; }
    .btn-delete:hover { background: #fecaca; }

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

    .form-control, .form-select, .form-control[readonly] {
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
      <a class="nav-link active" href="../admin/admin_partners.php"><i class="fas fa-bus"></i><span>Quản lý nhà xe</span></a>
      <a class="nav-link" href="../admin/admin_promotions.php"><i class="fas fa-tags"></i><span>Khuyến mãi</span></a>
      <a class="nav-link" href="../admin/admin_reports.php"><i class="fas fa-chart-line"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../admin/admin_operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../admin/admin_feedback.php"><i class="fas fa-headset"></i><span>Hỗ trợ</span></a>
      
      <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
      <div style="padding: 0 15px; margin-bottom: 10px;">
        <small style="color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Chuyển giao diện</small>
      </div>
      <a class="nav-link" href="../index.php" style="background: rgba(59, 130, 246, 0.1);"><i class="fas fa-home"></i><span>Giao diện User</span></a>
      
      <a class="nav-link" href="<?php echo appUrl('admin/auth/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="page-header fade-in-up">
      <h1 class="page-title">
        <i class="fas fa-bus"></i> Quản lý nhà xe
      </h1>
      <div class="d-flex gap-2">
        <input id="partnerSearch" class="form-control search-input" placeholder="Tìm tên, email, SĐT...">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
          <i class="fas fa-plus"></i> Thêm nhà xe
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
        <table class="table table-hover mb-0" id="partnersTable">
          <thead>
            <tr>
              <th>Tên nhà xe</th>
              <th>Email</th>
              <th>SĐT</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th>Chính sách</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($partners as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
              <td><?= htmlspecialchars($p['email']) ?></td>
              <td><?= htmlspecialchars($p['phone'] ?: '—') ?></td>
              <td>
                <?php
                  $badgeClass = $p['status']==='approved' ? 'bg-success' : ($p['status']==='pending' ? 'bg-warning text-dark' : 'bg-danger');
                  $label = $p['status']==='approved' ? 'ĐÃ DUYỆT' : ($p['status']==='pending' ? 'CHỜ DUYỆT' : 'TẠM KHÓA');
                ?>
                <span class="badge <?= $badgeClass ?> px-3 py-2"><?= $label ?></span>
              </td>
              <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
              <td><small class="text-muted"><?= htmlspecialchars($p['policy'] ?? '—') ?></small></td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <!-- Duyệt -->
                  <?php if ($p['status'] !== 'approved'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_partner_status">
                    <input type="hidden" name="partner_id" value="<?= $p['partner_id'] ?>">
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="action-btn btn-approve" title="Duyệt">
                      <i class="fas fa-check"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Tạm khóa -->
                  <?php if ($p['status'] !== 'suspended'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_partner_status">
                    <input type="hidden" name="partner_id" value="<?= $p['partner_id'] ?>">
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="action-btn btn-suspend" title="Tạm khóa">
                      <i class="fas fa-ban"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Chờ duyệt -->
                  <?php if ($p['status'] !== 'pending'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_partner_status">
                    <input type="hidden" name="partner_id" value="<?= $p['partner_id'] ?>">
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="action-btn btn-pending" title="Chờ duyệt">
                      <i class="fas fa-clock"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Chỉnh sửa -->
                  <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#editPartnerModal"
                          data-id="<?= $p['partner_id'] ?>"
                          data-name="<?= htmlspecialchars($p['name']) ?>"
                          data-email="<?= htmlspecialchars($p['email']) ?>"
                          data-phone="<?= htmlspecialchars($p['phone']) ?>"
                          data-policy="<?= htmlspecialchars($p['policy'] ?? '') ?>"
                          data-logo="<?= htmlspecialchars($p['logo_url'] ?? '') ?>" title="Chỉnh sửa">
                    <i class="fas fa-edit"></i>
                  </button>

                  <!-- Đổi mật khẩu -->
                  <button class="action-btn btn-reset" data-bs-toggle="modal" data-bs-target="#resetPartnerPasswordModal"
                          data-id="<?= $p['partner_id'] ?>"
                          data-name="<?= htmlspecialchars($p['name']) ?>" title="Đổi mật khẩu">
                    <i class="fas fa-key"></i>
                  </button>

                  <!-- Xóa -->
                  <form method="POST" onsubmit="return confirm('Xóa nhà xe này? Không thể hoàn tác!');" class="d-inline">
                    <input type="hidden" name="action" value="delete_partner">
                    <input type="hidden" name="partner_id" value="<?= $p['partner_id'] ?>">
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

  <!-- Add Partner Modal -->
  <div class="modal fade" id="addPartnerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-bus me-2"></i>Thêm nhà xe</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_partner">
            <div class="mb-3"><label class="form-label">Tên nhà xe *</label><input name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Mật khẩu *</label><input type="password" name="password" class="form-control" minlength="6" required></div>
            <div class="mb-3">
              <label class="form-label">Logo nhà xe</label>
              <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control">
              <small class="text-muted">PNG/JPG/WebP, tối đa 2MB</small>
            </div>
            <div class="mb-3"><label class="form-label">Chính sách</label><textarea name="policy" class="form-control" rows="3"></textarea></div>
            <div class="mb-3"><label class="form-label">Trạng thái</label>
              <select name="status" class="form-select">
                <option value="approved">Đã duyệt</option>
                <option value="pending">Chờ duyệt</option>
                <option value="suspended">Tạm khóa</option>
              </select>
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

  <!-- Edit Partner Modal -->
  <div class="modal fade" id="editPartnerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh sửa nhà xe</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" name="action" value="update_partner">
            <input type="hidden" name="partner_id" id="edit_partner_id">
            <div class="mb-3"><label class="form-label">Tên nhà xe *</label><input name="name" id="edit_name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Số điện thoại</label><input name="phone" id="edit_phone" class="form-control"></div>
            <div class="mb-3">
              <label class="form-label">Logo nhà xe</label>
              <div class="d-flex align-items-center gap-3">
                <img id="edit_logo_preview" src="../assets/images/bus-default.jpg" alt="Logo" style="width:60px;height:60px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc;padding:6px;">
                <div class="flex-grow-1">
                  <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control mb-1">
                  <small class="text-muted d-block">PNG/JPG/WebP, tối đa 2MB</small>
                </div>
              </div>
            </div>
            <div class="mb-3"><label class="form-label">Chính sách</label><textarea name="policy" id="edit_policy" class="form-control" rows="3"></textarea></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Lưu thay đổi</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Reset Partner Password Modal -->
  <div class="modal fade" id="resetPartnerPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-key me-2"></i>Đổi mật khẩu</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="reset_partner_password">
            <input type="hidden" name="partner_id" id="reset_partner_id">
            <div class="mb-2"><strong id="reset_partner_name"></strong></div>
            <div class="mb-3"><label class="form-label">Mật khẩu mới *</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
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
    // Populate Edit Modal
    document.getElementById('editPartnerModal')?.addEventListener('show.bs.modal', e => {
      const b = e.relatedTarget;
      document.getElementById('edit_partner_id').value = b.dataset.id;
      document.getElementById('edit_name').value = b.dataset.name;
      document.getElementById('edit_email').value = b.dataset.email;
      document.getElementById('edit_phone').value = b.dataset.phone || '';
      document.getElementById('edit_policy').value = b.dataset.policy || '';
      const logo = b.dataset.logo || '';
      const preview = document.getElementById('edit_logo_preview');
      if (preview) {
        let src = '../assets/images/bus-default.jpg';
        if (logo) {
          src = /^https?:\/\//i.test(logo) ? logo : ('../' + logo.replace(/^\/+/, ''));
        }
        preview.src = src;
      }
    });

    // Populate Reset Modal
    document.getElementById('resetPartnerPasswordModal')?.addEventListener('show.bs.modal', e => {
      const b = e.relatedTarget;
      document.getElementById('reset_partner_id').value = b.dataset.id;
      document.getElementById('reset_partner_name').innerText = 'Nhà xe: ' + b.dataset.name;
    });

    // Live Search
    const searchInput = document.getElementById('partnerSearch');
    const table = document.getElementById('partnersTable');
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