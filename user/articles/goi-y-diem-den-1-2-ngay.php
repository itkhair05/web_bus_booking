<?php
/**
 * Bài viết: Gợi ý điểm đến 1–2 ngày
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Gợi ý điểm đến 1–2 ngày: đi gần, dễ đi, tiết kiệm';
$pageDescription = 'Những địa điểm du lịch ngắn ngày lý tưởng bằng xe khách';

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
    font-size: 18px;
    font-weight: 600;
    color: #1a1a2e;
    margin: 24px 0 12px;
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
    content: '✈️';
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

/* Destination Cards */
.destination-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 24px 0;
}

.destination-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
}

.destination-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.destination-card .card-image {
    height: 160px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.destination-card .card-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(255, 255, 255, 0.95);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #333;
}

.destination-card .card-content {
    padding: 16px 20px;
    background: #fff;
}

.destination-card .card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.destination-card .card-desc {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
    line-height: 1.5;
}

.destination-card .card-info {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: #888;
}

.destination-card .card-info span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.destination-card .card-info i {
    color: #FF6B35;
}

/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 12px;
    margin: 24px 0;
    flex-wrap: wrap;
}

.category-tab {
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid #e5e7eb;
    background: #fff;
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-tab:hover {
    border-color: #FF6B35;
    color: #FF6B35;
}

.category-tab.active {
    background: #FF6B35;
    border-color: #FF6B35;
    color: #fff;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 30px;
    margin: 24px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, #FF6B35, #f97316);
    border-radius: 2px;
}

.timeline-item {
    position: relative;
    padding-bottom: 24px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 4px;
    width: 12px;
    height: 12px;
    background: #FF6B35;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #FF6B35;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-time {
    font-size: 14px;
    font-weight: 700;
    color: #FF6B35;
    margin-bottom: 4px;
}

.timeline-content {
    font-size: 15px;
    color: #333;
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
    background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
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
    color: #be185d;
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
    
    .destination-grid {
        grid-template-columns: 1fr;
    }
    
    .category-tabs {
        justify-content: center;
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
            <span>Gợi ý điểm đến 1–2 ngày</span>
        </div>
        
        <!-- Article Header -->
        <header class="article-header">
            <h1>Gợi ý điểm đến 1–2 ngày: đi gần, dễ đi, tiết kiệm</h1>
        </header>
        
        <!-- Featured Image -->
        <div class="article-featured-image">
            <img src="<?php echo IMG_URL; ?>/baiviet6.jpg" alt="Gợi ý điểm đến 1–2 ngày" onerror="this.src='<?php echo IMG_URL; ?>/baiviet6.png'">
        </div>
        
        <!-- Article Content -->
        <article class="article-content">
            <p>Một chuyến đi ngắn cũng đủ để <strong>"đổi gió"</strong> và nạp năng lượng. Bí quyết là chọn nơi không quá xa và lên lịch hợp lý để không mất thời gian trên đường. Với các tuyến phổ biến, bạn có thể dễ dàng chọn chuyến phù hợp trên <strong>4F Bus Booking</strong>.</p>
            
            <h2>🎯 Đi 1–2 ngày nên chọn điểm đến như thế nào?</h2>
            <ul>
                <li><strong>Thời gian di chuyển hợp lý</strong>, không làm bạn mệt trước khi đến nơi.</li>
                <li>Có <strong>nhiều hoạt động</strong> gói gọn trong 1–2 ngày.</li>
                <li>Có <strong>nhiều khung giờ xe chạy</strong> để bạn linh hoạt lựa chọn.</li>
            </ul>
            
            <div class="info-box">
                <p>💡 <strong>Mẹo:</strong> Ưu tiên điểm đến cách 2-4 tiếng đi xe để có thời gian vui chơi nhiều nhất!</p>
            </div>
            
            <h2>🏖️ Gợi ý điểm đến theo loại hình</h2>
            
            <div class="category-tabs">
                <span class="category-tab active">🏖️ Biển</span>
                <span class="category-tab">🏔️ Núi/Đồi</span>
                <span class="category-tab">🏙️ Thành phố</span>
            </div>
            
            <div class="destination-grid">
                <!-- Destination 1 -->
                <a href="<?php echo appUrl('user/search/results.php?from=Sài Gòn&to=Vũng Tàu&date=' . date('Y-m-d', strtotime('+7 days'))); ?>" class="destination-card">
                    <div class="card-image" style="background-image: url('<?php echo IMG_URL; ?>/vũng tàu.jpg');">
                        <span class="card-badge">🏖️ Biển</span>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Vũng Tàu</div>
                        <div class="card-desc">Biển đẹp, hải sản tươi, café view biển cực chill</div>
                        <div class="card-info">
                            <span><i class="fas fa-clock"></i> ~2h từ Sài Gòn</span>
                            <span><i class="fas fa-tag"></i> Từ 90.000đ</span>
                        </div>
                    </div>
                </a>
                
                <!-- Destination 2 -->
                <a href="<?php echo appUrl('user/search/results.php?from=Sài Gòn&to=Đà Lạt&date=' . date('Y-m-d', strtotime('+7 days'))); ?>" class="destination-card">
                    <div class="card-image" style="background-image: url('<?php echo IMG_URL; ?>/đà lạt.jpg');">
                        <span class="card-badge">🏔️ Núi</span>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Đà Lạt</div>
                        <div class="card-desc">Thành phố ngàn hoa, khí hậu mát mẻ quanh năm</div>
                        <div class="card-info">
                            <span><i class="fas fa-clock"></i> ~6h từ Sài Gòn</span>
                            <span><i class="fas fa-tag"></i> Từ 200.000đ</span>
                        </div>
                    </div>
                </a>
                
                <!-- Destination 3 -->
                <a href="<?php echo appUrl('user/search/results.php?from=Quảng Ngãi&to=Đà Nẵng&date=' . date('Y-m-d', strtotime('+7 days'))); ?>" class="destination-card">
                    <div class="card-image" style="background-image: url('<?php echo IMG_URL; ?>/đà nẵng.jpg');">
                        <span class="card-badge">🏙️ Thành phố</span>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Đà Nẵng</div>
                        <div class="card-desc">Cầu Rồng, Bà Nà Hills, biển Mỹ Khê nổi tiếng</div>
                        <div class="card-info">
                            <span><i class="fas fa-clock"></i> ~2h từ Quảng Ngãi</span>
                            <span><i class="fas fa-tag"></i> Từ 90.000đ</span>
                        </div>
                    </div>
                </a>
                
                <!-- Destination 4 -->
                <a href="<?php echo appUrl('user/search/results.php?from=Hà Nội&to=Sapa&date=' . date('Y-m-d', strtotime('+7 days'))); ?>" class="destination-card">
                    <div class="card-image" style="background-image: url('<?php echo IMG_URL; ?>/sapa.jpg');">
                        <span class="card-badge">🏔️ Núi</span>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Sapa</div>
                        <div class="card-desc">Ruộng bậc thang, đỉnh Fansipan, văn hóa dân tộc</div>
                        <div class="card-info">
                            <span><i class="fas fa-clock"></i> ~5h từ Hà Nội</span>
                            <span><i class="fas fa-tag"></i> Từ 250.000đ</span>
                        </div>
                    </div>
                </a>
            </div>
            
            <h2>📅 Lịch trình gợi ý cho chuyến đi 2 ngày 1 đêm</h2>
            
            <h3>Ngày 1:</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-time">5:00 - 6:00</div>
                    <div class="timeline-content">Khởi hành từ điểm đón, tranh thủ ngủ thêm trên xe</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">9:00 - 10:00</div>
                    <div class="timeline-content">Đến nơi, nhận phòng khách sạn/homestay</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">10:00 - 12:00</div>
                    <div class="timeline-content">Khám phá địa điểm đầu tiên</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">12:00 - 14:00</div>
                    <div class="timeline-content">Ăn trưa, thưởng thức đặc sản địa phương</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">14:00 - 18:00</div>
                    <div class="timeline-content">Tiếp tục tham quan, check-in các điểm đẹp</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">18:00 - 21:00</div>
                    <div class="timeline-content">Ăn tối, dạo phố đêm, nghỉ ngơi</div>
                </div>
            </div>
            
            <h3>Ngày 2:</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-time">7:00 - 8:00</div>
                    <div class="timeline-content">Ăn sáng, trả phòng</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">8:00 - 11:00</div>
                    <div class="timeline-content">Tham quan địa điểm còn lại</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">11:00 - 12:00</div>
                    <div class="timeline-content">Mua quà, đặc sản về làm quà</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">13:00 - 14:00</div>
                    <div class="timeline-content">Lên xe về, nghỉ ngơi trên đường</div>
                </div>
            </div>
            
            <div class="highlight-box">
                <p>⚠️ <strong>Lưu ý:</strong></p>
                <p style="margin-top: 8px !important;">• Nếu đi nhóm, hãy thống nhất giờ đi và điểm đón để tránh chờ đợi.</p>
                <p style="margin-top: 4px !important;">• Mang hành lý gọn để di chuyển thuận tiện.</p>
                <p style="margin-top: 4px !important;">• Đi cuối tuần nên đặt vé sớm để có chỗ ngồi đẹp!</p>
            </div>
            
            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Câu hỏi thường gặp</h2>
                
                <div class="faq-item">
                    <div class="faq-question">Đi 2 ngày 1 đêm có đủ không?</div>
                    <div class="faq-answer">Đủ nếu bạn chọn điểm đến gần (2-4 tiếng đi xe) và lên lịch gọn, ưu tiên trải nghiệm chính. Nếu muốn thư thả hơn, có thể chọn 3 ngày 2 đêm.</div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Nên đi một mình hay theo nhóm?</div>
                    <div class="faq-answer">Đi nhóm sẽ vui hơn và tiết kiệm chi phí chia phòng, xe. Nhưng đi một mình cũng rất thú vị vì bạn tự do sắp xếp lịch trình theo ý thích.</div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <div class="article-cta">
                <h3>🎒 Sẵn sàng "đổi gió" chưa?</h3>
                <p>Đặt vé ngay và khám phá điểm đến mới cùng 4F Bus Booking!</p>
                <a href="<?php echo appUrl(); ?>" class="btn-cta">Tìm chuyến xe</a>
            </div>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3>Bài viết liên quan</h3>
                <div class="related-grid">
                    <a href="<?php echo appUrl('user/articles/meo-san-ve-gia-tot.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet3.jpg" alt="Mẹo săn vé giá tốt cuối tuần" onerror="this.src='<?php echo IMG_URL; ?>/baiviet3.png'">
                        <div class="card-title">Mẹo săn vé giá tốt cuối tuần</div>
                    </a>
                    <a href="<?php echo appUrl('user/articles/checklist-truoc-khi-len-xe.php'); ?>" class="related-card">
                        <img src="<?php echo IMG_URL; ?>/baiviet2.jpg" alt="Checklist trước khi lên xe khách" onerror="this.src='<?php echo IMG_URL; ?>/baiviet2.png'">
                        <div class="card-title">Checklist trước khi lên xe khách</div>
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

