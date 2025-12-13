<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$operator_id = getCurrentOperator();

$message = '';
$message_type = '';

// Xử lý cập nhật trạng thái vé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_ticket_status') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!$ticket_id || !$status) {
        $message = 'Dữ liệu không hợp lệ.';
        $message_type = 'danger';
    } else {
        try {
            $allowed = ['active','cancelled','checked_in','used'];
            if (!in_array($status, $allowed)) {
                throw new Exception('Trạng thái vé không hợp lệ');
            }
            $query = "UPDATE tickets SET status = ? WHERE ticket_id = ? AND trip_id IN (SELECT trip_id FROM trips WHERE partner_id = ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$status, $ticket_id, $operator_id]);

            $message = 'Cập nhật trạng thái vé thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Xử lý cập nhật thông tin vé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_ticket') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $passenger_name = trim($_POST['passenger_name'] ?? '');
    $passenger_phone = trim($_POST['passenger_phone'] ?? '');
    $passenger_email = trim($_POST['passenger_email'] ?? '');
    $seat_number = trim($_POST['seat_number'] ?? '');

    if (!$ticket_id || !$passenger_name || !$passenger_phone || !$seat_number) {
        $message = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
        $message_type = 'danger';
    } else {
        try {
            $query = "UPDATE tickets SET passenger_name = ?, passenger_phone = ?, passenger_email = ?, seat_number = ? 
                      WHERE ticket_id = ? AND trip_id IN (SELECT trip_id FROM trips WHERE partner_id = ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$passenger_name, $passenger_phone, $passenger_email, $seat_number, $ticket_id, $operator_id]);

            $message = 'Cập nhật thông tin vé thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Lấy tham số lọc
$trip_filter = $_GET['trip_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Xây dựng query với filters
$where_conditions = ["tr.partner_id = ?"];
$params = [$operator_id];

if ($trip_filter) {
    $where_conditions[] = "t.trip_id = ?";
    $params[] = $trip_filter;
}

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(tr.departure_time) = ?";
    $params[] = $date_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy danh sách vé
$query = "SELECT t.ticket_id, t.ticket_code, t.passenger_name, t.passenger_phone, t.passenger_email, t.seat_number,
                 t.status, t.created_at, tr.trip_id, tr.departure_time, tr.arrival_time, tr.price,
                 r.start_point, r.end_point, v.license_plate, v.type as vehicle_type, d.name as driver_name
          FROM tickets t
          JOIN trips tr ON t.trip_id = tr.trip_id
          JOIN routes r ON tr.route_id = r.route_id
          JOIN vehicles v ON tr.vehicle_id = v.vehicle_id
          LEFT JOIN drivers d ON tr.driver_id = d.driver_id
          WHERE $where_clause
          ORDER BY tr.departure_time DESC, t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách chuyến để filter
$query = "SELECT t.trip_id, t.departure_time, r.start_point, r.end_point
          FROM trips t
          JOIN routes r ON t.route_id = r.route_id
          WHERE t.partner_id = ?
          ORDER BY t.departure_time DESC";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === TÍNH 4 THỐNG KÊ MỚI (HÔM NAY) ===
$today = date('Y-m-d');
$today_stats = [
    'total_today' => 0,
    'waiting_checkin' => 0,
    'cancelled_today' => 0,
    'revenue_today' => 0
];

try {
    // 1. Tổng vé hôm nay
    $q1 = "SELECT COUNT(*) FROM tickets t
           JOIN trips tr ON t.trip_id = tr.trip_id
           WHERE tr.partner_id = ? AND DATE(t.created_at) = ?";
    $stmt = $db->prepare($q1);
    $stmt->execute([$operator_id, $today]);
    $today_stats['total_today'] = $stmt->fetchColumn();

    // 2. Chờ check-in: active + chuyến hôm nay
    $q2 = "SELECT COUNT(*) FROM tickets t
           JOIN trips tr ON t.trip_id = tr.trip_id
           WHERE tr.partner_id = ? AND t.status = 'active' AND DATE(tr.departure_time) = ?";
    $stmt = $db->prepare($q2);
    $stmt->execute([$operator_id, $today]);
    $today_stats['waiting_checkin'] = $stmt->fetchColumn();

    // 3. Đã hủy hôm nay (dùng checked_in_at nếu có, hoặc created_at)
    $q3 = "SELECT COUNT(*) FROM tickets t
           JOIN trips tr ON t.trip_id = tr.trip_id
           WHERE tr.partner_id = ? AND t.status = 'cancelled' 
           AND (t.checked_in_at IS NOT NULL AND DATE(t.checked_in_at) = ? OR DATE(t.created_at) = ?)";
    $stmt = $db->prepare($q3);
    $stmt->execute([$operator_id, $today, $today]);
    $today_stats['cancelled_today'] = $stmt->fetchColumn();

    // 4. Doanh thu hôm nay
    $q4 = "SELECT COALESCE(SUM(p.amount), 0)
           FROM payments p
           JOIN bookings b ON p.booking_id = b.booking_id
           JOIN tickets tt ON tt.booking_id = b.booking_id
           JOIN trips ttr ON tt.trip_id = ttr.trip_id
           WHERE p.status = 'success' AND ttr.partner_id = ? AND DATE(p.paid_at) = ?";
    $stmt = $db->prepare($q4);
    $stmt->execute([$operator_id, $today]);
    $today_stats['revenue_today'] = $stmt->fetchColumn();

} catch (Exception $e) {
    // Nếu lỗi, giữ = 0
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý vé - <?= htmlspecialchars($_SESSION['company_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1E90FF;
            --primary-hover: #1873CC;
            --success: #10b981;
            --info: #0dcaf0;
            --danger: #ef4444;
            --purple: #8b5cf6;
            --dark: #1f2937;
            --gray: #94a3b8;
            --light: #f8fafc;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; }

        .sidebar {
            position: fixed; top: 0; left: 0; width: 280px; height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, #17a2b8 100%);
            color: white; z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .brand { padding: 1.8rem 1.5rem; font-weight: 700; font-size: 1.4rem; border-bottom: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 12px; }
        .nav-link { color: rgba(255,255,255,0.85); padding: 0.9rem 1.5rem; display: flex; align-items: center; gap: 12px; transition: all 0.25s ease; border-left: 3px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.15); color: white; border-left-color: white; transform: translateX(4px); }
        .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

        .main-content { margin-left: 280px; padding: 2rem; }
        .page-header { background: white; padding: 1.5rem 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .page-title { font-weight: 700; color: var(--dark); font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }

        .stats-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 8px 25px rgba(0,0,0,0.08); transition: all 0.3s ease; border-left: 5px solid; }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: white; margin-bottom: 0.75rem; }
        .stats-value { font-size: 1.8rem; font-weight: 700; color: var(--dark); margin: 0; }
        .stats-label { color: var(--gray); font-size: 0.9rem; margin: 0.5rem 0 0 0; }

        .filter-card, .content-card { background: white; border-radius: 16px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .filter-card { padding: 1.5rem; margin-bottom: 1.5rem; }

        .table thead { background: #f8fafc; font-weight: 600; font-size: 0.8rem; color: #64748b; }
        .table tbody tr:hover { background-color: #f1f5f9; }

        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-checked_in { background: #dbeafe; color: #1e40af; }
        .status-used { background: #e5e7eb; color: #374151; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .btn-action { padding: 0.35rem 0.65rem; font-size: 0.8rem; border-radius: 8px; min-width: 36px; display: inline-flex; align-items: center; justify-content: center; gap: 4px; }
        .btn-action:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-up { animation: fadeInUp 0.6s ease-out; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar .brand, .nav-link span { display: none; }
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
            <a class="nav-link active" href="../partner/tickets.php"><i class="fas fa-ticket-alt"></i><span>Đặt vé</span></a>
            <a class="nav-link" href="../partner/operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
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
            <div>
                <h1 class="page-title"><i class="fas fa-ticket-alt"></i> Quản lý vé</h1>
                <p class="text-muted mb-0">Check-in, hủy vé, chỉnh sửa thông tin hành khách</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show fade-in-up" role="alert">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- 4 CARD MỚI – SIÊU THỰC TẾ -->
        <div class="row g-3 mb-4">
            <!-- 1. Tổng vé hôm nay -->
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stats-card" style="border-left-color: #10b981;">
                    <div class="stats-icon" style="background: #2fd085ff;"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stats-value text-success"><?= $today_stats['total_today'] ?></div>
                    <div class="stats-label">Tổng vé hôm nay</div>
                </div>
            </div>

            <!-- 2. Chờ check-in -->
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stats-card" style="border-left-color: #0dcaf0;">
                    <div class="stats-icon" style="background: #3391cfff;"><i class="fas fa-clock"></i></div>
                    <div class="stats-value text-info"><?= $today_stats['waiting_checkin'] ?></div>
                    <div class="stats-label">Chờ check-in</div>
                </div>
            </div>

            <!-- 3. Đã hủy hôm nay -->
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stats-card" style="border-left-color: #ef4444;">
                    <div class="stats-icon" style="background: #d62d2dff;"><i class="fas fa-times-circle"></i></div>
                    <div class="stats-value text-danger"><?= $today_stats['cancelled_today'] ?></div>
                    <div class="stats-label">Đã hủy hôm nay</div>
                </div>
            </div>

            <!-- 4. Doanh thu hôm nay -->
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stats-card" style="border-left-color: #8b5cf6;">
                    <div class="stats-icon" style="background: #681eb8ff;"><i class="fas fa-coins"></i></div>
                    <div class="stats-value text-primary"><?= number_format($today_stats['revenue_today']) ?>đ</div>
                    <div class="stats-label">Doanh thu hôm nay</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-card fade-in-up">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Chuyến xe</label>
                    <select class="form-select" name="trip_id">
                        <option value="">Tất cả</option>
                        <?php foreach ($trips as $trip): ?>
                        <option value="<?= $trip['trip_id'] ?>" <?= $trip_filter == $trip['trip_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($trip['start_point'] . ' → ' . $trip['end_point'] . ' (' . date('d/m H:i', strtotime($trip['departure_time'])) . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status">
                        <option value="">Tất cả</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                        <option value="checked_in" <?= $status_filter == 'checked_in' ? 'selected' : '' ?>>Đã check-in</option>
                        <option value="used" <?= $status_filter == 'used' ? 'selected' : '' ?>>Đã sử dụng</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày</label>
                    <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Lọc</button>
                    <a href="tickets.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Xóa</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="content-card fade-in-up">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Mã vé</th>
                            <th>Hành khách</th>
                            <th>Chuyến</th>
                            <th>Ghế</th>
                            <th>Giá</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets): ?>
                            <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['ticket_code'] ?: '#'.str_pad($t['ticket_id'], 6, '0', STR_PAD_LEFT)) ?></strong></td>
                                <td>
                                    <div><?= htmlspecialchars($t['passenger_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($t['passenger_phone']) ?></small>
                                    <?php if ($t['passenger_email']): ?><br><small class="text-muted"><?= htmlspecialchars($t['passenger_email']) ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($t['start_point'] . ' → ' . $t['end_point']) ?></div>
                                    <small class="text-muted"><?= date('d/m H:i', strtotime($t['departure_time'])) ?> - <?= date('H:i', strtotime($t['arrival_time'])) ?></small>
                                    <br><small class="text-muted"><?= htmlspecialchars($t['license_plate']) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($t['seat_number'] ?? 'Chưa chọn') ?></span></td>
                                <td><strong><?= number_format($t['price']) ?>đ</strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $t['status'] ?>">
                                        <?= ['active'=>'Hoạt động','checked_in'=>'Check-in','used'=>'Đã dùng','cancelled'=>'Hủy'][$t['status']] ?? $t['status'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button class="btn btn-outline-primary btn-action" onclick="editTicket(<?= $t['ticket_id'] ?>)" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($t['status'] !== 'checked_in'): ?>
                                        <button class="btn btn-info btn-action text-white" onclick="updateTicketStatus(<?= $t['ticket_id'] ?>, 'checked_in')" title="Check-in">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($t['status'] !== 'used'): ?>
                                        <button class="btn btn-secondary btn-action text-white" onclick="updateTicketStatus(<?= $t['ticket_id'] ?>, 'used')" title="Đã dùng">
                                            <i class="fas fa-bus"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($t['status'] !== 'cancelled'): ?>
                                        <button class="btn btn-danger btn-action" onclick="updateTicketStatus(<?= $t['ticket_id'] ?>, 'cancelled')" title="Hủy">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">Chưa có vé nào</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh sửa vé</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editTicketForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_ticket">
                        <input type="hidden" name="ticket_id" id="edit_ticket_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tên *</label>
                                <input type="text" class="form-control" name="passenger_name" id="edit_passenger_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SĐT *</label>
                                <input type="tel" class="form-control" name="passenger_phone" id="edit_passenger_phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="passenger_email" id="edit_passenger_email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số ghế *</label>
                                <input type="text" class="form-control" name="seat_number" id="edit_seat_number" required>
                            </div>
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

    <!-- Status Form -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_ticket_status">
        <input type="hidden" name="ticket_id" id="statusTicketId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tickets = <?= json_encode($tickets) ?>;

        function updateTicketStatus(id, status) {
            const text = {checked_in: 'check-in', used: 'đánh dấu đã sử dụng', cancelled: 'hủy'}[status];
            if (confirm(`Xác nhận ${text} vé này?`)) {
                document.getElementById('statusTicketId').value = id;
                document.getElementById('statusValue').value = status;
                document.getElementById('statusForm').submit();
            }
        }

        function editTicket(id) {
            const t = tickets.find(x => x.ticket_id == id);
            if (!t) return alert('Không tìm thấy vé.');

            document.getElementById('edit_ticket_id').value = t.ticket_id;
            document.getElementById('edit_passenger_name').value = t.passenger_name;
            document.getElementById('edit_passenger_phone').value = t.passenger_phone;
            document.getElementById('edit_passenger_email').value = t.passenger_email || '';
            document.getElementById('edit_seat_number').value = t.seat_number;

            new bootstrap.Modal(document.getElementById('editTicketModal')).show();
        }
    </script>
</body>
</html>