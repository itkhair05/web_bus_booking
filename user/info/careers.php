<?php
/**
 * Trang Tuyển dụng
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Tuyển dụng - BusBooking';
$pageDescription = 'Cơ hội nghề nghiệp tại BusBooking - Tham gia đội ngũ trẻ trung, năng động và đầy sáng tạo';

include '../../includes/header_user.php';
?>

<style>
/* Careers Page Styles */
.careers-page {
    background: #f8fafc;
    min-height: 100vh;
}

.careers-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.careers-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.careers-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.careers-breadcrumb a:hover {
    text-decoration: underline;
}

.careers-breadcrumb span {
    color: #666;
}

.careers-header {
    text-align: center;
    margin-bottom: 48px;
}

.careers-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.careers-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.careers-hero {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 60px 40px;
    color: white;
    text-align: center;
    margin-bottom: 60px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.careers-hero h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
}

.careers-hero p {
    font-size: 18px;
    line-height: 1.8;
    opacity: 0.95;
}

.careers-section {
    background: white;
    border-radius: 12px;
    padding: 40px;
    margin-bottom: 32px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.careers-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #2196F3;
}

.careers-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.careers-benefits {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 32px 0;
}

.careers-benefit {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
}

.careers-benefit-icon {
    font-size: 32px;
    color: #2196F3;
    flex-shrink: 0;
}

.careers-benefit-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.careers-benefit-content p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin: 0;
}

.careers-jobs {
    margin-top: 32px;
}

.careers-job {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.careers-job:hover {
    border-color: #2196F3;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.1);
}

.careers-job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 16px;
}

.careers-job-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.careers-job-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    font-size: 14px;
    color: #666;
}

.careers-job-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.careers-job-meta i {
    color: #2196F3;
}

.careers-job-description {
    font-size: 15px;
    color: #666;
    line-height: 1.7;
    margin-bottom: 16px;
}

.careers-job-requirements {
    margin-top: 16px;
}

.careers-job-requirements h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.careers-job-requirements ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.careers-job-requirements ul li {
    padding-left: 24px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
    position: relative;
}

.careers-job-requirements ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #2196F3;
    font-weight: bold;
}

.careers-job-actions {
    margin-top: 20px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-apply {
    background: #2196F3;
    color: white;
    padding: 10px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-apply:hover {
    background: #1976D2;
    color: white;
}

.btn-view-details {
    background: transparent;
    color: #2196F3;
    padding: 10px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    border: 1px solid #2196F3;
    transition: all 0.3s ease;
}

.btn-view-details:hover {
    background: #2196F3;
    color: white;
}

.careers-cta {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 48px 40px;
    color: white;
    text-align: center;
    margin-top: 48px;
}

.careers-cta h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 16px;
    border: none;
    padding: 0;
    color: white;
}

.careers-cta p {
    font-size: 18px;
    margin-bottom: 32px;
    opacity: 0.95;
}

@media (max-width: 768px) {
    .careers-header h1 {
        font-size: 32px;
    }
    
    .careers-hero {
        padding: 40px 24px;
    }
    
    .careers-section {
        padding: 24px;
    }
    
    .careers-job-header {
        flex-direction: column;
    }
}
</style>

<main class="careers-page">
    <div class="careers-container">
        <!-- Breadcrumb -->
        <div class="careers-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Tuyển dụng
        </div>

        <!-- Header -->
        <div class="careers-header">
            <h1>Gia nhập đội ngũ BusBooking</h1>
            <p>Cùng chúng tôi xây dựng tương lai của ngành vận tải hành khách. Tìm kiếm những tài năng trẻ, đam mê công nghệ và muốn tạo ra giá trị cho cộng đồng.</p>
        </div>

        <!-- Hero Section -->
        <div class="careers-hero">
            <h2>Tại sao làm việc tại BusBooking?</h2>
            <p>Chúng tôi không chỉ là một công ty công nghệ, mà còn là một gia đình nơi mọi người được khuyến khích phát triển, sáng tạo và đóng góp vào sự thành công chung. Môi trường làm việc trẻ trung, năng động và đầy thử thách đang chờ đón bạn.</p>
        </div>

        <!-- Văn hóa công ty -->
        <div class="careers-section">
            <h2>Văn hóa công ty</h2>
            <p>BusBooking tin rằng con người là tài sản quý giá nhất. Chúng tôi xây dựng một môi trường làm việc:</p>
            
            <div class="careers-benefits">
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Sáng tạo & Đổi mới</h3>
                        <p>Khuyến khích nhân viên đưa ra ý tưởng mới và thử nghiệm những giải pháp sáng tạo.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Làm việc nhóm</h3>
                        <p>Tinh thần đồng đội, hỗ trợ lẫn nhau để đạt được mục tiêu chung.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Phát triển nghề nghiệp</h3>
                        <p>Cơ hội học hỏi, phát triển kỹ năng và thăng tiến trong sự nghiệp.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Cân bằng cuộc sống</h3>
                        <p>Chế độ làm việc linh hoạt, nghỉ phép hợp lý để cân bằng công việc và cuộc sống.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phúc lợi -->
        <div class="careers-section">
            <h2>Phúc lợi & Đãi ngộ</h2>
            <p>Chúng tôi cam kết mang đến môi trường làm việc tốt nhất và đãi ngộ xứng đáng cho mọi nhân viên:</p>
            
            <div class="careers-benefits">
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Lương thưởng cạnh tranh</h3>
                        <p>Mức lương và thưởng theo năng lực, đánh giá định kỳ và điều chỉnh phù hợp.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Bảo hiểm đầy đủ</h3>
                        <p>Bảo hiểm xã hội, y tế, thất nghiệp và bảo hiểm sức khỏe cao cấp.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Phụ cấp & Trợ cấp</h3>
                        <p>Phụ cấp ăn trưa, xăng xe, điện thoại và các khoản trợ cấp khác.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Đào tạo & Phát triển</h3>
                        <p>Chương trình đào tạo nội bộ, hỗ trợ học phí các khóa học liên quan.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Nghỉ phép linh hoạt</h3>
                        <p>12 ngày phép/năm, nghỉ lễ theo quy định và các ngày nghỉ đặc biệt.</p>
                    </div>
                </div>
                <div class="careers-benefit">
                    <div class="careers-benefit-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="careers-benefit-content">
                        <h3>Hoạt động team building</h3>
                        <p>Du lịch công ty, team building, tiệc sinh nhật và các hoạt động vui chơi khác.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vị trí tuyển dụng -->
        <div class="careers-section">
            <h2>Vị trí đang tuyển dụng</h2>
            
            <div class="careers-jobs">
                <div class="careers-job">
                    <div class="careers-job-header">
                        <div>
                            <div class="careers-job-title">Frontend Developer</div>
                            <div class="careers-job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> Hà Nội / TP.HCM</span>
                                <span><i class="fas fa-briefcase"></i> Full-time</span>
                                <span><i class="fas fa-dollar-sign"></i> 15-25 triệu</span>
                            </div>
                        </div>
                    </div>
                    <div class="careers-job-description">
                        Tìm kiếm Frontend Developer có kinh nghiệm với React/Vue.js để phát triển và cải thiện giao diện người dùng của nền tảng BusBooking.
                    </div>
                    <div class="careers-job-requirements">
                        <h4>Yêu cầu:</h4>
                        <ul>
                            <li>2+ năm kinh nghiệm với React hoặc Vue.js</li>
                            <li>Thành thạo HTML, CSS, JavaScript</li>
                            <li>Kinh nghiệm với responsive design</li>
                            <li>Kỹ năng làm việc nhóm tốt</li>
                        </ul>
                    </div>
                    <div class="careers-job-actions">
                        <a href="mailto:hr@busbooking.com?subject=Ứng tuyển Frontend Developer" class="btn-apply">Ứng tuyển ngay</a>
                        <a href="#" class="btn-view-details">Xem chi tiết</a>
                    </div>
                </div>

                <div class="careers-job">
                    <div class="careers-job-header">
                        <div>
                            <div class="careers-job-title">Backend Developer (PHP/Node.js)</div>
                            <div class="careers-job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> Hà Nội / TP.HCM</span>
                                <span><i class="fas fa-briefcase"></i> Full-time</span>
                                <span><i class="fas fa-dollar-sign"></i> 18-30 triệu</span>
                            </div>
                        </div>
                    </div>
                    <div class="careers-job-description">
                        Phát triển và tối ưu hóa hệ thống backend, API và cơ sở dữ liệu để đảm bảo hiệu suất và độ tin cậy của nền tảng.
                    </div>
                    <div class="careers-job-requirements">
                        <h4>Yêu cầu:</h4>
                        <ul>
                            <li>3+ năm kinh nghiệm với PHP hoặc Node.js</li>
                            <li>Thành thạo MySQL/PostgreSQL</li>
                            <li>Kinh nghiệm với RESTful API</li>
                            <li>Hiểu biết về microservices architecture</li>
                        </ul>
                    </div>
                    <div class="careers-job-actions">
                        <a href="mailto:hr@busbooking.com?subject=Ứng tuyển Backend Developer" class="btn-apply">Ứng tuyển ngay</a>
                        <a href="#" class="btn-view-details">Xem chi tiết</a>
                    </div>
                </div>

                <div class="careers-job">
                    <div class="careers-job-header">
                        <div>
                            <div class="careers-job-title">Customer Support Specialist</div>
                            <div class="careers-job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> Hà Nội / TP.HCM</span>
                                <span><i class="fas fa-briefcase"></i> Full-time</span>
                                <span><i class="fas fa-dollar-sign"></i> 8-12 triệu</span>
                            </div>
                        </div>
                    </div>
                    <div class="careers-job-description">
                        Hỗ trợ khách hàng qua nhiều kênh (hotline, email, chat), giải đáp thắc mắc và xử lý các vấn đề liên quan đến đặt vé.
                    </div>
                    <div class="careers-job-requirements">
                        <h4>Yêu cầu:</h4>
                        <ul>
                            <li>Kỹ năng giao tiếp tốt</li>
                            <li>Khả năng xử lý tình huống nhanh</li>
                            <li>Thái độ phục vụ khách hàng chuyên nghiệp</li>
                            <li>Làm việc được ca đêm và cuối tuần</li>
                        </ul>
                    </div>
                    <div class="careers-job-actions">
                        <a href="mailto:hr@busbooking.com?subject=Ứng tuyển Customer Support" class="btn-apply">Ứng tuyển ngay</a>
                        <a href="#" class="btn-view-details">Xem chi tiết</a>
                    </div>
                </div>

                <div class="careers-job">
                    <div class="careers-job-header">
                        <div>
                            <div class="careers-job-title">Business Development Manager</div>
                            <div class="careers-job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> Hà Nội / TP.HCM</span>
                                <span><i class="fas fa-briefcase"></i> Full-time</span>
                                <span><i class="fas fa-dollar-sign"></i> 20-35 triệu</span>
                            </div>
                        </div>
                    </div>
                    <div class="careers-job-description">
                        Mở rộng mạng lưới đối tác nhà xe, đàm phán hợp đồng và xây dựng mối quan hệ bền vững với các đối tác.
                    </div>
                    <div class="careers-job-requirements">
                        <h4>Yêu cầu:</h4>
                        <ul>
                            <li>5+ năm kinh nghiệm trong sales/business development</li>
                            <li>Kỹ năng đàm phán và thuyết phục tốt</li>
                            <li>Hiểu biết về ngành vận tải là một lợi thế</li>
                            <li>Có mạng lưới quan hệ trong ngành</li>
                        </ul>
                    </div>
                    <div class="careers-job-actions">
                        <a href="mailto:hr@busbooking.com?subject=Ứng tuyển Business Development Manager" class="btn-apply">Ứng tuyển ngay</a>
                        <a href="#" class="btn-view-details">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="careers-cta">
            <h2>Không thấy vị trí phù hợp?</h2>
            <p>Chúng tôi luôn tìm kiếm những tài năng mới. Gửi CV của bạn cho chúng tôi và chúng tôi sẽ liên hệ khi có cơ hội phù hợp.</p>
            <a href="mailto:hr@busbooking.com?subject=Ứng tuyển vị trí khác" class="btn-apply" style="background: white; color: #1E90FF; font-size: 16px; padding: 14px 32px;">
                Gửi CV ngay
            </a>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

