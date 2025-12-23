<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// === LỌC ===
$partner_id = $_GET['partner_id'] ?? '';
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$route_id = $_GET['route_id'] ?? '';

// Danh sách nhà xe
$partners = $db->query("SELECT partner_id, name FROM partners ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Xây dựng WHERE
$where = " WHERE 1=1";
$params = [];

if ($partner_id) {
    $where .= " AND tr.partner_id = ?";
    $params[] = $partner_id;
}
if ($route_id) {
    $where .= " AND tr.route_id = ?";
    $params[] = $route_id;
}

// === THỐNG KÊ ===
$stats = ['total_revenue' => 0, 'total_tickets' => 0, 'avg_price' => 0, 'active_partners' => 0];

// Tổng doanh thu + vé
$sql = "SELECT COALESCE(SUM(p.amount),0) total_revenue, COUNT(*) total_tickets
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id AND p.status = 'success'
        JOIN tickets t ON t.booking_id = b.booking_id
        JOIN trips tr ON t.trip_id = tr.trip_id
        $where";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_revenue'] = (float)$row['total_revenue'];
$stats['total_tickets'] = (int)$row['total_tickets'];
$stats['avg_price'] = $stats['total_tickets'] > 0 ? $stats['total_revenue'] / $stats['total_tickets'] : 0;

// Nhà xe hoạt động
$sql = "SELECT COUNT(DISTINCT tr.partner_id) active_partners
        FROM trips tr
        JOIN tickets t ON tr.trip_id = t.trip_id
        JOIN payments p ON p.booking_id = t.booking_id AND p.status = 'success'
        $where";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$stats['active_partners'] = (int)$stmt->fetchColumn();

// === DỮ LIỆU BIỂU ĐỒ ===
$daily_stats = $monthly_stats = $status_stats = $hourly_stats = $top_routes = [];

// 7 ngày gần nhất
$sql = "SELECT DATE(p.paid_at) date,
               COUNT(*) ticket_count,
               COALESCE(SUM(p.amount),0) revenue
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id AND p.status = 'success'
        JOIN tickets t ON t.booking_id = b.booking_id
        JOIN trips tr ON t.trip_id = tr.trip_id
        $where AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(p.paid_at)
        ORDER BY date DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Theo tháng
$sql = "SELECT MONTH(p.paid_at) month,
               COUNT(*) ticket_count,
               COALESCE(SUM(p.amount),0) revenue
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id AND p.status = 'success'
        JOIN tickets t ON t.booking_id = b.booking_id
        JOIN trips tr ON t.trip_id = tr.trip_id
        $where AND YEAR(p.paid_at) = ?
        GROUP BY MONTH(p.paid_at)
        ORDER BY month";
$stmt = $db->prepare($sql);
$stmt->execute(array_merge($params, [$year]));
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trạng thái vé
$sql = "SELECT t.status, COUNT(*) count
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.trip_id
        $where
        GROUP BY t.status";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Giờ cao điểm
$sql = "SELECT HOUR(tr.departure_time) hour, COUNT(t.ticket_id) ticket_count
        FROM trips tr
        LEFT JOIN tickets t ON tr.trip_id = t.trip_id
        $where AND tr.departure_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY HOUR(tr.departure_time)
        ORDER BY hour";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$hourly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top tuyến
$sql = "SELECT r.route_id, r.start_point, r.end_point,
               COUNT(*) ticket_count,
               COALESCE(SUM(p.amount),0) revenue
        FROM routes r
        JOIN trips tr ON r.route_id = tr.route_id
        JOIN tickets t ON tr.trip_id = t.trip_id
        JOIN bookings b ON t.booking_id = b.booking_id
        JOIN payments p ON p.booking_id = b.booking_id AND p.status = 'success'
        $where
        GROUP BY r.route_id
        ORDER BY revenue DESC
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$top_routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Danh sách tuyến
$sql = "SELECT DISTINCT r.route_id, r.start_point, r.end_point
        FROM routes r
        JOIN trips tr ON r.route_id = tr.route_id
        WHERE 1=1" . ($partner_id ? " AND tr.partner_id = ?" : "");
$stmt = $db->prepare($sql);
$stmt->execute($partner_id ? [$partner_id] : []);
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Báo cáo & Thống kê - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <style>
    :root {  --primary: #1E90FF;
             --primary-hover: #1873CC;
             --secondary: #17a2b8; 
             --success: #10b981; 
             --danger: #ef4444; 
             --warning: #f59e0b; 
             --info: #0dcaf0; 
             --dark: #1f2937; 
             --light: #f8fafc; 
             --gray: #94a3b8; 
             --border: #e2e8f0; }
    * { font-family: 'Inter', sans-serif; }
    body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; }
    .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100vh; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,0.1); }
    .brand { padding: 1.8rem 1.5rem; font-weight: 700; font-size: 1.4rem; border-bottom: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 12px; }
    .nav-link { color: rgba(255,255,255,0.85); padding: 0.9rem 1.5rem; display: flex; align-items: center; gap: 12px; transition: all 0.25s ease; border-left: 3px solid transparent; }
    .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.15); color: white; border-left-color: white; transform: translateX(4px); }
    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }
    .main-content { margin-left: 280px; padding: 2rem; }
    .top-bar { background: white; padding: 1.5rem 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-weight: 700; color: var(--dark); font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
    .stat-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.06); transition: all 0.3s ease; border-left: 5px solid; height: 100%; }
    .stat-card:hover { transform: translateY(-6px); box-shadow: 0 12px 25px rgba(0,0,0,0.12); }
    .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; margin-bottom: 1rem; }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: var(--dark); }
    .stat-label { color: #64748b; font-size: 0.9rem; font-weight: 500; }
    .filter-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
    .chart-card { background: white; border-radius: 16px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem; }
    .chart-title { font-weight: 600; color: var(--dark); margin-bottom: 1rem; font-size: 1.1rem; }
    .chart-container { position: relative; height: 300px; }
    .badge-rank { min-width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; }
    .btn-export { padding: 0.5rem 1rem; font-size: 0.9rem; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in-up { animation: fadeInUp 0.6s ease-out; }
    @media (max-width: 992px) { .sidebar { width: 80px; } .sidebar .brand, .nav-link span { display: none; } .nav-link { justify-content: center; } .main-content { margin-left: 80px; } }
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
      <a class="nav-link active" href="../admin/admin_reports.php"><i class="fas fa-chart-line"></i><span>Báo cáo</span></a>
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
    <div class="top-bar fade-in-up">
      <div>
        <h1 class="page-title"><i class="fas fa-chart-line"></i> Báo cáo & Thống kê</h1>
        <p class="text-muted mb-0">Toàn hệ thống - Phân tích chi tiết</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-danger btn-export" onclick="exportPDF()">
          <i class="fas fa-file-pdf"></i> PDF
        </button>
        <button class="btn btn-outline-success btn-export" onclick="exportExcel()">
          <i class="fas fa-file-excel"></i> Excel
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-card fade-in-up">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Nhà xe</label>
          <select class="form-select" name="partner_id">
            <option value="">Tất cả nhà xe</option>
            <?php foreach ($partners as $p): ?>
              <option value="<?= $p['partner_id'] ?>" <?= $partner_id == $p['partner_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Năm</label>
          <select class="form-select" name="year">
            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
              <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Tuyến đường</label>
          <select class="form-select" name="route_id">
            <option value="">Tất cả tuyến</option>
            <?php foreach ($routes as $r): ?>
              <option value="<?= $r['route_id'] ?>" <?= $route_id == $r['route_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['start_point'] . ' → ' . $r['end_point']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search"></i> Xem
          </button>
        </div>
      </form>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #20c997;">
          <div class="stat-icon" style="background: #ecfdf5; color: #20c997;">
            <i class="fas fa-coins"></i>
          </div>
          <div class="stat-value"><?= number_format($stats['total_revenue']) ?>đ</div>
          <div class="stat-label">Tổng doanh thu</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #10b981;">
          <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
            <i class="fas fa-ticket-alt"></i>
          </div>
          <div class="stat-value"><?= number_format($stats['total_tickets']) ?></div>
          <div class="stat-label">Tổng số vé</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #0ea5e9;">
          <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;">
            <i class="fas fa-calculator"></i>
          </div>
          <div class="stat-value"><?= number_format($stats['avg_price']) ?>đ</div>
          <div class="stat-label">Giá vé trung bình</div>
        </div>
      </div>
      <div class="col-md-3 fade-in-up">
        <div class="stat-card" style="border-left-color: #f59e0b;">
          <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;">
            <i class="fas fa-bus"></i>
          </div>
          <div class="stat-value"><?= $stats['active_partners'] ?></div>
          <div class="stat-label">Nhà xe hoạt động</div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Doanh thu 7 ngày gần nhất</h5>
          <div class="chart-container">
            <canvas id="dailyChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Phân bố trạng thái vé</h5>
          <div class="chart-container">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Doanh thu theo tháng (<?= $year ?>)</h5>
          <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="chart-card fade-in-up">
          <h5 class="chart-title">Hiệu suất theo giờ</h5>
          <div class="chart-container">
            <canvas id="hourlyChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Routes -->
    <div class="chart-card fade-in-up">
      <h5 class="chart-title">Top 10 tuyến doanh thu cao nhất</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Xếp hạng</th>
              <th>Tuyến đường</th>
              <th class="text-center">Số vé</th>
              <th class="text-end">Doanh thu</th>
              <th class="text-end">Giá TB</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_routes as $i => $r): ?>
            <tr>
              <td>
                <span class="badge-rank <?= $i < 3 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                  #<?= $i + 1 ?>
                </span>
              </td>
              <td><strong><?= htmlspecialchars($r['start_point'] . ' → ' . $r['end_point']) ?></strong></td>
              <td class="text-center"><?= number_format($r['ticket_count']) ?></td>
              <td class="text-end fw-bold text-success"><?= number_format($r['revenue']) ?>đ</td>
              <td class="text-end"><?= $r['ticket_count'] > 0 ? number_format($r['revenue'] / $r['ticket_count']) : 0 ?>đ</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const dailyData = <?= json_encode($daily_stats) ?>;
    const monthlyData = <?= json_encode($monthly_stats) ?>;
    const statusData = <?= json_encode($status_stats) ?>;
    const hourlyData = <?= json_encode($hourly_stats) ?>;

    // Daily Chart
    new Chart(document.getElementById('dailyChart'), {
      type: 'line',
      data: {
        labels: dailyData.map(d => new Date(d.date).toLocaleDateString('vi-VN')),
        datasets: [{
          label: 'Doanh thu',
          data: dailyData.map(d => d.revenue),
          borderColor: '#20c997',
          backgroundColor: 'rgba(32, 201, 151, 0.1)',
          tension: 0.4,
          fill: true
        }, {
          label: 'Số vé',
          data: dailyData.map(d => d.ticket_count),
          borderColor: '#17a2b8',
          backgroundColor: 'rgba(23, 162, 184, 0.1)',
          tension: 0.4,
          yAxisID: 'y1'
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { position: 'left' }, y1: { position: 'right', grid: { drawOnChartArea: false } } } }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: statusData.map(s => {
          const map = { 'active': 'Đang hoạt động', 'checked_in': 'Đã check-in', 'used': 'Đã sử dụng', 'cancelled': 'Đã hủy' };
          return map[s.status] || s.status;
        }),
        datasets: [{ data: statusData.map(s => s.count), backgroundColor: ['#10b981', '#f59e0b', '#0dcaf0', '#ef4444'] }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Monthly Chart
    const months = Array.from({length:12},(_,i)=>i+1);
    const monthlyRev = months.map(m => {
      const found = monthlyData.find(d => +d.month === m);
      return found ? +found.revenue : 0;
    });
    new Chart(document.getElementById('monthlyChart'), {
      type: 'bar',
      data: { labels: months.map(m => `Tháng ${m}`), datasets: [{ label: 'Doanh thu', data: monthlyRev, backgroundColor: '#20c997' }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // Hourly Chart
    const hours = Array.from({length:24},(_,i)=>i);
    const hourlyCount = hours.map(h => {
      const found = hourlyData.find(d => +d.hour === h);
      return found ? +found.ticket_count : 0;
    });
    new Chart(document.getElementById('hourlyChart'), {
      type: 'bar',
      data: { labels: hours.map(h => `${h}h`), datasets: [{ label: 'Số vé', data: hourlyCount, backgroundColor: '#17a2b8' }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // Export PDF
    async function exportPDF() {
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF('p', 'mm', 'a4');
      const pageWidth = pdf.internal.pageSize.getWidth();
      pdf.setFontSize(18);
      pdf.text('BÁO CÁO DOANH THU', pageWidth/2, 20, { align: 'center' });

      const content = document.querySelector('.main-content');
      const canvas = await html2canvas(content, { scale: 2 });
      const imgData = canvas.toDataURL('image/png');
      const imgHeight = (canvas.height * pageWidth) / canvas.width;

      let heightLeft = imgHeight;
      let position = 30;

      pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
      heightLeft -= 210;

      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
        heightLeft -= 210;
      }

      pdf.save('bao-cao-admin.pdf');
    }

    // Export Excel
    function exportExcel() {
      const wb = XLSX.utils.book_new();
      const topData = [['Xếp hạng', 'Tuyến', 'Số vé', 'Doanh thu', 'Giá TB']];
      <?php foreach($top_routes as $i => $r): ?>
      topData.push([<?= $i+1 ?>, "<?= $r['start_point'] ?> → <?= $r['end_point'] ?>", <?= $r['ticket_count'] ?>, <?= $r['revenue'] ?>, <?= $r['ticket_count']>0 ? $r['revenue']/$r['ticket_count'] : 0 ?>]);
      <?php endforeach; ?>
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(topData), "Top Tuyến");

      const monthly = [['Tháng', 'Doanh thu']];
      for(let m=1; m<=12; m++) monthly.push([`Tháng ${m}`, monthlyRev[m-1] || 0]);
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(monthly), "Doanh thu tháng");

      XLSX.writeFile(wb, 'bao-cao-admin.xlsx');
    }
  </script>
</body>
</html>