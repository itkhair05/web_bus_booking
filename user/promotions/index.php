<?php
/**
 * Trang Ưu đãi nổi bật
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Ưu đãi nổi bật - 4F Bus Booking';
$pageDescription = 'Tổng hợp các mã giảm giá và ưu đãi hấp dẫn khi đặt vé xe khách';

include '../../includes/header_user.php';

// Danh sách ưu đãi
$promotions = [
    [
        'id' => 1,
        'title' => 'Vé Lễ/Tết – Mở bán sớm',
        'subtitle' => 'Đặt sớm giá tốt, chỗ đẹp',
        'description' => 'Mở bán vé Tết Nguyên Đán 2025 sớm! Đặt ngay để có giá tốt nhất và chọn được chỗ ngồi ưng ý.',
        'code' => 'TET2025',
        'discount' => 'Giảm đến 15%',
        'discount_value' => '15%',
        'image' => 'uudai1.jpg',
        'color' => '#dc2626',
        'gradient' => 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
        'valid_until' => '28/01/2025',
        'conditions' => [
            'Áp dụng cho tất cả tuyến đường',
            'Đặt vé từ 15/12/2024 - 28/01/2025',
            'Ngày đi: 20/01 - 15/02/2025',
            'Giảm tối đa 200.000đ/vé'
        ],
        'icon' => '🧧'
    ],
    [
        'id' => 2,
        'title' => 'Chớp deal 2 giờ',
        'subtitle' => 'Flash Sale mỗi ngày',
        'description' => 'Deal sốc xuất hiện bất ngờ! Giảm đến 30% chỉ trong 2 giờ. Theo dõi thông báo để không bỏ lỡ.',
        'code' => 'FLASH30',
        'discount' => 'Giảm đến 30%',
        'discount_value' => '30%',
        'image' => 'uudai2.jpg',
        'color' => '#ea580c',
        'gradient' => 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Áp dụng ngẫu nhiên trong ngày',
            'Thời gian: 2 giờ kể từ khi kích hoạt',
            'Số lượng có hạn',
            'Giảm tối đa 150.000đ/vé'
        ],
        'icon' => '⚡'
    ],
    [
        'id' => 3,
        'title' => 'Thứ 6 vui vẻ',
        'subtitle' => 'Happy Friday mỗi tuần',
        'description' => 'Mỗi thứ 6, nhập mã 4FRIDAY để được giảm 20% cho tất cả chuyến đi. Đặt vé cuối tuần thật tiết kiệm!',
        'code' => '4FRIDAY',
        'discount' => 'Giảm 20%',
        'discount_value' => '20%',
        'image' => 'uudai3.jpg',
        'color' => '#2563eb',
        'gradient' => 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Áp dụng mỗi thứ 6 hàng tuần',
            'Thanh toán online để được áp dụng',
            'Áp dụng tất cả tuyến đường',
            'Giảm tối đa 100.000đ/vé'
        ],
        'icon' => '🎉'
    ],
    [
        'id' => 4,
        'title' => 'Ưu đãi sinh viên',
        'subtitle' => 'Dành riêng cho sinh viên',
        'description' => 'Sinh viên đặt vé được giảm ngay 10%! Chỉ cần xác thực email .edu hoặc thẻ sinh viên.',
        'code' => 'SINHVIEN10',
        'discount' => 'Giảm 10%',
        'discount_value' => '10%',
        'image' => 'uudai4.jpg',
        'color' => '#7c3aed',
        'gradient' => 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Dành cho sinh viên có thẻ sinh viên còn hạn',
            'Mỗi tài khoản áp dụng 2 lần/tháng',
            'Áp dụng tất cả tuyến đường',
            'Giảm tối đa 50.000đ/vé'
        ],
        'icon' => '🎓'
    ],
    [
        'id' => 5,
        'title' => 'Tuyến hot – Giảm đến 25%',
        'subtitle' => 'Ưu đãi tuyến phổ biến',
        'description' => 'Các tuyến đường hot nhất được giảm giá đặc biệt. Sài Gòn - Đà Lạt, Hà Nội - Sapa và nhiều tuyến khác!',
        'code' => 'HOTROUTE',
        'discount' => 'Giảm đến 25%',
        'discount_value' => '25%',
        'image' => 'uudai5.jpg',
        'color' => '#059669',
        'gradient' => 'linear-gradient(135deg, #059669 0%, #047857 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Áp dụng tuyến: SG-Đà Lạt, HN-Sapa, SG-Vũng Tàu...',
            'Đặt trước ít nhất 3 ngày',
            'Thanh toán online',
            'Giảm tối đa 120.000đ/vé'
        ],
        'icon' => '🔥'
    ],
    [
        'id' => 6,
        'title' => 'Đặt sớm – Giá tốt hơn',
        'subtitle' => 'Early bird discount',
        'description' => 'Đặt vé trước 7 ngày để nhận ưu đãi giảm giá đặc biệt. Càng đặt sớm, giá càng tốt!',
        'code' => 'EARLYBIRD',
        'discount' => 'Giảm 15%',
        'discount_value' => '15%',
        'image' => 'uudai6.jpg',
        'color' => '#0891b2',
        'gradient' => 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Đặt trước ít nhất 7 ngày so với ngày đi',
            'Áp dụng tất cả tuyến đường',
            'Không áp dụng dịp Lễ/Tết',
            'Giảm tối đa 80.000đ/vé'
        ],
        'icon' => '🐦'
    ],
    [
        'id' => 7,
        'title' => 'Giờ vàng mỗi ngày',
        'subtitle' => 'Golden Hour 10h-12h',
        'description' => 'Mỗi ngày từ 10h-12h trưa, đặt vé với giá ưu đãi đặc biệt. Deal đẹp chờ bạn săn!',
        'code' => 'GOLDENHOUR',
        'discount' => 'Giảm 18%',
        'discount_value' => '18%',
        'image' => 'uudai7.jpg',
        'color' => '#ca8a04',
        'gradient' => 'linear-gradient(135deg, #ca8a04 0%, #a16207 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Áp dụng từ 10:00 - 12:00 mỗi ngày',
            'Thanh toán trong khung giờ vàng',
            'Áp dụng tất cả tuyến đường',
            'Giảm tối đa 90.000đ/vé'
        ],
        'icon' => '⏰'
    ],
    [
        'id' => 8,
        'title' => 'Combo khứ hồi',
        'subtitle' => 'Đặt 2 chiều tiết kiệm hơn',
        'description' => 'Đặt vé khứ hồi cùng lúc để được giảm thêm. Tiết kiệm hơn và không lo hết vé chiều về!',
        'code' => 'ROUNDTRIP',
        'discount' => 'Giảm thêm 10%',
        'discount_value' => '10%',
        'image' => 'uudai8.jpg',
        'color' => '#db2777',
        'gradient' => 'linear-gradient(135deg, #db2777 0%, #be185d 100%)',
        'valid_until' => '31/12/2025',
        'conditions' => [
            'Đặt vé đi và về cùng lúc',
            'Áp dụng tất cả tuyến đường',
            'Khoảng cách 2 chiều tối thiểu 1 ngày',
            'Giảm tối đa 100.000đ/đơn'
        ],
        'icon' => '🔄'
    ]
];
?>

<style>
/* Promotions Page Styles */
.promotions-page {
    background: linear-gradient(180deg, #fef3c7 0%, #fff 100%);
    min-height: 100vh;
    padding: 40px 20px 80px;
}

.promotions-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header */
.promotions-header {
    text-align: center;
    margin-bottom: 48px;
}

.promotions-header h1 {
    font-size: 36px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.promotions-header p {
    font-size: 16px;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

/* Highlight Banner */
.highlight-banner {
    background: linear-gradient(135deg, #FF6B35 0%, #f97316 100%);
    border-radius: 16px;
    padding: 24px 32px;
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
    box-shadow: 0 8px 30px rgba(255, 107, 53, 0.3);
}

.highlight-banner .banner-content h3 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
}

.highlight-banner .banner-content p {
    opacity: 0.95;
    font-size: 15px;
}

.highlight-banner .banner-code {
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.highlight-banner .banner-code:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Promotions Grid */
.promotions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 28px;
}

/* Promotion Card */
.promo-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.promo-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.promo-card-header {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.promo-card-header img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.promo-card:hover .promo-card-header img {
    transform: scale(1.08);
}

.promo-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.promo-icon {
    position: absolute;
    bottom: 16px;
    left: 16px;
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.promo-card-body {
    padding: 24px;
}

.promo-card-body h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.promo-card-body .subtitle {
    font-size: 14px;
    color: #888;
    margin-bottom: 12px;
}

.promo-card-body .description {
    font-size: 14px;
    color: #555;
    line-height: 1.6;
    margin-bottom: 16px;
}

/* Code Box */
.code-box {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.code-display {
    flex: 1;
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 12px 16px;
    font-family: 'Courier New', monospace;
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    text-align: center;
    letter-spacing: 2px;
}

.copy-btn {
    background: #FF6B35;
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.copy-btn:hover {
    background: #e55a2b;
    transform: scale(1.05);
}

.copy-btn.copied {
    background: #10b981;
}

/* Conditions */
.promo-conditions {
    background: #f8fafc;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
}

.promo-conditions h4 {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.promo-conditions ul {
    margin: 0;
    padding-left: 18px;
    font-size: 13px;
    color: #555;
}

.promo-conditions li {
    margin-bottom: 4px;
}

/* Footer */
.promo-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.valid-date {
    font-size: 13px;
    color: #888;
    display: flex;
    align-items: center;
    gap: 6px;
}

.valid-date i {
    color: #FF6B35;
}

.use-btn {
    background: linear-gradient(135deg, #FF6B35 0%, #f97316 100%);
    color: #fff;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.use-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
    color: #fff;
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1a1a2e;
    color: #fff;
    padding: 16px 28px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    opacity: 0;
    transition: all 0.4s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.toast i {
    color: #10b981;
    font-size: 18px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .promotions-page {
        padding: 24px 16px 60px;
    }
    
    .promotions-header h1 {
        font-size: 28px;
    }
    
    .highlight-banner {
        flex-direction: column;
        text-align: center;
        gap: 16px;
        padding: 20px;
    }
    
    .promotions-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .promo-card-header {
        height: 160px;
    }
    
    .promo-card-body {
        padding: 20px;
    }
    
    .code-box {
        flex-direction: column;
    }
    
    .code-display {
        width: 100%;
    }
    
    .copy-btn {
        width: 100%;
        justify-content: center;
    }
    
    .promo-card-footer {
        flex-direction: column;
        gap: 12px;
    }
    
    .use-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<main class="promotions-page">
    <div class="promotions-container">
        <!-- Header -->
        <header class="promotions-header">
            <h1>🎁 Ưu đãi nổi bật</h1>
            <p>Săn mã giảm giá và đặt vé xe khách với giá tốt nhất tại 4F Bus Booking</p>
        </header>
        
        <!-- Highlight Banner -->
        <div class="highlight-banner">
            <div class="banner-content">
                <h3>🔥 Mã HOT nhất tuần: 4FRIDAY</h3>
                <p>Giảm ngay 20% mỗi thứ 6 - Áp dụng tất cả tuyến đường!</p>
            </div>
            <div class="banner-code" onclick="copyCode('4FRIDAY', this)">
                <span>4FRIDAY</span>
                <i class="fas fa-copy"></i>
            </div>
        </div>
        
        <!-- Promotions Grid -->
        <div class="promotions-grid">
            <?php foreach ($promotions as $promo): ?>
            <div class="promo-card">
                <div class="promo-card-header">
                    <img src="<?php echo IMG_URL; ?>/<?php echo $promo['image']; ?>" alt="<?php echo e($promo['title']); ?>" onerror="this.src='<?php echo IMG_URL; ?>/<?php echo str_replace('.jpg', '.png', $promo['image']); ?>'">
                    <div class="promo-badge" style="background: <?php echo $promo['gradient']; ?>">
                        <?php echo $promo['discount']; ?>
                    </div>
                    <div class="promo-icon"><?php echo $promo['icon']; ?></div>
                </div>
                
                <div class="promo-card-body">
                    <h3><?php echo e($promo['title']); ?></h3>
                    <div class="subtitle"><?php echo e($promo['subtitle']); ?></div>
                    <p class="description"><?php echo e($promo['description']); ?></p>
                    
                    <div class="code-box">
                        <div class="code-display"><?php echo $promo['code']; ?></div>
                        <button class="copy-btn" onclick="copyCode('<?php echo $promo['code']; ?>', this)">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    
                    <div class="promo-conditions">
                        <h4><i class="fas fa-info-circle"></i> Điều kiện áp dụng</h4>
                        <ul>
                            <?php foreach ($promo['conditions'] as $condition): ?>
                            <li><?php echo e($condition); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="promo-card-footer">
                        <div class="valid-date">
                            <i class="fas fa-calendar-alt"></i>
                            HSD: <?php echo $promo['valid_until']; ?>
                        </div>
                        <a href="<?php echo appUrl(); ?>" class="use-btn">
                            Dùng ngay <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Toast Notification -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span>Đã copy mã giảm giá!</span>
</div>

<script>
function copyCode(code, button) {
    // Copy to clipboard
    navigator.clipboard.writeText(code).then(() => {
        // Show success state on button
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Đã copy';
        button.classList.add('copied');
        
        // Show toast
        const toast = document.getElementById('toast');
        toast.querySelector('span').textContent = `Đã copy mã: ${code}`;
        toast.classList.add('show');
        
        // Reset after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('copied');
            toast.classList.remove('show');
        }, 2000);
    }).catch(err => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        // Show toast
        const toast = document.getElementById('toast');
        toast.querySelector('span').textContent = `Đã copy mã: ${code}`;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2000);
    });
}
</script>

<?php include '../../includes/footer_user.php'; ?>

