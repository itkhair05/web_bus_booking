<?php
/**
 * Trang Dành cho nhà xe - Thông tin đối tác
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Dành cho nhà xe - BusBooking';
$pageDescription = 'Tham gia mạng lưới đối tác BusBooking - Tăng doanh thu và mở rộng khách hàng';

include '../../includes/header_user.php';
?>

<style>
/* For Partners Page Styles */
.partners-page {
    background: #fff;
    min-height: 100vh;
}

.partners-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.partners-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.partners-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.partners-breadcrumb a:hover {
    text-decoration: underline;
}

.partners-breadcrumb span {
    color: #666;
}

.partners-header {
    text-align: center;
    margin-bottom: 48px;
}

.partners-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.partners-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.partners-hero {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 60px 40px;
    color: white;
    text-align: center;
    margin-bottom: 60px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.partners-hero h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
}

.partners-hero p {
    font-size: 18px;
    line-height: 1.8;
    opacity: 0.95;
}

.partners-section {
    margin-bottom: 48px;
}

.partners-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #2196F3;
}

.partners-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.partners-benefits {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin: 32px 0;
}

.partners-benefit {
    background: #f8fafc;
    padding: 32px 24px;
    border-radius: 12px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.partners-benefit:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.partners-benefit-icon {
    font-size: 48px;
    margin-bottom: 16px;
    color: #2196F3;
}

.partners-benefit h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.partners-benefit p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin: 0;
}

.partners-process {
    background: #f8fafc;
    border-radius: 12px;
    padding: 40px;
    margin: 32px 0;
}

.partners-process-step {
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
    align-items: flex-start;
}

.partners-process-step:last-child {
    margin-bottom: 0;
}

.partners-process-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
}

.partners-process-content h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.partners-process-content p {
    font-size: 15px;
    color: #666;
    line-height: 1.7;
    margin: 0;
}

.partners-cta {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 48px 40px;
    color: white;
    text-align: center;
    margin-top: 48px;
}

.partners-cta h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 16px;
}

.partners-cta p {
    font-size: 18px;
    margin-bottom: 32px;
    opacity: 0.95;
}

.btn-register {
    background: white;
    color: #1E90FF;
    padding: 14px 32px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 18px;
    display: inline-block;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    color: #1E90FF;
}

@media (max-width: 768px) {
    .partners-header h1 {
        font-size: 32px;
    }
    
    .partners-hero {
        padding: 40px 24px;
    }
    
    .partners-process-step {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<main class="partners-page">
    <div class="partners-container">
        <!-- Breadcrumb -->
        <div class="partners-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Dành cho nhà xe
        </div>

        <!-- Header -->
        <div class="partners-header">
            <h1>Đối tác nhà xe</h1>
            <p>Tham gia mạng lưới đối tác BusBooking để tăng doanh thu, mở rộng khách hàng và quản lý chuyến xe hiệu quả hơn.</p>
        </div>

        <!-- Hero Section -->
        <div class="partners-hero">
            <h2>Tại sao hợp tác với BusBooking?</h2>
            <p>BusBooking là nền tảng đặt vé xe khách hàng đầu Việt Nam với hàng triệu khách hàng tiềm năng. Khi trở thành đối tác, bạn sẽ được hỗ trợ toàn diện để phát triển kinh doanh và nâng cao chất lượng dịch vụ.</p>
        </div>

        <!-- Lợi ích -->
        <div class="partners-section">
            <h2>Lợi ích khi hợp tác</h2>
            <div class="partners-benefits">
                <div class="partners-benefit">
                    <div class="partners-benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Tăng doanh thu</h3>
                    <p>Tiếp cận hàng triệu khách hàng tiềm năng trên toàn quốc, tăng tỷ lệ lấp đầy ghế và doanh thu.</p>
                </div>
                <div class="partners-benefit">
                    <div class="partners-benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Quản lý dễ dàng</h3>
                    <p>Hệ thống quản lý chuyến xe, vé và doanh thu trực tuyến, tiết kiệm thời gian và công sức.</p>
                </div>
                <div class="partners-benefit">
                    <div class="partners-benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Thanh toán an toàn</h3>
                    <p>Hỗ trợ nhiều phương thức thanh toán, đảm bảo an toàn và thanh toán đúng hạn.</p>
                </div>
                <div class="partners-benefit">
                    <div class="partners-benefit-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Hỗ trợ 24/7</h3>
                    <p>Đội ngũ hỗ trợ chuyên nghiệp luôn sẵn sàng giúp đỡ bạn mọi lúc, mọi nơi.</p>
                </div>
                <div class="partners-benefit">
                    <div class="partners-benefit-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Ứng dụng di động</h3>
                    <p>Quản lý chuyến xe mọi lúc mọi nơi với ứng dụng di động tiện lợi.</p>
                </div>
                <div class="partners-benefit">
                    <div class="partners-benefit-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Xây dựng thương hiệu</h3>
                    <p>Hiển thị thông tin nhà xe, đánh giá khách hàng để xây dựng uy tín và thương hiệu.</p>
                </div>
            </div>
        </div>

        <!-- Quy trình đăng ký -->
        <div class="partners-section">
            <h2>Quy trình đăng ký</h2>
            <div class="partners-process">
                <div class="partners-process-step">
                    <div class="partners-process-number">1</div>
                    <div class="partners-process-content">
                        <h3>Điền form đăng ký</h3>
                        <p>Điền đầy đủ thông tin về công ty, nhà xe và các tuyến đường bạn muốn khai thác.</p>
                    </div>
                </div>
                <div class="partners-process-step">
                    <div class="partners-process-number">2</div>
                    <div class="partners-process-content">
                        <h3>Xét duyệt hồ sơ</h3>
                        <p>Đội ngũ của chúng tôi sẽ xem xét và liên hệ với bạn trong vòng 24-48 giờ.</p>
                    </div>
                </div>
                <div class="partners-process-step">
                    <div class="partners-process-number">3</div>
                    <div class="partners-process-content">
                        <h3>Ký hợp đồng</h3>
                        <p>Ký kết hợp đồng hợp tác và nhận tài khoản quản lý hệ thống.</p>
                    </div>
                </div>
                <div class="partners-process-step">
                    <div class="partners-process-number">4</div>
                    <div class="partners-process-content">
                        <h3>Đào tạo & Hướng dẫn</h3>
                        <p>Nhận hướng dẫn sử dụng hệ thống và bắt đầu đăng chuyến xe lên nền tảng.</p>
                    </div>
                </div>
                <div class="partners-process-step">
                    <div class="partners-process-number">5</div>
                    <div class="partners-process-content">
                        <h3>Bắt đầu kinh doanh</h3>
                        <p>Bắt đầu nhận đặt vé từ khách hàng và quản lý chuyến xe hiệu quả.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yêu cầu -->
        <div class="partners-section">
            <h2>Yêu cầu đối tác</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="padding-left: 28px; margin-bottom: 12px; position: relative;">
                    <span style="position: absolute; left: 0; color: #2196F3; font-weight: bold;">✓</span>
                    Có giấy phép kinh doanh vận tải hành khách hợp lệ
                </li>
                <li style="padding-left: 28px; margin-bottom: 12px; position: relative;">
                    <span style="position: absolute; left: 0; color: #2196F3; font-weight: bold;">✓</span>
                    Có đội xe và tuyến đường cố định
                </li>
                <li style="padding-left: 28px; margin-bottom: 12px; position: relative;">
                    <span style="position: absolute; left: 0; color: #2196F3; font-weight: bold;">✓</span>
                    Cam kết cung cấp dịch vụ chất lượng và đúng lịch trình
                </li>
                <li style="padding-left: 28px; margin-bottom: 12px; position: relative;">
                    <span style="position: absolute; left: 0; color: #2196F3; font-weight: bold;">✓</span>
                    Có khả năng thanh toán và quản lý tài chính minh bạch
                </li>
            </ul>
        </div>

        <!-- Call to Action -->
        <div class="partners-cta">
            <h2>Sẵn sàng trở thành đối tác?</h2>
            <p>Đăng ký ngay hôm nay để bắt đầu hành trình phát triển cùng BusBooking</p>
            <a href="<?php echo appUrl('partner/auth/register.php'); ?>" class="btn-register">
                <i class="fas fa-user-plus"></i> Đăng ký ngay
            </a>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

