<?php
/**
 * E-Ticket Page
 * Trang chi tiết vé điện tử với QR code check-in
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// NOTE: Không require login vì user có thể đặt vé guest (không cần đăng nhập)
// Thay vào đó, verify ownership sau khi lấy booking

$pageTitle = 'Chi tiết vé - Bus Booking';
$currentPage = 'my_tickets';

// Get booking ID
$bookingId = intval($_GET['booking_id'] ?? 0);

if (empty($bookingId)) {
    $_SESSION['error'] = 'Mã đặt vé không hợp lệ.';
    redirect(appUrl());
}

// Query booking details FIRST (without user_id check - we'll verify after)
$sql = "
    SELECT 
        b.booking_id,
        b.user_id,
        b.booking_code,
        b.total_price,
        b.discount_amount,
        b.final_price,
        b.status,
        b.payment_status,
        b.created_at,
        t.trip_id,
        t.departure_time,
        t.arrival_time,
        t.price as trip_price,
        r.route_name,
        r.origin,
        r.destination,
        r.distance_km,
        r.duration_hours,
        p.name as partner_name,
        p.phone as partner_phone,
        p.email as partner_email,
        v.vehicle_type,
        v.license_plate
    FROM bookings b
    LEFT JOIN trips t ON b.trip_id = t.trip_id
    LEFT JOIN routes r ON t.route_id = r.route_id
    LEFT JOIN partners p ON t.partner_id = p.partner_id
    LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    WHERE b.booking_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

// Check if booking exists
if (!$booking) {
    $_SESSION['error'] = 'Không tìm thấy vé.';
    redirect(appUrl());
}

// SECURITY: Verify booking ownership AFTER getting booking
// If user is logged in, verify user_id matches
// If user is not logged in, allow if booking is guest booking (user_id = 0 or guest user)
$userId = null;
if (isLoggedIn()) {
    $currentUserId = getCurrentUserId();
    // If booking has a real user_id, it must match current user
    if ($booking['user_id'] > 0 && $booking['user_id'] != $currentUserId) {
        logError('Unauthorized eticket access attempt', [
            'booking_id' => $bookingId,
            'booking_user_id' => $booking['user_id'],
            'current_user_id' => $currentUserId
        ]);
        $_SESSION['error'] = 'Bạn không có quyền xem vé này.';
        // Redirect về trang chủ thay vì my_tickets (vì có thể user chưa login)
        redirect(appUrl());
        exit;
    }
    // Use booking's user_id
    $userId = $booking['user_id'];
} else {
    // Guest booking - use booking's user_id (can be 0 or guest user id)
    $userId = $booking['user_id'];
}

// Get tickets (passengers)
$stmt = $conn->prepare("
    SELECT 
        ticket_id,
        seat_number,
        passenger_name,
        passenger_phone,
        passenger_email,
        ticket_code,
        status,
        checked_in_at
    FROM tickets
    WHERE booking_id = ?
    ORDER BY seat_number
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Generate QR code URL for check-in
$qrData = $booking['booking_code'];

// Primary: QR Server API (free, no registration)
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

// Fallback: QuickChart API (if primary fails)
$qrUrlFallback = "https://quickchart.io/qr?text=" . urlencode($qrData) . "&size=300";

// Format data
$departureTime = $booking['departure_time'] ? date('H:i, d/m/Y', strtotime($booking['departure_time'])) : 'N/A';
$arrivalTime = $booking['arrival_time'] ? date('H:i, d/m/Y', strtotime($booking['arrival_time'])) : 'N/A';
$createdAt = date('d/m/Y H:i', strtotime($booking['created_at']));

// Status badge
$statusClass = 'status-pending';
$statusText = 'Chưa thanh toán';

if ($booking['status'] === 'cancelled') {
    $statusClass = 'status-cancelled';
    $statusText = 'Đã hủy';
} elseif ($booking['status'] === 'completed') {
    $statusClass = 'status-completed';
    $statusText = 'Hoàn thành';
} elseif ($booking['payment_status'] === 'paid') {
    $statusClass = 'status-paid';
    $statusText = 'Đã thanh toán';
}

// Check if can cancel
$canCancel = ($booking['status'] === 'pending' || $booking['status'] === 'confirmed') 
             && $booking['status'] !== 'cancelled' 
             && $booking['status'] !== 'completed';

// Include header
include '../../includes/header_user.php';
?>

<style>
/* E-Ticket Page Styles */
.eticket-page {
    background: #f5f7fa;
    min-height: calc(100vh - 200px);
    padding: 40px 0;
}

.eticket-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Action Bar */
.action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #666;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.back-link:hover {
    color: #1E90FF;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

/* E-Ticket Card */
.eticket-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 20px;
}

/* Ticket Header */
.ticket-header {
    background: linear-gradient(135deg, #1E90FF 0%, #4169E1 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.ticket-header h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.ticket-header .booking-code {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: 2px;
    margin: 10px 0;
}

/* Status Badge in Header */
.header-status {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    background: rgba(255,255,255,0.2);
    margin-top: 10px;
}

/* Ticket Body */
.ticket-body {
    padding: 40px;
}

/* Section */
.ticket-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Route Display */
.route-display {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 30px;
}

.route-point {
    flex: 1;
    text-align: center;
}

.route-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 8px;
}

.route-city {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.route-icon {
    font-size: 32px;
    color: #1E90FF;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-item {
    display: flex;
    align-items: start;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1E90FF;
    font-size: 18px;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 12px;
    color: #999;
    margin-bottom: 5px;
    font-weight: 600;
}

.info-value {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

/* Passengers Table */
.passengers-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.passengers-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: #666;
    border-bottom: 2px solid #e5e7eb;
}

.passengers-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.seat-badge {
    background: #1E90FF;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}

/* QR Code Section */
.qr-section {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
}

.qr-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 15px;
}

.qr-code-img {
    width: 250px;
    height: 250px;
    margin: 20px auto;
    padding: 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.qr-instruction {
    font-size: 14px;
    color: #666;
    margin-top: 15px;
}

.qr-instruction i {
    color: #1E90FF;
    margin-right: 5px;
}

/* Payment Summary */
.payment-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 25px;
    border-radius: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 15px;
}

.summary-row.total {
    border-top: 2px solid #dee2e6;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 20px;
    font-weight: 700;
    color: #FF6B35;
}

/* Buttons */
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

.btn-success {
    background: #28A745;
    color: white;
}

.btn-success:hover {
    background: #218838;
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

/* Print Styles */
@media print {
    .action-bar,
    .action-buttons,
    header,
    footer,
    .btn {
        display: none !important;
    }
    
    .eticket-page {
        background: white;
        padding: 0;
    }
    
    .eticket-card {
        box-shadow: none;
        page-break-inside: avoid;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .eticket-page {
        padding: 20px 0;
    }
    
    .ticket-body {
        padding: 20px;
    }
    
    .action-bar {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .route-display {
        flex-direction: column;
    }
    
    .route-icon {
        transform: rotate(90deg);
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .passengers-table {
        font-size: 12px;
    }
    
    .passengers-table th,
    .passengers-table td {
        padding: 8px 5px;
    }
}
</style>

<div class="eticket-page">
    <div class="eticket-container">
        
        <!-- Action Bar -->
        <div class="action-bar">
            <?php if (isLoggedIn()): ?>
                <a href="my_tickets.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại danh sách vé
                </a>
            <?php else: ?>
                <a href="<?php echo appUrl('user/booking/success.php?booking_id=' . $bookingId); ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại trang đặt vé thành công
                </a>
            <?php endif; ?>
            
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i>
                    In vé
                </button>
                
                <?php if ($canCancel): ?>
                    <form method="POST" action="../booking/cancel_booking.php" style="display: inline;" onsubmit="return confirmCancelBooking()">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i>
                            Hủy vé
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- E-Ticket Card -->
        <div class="eticket-card">
            
            <!-- Header -->
            <div class="ticket-header">
                <h1><i class="fas fa-ticket-alt"></i> VÉ ĐIỆN TỬ</h1>
                <div class="booking-code"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
                <div class="header-status"><?php echo $statusText; ?></div>
                <p style="margin-top: 15px; font-size: 14px; opacity: 0.9;">
                    Đặt ngày: <?php echo $createdAt; ?>
                </p>
            </div>
            
            <!-- Body -->
            <div class="ticket-body">
                
                <!-- Route -->
                <div class="route-display">
                    <div class="route-point">
                        <div class="route-label">Điểm đi</div>
                        <div class="route-city"><?php echo htmlspecialchars($booking['origin'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="route-icon">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="route-point">
                        <div class="route-label">Điểm đến</div>
                        <div class="route-city"><?php echo htmlspecialchars($booking['destination'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <!-- Trip Information -->
                <div class="ticket-section">
                    <div class="section-title"><i class="fas fa-bus"></i> Thông tin chuyến xe</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-building"></i></div>
                            <div class="info-content">
                                <div class="info-label">Nhà xe</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['partner_name'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-bus-alt"></i></div>
                            <div class="info-content">
                                <div class="info-label">Loại xe</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['vehicle_type'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-id-card"></i></div>
                            <div class="info-content">
                                <div class="info-label">Biển số xe</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['license_plate'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div class="info-content">
                                <div class="info-label">Giờ khởi hành</div>
                                <div class="info-value"><?php echo $departureTime; ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div class="info-content">
                                <div class="info-label">Giờ đến (dự kiến)</div>
                                <div class="info-value"><?php echo $arrivalTime; ?></div>
                            </div>
                        </div>
                        
                        <?php if ($booking['duration_hours']): ?>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-hourglass-half"></i></div>
                            <div class="info-content">
                                <div class="info-label">Thời gian di chuyển</div>
                                <div class="info-value"><?php echo $booking['duration_hours']; ?> giờ</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Passengers -->
                <div class="ticket-section">
                    <div class="section-title"><i class="fas fa-users"></i> Thông tin hành khách</div>
                    <table class="passengers-table">
                        <thead>
                            <tr>
                                <th>Số ghế</th>
                                <th>Họ tên</th>
                                <th>Số điện thoại</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><span class="seat-badge"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></td>
                                <td><?php echo htmlspecialchars($ticket['passenger_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['passenger_phone']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['passenger_email'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- QR Code -->
                <div class="ticket-section">
                    <div class="qr-section">
                        <h3><i class="fas fa-qrcode"></i> Mã QR Check-in</h3>
                        <img src="<?php echo $qrUrl; ?>" 
                             alt="QR Code" 
                             class="qr-code-img"
                             onerror="this.onerror=null; this.src='<?php echo $qrUrlFallback; ?>';">
                        <p class="qr-instruction">
                            <i class="fas fa-info-circle"></i>
                            Vui lòng xuất trình mã QR này khi lên xe
                        </p>
                        <p style="font-size: 18px; font-weight: 700; color: #333; margin-top: 15px;">
                            <?php echo htmlspecialchars($booking['booking_code']); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Payment Summary -->
                <div class="ticket-section">
                    <div class="section-title"><i class="fas fa-receipt"></i> Thông tin thanh toán</div>
                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Tổng tiền vé:</span>
                            <span><?php echo number_format($booking['total_price'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="summary-row">
                            <span>Giảm giá:</span>
                            <span style="color: #28A745;">- <?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Tổng thanh toán:</span>
                            <span><?php echo number_format($booking['final_price'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="summary-row" style="padding-top: 15px; border-top: 1px solid #dee2e6;">
                            <span>Trạng thái thanh toán:</span>
                            <span style="font-weight: 700; color: <?php echo $booking['payment_status'] === 'paid' ? '#28A745' : '#FF6B35'; ?>;">
                                <?php echo $booking['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <?php if ($booking['partner_phone']): ?>
                <div class="ticket-section">
                    <div class="section-title"><i class="fas fa-phone"></i> Liên hệ hỗ trợ</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="info-content">
                                <div class="info-label">Hotline nhà xe</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['partner_phone']); ?></div>
                            </div>
                        </div>
                        <?php if ($booking['partner_email']): ?>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-envelope"></i></div>
                            <div class="info-content">
                                <div class="info-label">Email nhà xe</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['partner_email']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
</div>

<script>
function confirmCancelBooking() {
    return confirm('Bạn có chắc chắn muốn hủy vé này?\n\nLưu ý: Vé đã thanh toán sẽ được hoàn tiền theo chính sách của nhà xe.');
}
</script>

<?php
// Include footer
include '../../includes/footer_user.php';
?>

