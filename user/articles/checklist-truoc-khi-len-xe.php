<?php
/**
 * Bài viết: Checklist trước khi lên xe khách
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Checklist trước khi lên xe khách';
$pageDescription = 'Danh sách những thứ cần chuẩn bị trước khi bắt đầu hành trình xe khách';

include '../../includes/header_user.php';
?>

<style>
/* Article Page Styles */
.article-page {
    background: #fff;
    min-height: 100vh;
}

.article-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.article-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.article-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.article-breadcrumb a:hover {
    text-decoration: underline;
}

.article-breadcrumb span {
    color: #666;
}

.article-header {
    margin-bottom: 32px;
}

.article-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.3;
    margin-bottom: 0;
}

.article-featured-image {
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.article-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.article-content {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
}

.article-content p {
    margin-bottom: 20px;
}

.article-content h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 40px 0 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.article-content h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin: 32px 0 16px;
}

.article-content ul {
    margin: 16px 0 24px 0;
    padding-left: 0;
    list-style: none;
}

.article-content ul li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 12px;
    line-height: 1.7;
}

.article-content ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    top: 0;
    color: #10b981;
    font-weight: 700;
    font-size: 16px;
}

.article-content .highlight-box {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border-left: 4px solid #FF6B35;
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.article-content .highlight-box p {
    margin: 0;
    color: #9a3412;
}

.article-content .info-box {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-left: 4px solid #3B82F6;
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.article-content .info-box p {
    margin: 0;
    color: #1e40af;
}

.article-content .warning-box {
    background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%);
    border-left: 4px solid #eab308;
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.article-content .warning-box p {
    margin: 0;
    color: #854d0e;
}

.article-content a {
    color: #2196F3;
    text-decoration: none;
    font-weight: 500;
}

.article-content a:hover {
    text-decoration: underline;
}

/* Checklist Style */
.checklist-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    margin: 24px 0;
}

.checklist-section h3 {
    margin-top: 0 !important;
    color: #1e40af;
    font-size: 18px;
}

.checklist-section ul li::before {
    content: '☐';
    color: #6b7280;
}

/* FAQ Section */
.faq-section {
    margin-top: 48px;
    padding-top: 32px;
    border-top: 2px solid #e5e7eb;
}

.faq-item {
    margin-bottom: 24px;
}

.faq-question {
    font-size: 17px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.faq-answer {
    color: #555;
    line-height: 1.7;
}

/* CTA Section */
.article-cta {
    background: linear-gradient(135deg, #3B82F6 0%, #2563eb 100%);
    border-radius: 16px;
    padding: 32px;
    margin-top: 48px;
    text-align: center;
    color: #fff;
}

.article-cta h3 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #fff;
}

.article-cta p {
    font-size: 16px;
    opacity: 0.95;
    margin-bottom: 20px;
}

.article-cta .btn-cta {
    display: inline-block;
    background: #fff;
    color: #3B82F6;
    padding: 14px 32px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.article-cta .btn-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* Related Articles */
.related-articles {
    margin-top: 48px;
    padding-top: 32px;
    border-top: 2px solid #e5e7eb;
}

.related-articles h3 {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.related-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    text-decoration: none;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.related-card img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}

.related-card .card-title {
    padding: 14px 16px;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    line-height: 1.4;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .article-container {
        padding: 24px 16px 60px;
    }
    
    .article-header h1 {
        font-size: 24px;
    }
    
    .article-content h2 {
        font-size: 20px;
    }
    
    .article-content h3 {
        font-size: 18px;
    }
    
    .article-content {
        font-size: 15px;
    }
    
    .article-cta {
        padding: 24px 20px;
    }
    
    .article-cta h3 {
        font-size: 18px;
    }
    
    .checklist-section {
        padding: 16px;
    }
}
</style>

<main class="article-page">
    <div class="article-container">
        <!-- Breadcrumb -->
        <div class="article-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> 
            <span> › </span>
            <a href="<?php echo appUrl('user/articles/'); ?>">Bài viết hay</a>
            <span> › </span>
            <span>Checklist trước khi lên xe</span>
        </div>
        
        <!-- Article Header -->
        <header class="article-header">
            <h1>Checklist trước khi lên xe khách</h1>
        </header>
        
        <!-- Featured Image -->
        <div class="article-featured-image">
            <img src="<?php echo IMG_URL; ?>/baiviet2.jpg" alt="Checklist trước khi lên xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet2.png'">
        </div>
        
        <!-- Article Content -->
        <article class="article-content">
            <p>Một checklist nhỏ sẽ giúp bạn tránh được những tình huống rất "đời": ra bến mới nhớ quên giấy tờ, tới nơi mới phát hiện sai điểm đón, hoặc trễ chuyến vì kẹt xe. Đặc biệt khi đi xa, đi cuối tuần hoặc đi dịp cao điểm, <strong>chuẩn bị đúng sẽ giúp bạn nhẹ đầu và đi thoải mái hơn</strong>.</p>
            
            <div class="info-box">
                <p>📋 Dưới đây là checklist gọn, dễ áp dụng cho hầu hết chuyến đi khi đặt vé trên <strong>4F Bus Booking</strong>.</p>
            </div>
            
            <h2>🏠 Trước khi rời nhà</h2>
            <div class="checklist-section">
                <ul>
                    <li>Kiểm tra lại <strong>ngày đi, giờ đi, điểm đón, điểm trả</strong> theo kế hoạch của bạn.</li>
                    <li>Mở lại vé trong <strong>Vé của tôi</strong> để chắc chắn đúng thông tin.</li>
                    <li><strong>Chụp màn hình mã vé/QR</strong> phòng khi không có mạng.</li>
                    <li>Sạc pin điện thoại, chuẩn bị <strong>sạc dự phòng</strong> nếu chuyến đi dài.</li>
                    <li>Xem <strong>thời tiết</strong> để mang áo khoác/áo mưa phù hợp.</li>
                </ul>
            </div>
            
            <h2>📌 Đồ quan trọng nên để riêng</h2>
            <div class="checklist-section">
                <ul>
                    <li><strong>CCCD/giấy tờ cần thiết</strong> (tùy tuyến/nhà xe có thể đối soát).</li>
                    <li>Ví, thẻ, <strong>tiền mặt nhỏ</strong>.</li>
                    <li>Thuốc cá nhân, khẩu trang, khăn giấy, nước rửa tay.</li>
                    <li>Tai nghe, gối cổ, bịt mắt nếu bạn cần ngủ.</li>
                </ul>
            </div>
            
            <h2>🎒 Hành lý mang theo và hành lý gửi</h2>
            <div class="checklist-section">
                <ul>
                    <li>Sắp xếp hành lý gọn vào <strong>1–2 túi</strong>, tránh quá nhiều túi nhỏ.</li>
                    <li>Đồ giá trị (laptop, máy ảnh, giấy tờ) <strong>nên mang theo người</strong>.</li>
                    <li>Nếu hành lý dễ vỡ, bọc kỹ và ghi chú <strong>"Hàng dễ vỡ"</strong>.</li>
                    <li>Nếu gửi cốp nhiều đồ, bạn nên <strong>chụp nhanh ảnh</strong> để tiện đối chiếu.</li>
                </ul>
            </div>
            
            <h2>🚏 Đến điểm đón/bến xe</h2>
            <div class="checklist-section">
                <ul>
                    <li>Đi <strong>ngày thường</strong> nên đến sớm để không bị cuống.</li>
                    <li>Đi <strong>lễ/Tết hoặc giờ cao điểm</strong> nên cộng thêm thời gian di chuyển vì dễ kẹt xe.</li>
                    <li>Kiểm tra đúng <strong>nhà xe/tuyến/biển số</strong> trước khi lên xe (nếu có thông tin).</li>
                    <li>Giữ đồ quan trọng bên người và <strong>hạn chế để điện thoại, ví ở túi ngoài</strong> dễ rơi.</li>
                </ul>
            </div>
            
            <div class="warning-box">
                <p>⚠️ <strong>Lưu ý:</strong></p>
                <p style="margin-top: 8px !important;">• Nếu đi cùng <strong>người lớn tuổi hoặc trẻ em</strong>, hãy chuẩn bị nước, đồ ăn nhẹ và áo khoác.</p>
                <p style="margin-top: 4px !important;">• Nếu bạn <strong>dễ say xe</strong>, nên mang theo kẹo gừng và chọn chỗ ngồi ổn định.</p>
            </div>
            
            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Câu hỏi thường gặp</h2>
                
                <div class="faq-item">
                    <div class="faq-question">Không có mạng thì mở vé thế nào?</div>
                    <div class="faq-answer">Hãy chụp màn hình mã vé/QR trước khi ra bến. Bạn có thể xuất vé từ mục "Vé của tôi" và lưu ảnh vào điện thoại.</div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Có cần in vé không?</div>
                    <div class="faq-answer">Thường không cần, trừ khi nhà xe yêu cầu. Mã vé/QR trên điện thoại thường đủ để lên xe.</div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <div class="article-cta">
                <h3>🎫 Đã sẵn sàng cho chuyến đi?</h3>
                <p>Đặt vé ngay trên 4F Bus Booking - Nhanh chóng, tiện lợi!</p>
                <a href="<?php echo appUrl(); ?>" class="btn-cta">Đặt vé ngay</a>
            </div>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3>Bài viết liên quan</h3>
                <div class="related-grid">
                    <a href="<?php echo appUrl('user/articles/cach-chon-cho-ngoi-it-say-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet1.jpg" alt="Cách chọn chỗ ngồi ít say xe" onerror="this.src='<?php echo IMG_URL; ?>/baiviet1.png'">
                        <div class="card-title">Cách chọn chỗ ngồi ít say xe</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/nen-den-ben-truoc-bao-lau.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet4.jpg" alt="Nên đến bến trước bao lâu?" onerror="this.src='<?php echo IMG_URL; ?>/baiviet4.png'">
                        <div class="card-title">Nên đến bến trước bao lâu?</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/quy-dinh-hanh-ly.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet5.jpg" alt="Quy định hành lý khi đi xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet5.png'">
                        <div class="card-title">Quy định hành lý khi đi xe khách</div>
                    </a>
                </div>
            </section>
        </article>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

