<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Actions: reply, update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'reply') {
            $complaint_id = (int)$_POST['complaint_id'];
            $response = trim($_POST['response']);
            if (!$complaint_id || $response === '') throw new Exception('Vui lòng nhập nội dung phản hồi.');

            // Kiểm tra cột responded_at tồn tại trước khi UPDATE
            $stmt = $db->prepare("
                UPDATE complaints 
                SET response = ?, status = 'resolved', 
                    responded_at = CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_NAME = 'complaints' AND COLUMN_NAME = 'responded_at'
                        ) THEN NOW() 
                        ELSE responded_at 
                    END 
                WHERE complaint_id = ?
            ");
            $stmt->execute([$response, $complaint_id]);

            $message = 'Đã trả lời và chuyển trạng thái ĐÃ XỬ LÝ.';
            $message_type = 'success';
        } 
        elseif ($_POST['action'] === 'update_status') {
            $complaint_id = (int)$_POST['complaint_id'];
            $status = in_array($_POST['status'], ['pending', 'in_progress', 'resolved']) ? $_POST['status'] : 'pending';
            $stmt = $db->prepare("UPDATE complaints SET status = ? WHERE complaint_id = ?");
            $stmt->execute([$status, $complaint_id]);
            $message = 'Cập nhật trạng thái thành công.';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Filters
$partners = [];
try {
    $partners = $db->query("SELECT partner_id, name FROM partners ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }

$partner_id = isset($_GET['partner_id']) && $_GET['partner_id'] !== '' ? (int)$_GET['partner_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$rating = isset($_GET['rating']) ? $_GET['rating'] : '';

$where = [];
$params = [];
if ($partner_id) { $where[] = 'c.partner_id = ?'; $params[] = $partner_id; }
if (in_array($status, ['pending', 'in_progress', 'resolved'])) { $where[] = 'c.status = ?'; $params[] = $status; }
if (in_array($rating, ['1', '2', '3', '4', '5'])) { $where[] = 'c.rating = ?'; $params[] = $rating; }
$where_clause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Stats
$feedback_stats = [
    'total_feedback' => 0,
    'pending_feedback' => 0,
    'inprogress_feedback' => 0,
    'resolved_feedback' => 0,
    'avg_rating' => 0.0
];
try {
    $sql = "SELECT 
              COUNT(*) total_feedback,
              SUM(CASE WHEN c.status='pending' THEN 1 ELSE 0 END) pending_feedback,
              SUM(CASE WHEN c.status='in_progress' THEN 1 ELSE 0 END) inprogress_feedback,
              SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) resolved_feedback,
              AVG(c.rating) avg_rating
            FROM complaints c" . ($partner_id ? " WHERE c.partner_id = ?" : "");
    $st = $db->prepare($sql);
    $partner_id ? $st->execute([$partner_id]) : $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $feedback_stats = [
            'total_feedback' => (int)$row['total_feedback'],
            'pending_feedback' => (int)$row['pending_feedback'],
            'inprogress_feedback' => (int)$row['inprogress_feedback'],
            'resolved_feedback' => (int)$row['resolved_feedback'],
            'avg_rating' => $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0.0
        ];
    }
} catch (Exception $e) { /* ignore */ }

// List complaints
$complaints = [];
try {
    $sql = "SELECT c.*, u.name AS customer_name, u.email AS customer_email, p.name AS partner_name
            FROM complaints c
            LEFT JOIN users u ON u.user_id = c.user_id
            LEFT JOIN partners p ON p.partner_id = c.partner_id
            $where_clause
            ORDER BY c.created_at DESC
            LIMIT 500";
    $st = $db->prepare($sql);
    $st->execute($params);
    $complaints = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hỗ trợ & Khiếu nại</title>
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

    /* Stat Cards */
    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      transition: all 0.3s ease;
      border-left: 5px solid;
      height: 100%;
    }

    .stat-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 25px rgba(0,0,0,0.12);
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      color: white;
      margin-bottom: 1rem;
    }

    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--dark);
    }

    .stat-label {
      color: #64748b;
      font-size: 0.9rem;
      font-weight: 500;
    }

    /* Filter Card */
    .filter-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
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

    .btn-filter {
      background: var(--primary);
      border: none;
      border-radius: 12px;
      padding: 0.65rem 1.3rem;
      font-weight: 600;
    }

    .btn-filter:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
    }

    /* Table Card */
    .table-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      overflow: hidden;
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

    .dropdown-menu {
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .dropdown-item {
      padding: 0.6rem 1rem;
      font-size: 0.9rem;
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

    .form-label {
      font-weight: 600;
      color: #374151;
    }

    /* Animation */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .fade-in-up {
      animation: fadeInUp 0.6s ease-out;
    }

    /* Responsive */
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
      <i class="fas fa-shield-alt"></i>
      <span>Admin Panel</span>
    </div>
    <nav class="nav flex-column mt-3">
      <a class="nav-link" href="../admin/admin_dashboard.php"><i class="fas fa-gauge-high"></i><span>Tổng quan</span></a>
      <a class="nav-link" href="../admin/admin_users.php"><i class="fas fa-users"></i><span>Quản lý người dùng</span></a>
      <a class="nav-link" href="../admin/admin_partners.php"><i class="fas fa-bus"></i><span>Quản lý nhà xe</span></a>
      <a class="nav-link" href="../admin/admin_promotions.php"><i class="fas fa-tags"></i><span>Khuyến mãi</span></a>
      <a class="nav-link" href="../admin/admin_reports.php"><i class="fas fa-chart-line"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../admin/admin_operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link active" href="../admin/admin_feedback.php"><i class="fas fa-headset"></i><span>Hỗ trợ</span></a>
      
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
        <i class="fas fa-headset"></i> Hỗ trợ & Khiếu nại
      </h1>
      <div class="text-muted">
        <small>Quản lý phản hồi từ khách hàng</small>
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
    <div class="row g-3 mb-4">
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #6366f1;">
          <div class="stat-icon" style="background: #eef2ff; color: #6366f1;">
            <i class="fas fa-comments"></i>
          </div>
          <div class="stat-value"><?= number_format($feedback_stats['total_feedback']) ?></div>
          <div class="stat-label">Tổng phản hồi</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #f59e0b;">
          <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-value"><?= number_format($feedback_stats['pending_feedback']) ?></div>
          <div class="stat-label">Chờ xử lý</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #0ea5e9;">
          <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;">
            <i class="fas fa-spinner"></i>
          </div>
          <div class="stat-value"><?= number_format($feedback_stats['inprogress_feedback']) ?></div>
          <div class="stat-label">Đang xử lý</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #10b981;">
          <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-value"><?= number_format($feedback_stats['resolved_feedback']) ?></div>
          <div class="stat-label">Đã xử lý</div>
        </div>
      </div>
    </div>

    <!-- Average Rating -->
    <?php if ($feedback_stats['total_feedback'] > 0): ?>
    <div class="text-center mb-4 fade-in-up">
      <div class="d-inline-flex align-items-center bg-white px-4 py-3 rounded-3 shadow-sm">
        <span class="h5 mb-0 me-2">Đánh giá trung bình:</span>
        <span class="h4 mb-0 text-warning">
          <?= str_repeat('★', (int)$feedback_stats['avg_rating']) ?>
          <?= $feedback_stats['avg_rating'] > (int)$feedback_stats['avg_rating'] ? '☆' : '' ?>
        </span>
        <span class="h5 mb-0 ms-2 text-muted">(<?= $feedback_stats['avg_rating'] ?>/5)</span>
      </div>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="filter-card fade-in-up">
      <form method="GET" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nhà xe</label>
          <select class="form-select" name="partner_id">
            <option value="">Tất cả nhà xe</option>
            <?php foreach ($partners as $p): ?>
              <option value="<?= $p['partner_id'] ?>" <?= $partner_id == $p['partner_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Trạng thái</label>
          <select class="form-select" name="status">
            <option value="">Tất cả</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
            <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>Đang xử lý</option>
            <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Đánh giá</label>
          <select class="form-select" name="rating">
            <option value="">Tất cả</option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>" <?= $rating == $i ? 'selected' : '' ?>><?= $i ?> sao</option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-filter" type="submit">
            <i class="fas fa-search"></i> Lọc
          </button>
          <a href="admin_feedback.php" class="btn btn-outline-secondary">
            <i class="fas fa-times"></i> Xóa bộ lọc
          </a>
        </div>
      </form>
    </div>

    <!-- Complaints Table -->
    <div class="table-card fade-in-up">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Tiêu đề</th>
              <th>Khách hàng</th>
              <th>Nhà xe</th>
              <th>Đánh giá</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($complaints)): ?>
              <?php foreach ($complaints as $c): ?>
              <tr>
                <td>
                  <div class="fw-bold"><?= htmlspecialchars($c['title']) ?></div>
                  <small class="text-muted d-block text-truncate" style="max-width: 300px;">
                    <?= htmlspecialchars($c['message']) ?>
                  </small>
                </td>
                <td>
                  <div><?= htmlspecialchars($c['customer_name'] ?? 'Khách') ?></div>
                  <small class="text-muted"><?= htmlspecialchars($c['customer_email'] ?? '') ?></small>
                </td>
                <td><?= htmlspecialchars($c['partner_name'] ?? ('#' . $c['partner_id'])) ?></td>
                <td>
                  <?php if ($c['rating']): ?>
                    <span class="text-warning"><?= str_repeat('★', $c['rating']) ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $statusMap = [
                      'pending' => ['bg-warning text-dark', 'Chờ xử lý'],
                      'in_progress' => ['bg-info text-dark', 'Đang xử lý'],
                      'resolved' => ['bg-success', 'Đã xử lý']
                    ];
                    $s = $statusMap[$c['status']] ?? ['bg-secondary', 'Không rõ'];
                  ?>
                  <span class="badge <?= $s[0] ?>"><?= $s[1] ?></span>
                </td>
                <td>
                  <small class="text-muted">
                    <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                  </small>
                </td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                      <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu">
                      <?php if ($c['status'] !== 'in_progress'): ?>
                      <li>
                        <form method="POST" class="dropdown-item px-3 py-2">
                          <input type="hidden" name="action" value="update_status">
                          <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
                          <input type="hidden" name="status" value="in_progress">
                          <button type="submit" class="text-decoration-none text-info">
                            <i class="fas fa-share me-2"></i>Chuyển xử lý
                          </button>
                        </form>
                      </li>
                      <?php endif; ?>
                      <?php if ($c['status'] !== 'resolved'): ?>
                      <li>
                        <button class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#replyModal"
                          data-id="<?= $c['complaint_id'] ?>" data-title="<?= htmlspecialchars($c['title']) ?>">
                          <i class="fas fa-reply me-2"></i>Trả lời & Hoàn tất
                        </button>
                      </li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="fas fa-inbox fa-2x mb-3 text-gray"></i><br>
                  Không có khiếu nại nào
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Reply Modal -->
  <div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-reply me-2"></i>Trả lời khiếu nại</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="complaint_id" id="reply_id">
            <div class="mb-3">
              <strong id="reply_title" class="d-block mb-2"></strong>
              <label class="form-label">Nội dung phản hồi *</label>
              <textarea name="response" class="form-control" rows="5" placeholder="Nhập phản hồi chi tiết..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-primary" type="submit">
              <i class="fas fa-paper-plane me-2"></i>Gửi & Hoàn tất
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const replyModal = document.getElementById('replyModal');
    replyModal.addEventListener('show.bs.modal', e => {
      const btn = e.relatedTarget;
      document.getElementById('reply_id').value = btn.getAttribute('data-id');
      document.getElementById('reply_title').innerText = btn.getAttribute('data-title');
    });
  </script>
</body>
</html>