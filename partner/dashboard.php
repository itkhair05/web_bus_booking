<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();
$operator_id = getCurrentOperator();

$database = new Database();
$db = $database->getConnection();

// === THỐNG KÊ TỔNG QUAN ===
$stats = [];

// Doanh thu hôm nay
$query = "SELECT COALESCE(SUM(p.amount), 0) as today_revenue
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN tickets t ON t.booking_id = b.booking_id
          JOIN trips tr ON t.trip_id = tr.trip_id
          WHERE tr.partner_id = ? AND DATE(p.paid_at) = CURDATE() AND p.status = 'success'";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$stats['today_revenue'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['today_revenue'];

// Số vé bán hôm nay
$query = "SELECT COUNT(*) as tickets_sold
          FROM tickets t
          JOIN trips tr ON t.trip_id = tr.trip_id
          WHERE tr.partner_id = ? AND DATE(t.created_at) = CURDATE() AND t.status IN ('active','checked_in','used')";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$stats['tickets_sold'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['tickets_sold'];

// Số chuyến hôm nay
$query = "SELECT COUNT(*) as departures
          FROM trips
          WHERE partner_id = ? AND DATE(departure_time) = CURDATE() AND status IN ('scheduled','open')";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$stats['departures'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['departures'];

// Tỷ lệ lấp đầy
$query = "SELECT AVG((v.total_seats - tr.available_seats) / v.total_seats * 100) as occupancy_rate
          FROM trips tr
          JOIN vehicles v ON tr.vehicle_id = v.vehicle_id
          WHERE tr.partner_id = ? AND DATE(tr.departure_time) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['occupancy_rate'] = $row['occupancy_rate'] !== null ? round((float)$row['occupancy_rate'], 1) : 0.0;

// Top 5 tuyến
$query = "SELECT r.start_point, r.end_point, COALESCE(SUM(p.amount),0) as revenue
          FROM routes r
          JOIN trips tr ON r.route_id = tr.route_id
          JOIN tickets t ON tr.trip_id = t.trip_id
          JOIN bookings b ON t.booking_id = b.booking_id
          JOIN payments p ON p.booking_id = b.booking_id AND p.status = 'success'
          WHERE tr.partner_id = ?
          GROUP BY r.route_id
          ORDER BY revenue DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$top_routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$max_revenue = !empty($top_routes) ? max(array_column($top_routes, 'revenue')) : 1;

// Doanh thu theo tháng (năm nay)
$monthly_labels = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];
$monthly_data = array_fill(0, 12, 0);

$query = "SELECT MONTH(p.paid_at) as month, SUM(p.amount) as revenue
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN tickets t ON t.booking_id = b.booking_id
          JOIN trips tr ON t.trip_id = tr.trip_id
          WHERE tr.partner_id = ? AND p.status = 'success' AND YEAR(p.paid_at) = YEAR(CURDATE())
          GROUP BY MONTH(p.paid_at)";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $monthly_data[(int)$row['month'] - 1] = (int)$row['revenue'];
}

// Trạng thái vé
$query = "SELECT t.status, COUNT(*) as count
          FROM tickets t
          JOIN trips tr ON t.trip_id = tr.trip_id
          WHERE tr.partner_id = ?
          GROUP BY t.status";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$status_data = ['active' => 0, 'pending' => 0, 'cancelled' => 0, 'empty' => 0];
$total_tickets = 0;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $status = $row['status'];
    if (in_array($status, ['active', 'checked_in', 'used'])) {
        $status = 'active';
    } elseif ($status === 'pending') {
        $status = 'pending';
    } elseif ($status === 'cancelled') {
        $status = 'cancelled';
    } else {
        // Ignore other statuses
        continue;
    }
    
    $status_data[$status] += (int)$row['count'];
    $total_tickets += (int)$row['count'];
}
$status_data['empty'] = max(0, 100 - $total_tickets); // giả lập ghế trống

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
  <title>Dashboard - <?= htmlspecialchars($_SESSION['company_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    /* Chart Card */
    .chart-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .chart-title {
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 1rem;
      font-size: 1.1rem;
    }

    .chart-container {
      position: relative;
      height: 300px;
    }

    /* Top Routes */
    .route-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .route-item:last-child { border-bottom: none; }

    .route-name {
      font-weight: 500;
      color: #374151;
      flex: 1;
    }

    .route-revenue {
      font-weight: 600;
      color: var(--primary);
      white-space: nowrap;
    }

    .progress {
      height: 6px;
      background: #e2e8f0;
      border-radius: 3px;
      overflow: hidden;
      flex: 1;
      margin: 0 1rem;
    }

    .progress-bar {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
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
      <a class="nav-link active" href="../partner/dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a>
      <a class="nav-link" href="../partner/trips.php"><i class="fas fa-route"></i><span>Chuyến xe</span></a>
      <a class="nav-link" href="../partner/tickets.php"><i class="fas fa-ticket-alt"></i><span>Đặt vé</span></a>
      <a class="nav-link" href="../partner/operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../partner/reports.php"><i class="fas fa-chart-bar"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../partner/feedback.php"><i class="fas fa-star"></i><span>Phản hồi</span></a>
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
      
      <a class="nav-link" href="auth/logout.php"><i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="top-bar fade-in-up">
      <div>
        <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p class="text-muted mb-0">Hôm nay, <?= date('d/m/Y') ?></p>
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
            <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #6366f1;">
          <div class="stat-icon" style="background: #eef2ff; color: #6366f1;">
            <i class="fas fa-coins"></i>
          </div>
          <div class="stat-value"><?= number_format($stats['today_revenue']) ?>đ</div>
          <div class="stat-label">Doanh thu hôm nay</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #10b981;">
          <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
            <i class="fas fa-ticket-alt"></i>
          </div>
          <div class="stat-value"><?= $stats['tickets_sold'] ?></div>
          <div class="stat-label">Vé đã bán</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #0ea5e9;">
          <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;">
            <i class="fas fa-bus"></i>
          </div>
          <div class="stat-value"><?= $stats['departures'] ?></div>
          <div class="stat-label">Chuyến khởi hành</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #f59e0b;">
          <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;">
            <i class="fas fa-chair"></i>
          </div>
          <div class="stat-value"><?= $stats['occupancy_rate'] ?>%</div>
          <div class="stat-label">Tỷ lệ lấp đầy</div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Doanh thu theo tháng (<?= date('Y') ?>)</h5>
          <div class="chart-container">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Top 5 tuyến doanh thu</h5>
          <div style="max-height: 300px; overflow-y: auto;">
            <?php foreach ($top_routes as $route): ?>
            <div class="route-item">
              <div class="route-name">
                <?= htmlspecialchars($route['start_point'] . ' → ' . $route['end_point']) ?>
              </div>
              <div class="progress flex-grow-1 mx-3">
                <div class="progress-bar" style="width: <?= $max_revenue > 0 ? ($route['revenue'] / $max_revenue * 100) : 0 ?>%"></div>
              </div>
              <div class="route-revenue"><?= number_format($route['revenue']) ?>đ</div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($top_routes)): ?>
              <p class="text-muted text-center py-4">Chưa có dữ liệu</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Trạng thái đặt vé</h5>
          <div class="chart-container">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Doanh thu 12 tháng</h5>
          <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Doanh thu theo tháng
    new Chart(document.getElementById('revenueChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode($monthly_labels) ?>,
        datasets: [{
          label: 'Doanh thu (VND)',
          data: <?= json_encode($monthly_data) ?>,
          borderColor: '#20c997',
          backgroundColor: 'rgba(32, 201, 151, 0.1)',
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#20c997',
          pointRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
      }
    });

    // Trạng thái vé
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: ['Đã thanh toán', 'Đang chờ', 'Đã hủy', 'Ghế trống'],
        datasets: [{
          data: [
            <?= $status_data['active'] ?>,
            <?= $status_data['pending'] ?>,
            <?= $status_data['cancelled'] ?>,
            <?= $status_data['empty'] ?>
          ],
          backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#e5e7eb']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
      }
    });

    // Doanh thu 12 tháng (bar)
    new Chart(document.getElementById('monthlyChart'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($monthly_labels) ?>,
        datasets: [{
          label: 'Doanh thu',
          data: <?= json_encode($monthly_data) ?>,
          backgroundColor: '#20c997'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
</body>
</html>