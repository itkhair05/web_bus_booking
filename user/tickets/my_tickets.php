<?php
/**
 * My Tickets Page
 * Danh sách vé đã đặt của user
 * Version: Optimized với trip_id trong bookings
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Require login
requireLogin();

$pageTitle = 'Vé của tôi - Bus Booking';
$currentPage = 'my_tickets';

// Get current user
$userId = getCurrentUserId();

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';

// Build WHERE clause based on filter
$whereClause = "b.user_id = ?";
$params = [$userId];
$paramTypes = "i";

switch ($filter) {
    case 'pending':
        $whereClause .= " AND b.payment_status = 'unpaid'";
        break;
    case 'paid':
        $whereClause .= " AND b.payment_status = 'paid'";
        break;
    case 'completed':
        // Vé hoàn thành = đã thanh toán và đã qua giờ đến
        $whereClause .= " AND b.payment_status = 'paid' AND t.arrival_time IS NOT NULL AND t.arrival_time < NOW()";
        break;
    case 'cancelled':
        $whereClause .= " AND b.status = 'cancelled'";
        break;
}

// Simple query - direct JOIN with trips
$sql = "
    SELECT 
        b.booking_id,
        b.booking_code,
        b.total_price,
        b.final_price,
        b.status,
        b.payment_status,
        b.created_at,
        t.trip_id,
        t.departure_time,
        t.arrival_time,
        r.origin,
        r.destination,
        p.name as partner_name,
        v.vehicle_type,
        v.license_plate,
        GROUP_CONCAT(DISTINCT tk.seat_number ORDER BY tk.seat_number SEPARATOR ', ') as seat_numbers,
        COUNT(DISTINCT tk.ticket_id) as ticket_count
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    LEFT JOIN routes r ON t.route_id = r.route_id
    LEFT JOIN partners p ON t.partner_id = p.partner_id
    LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    LEFT JOIN tickets tk ON b.booking_id = tk.booking_id
    WHERE {$whereClause}
    GROUP BY b.booking_id
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include '../../includes/header_user.php';
?>

<style>
/* My Tickets Page Styles */
.my-tickets-page {
    background: #f5f7fa;
    min-height: calc(100vh - 200px);
    padding: 40px 0;
}

.tickets-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

/* Filter Tabs */
.filter-tabs {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tab {
    padding: 10px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #666;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab:hover {
    border-color: #1E90FF;
    color: #1E90FF;
    background: #f0f8ff;
}

.tab.active {
    background: #1E90FF;
    color: white;
    border-color: #1E90FF;
}

.tab i {
    font-size: 14px;
}

/* Tickets Grid */
.tickets-grid {
    display: grid;
    gap: 20px;
}

/* Ticket Card */
.ticket-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.ticket-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    border-color: #1E90FF;
    transform: translateY(-2px);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px dashed #e5e7eb;
}

.booking-code-section {
    display: flex;
    align-items: center;
    gap: 12px;
}

.booking-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1E90FF, #4169E1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.booking-code-info h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin: 0 0 5px 0;
}

.booking-code-info p {
    font-size: 13px;
    color: #999;
    margin: 0;
}

/* Status Badge */
.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: #FFF3CD;
    color: #856404;
}

.status-paid {
    background: #D1ECF1;
    color: #0C5460;
}

.status-completed {
    background: #D4EDDA;
    color: #155724;
}

.status-cancelled {
    background: #F8D7DA;
    color: #721C24;
}

/* Ticket Body */
.ticket-body {
    margin-bottom: 20px;
}

.route-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.route-location {
    flex: 1;
}

.route-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 5px;
}

.route-name {
    font-size: 20px;
    font-weight: 700;
    color: #333;
}

.route-arrow {
    font-size: 24px;
    color: #1E90FF;
}

/* Trip Details */
.trip-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.detail-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1E90FF;
    font-size: 18px;
}

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 12px;
    color: #999;
    margin-bottom: 3px;
}

.detail-value {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

/* Ticket Footer */
.ticket-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
}

.price-section {
    display: flex;
    flex-direction: column;
}

.price-label {
    font-size: 13px;
    color: #999;
    margin-bottom: 5px;
}

.price-value {
    font-size: 24px;
    font-weight: 700;
    color: #FF6B35;
}

.ticket-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: #1E90FF;
    color: white;
}

.btn-primary:hover {
    background: #1873CC;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 144, 255, 0.3);
}

.btn-danger {
    background: #DC3545;
    color: white;
}

.btn-danger:hover {
    background: #C82333;
}

.btn-outline {
    background: white;
    color: #666;
    border: 2px solid #e5e7eb;
}

.btn-outline:hover {
    border-color: #1E90FF;
    color: #1E90FF;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.empty-icon {
    font-size: 80px;
    color: #e5e7eb;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    font-size: 16px;
    margin-bottom: 30px;
}

/* Responsive */
@media (max-width: 768px) {
    .my-tickets-page {
        padding: 20px 0;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 10px;
    }
    
    .tab {
        white-space: nowrap;
    }
    
    .ticket-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .route-info {
        flex-direction: column;
        text-align: center;
    }
    
    .route-arrow {
        transform: rotate(90deg);
    }
    
    .trip-details {
        grid-template-columns: 1fr;
    }
    
    .ticket-footer {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .ticket-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="my-tickets-page">
    <div class="tickets-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-ticket-alt"></i> Vé của tôi</h1>
            <p>Quản lý và xem chi tiết các vé đã đặt</p>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <div class="tabs">
                <a href="?filter=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    Tất cả (<?php echo count($bookings); ?>)
                </a>
                <a href="?filter=pending" class="tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    Chưa thanh toán
                </a>
                <a href="?filter=paid" class="tab <?php echo $filter === 'paid' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    Đã thanh toán
                </a>
                <a href="?filter=completed" class="tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-flag-checkered"></i>
                    Hoàn thành
                </a>
                <a href="?filter=cancelled" class="tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i>
                    Đã hủy
                </a>
            </div>
        </div>
        
        <!-- Tickets Grid -->
        <?php if (!empty($bookings)): ?>
            <div class="tickets-grid">
                <?php foreach ($bookings as $booking): ?>
                    <?php
                    // Determine status badge
                    $statusClass = 'status-pending';
                    $statusText = 'Chưa thanh toán';

                    $isCompleted = (
                        $booking['payment_status'] === 'paid'
                        && !empty($booking['arrival_time'])
                        && strtotime($booking['arrival_time']) < time()
                    );
                    
                    if ($booking['status'] === 'cancelled') {
                        $statusClass = 'status-cancelled';
                        $statusText = 'Đã hủy';
                    } elseif ($isCompleted || $booking['status'] === 'completed') {
                        $statusClass = 'status-completed';
                        $statusText = 'Hoàn thành';
                    } elseif ($booking['payment_status'] === 'paid') {
                        $statusClass = 'status-paid';
                        $statusText = 'Đã thanh toán';
                    }
                    
                    // Format datetime
                    $departureTime = $booking['departure_time'] ? date('H:i, d/m/Y', strtotime($booking['departure_time'])) : 'N/A';
                    $createdAt = date('d/m/Y H:i', strtotime($booking['created_at']));
                    
                    // Get seat numbers
                    $seatNumbers = $booking['seat_numbers'] ?: 'N/A';
                    $ticketCount = $booking['ticket_count'] ?: 0;
                    ?>
                    
                    <div class="ticket-card">
                        <!-- Header -->
                        <div class="ticket-header">
                            <div class="booking-code-section">
                                <div class="booking-icon">
                                    <i class="fas fa-bus"></i>
                                </div>
                                <div class="booking-code-info">
                                    <h3><?php echo htmlspecialchars($booking['booking_code']); ?></h3>
                                    <p>Đặt ngày: <?php echo $createdAt; ?></p>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <!-- Body -->
                        <div class="ticket-body">
                            <!-- Route -->
                            <div class="route-info">
                                <div class="route-location">
                                    <div class="route-label">Điểm đi</div>
                                    <div class="route-name"><?php echo htmlspecialchars($booking['origin'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="route-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="route-location">
                                    <div class="route-label">Điểm đến</div>
                                    <div class="route-name"><?php echo htmlspecialchars($booking['destination'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <!-- Trip Details -->
                            <div class="trip-details">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Giờ khởi hành</div>
                                        <div class="detail-value"><?php echo $departureTime; ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Nhà xe</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($booking['partner_name'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-chair"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Số ghế</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($seatNumbers); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-ticket-alt"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Số vé</div>
                                        <div class="detail-value"><?php echo $ticketCount; ?> vé</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="ticket-footer">
                            <div class="price-section">
                                <div class="price-label">Tổng tiền</div>
                                <div class="price-value">
                                    <?php echo number_format($booking['final_price'] ?? $booking['total_price'], 0, ',', '.'); ?>đ
                                </div>
                            </div>
                            
                            <div class="ticket-actions">
                                <a href="eticket.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                    Xem chi tiết
                                </a>
                                
                                <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                    <?php if ($booking['payment_status'] === 'unpaid'): ?>
                                        <a href="../booking/payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-credit-card"></i>
                                            Thanh toán
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)" class="btn btn-danger" title="Hủy trước giờ chạy ≥ 5 giờ: hoàn 80% tiền đã thanh toán. Trong 5 giờ trước giờ chạy: không thể hủy/hoàn. Vé chưa thanh toán: hủy miễn phí.">
                                        <i class="fas fa-times"></i>
                                        Hủy vé
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3>Chưa có vé nào</h3>
                <p>Bạn chưa đặt vé nào. Hãy tìm kiếm và đặt vé ngay!</p>
                <a href="<?php echo appUrl(); ?>" class="btn btn-primary" style="display: inline-flex;">
                    <i class="fas fa-search"></i>
                    Tìm chuyến xe
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
function cancelBooking(bookingId) {
    if (confirm('Bạn có chắc chắn muốn hủy vé này?\n\nQuy định:\n- Hủy trước giờ chạy >= 5 giờ: hoàn 80% số tiền đã thanh toán.\n- Trong 5 giờ trước giờ chạy: không thể hủy/hoàn.\n- Vé chưa thanh toán: hủy miễn phí.')) {
        window.location.href = '../booking/cancel_booking.php?booking_id=' + bookingId;
    }
}
</script>

<?php
// Include footer
include '../../includes/footer_user.php';
?>
