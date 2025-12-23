<?php
/**
 * Bài viết: Cách chọn chỗ ngồi ít say xe khi đi xe khách
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Cách chọn chỗ ngồi ít say xe khi đi xe khách';
$pageDescription = 'Hướng dẫn chọn vị trí ngồi phù hợp để giảm say xe khi đi xe khách đường dài';

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
    content: '';
    position: absolute;
    left: 0;
    top: 10px;
    width: 8px;
    height: 8px;
    background: #FF6B35;
    border-radius: 50%;
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
    background: linear-gradient(135deg, #FF6B35 0%, #f97316 100%);
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
    color: #FF6B35;
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
            <span>Cách chọn chỗ ngồi ít say xe</span>
        </div>
        
        <!-- Article Header -->
        <header class="article-header">
            <h1>Cách chọn chỗ ngồi ít say xe khi đi xe khách</h1>
        </header>
        
        <!-- Featured Image -->
        <div class="article-featured-image">
            <img src="<?php echo IMG_URL; ?>/baiviet1.jpg" alt="Cách chọn chỗ ngồi ít say xe" onerror="this.src='<?php echo IMG_URL; ?>/baiviet1.png'">
        </div>
        
        <!-- Article Content -->
        <article class="article-content">
            <p>Say xe không chỉ do "yếu bụng" mà còn liên quan đến <strong>vị trí ngồi</strong>, mức rung lắc và thói quen trong suốt hành trình. Nếu bạn thường chóng mặt, buồn nôn khi đi xe đường dài, hãy thử áp dụng các gợi ý dưới đây để chuyến đi nhẹ nhàng hơn.</p>
            
            <div class="info-box">
                <p>💡 Trên <strong>4F Bus Booking</strong>, bạn có thể chủ động chọn chuyến phù hợp và chỗ ngồi dễ chịu ngay từ đầu.</p>
            </div>
            
            <h2>Vì sao ngồi sai vị trí dễ say xe?</h2>
            <p>Khi xe tăng tốc, phanh, vào cua hoặc đi đường xấu, cơ thể cảm nhận rung lắc. Nếu mắt bạn lại nhìn một điểm cố định (như màn hình điện thoại), não sẽ "mâu thuẫn" giữa cảm giác chuyển động và hình ảnh, dẫn đến say xe.</p>
            
            <h2>Vị trí ngồi nên ưu tiên</h2>
            <ul>
                <li><strong>Khu vực giữa xe</strong> thường ổn định hơn, rung lắc ít hơn so với đầu và cuối xe.</li>
                <li><strong>Gần cửa sổ</strong> giúp bạn nhìn ra xa và theo dõi đường, giảm cảm giác chóng mặt.</li>
                <li>Nếu xe có nhiều dãy, chọn chỗ <strong>gần trục giữa</strong> (không quá sát bánh xe) để đỡ xóc.</li>
                <li>Với <strong>xe 2 tầng</strong>, người dễ say nên chọn tầng dưới vì ít chao hơn.</li>
            </ul>
            
            <h2>Vị trí nên hạn chế</h2>
            <ul>
                <li><strong>Cuối xe</strong> thường xóc và rung hơn, dễ gây khó chịu.</li>
                <li>Chỗ <strong>quá gần bánh xe</strong> có thể cảm nhận đường xấu rõ hơn.</li>
                <li>Ghế gần khu vực có mùi (tùy xe) dễ khiến bạn nôn nao.</li>
            </ul>
            
            <h2>Mẹo chống say trước chuyến đi</h2>
            <ul>
                <li>Ăn nhẹ trước khi đi khoảng <strong>1–2 giờ</strong>, tránh thức ăn nhiều dầu mỡ.</li>
                <li>Uống đủ nước, tránh cà phê/đồ có gas nếu bạn dễ say.</li>
                <li>Mang theo <strong>kẹo gừng, bạc hà</strong> hoặc dầu gió (nếu hợp).</li>
                <li>Nếu cần dùng thuốc chống say, nên dùng <strong>trước khi lên xe</strong> theo hướng dẫn phù hợp.</li>
            </ul>
            
            <h2>Mẹo trong lúc xe chạy</h2>
            <ul>
                <li><strong>Nhìn ra xa</strong>, tránh cúi nhìn điện thoại quá lâu.</li>
                <li>Ngồi thẳng lưng, thở đều, giữ tinh thần thư giãn.</li>
                <li>Nếu buồn nôn, mở nhẹ cửa gió/điều chỉnh tư thế và tập trung nhìn đường.</li>
            </ul>
            
            <div class="highlight-box">
                <p>🔔 <strong>Lưu ý:</strong> Mỗi người hợp "một kiểu ghế". Nếu chuyến này chưa ổn, bạn thử đổi sang vùng giữa hoặc gần cửa sổ ở chuyến sau. Khi đặt vé trên 4F Bus Booking, hãy ưu tiên chuyến có giờ chạy phù hợp để bạn không bị mệt trước khi lên xe.</p>
            </div>
            
            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Câu hỏi thường gặp</h2>
                
                <div class="faq-item">
                    <div class="faq-question">Ngồi đầu xe có ít say không?</div>
                    <div class="faq-answer">Thường ít xóc hơn, nhưng một số người lại khó chịu vì cảm giác tăng tốc/phanh rõ. Bạn có thể thử 1–2 lần để biết cơ địa mình hợp kiểu nào.</div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Say xe có phải do mùi không?</div>
                    <div class="faq-answer">Có thể. Mùi xe, mùi đồ ăn hoặc mùi xăng dầu đều dễ kích thích buồn nôn. Hãy chọn vị trí thoáng và tránh ăn đồ nặng mùi trước chuyến đi.</div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <div class="article-cta">
                <h3>🚌 Đặt vé ngay - Chọn chỗ ngồi ưng ý!</h3>
                <p>Chủ động chọn vị trí phù hợp với bạn trên 4F Bus Booking</p>
                <a href="<?php echo appUrl(); ?>" class="btn-cta">Tìm chuyến xe ngay</a>
            </div>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3>Bài viết liên quan</h3>
                <div class="related-grid">
                    <a href="<?php echo appUrl('user/articles/checklist-truoc-khi-len-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet2.jpg" alt="Checklist trước khi lên xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet2.png'">
                        <div class="card-title">Checklist trước khi lên xe khách</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/meo-san-ve-gia-tot.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet3.jpg" alt="Mẹo săn vé giá tốt cuối tuần" onerror="this.src='<?php echo IMG_URL; ?>/baiviet3.png'">
                        <div class="card-title">Mẹo săn vé giá tốt cuối tuần</div>
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

