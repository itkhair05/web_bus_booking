<?php
require_once '../../config/session.php';
$conn = require_once '../../config/db.php';
require_once '../../core/auth.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

requireLogin();
$userId = getCurrentUserId();

// Filters
$status = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereClause = "b.user_id = ?";
$params = [$userId];
$types = "i";

if ($status !== 'all') {
    $whereClause .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($dateFrom) {
    $whereClause .= " AND DATE(p.created_at) >= ?";
    $params[] = $dateFrom;
    $types .= "s";
}

if ($dateTo) {
    $whereClause .= " AND DATE(p.created_at) <= ?";
    $params[] = $dateTo;
    $types .= "s";
}

if ($search) {
    $whereClause .= " AND (b.booking_code LIKE ? OR p.transaction_code LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Get total count
$countQuery = "
    SELECT COUNT(*) as total 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    WHERE $whereClause
";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalPayments = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalPayments / $limit);

// Get payments
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$query = "
    SELECT 
        p.*,
        b.booking_code,
        b.total_price as booking_amount,
        t.departure_time,
        r.start_point,
        r.end_point,
        pt.name as partner_name,
        COUNT(tk.ticket_id) as seat_count
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN trips t ON b.trip_id = t.trip_id
    JOIN routes r ON t.route_id = r.route_id
    JOIN partners pt ON t.partner_id = pt.partner_id
    LEFT JOIN tickets tk ON b.booking_id = tk.booking_id
    WHERE $whereClause
    GROUP BY p.payment_id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get stats
$statsQuery = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN p.status = 'success' THEN p.amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END) as total_pending
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    WHERE b.user_id = ?
";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bind_param("i", $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

$pageTitle = 'Lịch sử giao dịch';
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
        body {
            background: #f3f4f6;
        }
        
        .payments-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 15px;
        }
        
        .page-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .stat-card-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
        }
        
        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .payment-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .payment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .payment-route {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .payment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .info-item i {
            color: #2563eb;
            width: 20px;
        }
        
        .payment-amount {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php require_once '../../includes/header_user.php'; ?>
    
    <div class="payments-container">
        <div class="page-header">
            <h1><i class="fas fa-receipt me-2"></i> Lịch sử giao dịch</h1>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-card-label">Tổng giao dịch</div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_transactions']); ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-card-label">Đã thanh toán</div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_paid']); ?>đ</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-card-label">Đang chờ</div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_pending']); ?>đ</div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>Thành công</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo e($dateFrom); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo e($dateTo); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" class="form-control" placeholder="Mã đặt vé, mã GD..." value="<?php echo e($search); ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <a href="<?php echo appUrl('user/payments/history.php'); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Payments List -->
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>Không tìm thấy giao dịch nào</p>
            </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <div class="payment-item">
                    <div class="payment-header">
                        <div>
                            <div class="payment-route">
                                <?php echo e($payment['start_point']); ?> 
                                <i class="fas fa-arrow-right" style="font-size: 16px; color: #2563eb;"></i> 
                                <?php echo e($payment['end_point']); ?>
                            </div>
                            <small class="text-muted">
                                Mã đặt vé: <strong><?php echo e($payment['booking_code']); ?></strong>
                            </small>
                        </div>
                        <span class="status-badge <?php echo $payment['status']; ?>">
                            <?php
                            $statusText = [
                                'success' => 'Thành công',
                                'pending' => 'Chờ xử lý',
                                'failed' => 'Thất bại'
                            ];
                            echo $statusText[$payment['status']] ?? $payment['status'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="payment-info">
                        <div class="info-item">
                            <i class="fas fa-calendar"></i>
                            <span>GD: <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-bus"></i>
                            <span>Khởi hành: <?php echo date('d/m/Y H:i', strtotime($payment['departure_time'])); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-building"></i>
                            <span><?php echo e($payment['partner_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-credit-card"></i>
                            <span><?php echo ucfirst($payment['method']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo $payment['seat_count']; ?> ghế</span>
                        </div>
                        <?php if ($payment['transaction_code']): ?>
                            <div class="info-item">
                                <i class="fas fa-hashtag"></i>
                                <span>Mã GD: <?php echo e($payment['transaction_code']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="payment-amount">
                            <?php echo number_format($payment['amount']); ?>đ
                        </div>
                        <a href="<?php echo appUrl('user/tickets/eticket.php?booking_id=' . $payment['booking_id']); ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-ticket-alt"></i> Xem vé
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

