<?php
/**
 * Trang danh sách bài viết hay
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Bài viết hay - 4F Bus Booking';
$pageDescription = 'Tổng hợp các bài viết hữu ích về kinh nghiệm đi xe khách, mẹo đặt vé và gợi ý điểm đến';

include '../../includes/header_user.php';

// Danh sách bài viết
$articles = [
    [
        'slug' => 'cach-chon-cho-ngoi-it-say-xe',
        'title' => 'Cách chọn chỗ ngồi ít say xe',
        'description' => 'Hướng dẫn chọn vị trí ngồi phù hợp để giảm say xe khi đi xe khách đường dài',
        'image' => 'baiviet1.jpg',
        'category' => 'Mẹo hay'
    ],
    [
        'slug' => 'checklist-truoc-khi-len-xe',
        'title' => 'Checklist trước khi lên xe khách',
        'description' => 'Danh sách những thứ cần chuẩn bị trước khi bắt đầu hành trình',
        'image' => 'baiviet2.jpg',
        'category' => 'Chuẩn bị'
    ],
    [
        'slug' => 'meo-san-ve-gia-tot',
        'title' => 'Mẹo săn vé giá tốt cuối tuần',
        'description' => 'Bí quyết đặt vé xe khách với giá ưu đãi nhất',
        'image' => 'baiviet3.jpg',
        'category' => 'Tiết kiệm'
    ],
    [
        'slug' => 'nen-den-ben-truoc-bao-lau',
        'title' => 'Nên đến bến trước bao lâu?',
        'description' => 'Thời gian hợp lý để đến bến xe trước giờ khởi hành',
        'image' => 'baiviet4.jpg',
        'category' => 'Kinh nghiệm'
    ],
    [
        'slug' => 'quy-dinh-hanh-ly',
        'title' => 'Quy định hành lý khi đi xe khách',
        'description' => 'Những điều cần biết về hành lý xách tay và ký gửi',
        'image' => 'baiviet5.jpg',
        'category' => 'Quy định'
    ],
    [
        'slug' => 'goi-y-diem-den-1-2-ngay',
        'title' => 'Gợi ý điểm đến 1–2 ngày',
        'description' => 'Những địa điểm du lịch ngắn ngày lý tưởng bằng xe khách',
        'image' => 'baiviet6.jpg',
        'category' => 'Du lịch'
    ]
];
?>

<style>
/* Articles List Page */
.articles-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 40px 20px 80px;
}

.articles-container {
    max-width: 1200px;
    margin: 0 auto;
}

.articles-header {
    text-align: center;
    margin-bottom: 48px;
}

.articles-header h1 {
    font-size: 36px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.articles-header p {
    font-size: 16px;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
}

.article-list-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.article-list-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

.article-list-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    position: relative;
}

.article-list-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.article-list-card:hover .article-list-image img {
    transform: scale(1.08);
}

.article-category {
    position: absolute;
    top: 16px;
    left: 16px;
    background: rgba(255, 107, 53, 0.95);
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.article-list-content {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.article-list-content h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
    line-height: 1.4;
}

.article-list-content p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    flex: 1;
}

.read-more {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #FF6B35;
    font-weight: 600;
    font-size: 14px;
    margin-top: 16px;
}

.read-more i {
    transition: transform 0.3s ease;
}

.article-list-card:hover .read-more i {
    transform: translateX(4px);
}

/* Mobile */
@media (max-width: 768px) {
    .articles-page {
        padding: 24px 16px 60px;
    }
    
    .articles-header h1 {
        font-size: 28px;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .article-list-image {
        height: 180px;
    }
    
    .article-list-content {
        padding: 20px;
    }
    
    .article-list-content h3 {
        font-size: 18px;
    }
}
</style>

<main class="articles-page">
    <div class="articles-container">
        <!-- Header -->
        <header class="articles-header">
            <h1>📚 Bài viết hay</h1>
            <p>Tổng hợp kinh nghiệm đi xe, mẹo đặt vé và gợi ý điểm đến hữu ích cho chuyến đi của bạn</p>
        </header>
        
        <!-- Articles Grid -->
        <div class="articles-grid">
            <?php foreach ($articles as $article): ?>
            <a href="<?php echo appUrl('user/articles/' . $article['slug'] . '.php'); ?>" class="article-list-card">
                <div class="article-list-image">
                    <img src="<?php echo IMG_URL; ?>/<?php echo $article['image']; ?>" alt="<?php echo e($article['title']); ?>" onerror="this.src='<?php echo IMG_URL; ?>/<?php echo str_replace('.jpg', '.png', $article['image']); ?>'">
                    <span class="article-category"><?php echo e($article['category']); ?></span>
                </div>
                <div class="article-list-content">
                    <h3><?php echo e($article['title']); ?></h3>
                    <p><?php echo e($article['description']); ?></p>
                    <span class="read-more">
                        Đọc tiếp <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

