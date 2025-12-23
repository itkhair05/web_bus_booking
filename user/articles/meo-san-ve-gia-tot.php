<?php
/**
 * Bài viết: Mẹo săn vé giá tốt cuối tuần
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Mẹo săn vé giá tốt cuối tuần trên 4F Bus Booking';
$pageDescription = 'Bí quyết đặt vé xe khách với giá ưu đãi nhất vào cuối tuần';

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
    content: '💡';
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

.article-content .success-box {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-left: 4px solid #10b981;
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.article-content .success-box p {
    margin: 0;
    color: #065f46;
}

.article-content a {
    color: #2196F3;
    text-decoration: none;
    font-weight: 500;
}

.article-content a:hover {
    text-decoration: underline;
}

/* Tip Card Style */
.tip-card {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 20px 24px;
    margin: 24px 0;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.tip-card .tip-icon {
    font-size: 28px;
    flex-shrink: 0;
}

.tip-card .tip-content {
    flex: 1;
}

.tip-card .tip-content strong {
    color: #92400e;
    display: block;
    margin-bottom: 4px;
}

.tip-card .tip-content p {
    margin: 0;
    color: #78350f;
    font-size: 15px;
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
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
    color: #10b981;
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
    
    .tip-card {
        flex-direction: column;
        gap: 12px;
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
            <span>Mẹo săn vé giá tốt</span>
        </div>
        
        <!-- Article Header -->
        <header class="article-header">
            <h1>Mẹo săn vé giá tốt cuối tuần trên 4F Bus Booking</h1>
        </header>
        
        <!-- Featured Image -->
        <div class="article-featured-image">
            <img src="<?php echo IMG_URL; ?>/baiviet3.jpg" alt="Mẹo săn vé giá tốt cuối tuần" onerror="this.src='<?php echo IMG_URL; ?>/baiviet3.png'">
        </div>
        
        <!-- Article Content -->
        <article class="article-content">
            <p>Cuối tuần là <strong>"mùa cao điểm mini"</strong>: sinh viên về quê, nhóm bạn đi chơi, gia đình đi nghỉ ngắn. Vì vậy, vé dễ hết và giá dễ tăng ở các giờ đẹp. Tuy nhiên, nếu bạn <strong>đặt đúng thời điểm và linh hoạt một chút</strong>, vẫn có thể săn được vé giá tốt và chỗ ngồi hợp lý.</p>
            
            <h2>⏰ Đặt vé sớm là lợi thế lớn</h2>
            <ul>
                <li>Đặt sớm giúp bạn có <strong>nhiều lựa chọn</strong> về giờ chạy và chỗ ngồi.</li>
                <li>Với tuyến hot, càng gần ngày đi càng dễ <strong>hết ghế đẹp</strong>.</li>
                <li>Nếu bạn đã chắc lịch, đặt sớm sẽ giảm rủi ro <strong>"chạy vé" phút chót</strong>.</li>
            </ul>
            
            <div class="tip-card">
                <div class="tip-icon">💰</div>
                <div class="tip-content">
                    <strong>Mẹo hay</strong>
                    <p>Đặt vé trước 3-5 ngày thường có giá ổn định hơn và còn nhiều ghế để chọn!</p>
                </div>
            </div>
            
            <h2>🕐 Chọn khung giờ ít cạnh tranh</h2>
            <ul>
                <li><strong>Tránh các giờ "vàng"</strong> như chiều tối thứ 6, sáng thứ 7, chiều chủ nhật.</li>
                <li>Thử <strong>giờ sáng sớm hoặc trưa</strong> vì thường dễ có giá tốt và ít kẹt xe hơn.</li>
                <li>Nếu bạn sợ mệt, hãy ưu tiên chuyến có <strong>giờ chạy hợp nhịp sinh hoạt</strong> để dễ ngủ.</li>
            </ul>
            
            <div class="success-box">
                <p>✅ <strong>Khung giờ dễ có giá tốt:</strong> 6h-8h sáng, 10h-12h trưa, sau 20h tối</p>
            </div>
            
            <h2>🎁 Tận dụng ưu đãi đúng cách</h2>
            <ul>
                <li>Theo dõi mục <strong>Ưu đãi nổi bật</strong> để lấy mã giảm hoặc chương trình giờ vàng.</li>
                <li>Ưu tiên <strong>thanh toán online</strong> nếu có cashback hoặc ưu đãi kèm theo.</li>
                <li>Nếu có kế hoạch rõ ràng, cân nhắc <strong>đặt khứ hồi</strong> để tiết kiệm và giảm công săn vé.</li>
            </ul>
            
            <div class="info-box">
                <p>🔥 <strong>Tip:</strong> Mỗi thứ 6 hàng tuần, 4F Bus Booking thường có chương trình <strong>"Thứ 6 vui vẻ"</strong> với mã giảm giá hấp dẫn!</p>
            </div>
            
            <h2>👀 Đừng chỉ nhìn giá</h2>
            <ul>
                <li>Xem <strong>đánh giá nhà xe</strong>, chất lượng xe, điểm đón/trả và giờ chạy.</li>
                <li>Chọn <strong>điểm đón phù hợp</strong> để tránh tốn tiền di chuyển nội thành.</li>
                <li>Kiểm tra <strong>chính sách đổi/huỷ</strong> nếu bạn chưa chắc lịch.</li>
            </ul>
            
            <div class="highlight-box">
                <p>⚠️ <strong>Lưu ý:</strong> Giá và ưu đãi có thể thay đổi theo thời điểm, số lượng vé còn lại và tuyến đường. Dịp lễ hoặc sự kiện lớn, bạn nên đặt trước lâu hơn.</p>
            </div>
            
            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Câu hỏi thường gặp</h2>
                
                <div class="faq-item">
                    <div class="faq-question">Vì sao cùng tuyến nhưng giá khác nhau?</div>
                    <div class="faq-answer">Khác loại xe, tiện ích, giờ chạy và chính sách của từng nhà xe. Xe limousine thường đắt hơn xe ghế ngồi thường, chuyến giờ đẹp thường có giá cao hơn chuyến khuya.</div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Đặt sát giờ có rẻ hơn không?</div>
                    <div class="faq-answer">Không chắc. Có lúc còn ít ghế nên giá cao hoặc hết vé hoàn toàn. Đặt sớm vẫn là cách an toàn và tiết kiệm hơn.</div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <div class="article-cta">
                <h3>🎯 Săn vé giá tốt ngay!</h3>
                <p>Xem ưu đãi mới nhất và đặt vé trên 4F Bus Booking</p>
                <a href="<?php echo appUrl(); ?>" class="btn-cta">Tìm vé giá tốt</a>
            </div>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3>Bài viết liên quan</h3>
                <div class="related-grid">
                    <a href="<?php echo appUrl('user/articles/cach-chon-cho-ngoi-it-say-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet1.jpg" alt="Cách chọn chỗ ngồi ít say xe" onerror="this.src='<?php echo IMG_URL; ?>/baiviet1.png'">
                        <div class="card-title">Cách chọn chỗ ngồi ít say xe</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/checklist-truoc-khi-len-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet2.jpg" alt="Checklist trước khi lên xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet2.png'">
                        <div class="card-title">Checklist trước khi lên xe khách</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/nen-den-ben-truoc-bao-lau.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet4.jpg" alt="Nên đến bến trước bao lâu?" onerror="this.src='<?php echo IMG_URL; ?>/baiviet4.png'">
                        <div class="card-title">Nên đến bến trước bao lâu?</div>
                    </a>
                </div>
            </section>
        </article>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

