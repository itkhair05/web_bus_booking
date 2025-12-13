<?php
require_once '../config/session.php';
$conn = require_once '../config/db.php';
require_once '../core/auth.php';
require_once '../core/helpers.php';
require_once '../core/csrf.php';

requireLogin();
$userId = getCurrentUserId();
$user = getCurrentUser();

// 1. Get user stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as completed_trips,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_spent
    FROM bookings 
    WHERE user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// 2. Get upcoming trips (next 30 days)
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_code, b.total_price, b.status, b.payment_status,
           t.trip_id, t.departure_time, 
           r.start_point, r.end_point,
           p.name as partner_name,
           COUNT(tk.ticket_id) as seat_count
    FROM bookings b
    JOIN trips t ON b.trip_id = t.trip_id
    JOIN routes r ON t.route_id = r.route_id
    JOIN partners p ON t.partner_id = p.partner_id
    LEFT JOIN tickets tk ON b.booking_id = tk.booking_id
    WHERE b.user_id = ? 
      AND t.departure_time >= NOW()
      AND t.departure_time <= DATE_ADD(NOW(), INTERVAL 30 DAY)
      AND b.status IN ('pending', 'confirmed')
    GROUP BY b.booking_id
    ORDER BY t.departure_time ASC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$upcomingTrips = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Get recent bookings
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_code, b.total_price, b.status, b.payment_status, b.created_at,
           t.trip_id, t.departure_time,
           r.start_point, r.end_point,
           p.name as partner_name,
           COUNT(tk.ticket_id) as seat_count
    FROM bookings b
    JOIN trips t ON b.trip_id = t.trip_id
    JOIN routes r ON t.route_id = r.route_id
    JOIN partners p ON t.partner_id = p.partner_id
    LEFT JOIN tickets tk ON b.booking_id = tk.booking_id
    WHERE b.user_id = ?
    GROUP BY b.booking_id
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Get unread notifications count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$unreadNotifications = $stmt->get_result()->fetch_assoc()['count'];

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bus Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        
        body {
            background: #f3f4f6;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .welcome-card h1 {
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-icon.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.bg-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }
        
        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .trip-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .trip-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        
        .trip-route {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .trip-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .trip-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .trip-info-item i {
            color: var(--primary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-action {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-primary-custom:hover {
            background: #1d4ed8;
            color: white;
            transform: translateY(-2px);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .quick-action-btn:hover {
            border-color: var(--primary);
            background: #eff6ff;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .quick-action-btn i {
            font-size: 32px;
            color: var(--primary);
        }
        
        .quick-action-btn span {
            font-weight: 600;
            color: #1f2937;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .welcome-card h1 { font-size: 24px; }
            .stat-value { font-size: 24px; }
            .trip-info { gap: 10px; }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header_user.php'; ?>
    
    <div class="dashboard-container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h1>👋 Xin chào, <?php echo e($user['name']); ?>!</h1>
            <p>Chào mừng bạn trở lại. Đây là tổng quan về tài khoản của bạn.</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_bookings']); ?></div>
                    <div class="stat-label">Tổng số vé</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['completed_trips']); ?></div>
                    <div class="stat-label">Chuyến đã đi</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0); ?>đ</div>
                    <div class="stat-label">Tổng chi tiêu</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-value"><?php echo $unreadNotifications; ?></div>
                    <div class="stat-label">Thông báo mới</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Thao tác nhanh
            </h2>
            <div class="quick-actions">
                <a href="<?php echo appUrl(); ?>" class="quick-action-btn">
                    <i class="fas fa-search"></i>
                    <span>Tìm chuyến xe</span>
                </a>
                <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>" class="quick-action-btn">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Vé của tôi</span>
                </a>
                <a href="<?php echo appUrl('user/payments/history.php'); ?>" class="quick-action-btn">
                    <i class="fas fa-history"></i>
                    <span>Lịch sử giao dịch</span>
                </a>
                <a href="<?php echo appUrl('user/profile/index.php'); ?>" class="quick-action-btn">
                    <i class="fas fa-user"></i>
                    <span>Hồ sơ</span>
                </a>
                <a href="<?php echo appUrl('user/profile/change_password.php'); ?>" class="quick-action-btn">
                    <i class="fas fa-lock"></i>
                    <span>Đổi mật khẩu</span>
                </a>
            </div>
        </div>
        
        <!-- Upcoming Trips -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i>
                Chuyến xe sắp tới
            </h2>
            
            <?php if (empty($upcomingTrips)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Bạn chưa có chuyến xe nào sắp tới</p>
                    <a href="<?php echo appUrl(); ?>" class="btn btn-primary-custom btn-action mt-3">
                        Đặt vé ngay
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingTrips as $trip): ?>
                    <div class="trip-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="trip-route">
                                <?php echo e($trip['start_point']); ?> <i class="fas fa-arrow-right" style="font-size: 14px; color: var(--primary);"></i> <?php echo e($trip['end_point']); ?>
                            </div>
                            <span class="status-badge <?php echo $trip['status']; ?>">
                                <?php 
                                $statusText = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $statusText[$trip['status']] ?? $trip['status'];
                                ?>
                            </span>
                        </div>
                        
                        <div class="trip-info">
                            <div class="trip-info-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d/m/Y', strtotime($trip['departure_time'])); ?></span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('H:i', strtotime($trip['departure_time'])); ?></span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-bus"></i>
                                <span><?php echo e($trip['partner_name']); ?></span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-users"></i>
                                <span><?php echo $trip['seat_count']; ?> ghế</span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span><?php echo number_format($trip['total_price']); ?>đ</span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <a href="<?php echo appUrl('user/tickets/eticket.php?booking_id=' . $trip['booking_id']); ?>" class="btn btn-primary-custom btn-action btn-sm">
                                <i class="fas fa-ticket-alt"></i> Xem vé
                            </a>
                            <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>" class="btn btn-outline-secondary btn-action btn-sm">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-3">
                    <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>" class="btn btn-outline-primary">
                        Xem tất cả vé <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Bookings -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Lịch sử đặt vé gần đây
            </h2>
            
            <?php if (empty($recentBookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Bạn chưa có đặt vé nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentBookings as $booking): ?>
                    <div class="trip-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="trip-route">
                                    <?php echo e($booking['start_point']); ?> <i class="fas fa-arrow-right" style="font-size: 14px; color: var(--primary);"></i> <?php echo e($booking['end_point']); ?>
                                </div>
                                <small class="text-muted">Mã: <?php echo e($booking['booking_code']); ?></small>
                            </div>
                            <span class="status-badge <?php echo $booking['status']; ?>">
                                <?php 
                                $statusText = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $statusText[$booking['status']] ?? $booking['status'];
                                ?>
                            </span>
                        </div>
                        
                        <div class="trip-info">
                            <div class="trip-info-item">
                                <i class="fas fa-calendar"></i>
                                <span>Đặt: <?php echo date('d/m/Y', strtotime($booking['created_at'])); ?></span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-bus"></i>
                                <span>Khởi hành: <?php echo date('d/m/Y H:i', strtotime($booking['departure_time'])); ?></span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-users"></i>
                                <span><?php echo $booking['seat_count']; ?> ghế</span>
                            </div>
                            <div class="trip-info-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span><?php echo number_format($booking['total_price']); ?>đ</span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <a href="<?php echo appUrl('user/tickets/eticket.php?booking_id=' . $booking['booking_id']); ?>" class="btn btn-primary-custom btn-action btn-sm">
                                <i class="fas fa-ticket-alt"></i> Xem vé
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-3">
                    <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>" class="btn btn-outline-primary">
                        Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

