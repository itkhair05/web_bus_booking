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
            case 'add_user':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $role = ($_POST['role'] === 'admin') ? 'admin' : (($_POST['role'] === 'partner') ? 'partner' : 'user');
                $password = $_POST['password'];
                if (!$name || !$email || !$password) throw new Exception('Thiếu thông tin bắt buộc.');
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, status, created_at, updated_at) VALUES (?,?,?,?,?,'active', NOW(), NOW())");
                $stmt->execute([$name,$email,$phone,$hash,$role]);
                $message = 'Tạo người dùng thành công!';
                $message_type = 'success';
                break;
            case 'update_user_status':
                $user_id = (int)$_POST['user_id'];
                $status = $_POST['status'] === 'locked' ? 'locked' : 'active';
                $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$status,$user_id]);
                $message = 'Cập nhật trạng thái thành công!';
                $message_type = 'success';
                break;
            case 'update_user':
                $user_id = (int)$_POST['user_id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $role = ($_POST['role'] === 'admin') ? 'admin' : (($_POST['role'] === 'partner') ? 'partner' : 'user');
                if (!$user_id || !$name || !$email) throw new Exception('Thiếu thông tin bắt buộc.');
                $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?");
                $chk->execute([$email, $user_id]);
                if ($chk->fetchColumn() > 0) throw new Exception('Email đã tồn tại.');
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$name,$email,$phone,$role,$user_id]);
                $message = 'Cập nhật người dùng thành công!';
                $message_type = 'success';
                break;
            case 'reset_user_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = $_POST['new_password'];
                if (!$user_id || !$new_password) throw new Exception('Thiếu thông tin bắt buộc.');
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$hash, $user_id]);
                $message = 'Đổi mật khẩu người dùng thành công!';
                $message_type = 'success';
                break;
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                if (!$user_id) throw new Exception('Thiếu thông tin người dùng.');
                if (isset($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] === $user_id) {
                    throw new Exception('Không thể xóa tài khoản đang đăng nhập.');
                }
                $chk = $db->prepare("SELECT role FROM users WHERE user_id = ?");
                $chk->execute([$user_id]);
                $role = $chk->fetchColumn();
                if (!$role) throw new Exception('Không tìm thấy người dùng.');
                if ($role === 'admin') throw new Exception('Không thể xóa tài khoản Admin.');
                $del = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $del->execute([$user_id]);
                $message = 'Đã xóa người dùng.';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

$users = [];
try {
    $usersStmt = $db->query("SELECT user_id, name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 500");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý người dùng</title>
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

    .btn-edit { background: #e0f2fe; color: #0ea5e9; }
    .btn-edit:hover { background: #bae6fd; }

    .btn-reset { background: #fef3c7; color: #d97706; }
    .btn-reset:hover { background: #fde68a; }

    .btn-delete { background: #fee2e2; color: #dc2626; }
    .btn-delete:hover { background: #fecaca; }

    .btn-status {
      background: #f0fdf4; color: #16a34a;
      font-weight: 600;
      font-size: 0.8rem;
      padding: 0.3rem 0.6rem;
    }

    .btn-status.locked {
      background: #fee2e2; color: #dc2626;
    }

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
      <a class="nav-link active" href="../admin/admin_users.php"><i class="fas fa-users"></i><span>Quản lý người dùng</span></a>
      <a class="nav-link" href="../admin/admin_partners.php"><i class="fas fa-bus"></i><span>Quản lý nhà xe</span></a>
      <a class="nav-link" href="../admin/admin_promotions.php"><i class="fas fa-tags"></i><span>Khuyến mãi</span></a>
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
        <i class="fas fa-users"></i> Quản lý người dùng
      </h1>
      <div class="d-flex gap-2">
        <input id="userSearch" class="form-control search-input" placeholder="Tìm tên, email, SĐT...">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="fas fa-plus"></i> Thêm mới
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
        <table class="table table-hover mb-0" id="usersTable">
          <thead>
            <tr>
              <th>Họ tên</th>
              <th>Email</th>
              <th>SĐT</th>
              <th>Vai trò</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
              <td>
                <?php
                  $role = $u['role'];
                  $roleLabel = strtoupper($role);
                  $roleColor = $role === 'admin' ? 'dark' : ($role === 'partner' ? 'info' : 'secondary');
                ?>
                <span class="badge bg-<?= $roleColor ?> px-3 py-2">
                  <?= $roleLabel ?>
                </span>
              </td>
              <td>
                <span class="badge bg-<?= $u['status']==='active'?'success':'danger' ?> px-3 py-2">
                  <?= $u['status']==='active'?'HOẠT ĐỘNG':'KHÓA' ?>
                </span>
              </td>
              <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <!-- Status Toggle -->
                  <?php if ($u['status'] === 'active'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_user_status">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="status" value="locked">
                    <button type="submit" class="action-btn btn-status locked" title="Khóa tài khoản">
                      <i class="fas fa-lock"></i>
                    </button>
                  </form>
                  <?php else: ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="update_user_status">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="action-btn btn-status" title="Kích hoạt">
                      <i class="fas fa-unlock"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <!-- Edit -->
                  <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#editUserModal"
                          data-id="<?= $u['user_id'] ?>"
                          data-name="<?= htmlspecialchars($u['name']) ?>"
                          data-email="<?= htmlspecialchars($u['email']) ?>"
                          data-phone="<?= htmlspecialchars($u['phone']) ?>"
                          data-role="<?= $u['role'] ?>" title="Chỉnh sửa">
                    <i class="fas fa-edit"></i>
                  </button>

                  <!-- Reset Password -->
                  <button class="action-btn btn-reset" data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                          data-id="<?= $u['user_id'] ?>"
                          data-name="<?= htmlspecialchars($u['name']) ?>" title="Đổi mật khẩu">
                    <i class="fas fa-key"></i>
                  </button>

                  <!-- Delete -->
                  <form method="POST" onsubmit="return confirm('Xóa người dùng này? Không thể hoàn tác!');" class="d-inline">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
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

  <!-- Modals (giữ nguyên) -->
  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Thêm người dùng</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_user">
            <div class="mb-3"><label class="form-label">Họ tên *</label><input name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Mật khẩu *</label><input type="password" name="password" class="form-control" minlength="6" required></div>
            <div class="mb-3"><label class="form-label">Vai trò</label>
              <select name="role" class="form-select">
                <option value="user">User</option>
                <option value="partner">Partner</option>
                <option value="admin">Admin</option>
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

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh sửa người dùng</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="mb-3"><label class="form-label">Họ tên *</label><input name="name" id="edit_name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Số điện thoại</label><input name="phone" id="edit_phone" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Vai trò</label>
              <select name="role" id="edit_role" class="form-select">
                <option value="user">User</option>
                <option value="partner">Partner</option>
                <option value="admin">Admin</option>
              </select>
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

  <!-- Reset Password Modal -->
  <div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-key me-2"></i>Đổi mật khẩu</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="reset_user_password">
            <input type="hidden" name="user_id" id="reset_user_id">
            <div class="mb-2"><strong id="reset_user_name"></strong></div>
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
    document.getElementById('editUserModal')?.addEventListener('show.bs.modal', e => {
      const b = e.relatedTarget;
      document.getElementById('edit_user_id').value = b.dataset.id;
      document.getElementById('edit_name').value = b.dataset.name;
      document.getElementById('edit_email').value = b.dataset.email;
      document.getElementById('edit_phone').value = b.dataset.phone || '';
      document.getElementById('edit_role').value = b.dataset.role;
    });

    // Populate Reset Modal
    document.getElementById('resetPasswordModal')?.addEventListener('show.bs.modal', e => {
      const b = e.relatedTarget;
      document.getElementById('reset_user_id').value = b.dataset.id;
      document.getElementById('reset_user_name').innerText = 'Người dùng: ' + b.dataset.name;
    });

    // Live Search
    const searchInput = document.getElementById('userSearch');
    const table = document.getElementById('usersTable');
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