<?php
/**
 * Booking Confirmation Page
 * Form điền thông tin hành khách và xác nhận đặt vé
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Get parameters
$tripId = intval($_GET['trip_id'] ?? 0);
$seats = sanitizeInput($_GET['seats'] ?? '');

if (empty($tripId) || empty($seats)) {
    redirect(appUrl());
}

// Parse seats
$seatNumbers = explode(',', $seats);
$totalSeats = count($seatNumbers);

// Get trip details
$query = "
    SELECT 
        t.trip_id,
        t.departure_time,
        t.arrival_time,
        t.price,
        t.available_seats,
        r.route_id,
        r.route_name,
        r.origin,
        r.destination,
        r.distance_km,
        r.duration_hours,
        p.partner_id,
        p.name AS company_name,
        u.phone as partner_phone,
        v.vehicle_id,
        v.vehicle_type,
        v.license_plate,
        v.total_seats
    FROM trips t
    INNER JOIN routes r ON t.route_id = r.route_id
    INNER JOIN partners p ON t.partner_id = p.partner_id
    INNER JOIN vehicles v ON t.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE t.trip_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tripId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect(appUrl());
}

$trip = $result->fetch_assoc();

// Calculate prices
$pricePerSeat = $trip['price'];
$subtotal = $pricePerSeat * $totalSeats;
$discount = 0;
$total = $subtotal - $discount;

// Get current user if logged in
$user = isLoggedIn() ? getCurrentUser() : null;

$pageTitle = 'Xác nhận đặt vé - BusBooking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
    body {
        background: #F3F4F6;
    }
    
    .booking-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .booking-layout {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 20px;
    }
    
    /* Main Form */
    .booking-form-section {
        background: #fff;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #E5E7EB;
    }
    
    .section-icon {
        width: 40px;
        height: 40px;
        background: #FF6B35;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #333;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-label .required {
        color: #EF4444;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #FF6B35;
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    }
    
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        cursor: pointer;
    }
    
    .passenger-card {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 16px;
    }
    
    .passenger-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .passenger-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .seat-badge {
        background: #FF6B35;
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .promo-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #E5E7EB;
    }
    
    .promo-input-wrapper {
        display: flex;
        gap: 12px;
    }
    
    .btn-apply-promo {
        background: #4F46E5;
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }
    
    /* Summary Sidebar */
    .booking-summary {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        height: fit-content;
        position: sticky;
        top: 20px;
    }
    
    .summary-title {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
    }
    
    .trip-summary {
        padding: 16px;
        background: #F9FAFB;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .trip-route {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .trip-location {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .trip-arrow {
        color: #9CA3AF;
    }
    
    .trip-detail {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
    }
    
    .seats-selected {
        margin: 16px 0;
        padding: 16px;
        background: #FFF5EB;
        border-radius: 8px;
    }
    
    .seats-label {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
    }
    
    .seats-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .seat-tag {
        background: #FF6B35;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .price-breakdown {
        padding: 16px 0;
        border-top: 1px solid #E5E7EB;
        border-bottom: 1px solid #E5E7EB;
        margin-bottom: 20px;
    }
    
    .price-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
    }
    
    .price-label {
        color: #666;
    }
    
    .price-value {
        font-weight: 600;
        color: #333;
    }
    
    .discount-value {
        color: #10B981;
    }
    
    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        background: #FFF5EB;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .total-label {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .total-value {
        font-size: 24px;
        font-weight: 700;
        color: #FF6B35;
    }
    
    .btn-confirm {
        width: 100%;
        padding: 16px;
        background: #FF6B35;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-confirm:hover {
        background: #E55A2B;
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(255, 107, 53, 0.3);
    }
    
    .policy-note {
        margin-top: 16px;
        padding: 12px;
        background: #EEF2FF;
        border-radius: 6px;
        font-size: 12px;
        color: #666;
        line-height: 1.5;
    }
    
    /* Responsive */
    @media (max-width: 968px) {
        .booking-layout {
            grid-template-columns: 1fr;
        }
        
        .booking-summary {
            order: -1;
            position: static;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="booking-page">
    <div class="booking-layout">
        <!-- Main Form -->
        <div class="booking-form-section">
            <form id="bookingForm" method="POST" action="<?php echo appUrl('api/bookings/create.php'); ?>">
                <?php echo csrfField(); ?>
                <input type="hidden" name="trip_id" value="<?php echo $tripId; ?>">
                <input type="hidden" name="seats" value="<?php echo e($seats); ?>">
                
                <!-- Contact Information -->
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="section-title">Thông tin liên hệ</h2>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            Họ và tên <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="contact_name" 
                            class="form-input" 
                            placeholder="Nguyễn Văn A"
                            value="<?php echo $user ? e($user['name']) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Số điện thoại <span class="required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            name="contact_phone" 
                            class="form-input" 
                            placeholder="0987654321"
                            value="<?php echo $user ? e($user['phone']) : ''; ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Email <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="contact_email" 
                        class="form-input" 
                        placeholder="email@example.com"
                        value="<?php echo $user ? e($user['email']) : ''; ?>"
                        required
                    >
                </div>
                
                <!-- Passengers Information -->
                <div class="section-header" style="margin-top: 32px;">
                    <div class="section-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="section-title">Thông tin hành khách</h2>
                </div>
                
                <?php foreach ($seatNumbers as $index => $seat): ?>
                <div class="passenger-card">
                    <div class="passenger-header">
                        <h3 class="passenger-title">Hành khách <?php echo $index + 1; ?></h3>
                        <span class="seat-badge">Ghế <?php echo e($seat); ?></span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Họ và tên</label>
                            <input 
                                type="text" 
                                name="passengers[<?php echo $index; ?>][name]" 
                                class="form-input" 
                                placeholder="Họ và tên hành khách"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số điện thoại</label>
                            <input 
                                type="tel" 
                                name="passengers[<?php echo $index; ?>][phone]" 
                                class="form-input" 
                                placeholder="Số điện thoại (tùy chọn)"
                            >
                        </div>
                    </div>
                    
                    <input type="hidden" name="passengers[<?php echo $index; ?>][seat]" value="<?php echo e($seat); ?>">
                </div>
                <?php endforeach; ?>
                
                <!-- Pickup & Drop-off Points -->
                <div class="section-header" style="margin-top: 32px;">
                    <div class="section-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h2 class="section-title">Điểm đón & trả</h2>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Điểm đón</label>
                        <select name="pickup_point" class="form-select" required>
                            <option value="">Chọn điểm đón</option>
                            <option value="<?php echo e($trip['origin']); ?>"><?php echo e($trip['origin']); ?></option>
                            <option value="Bến xe Miền Đông">Bến xe Miền Đông</option>
                            <option value="Bến xe An Sương">Bến xe An Sương</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Điểm trả</label>
                        <select name="dropoff_point" class="form-select" required>
                            <option value="">Chọn điểm trả</option>
                            <option value="<?php echo e($trip['destination']); ?>"><?php echo e($trip['destination']); ?></option>
                            <option value="Trung tâm thành phố">Trung tâm thành phố</option>
                        </select>
                    </div>
                </div>
                
                <!-- Promo Code -->
                <div class="promo-section">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Mã giảm giá
                    </label>
                    <div class="promo-input-wrapper">
                        <input 
                            type="text" 
                            name="promo_code" 
                            id="promoCode"
                            class="form-input" 
                            placeholder="Nhập mã giảm giá"
                        >
                        <button type="button" class="btn-apply-promo" onclick="applyPromo()">
                            Áp dụng
                        </button>
                    </div>
                    <div id="promoMessage" style="margin-top: 8px; font-size: 13px;"></div>
                </div>
                
                <!-- Notes -->
                <div class="form-group" style="margin-top: 24px;">
                    <label class="form-label">Ghi chú (tùy chọn)</label>
                    <textarea 
                        name="notes" 
                        class="form-input" 
                        rows="3" 
                        placeholder="Ghi chú thêm cho chuyến đi..."
                    ></textarea>
                </div>
            </form>
        </div>
        
        <!-- Summary Sidebar -->
        <div class="booking-summary">
            <h3 class="summary-title">Chi tiết đặt vé</h3>
            
            <div class="trip-summary">
                <div class="trip-route">
                    <span class="trip-location"><?php echo e($trip['origin']); ?></span>
                    <i class="fas fa-arrow-right trip-arrow"></i>
                    <span class="trip-location"><?php echo e($trip['destination']); ?></span>
                </div>
                
                <div class="trip-detail">
                    <span>Nhà xe</span>
                    <strong><?php echo e($trip['company_name']); ?></strong>
                </div>
                
                <div class="trip-detail">
                    <span>Khởi hành</span>
                    <strong><?php echo formatDate($trip['departure_time'], 'H:i - d/m/Y'); ?></strong>
                </div>
                
                <div class="trip-detail">
                    <span>Loại xe</span>
                    <strong><?php echo ucfirst($trip['vehicle_type']); ?></strong>
                </div>
            </div>
            
            <div class="seats-selected">
                <div class="seats-label">Ghế đã chọn</div>
                <div class="seats-list">
                    <?php foreach ($seatNumbers as $seat): ?>
                        <span class="seat-tag"><?php echo e($seat); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="price-breakdown">
                <div class="price-row">
                    <span class="price-label">Giá vé (<?php echo $totalSeats; ?> ghế)</span>
                    <span class="price-value"><?php echo formatPrice($subtotal); ?></span>
                </div>
                
                <div class="price-row" id="discountRow" style="display: none;">
                    <span class="price-label">Giảm giá</span>
                    <span class="price-value discount-value" id="discountValue">-0đ</span>
                </div>
            </div>
            
            <div class="total-row">
                <span class="total-label">Tổng thanh toán</span>
                <span class="total-value" id="totalValue"><?php echo formatPrice($total); ?></span>
            </div>
            
            <button type="submit" form="bookingForm" class="btn-confirm">
                <i class="fas fa-check-circle"></i> Xác nhận đặt vé
            </button>
            
            <div class="policy-note">
                <i class="fas fa-info-circle"></i>
                Vé sẽ được giữ trong 15 phút. Quý khách vui lòng thanh toán trong thời gian này để hoàn tất đặt vé.
            </div>
        </div>
    </div>
</div>

<script>
let discountAmount = 0;
const subtotal = <?php echo $subtotal; ?>;

function applyPromo() {
    const promoCode = document.getElementById('promoCode').value.trim();
    const message = document.getElementById('promoMessage');
    
    if (!promoCode) {
        message.style.color = '#EF4444';
        message.textContent = 'Vui lòng nhập mã giảm giá';
        return;
    }
    
    // Simulate promo validation (should call API)
    message.style.color = '#3B82F6';
    message.textContent = 'Đang kiểm tra...';
    
    setTimeout(() => {
        // Mock promo codes
        const promoCodes = {
            'SUMMER50': { type: 'percentage', value: 10, max: 50000 },
            'FLAT20': { type: 'fixed', value: 20000 }
        };
        
        if (promoCodes[promoCode]) {
            const promo = promoCodes[promoCode];
            
            if (promo.type === 'percentage') {
                discountAmount = Math.min((subtotal * promo.value / 100), promo.max);
            } else {
                discountAmount = promo.value;
            }
            
            updateTotal();
            
            message.style.color = '#10B981';
            message.textContent = `✓ Áp dụng thành công! Giảm ${formatPrice(discountAmount)}`;
            
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('discountValue').textContent = '-' + formatPrice(discountAmount);
        } else {
            message.style.color = '#EF4444';
            message.textContent = 'Mã giảm giá không hợp lệ';
        }
    }, 500);
}

function updateTotal() {
    const total = subtotal - discountAmount;
    document.getElementById('totalValue').textContent = formatPrice(total);
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
}

// Form submission
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.querySelector('.btn-confirm');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Đặt vé thành công!');
            window.location.href = '<?php echo appUrl('user/payment/index.php'); ?>?booking_id=' + result.data.booking_id;
        } else {
            alert(result.error || 'Đặt vé thất bại');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Xác nhận đặt vé';
        }
    } catch (error) {
        alert('Có lỗi xảy ra. Vui lòng thử lại!');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Xác nhận đặt vé';
    }
});
</script>

<?php include '../../includes/footer_user.php'; ?>

