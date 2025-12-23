<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_user':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
                $password = $_POST['password'];
                if (!$name || !$email || !$password) throw new Exception('Thiếu thông tin bắt buộc.');
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, status, created_at, updated_at) VALUES (?,?,?,?,?,'active', NOW(), NOW())");
                $stmt->execute([$name, $email, $phone, $hash, $role]);
                $message = 'Tạo người dùng thành công!';
                $message_type = 'success';
                break;
            case 'update_user_status':
                $user_id = (int)$_POST['user_id'];
                $status = $_POST['status'] === 'locked' ? 'locked' : 'active';
                $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$status, $user_id]);
                $message = 'Cập nhật trạng thái người dùng thành công!';
                $message_type = 'success';
                break;
            case 'add_partner':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $password = $_POST['password'];
                $policy = $_POST['policy'] ?? null;
                $status = in_array($_POST['status'] ?? 'approved', ['pending','approved','suspended']) ? $_POST['status'] : 'approved';
                if (!$name || !$email || !$password) throw new Exception('Thiếu thông tin bắt buộc.');
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO partners (name,email,phone,password,policy,status,created_at) VALUES (?,?,?,?,?,?, NOW())");
                $stmt->execute([$name,$email,$phone,$hash,$policy,$status]);
                $message = 'Tạo nhà xe thành công!';
                $message_type = 'success';
                break;
            case 'update_partner_status':
                $partner_id = (int)$_POST['partner_id'];
                $status = in_array($_POST['status'], ['pending','approved','suspended']) ? $_POST['status'] : 'approved';
                $stmt = $db->prepare("UPDATE partners SET status = ? WHERE partner_id = ?");
                $stmt->execute([$status, $partner_id]);
                $message = 'Cập nhật trạng thái nhà xe thành công!';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Stats
$stats = [ 'partners' => 0, 'users' => 0 ];
try {
    $stats['partners'] = (int)$db->query("SELECT COUNT(*) FROM partners")->fetchColumn();
    $stats['users'] = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) { /* ignore */ }

// Data lists
$users = [];
$partners = [];
// Filters
$filter_partner_id = isset($_GET['partner_id']) && $_GET['partner_id'] !== '' ? (int)$_GET['partner_id'] : null;
$filter_from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : date('Y-m-d', strtotime('-6 days'));
$filter_to = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : date('Y-m-d');
try {
    $usersStmt = $db->query("SELECT user_id, name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 200");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    $partnersStmt = $db->query("SELECT partner_id, name, email, phone, status, created_at FROM partners ORDER BY created_at DESC LIMIT 200");
    $partners = $partnersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }

// Chart data: last N days tickets & revenue (default 7)
$chartLabels = [];
$chartTickets = [];
$chartRevenue = [];
$ticketsToday = 0; $tickets7d = 0; $revenue7d = 0.0; $openComplaints = 0;
$status7d = ['active'=>0,'checked_in'=>0,'used'=>0,'cancelled'=>0];
$topPartnersLabels = []; $topPartnersRevenue = [];
try {
    $from = $filter_from;
    $to = $filter_to;
    $dateKeys = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dateKeys[$d] = ['tickets' => 0, 'revenue' => 0.0];
    }
    $sqlTicketsByDay = "SELECT DATE(t.created_at) d, COUNT(*) c
                        FROM tickets t
                        JOIN trips tr ON tr.trip_id = t.trip_id
                        ";
    $whereT = " WHERE DATE(t.created_at) BETWEEN :from AND :to";
    if ($filter_partner_id) { $whereT .= " AND tr.partner_id = :pid"; }
    $sqlTicketsByDay .= $whereT." GROUP BY DATE(t.created_at)";
    $stT = $db->prepare($sqlTicketsByDay);
    $stT->bindValue(':from', $from);
    $stT->bindValue(':to', $to);
    if ($filter_partner_id) { $stT->bindValue(':pid', $filter_partner_id, PDO::PARAM_INT); }
    $stT->execute();
    foreach ($stT->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $d = $row['d'];
        if (isset($dateKeys[$d])) $dateKeys[$d]['tickets'] = (int)$row['c'];
    }
    $sqlRevenueByDay = "SELECT DATE(pay.paid_at) d, COALESCE(SUM(pay.amount),0) r
                        FROM payments pay
                        JOIN bookings b ON b.booking_id = pay.booking_id
                        JOIN tickets t ON t.booking_id = b.booking_id
                        JOIN trips tr ON tr.trip_id = t.trip_id";
    $whereR = " WHERE pay.status = 'success' AND DATE(pay.paid_at) BETWEEN :from AND :to";
    if ($filter_partner_id) { $whereR .= " AND tr.partner_id = :pid"; }
    $sqlRevenueByDay .= $whereR." GROUP BY DATE(pay.paid_at)";
    $stR = $db->prepare($sqlRevenueByDay);
    $stR->bindValue(':from', $from);
    $stR->bindValue(':to', $to);
    if ($filter_partner_id) { $stR->bindValue(':pid', $filter_partner_id, PDO::PARAM_INT); }
    $stR->execute();
    foreach ($stR->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $d = $row['d'];
        if (isset($dateKeys[$d])) $dateKeys[$d]['revenue'] = (float)$row['r'];
    }
    foreach ($dateKeys as $d => $vals) {
        $chartLabels[] = date('d/m', strtotime($d));
        $chartTickets[] = $vals['tickets'];
        $chartRevenue[] = $vals['revenue'];
    }

    $sqlToday = "SELECT COUNT(*) FROM tickets t JOIN trips tr ON tr.trip_id = t.trip_id WHERE DATE(t.created_at) = CURDATE()";
    if ($filter_partner_id) { $sqlToday .= " AND tr.partner_id = ".(int)$filter_partner_id; }
    $ticketsToday = (int)$db->query($sqlToday)->fetchColumn();

    $sqlT7 = "SELECT COUNT(*) FROM tickets t JOIN trips tr ON tr.trip_id = t.trip_id WHERE DATE(t.created_at) BETWEEN :from AND :to";
    if ($filter_partner_id) { $sqlT7 .= " AND tr.partner_id = :pid"; }
    $t7dSt = $db->prepare($sqlT7);
    $t7dSt->bindValue(':from',$from);
    $t7dSt->bindValue(':to',$to);
    if ($filter_partner_id) { $t7dSt->bindValue(':pid',$filter_partner_id, PDO::PARAM_INT); }
    $t7dSt->execute();
    $tickets7d = (int)$t7dSt->fetchColumn();

    $sqlRev7 = "SELECT COALESCE(SUM(pay.amount),0)
                FROM payments pay
                JOIN bookings b ON b.booking_id = pay.booking_id
                JOIN tickets t ON t.booking_id = b.booking_id
                JOIN trips tr ON tr.trip_id = t.trip_id
                WHERE pay.status='success' AND DATE(pay.paid_at) BETWEEN :from AND :to";
    if ($filter_partner_id) { $sqlRev7 .= " AND tr.partner_id = :pid"; }
    $rev7dSt = $db->prepare($sqlRev7);
    $rev7dSt->bindValue(':from',$from);
    $rev7dSt->bindValue(':to',$to);
    if ($filter_partner_id) { $rev7dSt->bindValue(':pid',$filter_partner_id, PDO::PARAM_INT); }
    $rev7dSt->execute();
    $revenue7d = (float)$rev7dSt->fetchColumn();

    $sqlCompl = "SELECT COUNT(*) FROM complaints c";
    if ($filter_partner_id) { $sqlCompl .= " WHERE c.partner_id = ".(int)$filter_partner_id; }
    else { $sqlCompl .= " WHERE 1=1"; }
    $sqlCompl .= " AND c.status IN ('pending','in_progress')";
    $openComplaints = (int)$db->query($sqlCompl)->fetchColumn();

    $sqlS = "SELECT t.status, COUNT(*) c FROM tickets t JOIN trips tr ON tr.trip_id = t.trip_id WHERE DATE(t.created_at) BETWEEN :from AND :to";
    if ($filter_partner_id) { $sqlS .= " AND tr.partner_id = :pid"; }
    $sqlS .= " GROUP BY t.status";
    $stS = $db->prepare($sqlS);
    $stS->bindValue(':from',$from);
    $stS->bindValue(':to',$to);
    if ($filter_partner_id) { $stS->bindValue(':pid',$filter_partner_id, PDO::PARAM_INT); }
    $stS->execute();
    foreach ($stS->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $key = $r['status']; $cnt = (int)$r['c'];
        if (isset($status7d[$key])) $status7d[$key] = $cnt;
    }

    $stTopP = $db->prepare("SELECT p.name, COALESCE(SUM(pay.amount),0) revenue
                             FROM payments pay
                             JOIN bookings b ON b.booking_id = pay.booking_id
                             JOIN tickets t ON t.booking_id = b.booking_id
                             JOIN trips tr ON tr.trip_id = t.trip_id
                             JOIN partners p ON p.partner_id = tr.partner_id
                             WHERE pay.status='success' AND DATE(pay.paid_at) BETWEEN :from AND :to
                             ".($filter_partner_id?" AND tr.partner_id = :pid ":"")."
                             GROUP BY p.partner_id, p.name
                             ORDER BY revenue DESC
                             LIMIT 5");
    if ($filter_partner_id) { $stTopP->execute([':from'=>$from, ':to'=>$to, ':pid'=>$filter_partner_id]); }
    else { $stTopP->execute([':from'=>$from, ':to'=>$to]); }
    foreach ($stTopP->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $topPartnersLabels[] = $r['name'];
        $topPartnersRevenue[] = (float)$r['revenue'];
    }
} catch (Exception $e) { /* ignore chart errors */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        /* Sidebar - giữ nguyên màu */
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

        /* Stat Card */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: white;
        }

        /* KPI Card */
        .kpi-card {
            background: white;
            border-radius: 14px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            text-align: center;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1.5px solid var(--border);
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
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

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }

        .section-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
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
            .page-header { flex-direction: column; align-items: stretch; }
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
            <a class="nav-link active" href="../admin/admin_dashboard.php"><i class="fas fa-gauge-high"></i><span>Tổng quan</span></a>
            <a class="nav-link" href="../admin/admin_users.php"><i class="fas fa-users"></i><span>Quản lý người dùng</span></a>
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
            
            <a class="nav-link" href="<?php echo appUrl('admin/auth/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header fade-in-up">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i>
                Xin chào, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>
            </h1>
            <a href="<?php echo appUrl('admin/auth/logout.php'); ?>" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>

        <!-- Filter -->
        <div class="filter-card fade-in-up">
            <form class="row g-3" method="GET">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nhà xe</label>
                    <select name="partner_id" class="form-select">
                        <option value="">Tất cả</option>
                        <?php foreach ($partners as $p): ?>
                        <option value="<?= $p['partner_id'] ?>" <?= $filter_partner_id==$p['partner_id']?'selected':'' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Từ ngày</label>
                    <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filter_from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Đến ngày</label>
                    <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filter_to) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="row g-3 fade-in-up">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0 fw-bold"><?= number_format($stats['users']) ?></div>
                            <small class="text-muted">Tài khoản người dùng</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0 fw-bold"><?= number_format($stats['partners']) ?></div>
                            <small class="text-muted">Đối tác nhà xe</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row g-3 mt-3 fade-in-up">
            <div class="col-6 col-lg-3">
                <div class="kpi-card border-start border-primary border-4">
                    <div class="text-muted small">Vé hôm nay</div>
                    <div class="kpi-value text-primary"><?= number_format($ticketsToday) ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card border-start border-success border-4">
                    <div class="text-muted small">Vé 7 ngày</div>
                    <div class="kpi-value text-success"><?= number_format($tickets7d) ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card border-start border-info border-4">
                    <div class="text-muted small">Doanh thu 7 ngày</div>
                    <div class="kpi-value text-info"><?= number_format($revenue7d) ?>đ</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card border-start border-warning border-4">
                    <div class="text-muted small">Khiếu nại mở</div>
                    <div class="kpi-value text-warning"><?= number_format($openComplaints) ?></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-3 mt-3">
            <div class="col-lg-6 fade-in-up">
                <div class="chart-card">
                    <div class="section-title"><i class="fas fa-ticket-alt text-primary"></i>Vé theo ngày</div>
                    <canvas id="ticketsChart" height="140"></canvas>
                </div>
            </div>
            <div class="col-lg-6 fade-in-up">
                <div class="chart-card">
                    <div class="section-title"><i class="fas fa-coins text-success"></i>Doanh thu theo ngày</div>
                    <canvas id="revenueChart" height="140"></canvas>
                </div>
            </div>
            <div class="col-lg-6 fade-in-up">
                <div class="chart-card">
                    <div class="section-title"><i class="fas fa-chart-pie text-info"></i>Cơ cấu vé</div>
                    <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 fade-in-up">
                <div class="chart-card">
                    <div class="section-title"><i class="fas fa-ranking-star text-warning"></i>Top 5 Nhà xe</div>
                    <canvas id="partnersRevenueChart" height="140"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <script>
        // Data
        const labels = <?= json_encode($chartLabels) ?>;
        const ticketsData = <?= json_encode($chartTickets) ?>;
        const revenueData = <?= json_encode($chartRevenue) ?>;
        const statusData = <?= json_encode(array_values($status7d)) ?>;
        const statusLabels = <?= json_encode(['Hoạt động', 'Đã check-in', 'Đã dùng', 'Hủy']) ?>;
        const partnersLabels = <?= json_encode($topPartnersLabels) ?>;
        const partnersData = <?= json_encode($topPartnersRevenue) ?>;

        // Config
        const config = {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        };

        // Charts
        new Chart(document.getElementById('ticketsChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Vé',
                    data: ticketsData,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23,162,184,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#17a2b8',
                    pointRadius: 5
                }]
            },
            options: { ...config, plugins: { legend: { display: true, position: 'top' } } }
        });

        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: revenueData,
                    backgroundColor: 'rgba(32,201,151,0.7)',
                    borderColor: '#20c997',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: config
        });

        new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: ['#10b981', '#0dcaf0', '#6c757d', '#dc3545'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,  // ← BẮT BUỘC: Tắt tự động resize
        cutout: '70%',               // ← Làm lỗ tròn lớn → gọn hơn
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 11 }
                }
            }
        }
    }
});

        new Chart(document.getElementById('partnersRevenueChart'), {
            type: 'bar',
            data: {
                labels: partnersLabels,
                datasets: [{
                    label: 'Doanh thu',
                    data: partnersData,
                    backgroundColor: '#6366f1',
                    borderRadius: 8
                }]
            },
            options: { ...config, indexAxis: 'y' }
        });
    </script>
</body>
</html>