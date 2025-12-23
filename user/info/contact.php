<?php
/**
 * Trang Liên hệ
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Liên hệ - BusBooking';
$pageDescription = 'Liên hệ với BusBooking - Chúng tôi luôn sẵn sàng hỗ trợ bạn mọi lúc, mọi nơi';

// Xử lý form liên hệ
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validate
    if (empty($name) || empty($email) || empty($message)) {
        $errorMessage = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif (!validateEmail($email)) {
        $errorMessage = 'Email không hợp lệ.';
    } else {
        // Ở đây bạn có thể lưu vào database hoặc gửi email
        // Tạm thời hiển thị thông báo thành công
        $successMessage = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong vòng 24 giờ.';
        
        // Reset form
        $name = $email = $phone = $subject = $message = '';
    }
}

include '../../includes/header_user.php';
?>

<style>
/* Contact Page Styles */
.contact-page {
    background: #f8fafc;
    min-height: 100vh;
}

.contact-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.contact-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.contact-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.contact-breadcrumb a:hover {
    text-decoration: underline;
}

.contact-breadcrumb span {
    color: #666;
}

.contact-header {
    text-align: center;
    margin-bottom: 48px;
}

.contact-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.contact-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 60px;
}

.contact-info {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.contact-info h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 3px solid #2196F3;
}

.contact-info-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 32px;
}

.contact-info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.contact-info-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.contact-info-content p {
    font-size: 15px;
    color: #666;
    line-height: 1.6;
    margin: 0;
}

.contact-info-content a {
    color: #2196F3;
    text-decoration: none;
}

.contact-info-content a:hover {
    text-decoration: underline;
}

.contact-form-wrapper {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.contact-form-wrapper h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 3px solid #2196F3;
}

.contact-form .form-group {
    margin-bottom: 24px;
}

.contact-form label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.contact-form label .required {
    color: #e74c3c;
}

.contact-form input,
.contact-form select,
.contact-form textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s ease;
}

.contact-form input:focus,
.contact-form select:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.contact-form textarea {
    resize: vertical;
    min-height: 120px;
}

.contact-form .btn-submit {
    width: 100%;
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    color: white;
    padding: 14px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.contact-form .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(30, 144, 255, 0.3);
}

.contact-form .alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.contact-form .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.contact-form .alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.contact-map {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-top: 40px;
}

.contact-map h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 3px solid #2196F3;
}

.contact-map-placeholder {
    width: 100%;
    height: 400px;
    background: #e5e7eb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 16px;
}

.contact-hours {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 12px;
    padding: 32px;
    color: white;
    margin-top: 40px;
}

.contact-hours h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
}

.contact-hours-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.contact-hours-list li {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 16px;
}

.contact-hours-list li:last-child {
    border-bottom: none;
}

@media (max-width: 968px) {
    .contact-content {
        grid-template-columns: 1fr;
    }
    
    .contact-header h1 {
        font-size: 32px;
    }
}
</style>

<main class="contact-page">
    <div class="contact-container">
        <!-- Breadcrumb -->
        <div class="contact-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Liên hệ
        </div>

        <!-- Header -->
        <div class="contact-header">
            <h1>Liên hệ với chúng tôi</h1>
            <p>Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. Hãy liên hệ với chúng tôi qua bất kỳ kênh nào bạn cảm thấy thuận tiện nhất.</p>
        </div>

        <!-- Contact Content -->
        <div class="contact-content">
            <!-- Contact Info -->
            <div class="contact-info">
                <h2>Thông tin liên hệ</h2>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Địa chỉ văn phòng</h3>
                        <p>
                            <strong>Trụ sở chính:</strong><br>
                            123 Đường ABC, Phường XYZ<br>
                            Quận 1, TP. Hồ Chí Minh<br>
                            Việt Nam
                        </p>
                        <p style="margin-top: 12px;">
                            <strong>Văn phòng Hà Nội:</strong><br>
                            456 Đường DEF, Phường UVW<br>
                            Quận Ba Đình, Hà Nội<br>
                            Việt Nam
                        </p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Hotline</h3>
                        <p>
                            <strong>Hỗ trợ khách hàng:</strong><br>
                            <a href="tel:1900123456">1900 123 456</a> (Miễn phí)<br>
                            <small>Hoạt động 24/7</small>
                        </p>
                        <p style="margin-top: 12px;">
                            <strong>Đối tác nhà xe:</strong><br>
                            <a href="tel:1900123457">1900 123 457</a><br>
                            <small>Thứ 2 - Thứ 6: 8:00 - 17:30</small>
                        </p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Email</h3>
                        <p>
                            <strong>Hỗ trợ khách hàng:</strong><br>
                            <a href="mailto:support@busbooking.com">support@busbooking.com</a>
                        </p>
                        <p style="margin-top: 12px;">
                            <strong>Đối tác:</strong><br>
                            <a href="mailto:partners@busbooking.com">partners@busbooking.com</a>
                        </p>
                        <p style="margin-top: 12px;">
                            <strong>Tuyển dụng:</strong><br>
                            <a href="mailto:hr@busbooking.com">hr@busbooking.com</a>
                        </p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Mạng xã hội</h3>
                        <p>
                            Theo dõi chúng tôi trên các nền tảng mạng xã hội để cập nhật thông tin mới nhất và các chương trình ưu đãi đặc biệt.
                        </p>
                        <div style="display: flex; gap: 12px; margin-top: 12px;">
                            <a href="#" style="width: 40px; height: 40px; background: #3b5998; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" style="width: 40px; height: 40px; background: #e4405f; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" style="width: 40px; height: 40px; background: #ff0000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a href="#" style="width: 40px; height: 40px; background: #000000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                                <i class="fab fa-tiktok"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-wrapper">
                <h2>Gửi tin nhắn cho chúng tôi</h2>
                
                <?php if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label for="name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="0987654321">
                    </div>

                    <div class="form-group">
                        <label for="subject">Chủ đề <span class="required">*</span></label>
                        <select id="subject" name="subject" required>
                            <option value="">-- Chọn chủ đề --</option>
                            <option value="booking" <?php echo (isset($subject) && $subject == 'booking') ? 'selected' : ''; ?>>Hỗ trợ đặt vé</option>
                            <option value="payment" <?php echo (isset($subject) && $subject == 'payment') ? 'selected' : ''; ?>>Vấn đề thanh toán</option>
                            <option value="cancel" <?php echo (isset($subject) && $subject == 'cancel') ? 'selected' : ''; ?>>Hủy/Đổi vé</option>
                            <option value="refund" <?php echo (isset($subject) && $subject == 'refund') ? 'selected' : ''; ?>>Hoàn tiền</option>
                            <option value="partner" <?php echo (isset($subject) && $subject == 'partner') ? 'selected' : ''; ?>>Đối tác nhà xe</option>
                            <option value="other" <?php echo (isset($subject) && $subject == 'other') ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Nội dung tin nhắn <span class="required">*</span></label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                    </button>
                </form>
            </div>
        </div>

        <!-- Map Section -->
        <div class="contact-map">
            <h2>Bản đồ văn phòng</h2>
            <div class="contact-map-placeholder">
                <div style="text-align: center;">
                    <i class="fas fa-map-marked-alt" style="font-size: 48px; margin-bottom: 16px; display: block; color: #2196F3;"></i>
                    <p>Bản đồ sẽ được tích hợp tại đây</p>
                    <p style="font-size: 14px; margin-top: 8px; color: #999;">123 Đường ABC, Phường XYZ, Quận 1, TP.HCM</p>
                </div>
            </div>
        </div>

        <!-- Business Hours -->
        <div class="contact-hours">
            <h3>Giờ làm việc</h3>
            <ul class="contact-hours-list">
                <li>
                    <span><i class="fas fa-calendar-day"></i> Thứ 2 - Thứ 6</span>
                    <span>8:00 - 17:30</span>
                </li>
                <li>
                    <span><i class="fas fa-calendar-day"></i> Thứ 7</span>
                    <span>8:00 - 12:00</span>
                </li>
                <li>
                    <span><i class="fas fa-calendar-day"></i> Chủ nhật</span>
                    <span>Nghỉ</span>
                </li>
                <li>
                    <span><i class="fas fa-headset"></i> Hotline hỗ trợ</span>
                    <span>24/7</span>
                </li>
            </ul>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

