<?php
/**
 * Payment Failed Page
 * Trang thanh toán thất bại
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

$reason = $_GET['reason'] ?? 'unknown';

$messages = [
    'expired' => [
        'title' => '⏰ Hết thời gian thanh toán',
        'message' => 'Đơn hàng đã hết thời gian thanh toán (15 phút). Vui lòng đặt vé lại.',
        'icon' => 'fa-clock'
    ],
    'cancelled' => [
        'title' => '❌ Đơn hàng đã bị hủy',
        'message' => 'Đơn hàng của bạn đã được hủy thành công.',
        'icon' => 'fa-times-circle'
    ],
    'insufficient' => [
        'title' => '💰 Số tiền không đủ',
        'message' => 'Số tiền chuyển khoản không khớp với giá trị đơn hàng.',
        'icon' => 'fa-exclamation-triangle'
    ],
    'error' => [
        'title' => '⚠️ Có lỗi xảy ra',
        'message' => 'Không thể xử lý thanh toán. Vui lòng thử lại hoặc liên hệ hỗ trợ.',
        'icon' => 'fa-exclamation-circle'
    ],
    'unknown' => [
        'title' => '❓ Thanh toán không thành công',
        'message' => 'Có lỗi xảy ra trong quá trình thanh toán. Vui lòng thử lại.',
        'icon' => 'fa-question-circle'
    ]
];

$info = $messages[$reason] ?? $messages['unknown'];

$pageTitle = 'Thanh toán thất bại - BusBooking';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
    .failed-page {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        min-height: 100vh;
        padding: 60px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .failed-container {
        max-width: 600px;
        width: 100%;
    }
    
    /* Failed Icon */
    .failed-icon {
        text-align: center;
        margin-bottom: 30px;
        animation: shake 0.5s ease;
    }
    
    .failed-icon-circle {
        width: 120px;
        height: 120px;
        background: #fff;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    
    .failed-icon-circle i {
        font-size: 60px;
        color: #ef4444;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
        20%, 40%, 60%, 80% { transform: translateX(10px); }
    }
    
    /* Failed Card */
    .failed-card {
        background: #fff;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        text-align: center;
        animation: slideUp 0.5s ease 0.2s both;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .failed-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
    }
    
    .failed-message {
        font-size: 16px;
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    
    /* Suggestions */
    .suggestions-box {
        background: #f8fafc;
        border-radius: 16px;
        padding: 24px;
        text-align: left;
        margin-bottom: 30px;
    }
    
    .suggestions-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
    }
    
    .suggestions-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .suggestions-list li {
        padding: 12px 0;
        padding-left: 32px;
        position: relative;
        font-size: 14px;
        color: #475569;
        line-height: 1.6;
    }
    
    .suggestions-list li::before {
        content: '•';
        position: absolute;
        left: 12px;
        color: #3b82f6;
        font-size: 20px;
    }
    
    /* Actions */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .btn {
        padding: 16px 24px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary:hover {
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #fff;
        color: #475569;
        border: 2px solid #e5e7eb;
    }
    
    .btn-secondary:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }
    
    .btn-support {
        background: #10b981;
        color: #fff;
    }
    
    .btn-support:hover {
        background: #059669;
    }
    
    /* Support Info */
    .support-info {
        background: #eff6ff;
        border: 2px solid #3b82f6;
        border-radius: 12px;
        padding: 16px;
        margin-top: 20px;
    }
    
    .support-info p {
        margin: 0;
        font-size: 14px;
        color: #1e40af;
        line-height: 1.6;
    }
    
    .support-info strong {
        color: #1e3a8a;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .failed-card {
            padding: 30px 20px;
        }
        
        .failed-title {
            font-size: 22px;
        }
    }
</style>

<div class="failed-page">
    <div class="failed-container">
        <!-- Failed Icon -->
        <div class="failed-icon">
            <div class="failed-icon-circle">
                <i class="fas <?php echo $info['icon']; ?>"></i>
            </div>
        </div>
        
        <!-- Failed Card -->
        <div class="failed-card">
            <h1 class="failed-title"><?php echo $info['title']; ?></h1>
            <p class="failed-message"><?php echo $info['message']; ?></p>
            
            <!-- Suggestions -->
            <div class="suggestions-box">
                <div class="suggestions-title">💡 Bạn có thể thử:</div>
                <ul class="suggestions-list">
                    <li>Đặt vé lại và hoàn tất thanh toán trong 15 phút</li>
                    <li>Kiểm tra lại thông tin tài khoản và số dư</li>
                    <li>Sử dụng phương thức thanh toán khác (MoMo, VNPay)</li>
                    <li>Liên hệ hotline để được hỗ trợ: <strong>1900-xxxx</strong></li>
                </ul>
            </div>
            
            <!-- Actions -->
            <div class="action-buttons">
                <a href="<?php echo appUrl(); ?>" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Đặt vé mới
                </a>
                <a href="<?php echo appUrl('user/tickets'); ?>" class="btn btn-secondary">
                    <i class="fas fa-ticket-alt"></i> Xem vé đã đặt
                </a>
                <a href="<?php echo appUrl('support'); ?>" class="btn btn-support">
                    <i class="fas fa-headset"></i> Liên hệ hỗ trợ
                </a>
            </div>
            
            <!-- Support Info -->
            <div class="support-info">
                <p>
                    <i class="fas fa-phone-alt"></i>
                    <strong>Cần trợ giúp?</strong><br>
                    Hotline: <strong>1900-xxxx</strong> (24/7)<br>
                    Email: <strong>support@busbooking.com</strong>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer_user.php'; ?>

