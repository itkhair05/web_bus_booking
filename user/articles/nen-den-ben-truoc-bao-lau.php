<?php
/**
 * Bài viết: Nên đến bến trước bao lâu?
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Nên đến bến trước bao lâu để không trễ chuyến?';
$pageDescription = 'Hướng dẫn thời gian hợp lý để đến bến xe trước giờ khởi hành';

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
    content: '•';
    position: absolute;
    left: 8px;
    top: 0;
    color: #8b5cf6;
    font-weight: 700;
    font-size: 18px;
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

/* Time Table */
.time-table {
    width: 100%;
    border-collapse: collapse;
    margin: 24px 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.time-table th {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: #fff;
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
}

.time-table td {
    padding: 14px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}

.time-table tr:last-child td {
    border-bottom: none;
}

.time-table tr:hover td {
    background: #f8fafc;
}

.time-badge {
    display: inline-block;
    background: #8b5cf6;
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
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
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
    color: #8b5cf6;
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
    
    .time-table th,
    .time-table td {
        padding: 12px 14px;
        font-size: 14px;
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
            <span>Nên đến bến trước bao lâu?</span>
        </div>
        
        <!-- Article Header -->
        <header class="article-header">
            <h1>Nên đến bến trước bao lâu để không trễ chuyến?</h1>
        </header>
        
        <!-- Featured Image -->
        <div class="article-featured-image">
            <img src="<?php echo IMG_URL; ?>/baiviet4.jpg" alt="Nên đến bến trước bao lâu?" onerror="this.src='<?php echo IMG_URL; ?>/baiviet4.png'">
        </div>
        
        <!-- Article Content -->
        <article class="article-content">
            <p>Một trong những lý do khiến chuyến đi trở nên căng thẳng là <strong>"sợ trễ"</strong>. Chỉ cần kẹt xe 15–20 phút là bạn đã phải chạy vội. Đến bến sớm giúp bạn <strong>chủ động hơn</strong>, tìm đúng khu vực nhà xe nhanh hơn và xử lý phát sinh nếu có.</p>
            
            <h2>⏱️ Mốc thời gian an toàn</h2>
            
            <table class="time-table">
                <thead>
                    <tr>
                        <th>Tình huống</th>
                        <th>Nên đến trước</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Ngày thường</td>
                        <td><span class="time-badge">30–45 phút</span></td>
                    </tr>
                    <tr>
                        <td>Lễ/Tết hoặc bến lớn</td>
                        <td><span class="time-badge">45–60 phút</span></td>
                    </tr>
                    <tr>
                        <td>Điểm đón dọc đường</td>
                        <td><span class="time-badge">10–15 phút</span></td>
                    </tr>
                </tbody>
            </table>
            
            <h2>🚨 Những tình huống nên đi sớm hơn</h2>
            <ul>
                <li>Bạn <strong>chưa quen bến xe</strong> hoặc chưa từng đi tuyến đó.</li>
                <li>Bạn đi vào <strong>giờ cao điểm</strong>, dễ kẹt xe.</li>
                <li>Bạn có <strong>hành lý nhiều</strong> hoặc đi cùng trẻ em/người lớn tuổi.</li>
            </ul>
            
            <div class="info-box">
                <p>💡 <strong>Mẹo:</strong> Kiểm tra Google Maps hoặc ứng dụng giao thông trước khi xuất phát để ước tính thời gian di chuyển chính xác hơn.</p>
            </div>
            
            <h2>😌 Vì sao đi sớm giúp bạn "dễ thở"?</h2>
            <ul>
                <li>Có thời gian <strong>gửi hành lý</strong>, tìm đúng cổng và xếp đồ gọn.</li>
                <li>Có thể <strong>xử lý tình huống</strong> như đổi cổng, thay đổi điểm đón, mưa lớn.</li>
                <li>Tránh tâm lý hoảng, làm bạn <strong>mệt trước khi lên xe</strong>.</li>
            </ul>
            
            <div class="highlight-box">
                <p>⚠️ <strong>Lưu ý:</strong></p>
                <p style="margin-top: 8px !important;">• Hãy cộng thêm thời gian di chuyển nội thành, đặc biệt sáng sớm và chiều tối.</p>
                <p style="margin-top: 4px !important;">• Nếu bạn thấy có dấu hiệu trễ, hãy liên hệ sớm để được hướng dẫn.</p>
            </div>
            
            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Câu hỏi thường gặp</h2>
                
                <div class="faq-item">
                    <div class="faq-question">Lỡ trễ điểm đón thì sao?</div>
                    <div class="faq-answer">Tùy nhà xe. Bạn nên gọi ngay để hỏi phương án phù hợp, vì mỗi chuyến có lịch trình riêng. Một số nhà xe có thể chờ thêm vài phút hoặc hướng dẫn bạn đến điểm đón tiếp theo.</div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <div class="article-cta">
                <h3>🎫 Đặt vé và xem điểm đón chi tiết</h3>
                <p>Kiểm tra thông tin chuyến xe và điểm đón trên 4F Bus Booking</p>
                <a href="<?php echo appUrl(); ?>" class="btn-cta">Tìm chuyến xe</a>
            </div>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3>Bài viết liên quan</h3>
                <div class="related-grid">
                    <a href="<?php echo appUrl('user/articles/checklist-truoc-khi-len-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet2.jpg" alt="Checklist trước khi lên xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet2.png'">
                        <div class="card-title">Checklist trước khi lên xe khách</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/quy-dinh-hanh-ly.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet5.jpg" alt="Quy định hành lý khi đi xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet5.png'">
                        <div class="card-title">Quy định hành lý khi đi xe khách</div>
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

