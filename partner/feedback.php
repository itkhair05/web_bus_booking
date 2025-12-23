<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$operator_id = getCurrentOperator();

$message = '';
$message_type = '';

// Xử lý trả lời phản hồi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $feedback_id = $_POST['feedback_id'];
    $reply = trim($_POST['reply']);
    
    if (empty($reply渣)) {
        $message = 'Vui lòng nhập nội dung trả lời!';
        $message_type = 'danger';
    } else {
        try {
            $query = "UPDATE complaints SET response = ?, status = 'resolved' WHERE complaint_id = ? AND partner_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$reply, $feedback_id, $operator_id]);
            
            $message = 'Trả lời phản hồi thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Lỗi khi trả lời: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Xử lý chuyển tiếp
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'forward') {
    $feedback_id = $_POST['feedback_id'];
    
    try {
        $query = "UPDATE complaints SET status = 'in_progress' WHERE complaint_id = ? AND partner_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$feedback_id, $operator_id]);
        
        $message = 'Chuyển tiếp phản hồi thành công!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Lỗi khi chuyển tiếp: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Lấy tham số lọc
$status_filter = $_GET['status'] ?? '';
$view_type = $_GET['view'] ?? 'complaints'; // 'complaints' or 'reviews'

// Lấy danh sách đánh giá từ bảng reviews
$reviewsQuery = "
    SELECT r.review_id, r.rating, r.comment, r.created_at,
           u.fullname as customer_name, u.email as customer_email,
           tr.trip_id, tr.departure_time,
           rt.origin, rt.destination
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN trips tr ON r.trip_id = tr.trip_id
    JOIN routes rt ON tr.route_id = rt.route_id
    WHERE tr.partner_id = ?
    ORDER BY r.created_at DESC
    LIMIT 50
";
$reviewsStmt = $db->prepare($reviewsQuery);
$reviewsStmt->execute([$operator_id]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê đánh giá theo số sao
$ratingStatsQuery = "
    SELECT r.rating, COUNT(*) as count
    FROM reviews r
    JOIN trips tr ON r.trip_id = tr.trip_id
    WHERE tr.partner_id = ?
    GROUP BY r.rating
    ORDER BY r.rating DESC
";
$ratingStatsStmt = $db->prepare($ratingStatsQuery);
$ratingStatsStmt->execute([$operator_id]);
$ratingStats = [];
$totalReviews = 0;
while ($rs = $ratingStatsStmt->fetch(PDO::FETCH_ASSOC)) {
    $ratingStats[$rs['rating']] = (int)$rs['count'];
    $totalReviews += (int)$rs['count'];
}

// Xây dựng query
$where_conditions = ["c.partner_id = ?"];
$params = [$operator_id];

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy danh sách phản hồi
$query = "SELECT c.complaint_id, c.user_id, c.title, c.message, c.status, c.response, c.created_at,
                 u.name as customer_name, u.email as customer_email
          FROM complaints c
          LEFT JOIN users u ON c.user_id = u.user_id
          WHERE $where_clause
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$today = date('Y-m-d');
$snapshot = null;
try {
    $sstmt = $db->prepare("SELECT total_feedback, pending_feedback, inprogress_feedback, resolved_feedback, avg_rating
                            FROM partner_feedback_stats
                            WHERE stat_date = ? AND partner_id = ?");
    $sstmt->execute([$today, $operator_id]);
    $snapshot = $sstmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

if ($snapshot) {
    $feedback_stats = $snapshot;
} else {
    $query = "SELECT 
                COUNT(*) as total_feedback,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_feedback,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as inprogress_feedback,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_feedback
              FROM complaints WHERE partner_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$operator_id]);
    $feedback_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avgQuery = "SELECT AVG(r.rating) as avg_rating
                 FROM reviews r
                 JOIN trips tr ON tr.trip_id = r.trip_id
                 WHERE tr.partner_id = ?";
    $avgStmt = $db->prepare($avgQuery);
    $avgStmt->execute([$operator_id]);
    $avg = $avgStmt->fetch(PDO::FETCH_ASSOC);
    $feedback_stats['avg_rating'] = $avg && $avg['avg_rating'] !== null ? round((float)$avg['avg_rating'], 1) : 0.0;
}

// Thông báo
$query = "SELECT COUNT(*) as unread_count FROM notifications WHERE partner_id = ? AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$unread_notifications = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đánh giá & Phản hồi - <?= htmlspecialchars($_SESSION['company_name']) ?></title>
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

    .user-menu {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
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

    /* Feedback Card */
    .feedback-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }

    .feedback-card:hover {
      box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }

    .feedback-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .customer-name {
      font-weight: 600;
      color: var(--dark);
    }

    .feedback-meta {
      color: #64748b;
      font-size: 0.85rem;
      margin-top: 0.25rem;
    }

    .status-badge {
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-pending { background: #fef3c7; color: #d97706; }
    .status-in_progress { background: #dbeafe; color: #1d4ed8; }
    .status-resolved { background: #d1fae5; color: #065f46; }

    .feedback-content h6 {
      color: var(--primary);
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .feedback-reply {
      background: #f8fafc;
      border-left: 4px solid var(--primary);
      padding: 1rem;
      border-radius: 0 8px 8px 0;
      margin-top: 1rem;
      font-size: 0.95rem;
    }

    .btn-sm {
      padding: 0.35rem 0.75rem;
      font-size: 0.8rem;
    }

    /* Modal */
    .modal-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      border: none;
    }

    .modal-title i {
      margin-right: 8px;
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
      <a class="nav-link active" href="vfeedback.php"><i class="fas fa-star"></i><span>Phản hồi</span></a>
      <a class="nav-link" href="../partner/notifications.php">
        <i class="fas fa-bell"></i><span>Thông báo</span>
        <?php if ($unread_notifications > 0): ?>
          <span class="badge-notif"><?= $unread_notifications ?></span>
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
        <h1 class="page-title"><i class="fas fa-star"></i> Đánh giá & Phản hồi</h1>
        <p class="text-muted mb-0">Quản lý phản hồi và đánh giá từ khách hàng</p>
      </div>
      <div class="user-menu">
        <div class="dropdown">
          <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'] ?? $_SESSION['company_name'] ?? 'PT', 0, 2)) ?></div>
            <span><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['company_name'] ?? 'Partner') ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Cài đặt</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?php echo appUrl('partner/auth/logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
          </ul>
        </div>
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
            <i class="fas fa-comments"></i>
          </div>
          <div class="stat-value"><?= $feedback_stats['total_feedback'] ?></div>
          <div class="stat-label">Tổng phản hồi</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #f59e0b;">
          <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-value"><?= $feedback_stats['pending_feedback'] ?></div>
          <div class="stat-label">Chờ xử lý</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #10b981;">
          <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-value"><?= $feedback_stats['resolved_feedback'] ?></div>
          <div class="stat-label">Đã xử lý</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #0ea5e9;">
          <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;">
            <i class="fas fa-star"></i>
          </div>
          <div class="stat-value"><?= number_format($feedback_stats['avg_rating'], 1) ?></div>
          <div class="stat-label">Đánh giá TB</div>
        </div>
      </div>
    </div>

    <!-- View Type Tabs -->
    <div class="filter-card fade-in-up mb-3">
      <div class="d-flex gap-2 flex-wrap">
        <a href="?view=complaints" class="btn <?= $view_type === 'complaints' ? 'btn-primary' : 'btn-outline-primary' ?>">
          <i class="fas fa-comments me-1"></i> Khiếu nại (<?= $feedback_stats['total_feedback'] ?? 0 ?>)
        </a>
        <a href="?view=reviews" class="btn <?= $view_type === 'reviews' ? 'btn-primary' : 'btn-outline-primary' ?>">
          <i class="fas fa-star me-1"></i> Đánh giá sao (<?= $totalReviews ?>)
        </a>
      </div>
    </div>

    <?php if ($view_type === 'complaints'): ?>
    <!-- Filter for Complaints -->
    <div class="filter-card fade-in-up">
      <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="view" value="complaints">
        <div class="col-md-5">
          <label class="form-label fw-semibold">Trạng thái</label>
          <select class="form-select" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Chờ xử lý</option>
            <option value="in_progress" <?= $status_filter=='in_progress'?'selected':'' ?>>Đang xử lý</option>
            <option value="resolved" <?= $status_filter=='resolved'?'selected':'' ?>>Đã xử lý</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label fw-semibold">&nbsp;</label>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i> Lọc
            </button>
            <a href="feedback.php?view=complaints" class="btn btn-outline-secondary w-100">
              <i class="fas fa-times"></i> Xóa
            </a>
          </div>
        </div>
      </form>
    </div>
    <?php else: ?>
    <!-- Rating Stats for Reviews -->
    <div class="filter-card fade-in-up">
      <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2"></i>Thống kê đánh giá</h6>
      <div class="row">
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <?php 
            $count = $ratingStats[$i] ?? 0; 
            $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
          ?>
          <div class="col-12 mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="text-warning" style="width: 80px;">
                <?= $i ?> <i class="fas fa-star"></i>
              </span>
              <div class="progress flex-grow-1" style="height: 10px;">
                <div class="progress-bar bg-warning" style="width: <?= $percent ?>%"></div>
              </div>
              <span class="text-muted" style="width: 60px; text-align: right;"><?= $count ?></span>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Content List -->
    <div class="fade-in-up">
      <?php if ($view_type === 'complaints'): ?>
        <!-- Complaints List -->
        <?php foreach ($feedbacks as $f): ?>
        <div class="feedback-card">
          <div class="feedback-header">
            <div>
              <div class="customer-name">
                <?= htmlspecialchars($f['customer_name']) ?>
              </div>
              <div class="feedback-meta">
                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($f['customer_email']) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($f['created_at'])) ?>
              </div>
            </div>
            <span class="status-badge status-<?= $f['status'] ?>">
              <?= ['pending'=>'Chờ xử lý','in_progress'=>'Đang xử lý','resolved'=>'Đã xử lý'][$f['status']] ?? $f['status'] ?>
            </span>
          </div>

          <div class="feedback-content">
            <h6><?= htmlspecialchars($f['title']) ?></h6>
            <p class="mb-0"><?= nl2br(htmlspecialchars($f['message'])) ?></p>
          </div>

          <?php if ($f['response']): ?>
          <div class="feedback-reply">
            <strong><i class="fas fa-reply me-2"></i>Phản hồi từ bạn:</strong><br>
            <?= nl2br(htmlspecialchars($f['response'])) ?>
          </div>
          <?php endif; ?>

          <?php if ($f['status'] == 'pending'): ?>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#replyModal" onclick="setReply(<?= $f['complaint_id'] ?>)">
              <i class="fas fa-reply me-1"></i>Trả lời
            </button>
            <button class="btn btn-outline-info btn-sm" onclick="forwardFeedback(<?= $f['complaint_id'] ?>)">
              <i class="fas fa-share me-1"></i>Chuyển tiếp
            </button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if (empty($feedbacks)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fas fa-comments fa-3x mb-3"></i>
          <h5>Chưa có khiếu nại nào</h5>
          <p>Khách hàng sẽ gửi khiếu nại tại đây</p>
        </div>
        <?php endif; ?>
      
      <?php else: ?>
        <!-- Reviews List -->
        <?php foreach ($reviews as $rv): ?>
        <div class="feedback-card">
          <div class="feedback-header">
            <div>
              <div class="customer-name">
                <?= htmlspecialchars($rv['customer_name'] ?? 'Khách hàng') ?>
              </div>
              <div class="feedback-meta">
                <i class="fas fa-route me-1"></i><?= htmlspecialchars($rv['origin'] . ' → ' . $rv['destination']) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($rv['created_at'])) ?>
              </div>
            </div>
            <div class="rating-stars" style="color: #f59e0b; font-size: 18px;">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa<?= $i <= $rv['rating'] ? 's' : 'r' ?> fa-star"></i>
              <?php endfor; ?>
              <span class="ms-2 fw-bold"><?= $rv['rating'] ?>/5</span>
            </div>
          </div>

          <div class="feedback-content">
            <?php if (!empty($rv['comment'])): ?>
              <p class="mb-0">"<?= nl2br(htmlspecialchars($rv['comment'])) ?>"</p>
            <?php else: ?>
              <p class="mb-0 text-muted fst-italic">Không có bình luận</p>
            <?php endif; ?>
          </div>

          <div class="mt-2">
            <small class="text-muted">
              <i class="fas fa-bus me-1"></i>Chuyến: <?= date('H:i d/m/Y', strtotime($rv['departure_time'])) ?>
            </small>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($reviews)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fas fa-star fa-3x mb-3"></i>
          <h5>Chưa có đánh giá nào</h5>
          <p>Khách hàng sẽ đánh giá sau khi hoàn thành chuyến đi</p>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Reply Modal -->
  <div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-reply"></i> Trả lời phản hồi</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="feedback_id" id="replyFeedbackId">
            <div class="mb-3">
              <label class="form-label">Nội dung trả lời *</label>
              <textarea class="form-control" name="reply" rows="5" placeholder="Nhập nội dung trả lời..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane"></i> Gửi
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Forward Form -->
  <form id="forwardForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="forward">
    <input type="hidden" name="feedback_id" id="forwardFeedbackId">
  </form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function setReply(id) {
      document.getElementById('replyFeedbackId').value = id;
    }
    function forwardFeedback(id) {
      if (confirm('Chuyển tiếp phản hồi đến Admin?')) {
        document.getElementById('forwardFeedbackId').value = id;
        document.getElementById('forwardForm').submit();
      }
    }
  </script>
</body>
</html>