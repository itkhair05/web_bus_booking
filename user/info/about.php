<?php
/**
 * Trang Giới thiệu về BusBooking
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Giới thiệu - BusBooking';
$pageDescription = 'Tìm hiểu về BusBooking - Nền tảng đặt vé xe khách trực tuyến hàng đầu Việt Nam';

include '../../includes/header_user.php';
?>

<style>
/* About Page Styles */
.about-page {
    background: #fff;
    min-height: 100vh;
}

.about-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.about-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.about-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.about-breadcrumb a:hover {
    text-decoration: underline;
}

.about-breadcrumb span {
    color: #666;
}

.about-header {
    text-align: center;
    margin-bottom: 48px;
}

.about-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.about-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.about-hero {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 60px 40px;
    color: white;
    text-align: center;
    margin-bottom: 60px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.about-hero h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
}

.about-hero p {
    font-size: 18px;
    line-height: 1.8;
    opacity: 0.95;
}

.about-section {
    margin-bottom: 48px;
}

.about-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #2196F3;
}

.about-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.about-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin: 32px 0;
}

.about-feature {
    background: #f8fafc;
    padding: 32px 24px;
    border-radius: 12px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.about-feature:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.about-feature-icon {
    font-size: 48px;
    margin-bottom: 16px;
    color: #2196F3;
}

.about-feature h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.about-feature p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin: 0;
}

.about-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    margin: 40px 0;
}

.about-stat {
    text-align: center;
    padding: 24px;
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 12px;
    color: white;
}

.about-stat-number {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 8px;
}

.about-stat-label {
    font-size: 16px;
    opacity: 0.9;
}

.about-values {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin: 32px 0;
}

.about-value {
    padding: 24px;
    background: #f8fafc;
    border-left: 4px solid #2196F3;
    border-radius: 8px;
}

.about-value h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.about-value p {
    font-size: 15px;
    color: #666;
    line-height: 1.7;
    margin: 0;
}

@media (max-width: 768px) {
    .about-header h1 {
        font-size: 32px;
    }
    
    .about-hero {
        padding: 40px 24px;
    }
    
    .about-hero h2 {
        font-size: 24px;
    }
    
    .about-section h2 {
        font-size: 24px;
    }
}
</style>

<main class="about-page">
    <div class="about-container">
        <!-- Breadcrumb -->
        <div class="about-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Giới thiệu
        </div>

        <!-- Header -->
        <div class="about-header">
            <h1>Về BusBooking</h1>
            <p>Nền tảng đặt vé xe khách trực tuyến hàng đầu Việt Nam, kết nối hàng triệu hành khách với các nhà xe uy tín trên toàn quốc.</p>
        </div>

        <!-- Hero Section -->
        <div class="about-hero">
            <h2>Sứ mệnh của chúng tôi</h2>
            <p>BusBooking cam kết mang đến trải nghiệm đặt vé xe khách thuận tiện, nhanh chóng và an toàn nhất cho mọi hành khách. Chúng tôi không chỉ là một nền tảng đặt vé, mà còn là người bạn đồng hành tin cậy trong mọi chuyến đi của bạn.</p>
        </div>

        <!-- Giới thiệu chung -->
        <div class="about-section">
            <h2>Giới thiệu chung</h2>
            <p>BusBooking được thành lập với mong muốn cách mạng hóa cách thức đặt vé xe khách tại Việt Nam. Thay vì phải đến tận bến xe hoặc gọi điện thoại để đặt vé, giờ đây bạn có thể đặt vé mọi lúc, mọi nơi chỉ với vài cú click chuột.</p>
            <p>Chúng tôi hợp tác với hàng trăm nhà xe uy tín trên toàn quốc, mang đến cho bạn hàng nghìn chuyến xe mỗi ngày với đầy đủ thông tin về lịch trình, giá vé, tiện ích và đánh giá từ hành khách.</p>
        </div>

        <!-- Tính năng nổi bật -->
        <div class="about-section">
            <h2>Tại sao chọn BusBooking?</h2>
            <div class="about-features">
                <div class="about-feature">
                    <div class="about-feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Đặt vé nhanh chóng</h3>
                    <p>Quy trình đặt vé đơn giản, chỉ mất vài phút. Thanh toán an toàn với nhiều phương thức đa dạng.</p>
                </div>
                <div class="about-feature">
                    <div class="about-feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>An toàn & Bảo mật</h3>
                    <p>Thông tin cá nhân được mã hóa và bảo vệ. Cam kết hoàn tiền 150% nếu nhà xe không cung cấp dịch vụ.</p>
                </div>
                <div class="about-feature">
                    <div class="about-feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3>Đa dạng tuyến đường</h3>
                    <p>Hàng nghìn chuyến xe mỗi ngày, kết nối các tỉnh thành trên toàn quốc với nhiều điểm đón/trả tiện lợi.</p>
                </div>
                <div class="about-feature">
                    <div class="about-feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Hỗ trợ 24/7</h3>
                    <p>Đội ngũ chăm sóc khách hàng luôn sẵn sàng hỗ trợ bạn mọi lúc, mọi nơi qua hotline và email.</p>
                </div>
                <div class="about-feature">
                    <div class="about-feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Giao diện thân thiện</h3>
                    <p>Website được thiết kế tối ưu cho mọi thiết bị, từ máy tính đến điện thoại di động.</p>
                </div>
                <div class="about-feature">
                    <div class="about-feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Đánh giá thực tế</h3>
                    <p>Xem đánh giá và nhận xét từ hành khách thực tế để lựa chọn nhà xe phù hợp nhất.</p>
                </div>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="about-section">
            <h2>BusBooking trong con số</h2>
            <div class="about-stats">
                <div class="about-stat">
                    <div class="about-stat-number">500+</div>
                    <div class="about-stat-label">Nhà xe đối tác</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-number">1000+</div>
                    <div class="about-stat-label">Chuyến xe mỗi ngày</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-number">1M+</div>
                    <div class="about-stat-label">Khách hàng tin tưởng</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-number">98%</div>
                    <div class="about-stat-label">Độ hài lòng</div>
                </div>
            </div>
        </div>

        <!-- Giá trị cốt lõi -->
        <div class="about-section">
            <h2>Giá trị cốt lõi</h2>
            <div class="about-values">
                <div class="about-value">
                    <h3><i class="fas fa-heart" style="color: #e74c3c;"></i> Khách hàng là trung tâm</h3>
                    <p>Mọi quyết định và hành động của chúng tôi đều hướng đến việc mang lại giá trị tốt nhất cho khách hàng. Chúng tôi lắng nghe, học hỏi và không ngừng cải thiện dịch vụ.</p>
                </div>
                <div class="about-value">
                    <h3><i class="fas fa-handshake" style="color: #2196F3;"></i> Minh bạch & Trung thực</h3>
                    <p>Chúng tôi cam kết minh bạch trong mọi giao dịch, không có phí ẩn, giá vé rõ ràng. Thông tin về chuyến xe, nhà xe được cung cấp đầy đủ và chính xác.</p>
                </div>
                <div class="about-value">
                    <h3><i class="fas fa-rocket" style="color: #f39c12;"></i> Đổi mới & Sáng tạo</h3>
                    <p>Không ngừng đổi mới công nghệ và cải thiện trải nghiệm người dùng. Chúng tôi luôn tìm cách làm cho việc đặt vé trở nên dễ dàng và thuận tiện hơn.</p>
                </div>
                <div class="about-value">
                    <h3><i class="fas fa-users" style="color: #27ae60;"></i> Hợp tác cùng phát triển</h3>
                    <p>Xây dựng mối quan hệ hợp tác bền vững với các nhà xe, cùng nhau phát triển và nâng cao chất lượng dịch vụ vận tải hành khách.</p>
                </div>
            </div>
        </div>

        <!-- Tầm nhìn -->
        <div class="about-section">
            <h2>Tầm nhìn</h2>
            <p>BusBooking hướng đến trở thành nền tảng đặt vé xe khách số 1 tại Việt Nam và khu vực Đông Nam Á. Chúng tôi mong muốn góp phần hiện đại hóa ngành vận tải hành khách, mang đến trải nghiệm di chuyển tốt nhất cho mọi người.</p>
            <p>Trong tương lai, chúng tôi sẽ mở rộng sang các dịch vụ khác như đặt vé máy bay, tàu hỏa, và các dịch vụ du lịch khác, trở thành một siêu ứng dụng du lịch toàn diện.</p>
        </div>

        <!-- Call to Action -->
        <div class="about-section" style="text-align: center; margin-top: 60px;">
            <h2>Sẵn sàng bắt đầu hành trình của bạn?</h2>
            <p style="font-size: 18px; margin-bottom: 32px;">Đặt vé ngay hôm nay và trải nghiệm dịch vụ tuyệt vời của BusBooking</p>
            <a href="<?php echo appUrl('user/search/index.php'); ?>" class="btn btn-primary btn-lg" style="padding: 14px 32px; font-size: 18px; border-radius: 8px;">
                <i class="fas fa-search"></i> Tìm chuyến xe ngay
            </a>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

