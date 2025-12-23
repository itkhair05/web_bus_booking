<?php
/**
 * Bài viết: Quy định hành lý khi đi xe khách
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Quy định hành lý khi đi xe khách: những điều cần biết';
$pageDescription = 'Hướng dẫn về hành lý xách tay và ký gửi khi đi xe khách';

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
    content: '📦';
    position: absolute;
    left: 0;
    top: 0;
    font-size: 14px;
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

.article-content a {
    color: #2196F3;
    text-decoration: none;
    font-weight: 500;
}

.article-content a:hover {
    text-decoration: underline;
}

/* Luggage Cards */
.luggage-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 24px 0;
}

.luggage-card {
    border-radius: 12px;
    padding: 20px;
    border: 2px solid;
}

.luggage-card.carry-on {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: #10b981;
}

.luggage-card.checked {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-color: #3b82f6;
}

.luggage-card h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 700;
}

.luggage-card.carry-on h4 {
    color: #065f46;
}

.luggage-card.checked h4 {
    color: #1e40af;
}

.luggage-card ul {
    margin: 0;
}

.luggage-card ul li::before {
    content: '✓' !important;
    color: inherit;
    font-size: 14px;
}

.luggage-card.carry-on ul li::before {
    color: #10b981;
}

.luggage-card.checked ul li::before {
    color: #3b82f6;
}

/* Warning List */
.warning-list li::before {
    content: '⚠️' !important;
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
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    color: #d97706;
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
    
    .article-content {
        font-size: 15px;
    }
    
    .article-cta {
        padding: 24px 20px;
    }
    
    .article-cta h3 {
        font-size: 18px;
    }
    
    .luggage-grid {
        grid-template-columns: 1fr;
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
            <span>Quy định hành lý</span>
        </div>
        
        <!-- Article Header -->
        <header class="article-header">
            <h1>Quy định hành lý khi đi xe khách: những điều cần biết</h1>
        </header>
        
        <!-- Featured Image -->
        <div class="article-featured-image">
            <img src="<?php echo IMG_URL; ?>/baiviet5.jpg" alt="Quy định hành lý khi đi xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet5.png'">
        </div>
        
        <!-- Article Content -->
        <article class="article-content">
            <p>Mang hành lý đúng cách giúp bạn <strong>lên xe nhanh</strong>, tránh phát sinh phí và hạn chế rủi ro thất lạc. Mỗi nhà xe có thể có quy định riêng, nhưng đa số đều ưu tiên hành lý gọn, dễ xếp và đảm bảo an toàn cho chuyến đi.</p>
            
            <h2>📋 Nguyên tắc chung khi mang hành lý</h2>
            <ul>
                <li><strong>Đóng gói gọn</strong>, hạn chế nhiều túi nhỏ dễ thất lạc.</li>
                <li><strong>Đồ giá trị</strong> nên mang theo người.</li>
                <li><strong>Hàng cồng kềnh</strong> nên hỏi trước để biết phí và cách gửi.</li>
            </ul>
            
            <div class="luggage-grid">
                <div class="luggage-card carry-on">
                    <h4>🎒 Nên mang theo người</h4>
                    <ul>
                        <li>CCCD, ví, điện thoại, laptop</li>
                        <li>Thuốc cá nhân và đồ cần dùng</li>
                        <li>Đồ dễ vỡ hoặc giấy tờ quan trọng</li>
                    </ul>
                </div>
                
                <div class="luggage-card checked">
                    <h4>🧳 Nên gửi cốp</h4>
                    <ul>
                        <li>Vali, balo lớn, thùng đồ đóng gói chắc</li>
                        <li>Đồ không cần dùng trong hành trình</li>
                        <li>Quần áo, đồ dùng cá nhân</li>
                    </ul>
                </div>
            </div>
            
            <h2>⚡ Những thứ cần lưu ý đặc biệt</h2>
            <ul class="warning-list">
                <li><strong>Đồ dễ vỡ:</strong> bọc chống sốc, ghi chú rõ "Hàng dễ vỡ".</li>
                <li><strong>Đồ có mùi:</strong> nên hỏi trước để tránh gây khó chịu cho người khác.</li>
                <li><strong>Hàng hóa cồng kềnh:</strong> chuẩn bị sẵn thông tin kích thước/khối lượng nếu nhà xe hỏi.</li>
            </ul>
            
            <div class="info-box">
                <p>📸 <strong>Mẹo hay:</strong> Nếu bạn gửi nhiều đồ, nên chụp ảnh hành lý và giữ lại phiếu gửi (nếu có) để tiện đối chiếu khi nhận lại.</p>
            </div>
            
            <div class="highlight-box">
                <p>⚠️ <strong>Lưu ý quan trọng:</strong> Khi xuống xe, hãy kiểm tra lại đủ hành lý trước khi rời điểm trả. Nếu phát hiện thiếu đồ, báo ngay cho tài xế hoặc nhà xe.</p>
            </div>
            
            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Câu hỏi thường gặp</h2>
                
                <div class="faq-item">
                    <div class="faq-question">Hành lý có bị tính phí không?</div>
                    <div class="faq-answer">Có thể, đặc biệt với hàng cồng kềnh hoặc số lượng nhiều. Tốt nhất bạn hỏi trước khi đi để tránh bất ngờ. Thông thường, 1-2 vali/balo thường được miễn phí.</div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Có thể gửi xe máy theo xe khách không?</div>
                    <div class="faq-answer">Một số nhà xe có dịch vụ vận chuyển xe máy, nhưng bạn cần đăng ký trước và có thể tính phí riêng. Liên hệ nhà xe để biết chi tiết.</div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <div class="article-cta">
                <h3>🚌 Sẵn sàng cho chuyến đi!</h3>
                <p>Đặt vé và chuẩn bị hành lý gọn gàng cùng 4F Bus Booking</p>
                <a href="<?php echo appUrl(); ?>" class="btn-cta">Đặt vé ngay</a>
            </div>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3>Bài viết liên quan</h3>
                <div class="related-grid">
                    <a href="<?php echo appUrl('user/articles/checklist-truoc-khi-len-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet2.jpg" alt="Checklist trước khi lên xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet2.png'">
                        <div class="card-title">Checklist trước khi lên xe khách</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/nen-den-ben-truoc-bao-lau.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet4.jpg" alt="Nên đến bến trước bao lâu?" onerror="this.src='<?php echo IMG_URL; ?>/baiviet4.png'">
                        <div class="card-title">Nên đến bến trước bao lâu?</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/cach-chon-cho-ngoi-it-say-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet1.jpg" alt="Cách chọn chỗ ngồi ít say xe" onerror="this.src='<?php echo IMG_URL; ?>/baiviet1.png'">
                        <div class="card-title">Cách chọn chỗ ngồi ít say xe</div>
                    </a>
                </div>
            </section>
        </article>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

