<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$operator_id = getCurrentOperator();

$message = '';
$message_type = '';

// Xử lý đánh dấu đã đọc
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_read') {
    $notification_id = $_POST['notification_id'];
    
    try {
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND partner_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$notification_id, $operator_id]);
        
        $message = 'Đánh dấu đã đọc thành công!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Lỗi khi đánh dấu đã đọc: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Xử lý đánh dấu tất cả đã đọc
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_all_read') {
    try {
        $query = "UPDATE notifications SET is_read = 1 WHERE partner_id = ? AND is_read = 0";
        $stmt = $db->prepare($query);
        $stmt->execute([$operator_id]);
        
        $message = 'Đánh dấu tất cả đã đọc thành công!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Lỗi khi đánh dấu tất cả đã đọc: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Lấy tham số lọc
$type_filter = $_GET['type'] ?? '';
$read_filter = $_GET['read'] ?? '';

// Xây dựng query với filters
$where_conditions = ["partner_id = ?"];
$params = [$operator_id];

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

if ($read_filter !== '') {
    $where_conditions[] = "is_read = ?";
    $params[] = $read_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy danh sách thông báo
$query = "SELECT * FROM notifications 
          WHERE $where_clause
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê thông báo
$query = "SELECT 
            COUNT(*) as total_notifications,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications,
            SUM(CASE WHEN type = 'booking' THEN 1 ELSE 0 END) as booking_notifications,
            SUM(CASE WHEN type = 'payment' THEN 1 ELSE 0 END) as payment_notifications,
            SUM(CASE WHEN type = 'promotion' THEN 1 ELSE 0 END) as promotion_notifications,
            SUM(CASE WHEN type = 'system' THEN 1 ELSE 0 END) as system_notifications,
            SUM(CASE WHEN type = 'trip_update' THEN 1 ELSE 0 END) as trip_notifications
          FROM notifications WHERE partner_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$notification_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Tạo thông báo mẫu nếu chưa có
if (empty($notifications)) {
    $sample_notifications = [
        [
            'title' => 'Chào mừng đến với hệ thống!',
            'message' => 'Chúc mừng bạn đã đăng ký thành công tài khoản nhà xe. Hãy bắt đầu quản lý chuyến đi của bạn.',
            'type' => 'system'
        ],
        [
            'title' => 'Cập nhật hệ thống',
            'message' => 'Hệ thống đã được cập nhật với nhiều tính năng mới. Vui lòng làm mới trang để trải nghiệm.',
            'type' => 'system'
        ],
        [
            'title' => 'Lưu ý về bảo mật',
            'message' => 'Vui lòng thay đổi mật khẩu mặc định và bảo mật tài khoản của bạn.',
            'type' => 'system'
        ]
    ];
    
    foreach ($sample_notifications as $sample) {
        $query = "INSERT INTO notifications (partner_id, title, message, type) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$operator_id, $sample['title'], $sample['message'], $sample['type']]);
    }
    
    // Reload notifications
    $query = "SELECT * FROM notifications 
              WHERE $where_clause
              ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thông báo - <?= htmlspecialchars($_SESSION['company_name']) ?></title>
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
      position: relative;
    }

    .nav-link:hover, .nav-link.active {
      background: rgba(255,255,255,0.15);
      color: white;
      border-left-color: white;
      transform: translateX(4px);
    }

    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    .badge-notif {
      background: #ef4444;
      color: white;
      font-size: 0.65rem;
      padding: 0.25em 0.5em;
      border-radius: 50%;
      position: absolute;
      top: 12px;
      right: 20px;
    }

    /* Main */
    .main-content {
      margin-left: 280px;
      padding: 2rem;
    }

    .top-bar {
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
      font-size: 1.6rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Stats */
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
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      margin-bottom: 1.5rem;
    }

    /* Notification Card */
    .notification-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }

    .notification-card:hover {
      box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }

    .notification-card.unread {
      background: #f8fafc;
      border-left: 5px solid var(--primary);
    }

    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .notification-title {
      font-weight: 600;
      color: var(--dark);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .unread-dot {
      width: 10px;
      height: 10px;
      background: var(--primary);
      border-radius: 50%;
      display: inline-block;
    }

    .notification-meta {
      color: #64748b;
      font-size: 0.85rem;
      margin-top: 0.25rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .type-badge {
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .type-booking { background: #dbeafe; color: #1d4ed8; }
    .type-payment { background: #d1fae5; color: #065f46; }
    .type-promotion { background: #fef3c7; color: #d97706; }
    .type-system { background: #e0f2fe; color: #0369a1; }
    .type-trip_update { background: #fce7f3; color: #be185d; }

    .notification-content p {
      margin: 0;
      color: #475569;
    }

    .btn-sm {
      padding: 0.35rem 0.75rem;
      font-size: 0.8rem;
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
      <i class="fas fa-bus"></i>
      <span><?= htmlspecialchars($_SESSION['company_name']) ?></span>
    </div>
    <nav class="nav flex-column mt-3">
      <a class="nav-link" href="../partner/dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a>
      <a class="nav-link" href="../partner/trips.php"><i class="fas fa-route"></i><span>Chuyến xe</span></a>
      <a class="nav-link" href="../partner/tickets.php"><i class="fas fa-ticket-alt"></i><span>Đặt vé</span></a>
      <a class="nav-link" href="../partner/operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../partner/reports.php"><i class="fas fa-chart-bar"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../partner/feedback.php"><i class="fas fa-star"></i><span>Phản hồi</span></a>
      <a class="nav-link active" href="../partner/notifications.php">
        <i class="fas fa-bell"></i><span>Thông báo</span>
        <?php if ($notification_stats['unread_notifications'] > 0): ?>
          <span class="badge-notif"><?= $notification_stats['unread_notifications'] ?></span>
        <?php endif; ?>
      </a>
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
    <div class="top-bar fade-in-up">
      <div>
        <h1 class="page-title"><i class="fas fa-bell"></i> Thông báo</h1>
        <p class="text-muted mb-0">Cập nhật và thông báo từ hệ thống</p>
      </div>
      <div>
        <?php if ($notification_stats['unread_notifications'] > 0): ?>
        <button type="button" class="btn btn-outline-primary" onclick="markAllRead()">
          <i class="fas fa-check-double me-2"></i>Đánh dấu tất cả đã đọc
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show fade-in-up" role="alert">
      <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #6366f1;">
          <div class="stat-icon" style="background: #eef2ff; color: #6366f1;">
            <i class="fas fa-bell"></i>
          </div>
          <div class="stat-value"><?= $notification_stats['total_notifications'] ?></div>
          <div class="stat-label">Tổng thông báo</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #f59e0b;">
          <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="stat-value"><?= $notification_stats['unread_notifications'] ?></div>
          <div class="stat-label">Chưa đọc</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #10b981;">
          <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
            <i class="fas fa-cog"></i>
          </div>
          <div class="stat-value"><?= $notification_stats['system_notifications'] ?></div>
          <div class="stat-label">Hệ thống</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #0ea5e9;">
          <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="stat-value"><?= $notification_stats['payment_notifications'] ?></div>
          <div class="stat-label">Thanh toán</div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-card fade-in-up">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label fw-semibold">Loại thông báo</label>
          <select class="form-select" name="type">
            <option value="">Tất cả loại</option>
            <option value="booking" <?= $type_filter=='booking'?'selected':'' ?>>Đặt vé</option>
            <option value="payment" <?= $type_filter=='payment'?'selected':'' ?>>Thanh toán</option>
            <option value="promotion" <?= $type_filter=='promotion'?'selected':'' ?>>Khuyến mãi</option>
            <option value="system" <?= $type_filter=='system'?'selected':'' ?>>Hệ thống</option>
            <option value="trip_update" <?= $type_filter=='trip_update'?'selected':'' ?>>Cập nhật chuyến</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label fw-semibold">Trạng thái</label>
          <select class="form-select" name="read">
            <option value="">Tất cả</option>
            <option value="0" <?= $read_filter==='0'?'selected':'' ?>>Chưa đọc</option>
            <option value="1" <?= $read_filter==='1'?'selected':'' ?>>Đã đọc</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">&nbsp;</label>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i>
            </button>
            <a href="notifications.php" class="btn btn-outline-secondary w-100">
              <i class="fas fa-times"></i>
            </a>
          </div>
        </div>
      </form>
    </div>

    <!-- Notifications List -->
    <div class="fade-in-up">
      <?php foreach ($notifications as $n): ?>
      <div class="notification-card <?= !$n['is_read'] ? 'unread' : '' ?>">
        <div class="notification-header">
          <div>
            <h6 class="notification-title">
              <?php if (!$n['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
              <?= htmlspecialchars($n['title']) ?>
            </h6>
            <div class="notification-meta">
              <i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
              <span class="type-badge type-<?= $n['type'] ?>">
                <?= ['booking'=>'Đặt vé','payment'=>'Thanh toán','promotion'=>'Khuyến mãi','system'=>'Hệ thống','trip_update'=>'Cập nhật chuyến'][$n['type']] ?? $n['type'] ?>
              </span>
            </div>
          </div>
          <div>
            <?php if (!$n['is_read']): ?>
            <button class="btn btn-outline-primary btn-sm" onclick="markAsRead(<?= $n['notification_id'] ?>)">
              <i class="fas fa-check"></i> Đã đọc
            </button>
            <?php else: ?>
            <span class="text-success"><i class="fas fa-check-circle"></i> Đã đọc</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="notification-content">
          <p><?= nl2br(htmlspecialchars($n['message'])) ?></p>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($notifications)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fas fa-bell-slash fa-3x mb-3"></i>
        <h5>Chưa có thông báo nào</h5>
        <p>Thông báo từ hệ thống sẽ hiển thị tại đây</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Forms -->
  <form id="markReadForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="mark_read">
    <input type="hidden" name="notification_id" id="markReadId">
  </form>

  <form id="markAllReadForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="mark_all_read">
  </form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function markAsRead(id) {
      document.getElementById('markReadId').value = id;
      document.getElementById('markReadForm').submit();
    }
    function markAllRead() {
      if (confirm('Đánh dấu tất cả thông báo là đã đọc?')) {
        document.getElementById('markAllReadForm').submit();
      }
    }
  </script>
</body>
</html>