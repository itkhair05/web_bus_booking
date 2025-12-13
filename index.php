<?php
/**
 * Homepage - Trang chủ giống VeXeRe
 * Tìm kiếm chuyến xe bus
 */

// Load dependencies
require_once 'config/session.php';
require_once 'config/constants.php';
$conn = require_once 'config/db.php';
require_once 'core/helpers.php';
require_once 'core/auth.php';
require_once 'core/csrf.php';

// Set page variables
$pageTitle = 'Đặt vé xe khách online - BusBooking';
$currentPage = 'home';

// Include header
include 'includes/header_user.php';
?>

<!-- Hero Section with Search Box (VeXeRe Style) -->
<section class="hero-section" style="background-image: linear-gradient(110deg, rgba(59, 130, 246, 0.5) 0%, rgba(96, 165, 250, 0.5) 100%), url('images/e059b4cd-6a9d-4005-9f81-4efb76ce467b.png');">
    
    <!-- Flash Sale Banner trong Hero -->
    <div class="flash-sale-content">
        <div class="flash-sale-text">
            <span class="flash-badge">THỨ 3 HÀNG TUẦN</span>
            <h2>Flash Sale Tưng Bừng</h2>
            <div class="discount-badge">
                <span>Giảm Đến</span>
                <span class="discount-number">50<sup>%</sup></span>
            </div>
        </div>
        <div class="flash-sale-subtext">
            VeXeRe - Cam kết hoàn 150% nếu nhà xe không cung cấp dịch vụ vận chuyển (*) 🎉
        </div>
    </div>

    <!-- Search Box Card - Nổi lên trên nền xanh -->
    <div class="search-box-card">
        <div class="search-box-header">
            <i class="fas fa-bus"></i>
            <h3>Tìm chuyến xe khách</h3>
        </div>
        
        <form action="<?php echo appUrl('user/search/results.php'); ?>" method="GET" id="search-form">
            <!-- From Location -->
            <div class="form-group">
                <label for="from_location">
                    <i class="fas fa-map-marker-alt"></i> ĐIỂM ĐI
                </label>
                <input 
                    type="text" 
                    class="vexere-input" 
                    id="from_location" 
                    name="from" 
                    placeholder="Chọn điểm đi"
                    required
                    autocomplete="off"
                    value="<?php echo $_GET['from'] ?? ''; ?>"
                >
            </div>
            
            <!-- Swap Button -->
            <button type="button" class="btn-swap-locations" onclick="swapLocations()" title="Đổi điểm">
                <i class="fas fa-exchange-alt"></i>
            </button>
            
            <!-- To Location -->
            <div class="form-group">
                <label for="to_location">
                    <i class="fas fa-map-marker-alt"></i> ĐIỂM ĐẾN
                </label>
                <input 
                    type="text" 
                    class="vexere-input" 
                    id="to_location" 
                    name="to" 
                    placeholder="Chọn điểm đến"
                    required
                    autocomplete="off"
                    value="<?php echo $_GET['to'] ?? ''; ?>"
                >
            </div>
            
            <!-- Departure Date -->
            <div class="form-group">
                <label for="departure_date">
                    <i class="fas fa-calendar"></i> NGÀY ĐI
                </label>
                <input 
                    type="date" 
                    class="vexere-input" 
                    id="departure_date" 
                    name="date" 
                    required
                    value="<?php echo $_GET['date'] ?? date('Y-m-d'); ?>"
                >
            </div>
            
            <!-- Search Button (Vàng VeXeRe) -->
            <button type="submit" class="btn-tim-chuyen">
                <i class="fas fa-search"></i>
                Tìm chuyến
            </button>
        </form>
    </div>
</section>

<style>
/* Flash Sale Content trong Hero */
.flash-sale-content {
    position: relative;
    z-index: 1;
    text-align: center;
    margin-bottom: 30px;
    max-width: 1000px;
    width: 100%;
}

.flash-sale-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.flash-badge {
    background: var(--white);
    color: var(--primary-color);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 14px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.flash-sale-text h2 {
    color: var(--white);
    font-size: 36px;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.discount-badge {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    color: var(--white);
    padding: 15px 30px;
    border-radius: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.discount-badge span:first-child {
    font-size: 14px;
    font-weight: 600;
}

.discount-number {
    font-size: 48px !important;
    font-weight: 800 !important;
    line-height: 1;
}

.discount-number sup {
    font-size: 24px;
}

.flash-sale-subtext {
    color: var(--white);
    font-size: 16px;
    font-weight: 500;
}

/* Hero Section (VeXeRe Style - Nền xanh với card trắng nổi) */
.hero-section {
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
    width: 100%;
    min-height: 500px;
    
    /* Flexbox để căn giữa - theo chiều dọc */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
    position: relative;
    gap: 20px;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('<?php echo IMG_URL; ?>/bus-pattern.png') repeat;
    opacity: 0;
    pointer-events: none;
}

/* Search Box Card - Thẻ trắng nổi lên */
.search-box-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    padding: 30px;
    width: 100%;
    max-width: 1000px;
    position: relative;
    z-index: 1;
}

/* Search Box Header */
.search-box-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 30px;
}

.search-box-header i {
    font-size: 28px;
    color: var(--primary-color);
}

.search-box-header h3 {
    font-size: 22px;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0;
}

/* Form Layout - 1 HÀNG NGANG (VeXeRe Style) */
#search-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px;
}

/* Form Group - Style VeXeRe (Gạch chân) */
.form-group {
    flex: 1;
    min-width: 180px;
    border-bottom: 2px solid #f0f0f0;
    padding: 8px 0;
    position: relative;
    transition: var(--transition);
}

.form-group:hover,
.form-group:focus-within {
    border-bottom-color: var(--primary-color);
}

.form-group label {
    font-size: 11px;
    font-weight: 600;
    color: #888;
    display: flex;
    align-items: center;
    gap: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.form-group label i {
    font-size: 13px;
    color: var(--primary-color);
}

/* VeXeRe Input Style (Không border, chỉ gạch chân) */
.vexere-input {
    border: none;
    outline: none;
    width: 100%;
    font-size: 16px;
    font-weight: 600;
    padding: 5px 0;
    background: transparent;
    color: var(--gray-800);
}

.vexere-input::placeholder {
    color: #ccc;
    font-weight: 400;
}

.vexere-input:focus {
    color: var(--gray-900);
}

/* Swap Button - VeXeRe Style */
.btn-swap-locations {
    background: var(--white);
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-bottom: 10px;
}

.btn-swap-locations:hover {
    background: var(--primary-color);
    color: var(--white);
    transform: rotate(180deg);
}

/* Button Tìm Chuyến - VÀNG VEXERE */
.btn-tim-chuyen {
    background: #FFC107 !important;
    color: #000 !important;
    border: none;
    font-weight: 700;
    font-size: 16px;
    padding: 14px 32px;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.btn-tim-chuyen:hover {
    background: #FFB300 !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 193, 7, 0.4);
}

.btn-tim-chuyen i {
    font-size: 18px;
}

/* Platform Features */
.platform-features {
    background: #f8f9fa;
    padding: 60px 20px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    background: #fff;
    text-align: left;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.feature-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-4px);
}

.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 28px;
    color: #fff;
}

.icon-bus {
    background: linear-gradient(135deg, #4A90E2, #357ABD);
}

.icon-ticket {
    background: linear-gradient(135deg, #FFA726, #FB8C00);
}

.icon-check {
    background: linear-gradient(135deg, #66BB6A, #43A047);
}

.icon-gift {
    background: linear-gradient(135deg, #EF5350, #E53935);
}

.feature-card h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #212529;
}

.feature-card p {
    font-size: 14px;
    color: #6c757d;
    line-height: 1.6;
    margin: 0;
}

/* Autocomplete dropdown */
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-top: none;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.autocomplete-item {
    padding: 12px 16px;
    cursor: pointer;
    transition: var(--transition);
    color: #111827; /* always dark text for suggestions */
    background: #fff;
}

.autocomplete-item:hover {
    background: var(--gray-100);
    color: var(--primary-color);
}

/* ========== RESPONSIVE (MOBILE) ========== */
@media (max-width: 768px) {
    /* Hero Section */
    .hero-section {
        min-height: auto;
        padding: 30px 15px;
    }
    
    /* Flash Sale trong Hero */
    .flash-sale-text h2 {
        font-size: 24px;
    }
    
    .discount-number {
        font-size: 36px !important;
    }
    
    .flash-sale-subtext {
        font-size: 14px;
    }
    
    /* Search Box Card */
    .search-box-card {
        padding: 20px;
    }
    
    .search-box-header h3 {
        font-size: 18px;
    }
    
    /* Form chuyển từ ngang sang dọc */
    #search-form {
        flex-direction: column;
        gap: 20px;
    }
    
    .form-group {
        width: 100%;
        min-width: 100%;
    }
    
    .btn-swap-locations {
        align-self: center;
        margin: 0;
    }
    
    .btn-tim-chuyen {
        width: 100%;
        justify-content: center;
    }
    
    .swap-location-container {
        display: none;
    }
    
    .hero-title {
        font-size: 28px;
    }
    
    .hero-subtitle {
        font-size: 16px;
    }
}
</style>

<!-- Popular Routes Section -->
<section class="popular-routes">
    <div class="container">
        <h2 class="section-title">Tuyến đường phổ biến</h2>
        <p class="section-subtitle">Đặt vé nhanh chóng cho các tuyến đường hot nhất</p>
        
        <div class="routes-slider-wrapper">
            <!-- Prev Button -->
            <button class="slider-btn slider-btn-prev" onclick="slideRoutes(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <!-- Routes Slider -->
            <div class="routes-slider" id="routesSlider">
                <!-- Route 1: HCM - Đà Lạt -->
                <a href="<?php echo appUrl('user/search/results.php?from=Sài Gòn&to=Đà Lạt&date=' . date('Y-m-d', strtotime('+1 day'))); ?>" class="route-card" style="background-image: url('<?php echo IMG_URL; ?>/đà lạt.jpg');">
                    <div class="route-content">
                        <div class="route-info">
                            <h3>Sài Gòn - Đà Lạt</h3>
                            <p>Từ 200.000đ</p>
                        </div>
                    </div>
                </a>
                
                <!-- Route 2: Quảng Ngãi - Đà Nẵng -->
                <a href="<?php echo appUrl('user/search/results.php?from=Quảng Ngãi&to=Đà Nẵng&date=' . date('Y-m-d', strtotime('+1 day'))); ?>" class="route-card" style="background-image: url('<?php echo IMG_URL; ?>/đà nẵng.jpg');">
                    <div class="route-content">
                        <div class="route-info">
                            <h3>Quảng Ngãi - Đà Nẵng</h3>
                            <p>Từ 90.000đ</p>
                        </div>
                    </div>
                </a>
                
                <!-- Route 3: Quảng Ngãi - HCM -->
                <a href="<?php echo appUrl('user/search/results.php?from=Quảng Ngãi&to=Sài Gòn&date=' . date('Y-m-d', strtotime('+1 day'))); ?>" class="route-card" style="background-image: url('<?php echo IMG_URL; ?>/sài gòn.jpg');">
                    <div class="route-content">
                        <div class="route-info">
                            <h3>Quảng Ngãi - Sài Gòn</h3>
                            <p>Từ 160.000đ</p>
                        </div>
                    </div>
                </a>
                
                <!-- Route 4: HCM - Vũng Tàu -->
                <a href="<?php echo appUrl('user/search/results.php?from=Sài Gòn&to=Vũng Tàu&date=' . date('Y-m-d', strtotime('+1 day'))); ?>" class="route-card" style="background-image: url('<?php echo IMG_URL; ?>/vũng tàu.jpg');">
                    <div class="route-content">
                        <div class="route-info">
                            <h3>Sài Gòn - Vũng Tàu</h3>
                            <p>Từ 180.000đ</p>
                        </div>
                    </div>
                </a>
                
                <!-- Route 5: Hà Nội - Sapa -->
                <a href="<?php echo appUrl('user/search/results.php?from=Hà Nội&to=Sapa&date=' . date('Y-m-d', strtotime('+1 day'))); ?>" class="route-card" style="background-image: url('<?php echo IMG_URL; ?>/sapa.jpg');">
                    <div class="route-content">
                        <div class="route-info">
                            <h3>Hà Nội - Sapa</h3>
                            <p>Từ 300.000đ</p>
                        </div>
                    </div>
                </a>
                
                <!-- Route 6: Hà Nội - Quảng Ninh -->
                <a href="<?php echo appUrl('user/search/results.php?from=Hà Nội&to=Quảng Ninh&date=' . date('Y-m-d', strtotime('+1 day'))); ?>" class="route-card" style="background-image: url('<?php echo IMG_URL; ?>/vịnh hạ long.jpg');">
                    <div class="route-content">
                        <div class="route-info">
                            <h3>Hà Nội - Quảng Ninh</h3>
                            <p>Từ 250.000đ</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Next Button -->
            <button class="slider-btn slider-btn-next" onclick="slideRoutes(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<script>
let currentPage = 0;
const slider = document.getElementById('routesSlider');
const cards = slider.querySelectorAll('.route-card');
const totalCards = cards.length;

// Hàm lấy số card hiển thị theo màn hình
function getCardsPerView() {
    if (window.innerWidth <= 768) return 1;
    if (window.innerWidth <= 1024) return 2;
    return 3;
}

function slideRoutes(direction) {
    const cardsPerView = getCardsPerView();
    const totalPages = Math.ceil(totalCards / cardsPerView);
    
    // Cập nhật trang (mỗi trang = cardsPerView cards)
    currentPage += direction;
    
    // Giới hạn trang
    if (currentPage < 0) currentPage = 0;
    if (currentPage >= totalPages) currentPage = totalPages - 1;
    
    // Tính toán translate - trượt theo nhóm cards
    const cardWidth = cards[0].offsetWidth;
    const gap = 20;
    const slideIndex = currentPage * cardsPerView;
    const translateX = -slideIndex * (cardWidth + gap);
    
    slider.style.transform = `translateX(${translateX}px)`;
}

// Reset về slide đầu khi resize
window.addEventListener('resize', function() {
    currentPage = 0;
    slider.style.transform = 'translateX(0)';
});
</script>

<!-- Platform Features -->
<section class="platform-features">
    <div class="container">
        <h2 class="section-title">Nền tảng kết nối người dùng và nhà xe</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon icon-bus">
                    <i class="fas fa-bus"></i>
                </div>
                <h3>2000+ nhà xe chất lượng cao</h3>
                <p>5000+ tuyến đường trên toàn quốc, chủ động và đa dạng lựa chọn.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon icon-ticket">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3>Đặt vé dễ dàng</h3>
                <p>Đặt vé chỉ với 60s. Chọn xe yêu thích cực nhanh và thuận tiện.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon icon-check">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Chắc chắn có chỗ</h3>
                <p>Hoàn ngay 150% nếu nhà xe không cung cấp dịch vụ vận chuyển, mang đến hành trình trọn vẹn.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon icon-gift">
                    <i class="fas fa-gift"></i>
                </div>
                <h3>Nhiều ưu đãi</h3>
                <p>Hàng ngàn ưu đãi cực chất độc quyền tại Vexere.</p>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links Section -->
<section class="quick-links-section">
    <div class="container">
        <h2 class="section-title">Truy cập nhanh</h2>
        
        <div class="quick-links-grid">
            <a href="<?php echo appUrl(isLoggedIn() ? 'user/tickets/my_tickets.php' : 'user/auth/login.php'); ?>" class="quick-link-card">
                <div class="quick-link-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3>Vé của tôi</h3>
                <p>Xem và quản lý vé đã đặt</p>
            </a>
            
            <a href="<?php echo appUrl(isLoggedIn() ? 'user/profile/index.php' : 'user/auth/login.php'); ?>" class="quick-link-card">
                <div class="quick-link-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3>Tài khoản</h3>
                <p>Quản lý thông tin cá nhân</p>
            </a>
            
            <a href="<?php echo appUrl('partner/auth/login.php'); ?>" class="quick-link-card">
                <div class="quick-link-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Đối tác</h3>
                <p>Đăng ký trở thành đối tác</p>
            </a>
            
            <a href="#" class="quick-link-card">
                <div class="quick-link-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>Hỗ trợ</h3>
                <p>Câu hỏi thường gặp & liên hệ</p>
            </a>
        </div>
    </div>
</section>

<style>
/* Popular Routes */
.popular-routes {
    padding: 60px 20px;
    background: #fff;
}

.section-title {
    text-align: center;
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin-bottom: 12px;
}

.section-subtitle {
    text-align: center;
    font-size: 16px;
    color: #666;
    margin-bottom: 40px;
}

/* Routes Slider Wrapper */
.routes-slider-wrapper {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 60px;
    overflow: hidden;
}

/* Routes Slider */
.routes-slider {
    display: flex;
    gap: 20px;
    transition: transform 0.5s ease;
}

.routes-slider .route-card {
    flex: 0 0 calc(33.333% - 14px);
    min-width: calc(33.333% - 14px);
}

/* Slider Navigation Buttons */
.slider-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.slider-btn:hover {
    background: #fff;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    transform: translateY(-50%) scale(1.1);
}

.slider-btn-prev {
    left: 0;
}

.slider-btn-next {
    right: 0;
}

.slider-btn i {
    font-size: 18px;
    color: #333;
}

.route-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding: 20px;
    border-radius: 16px;
    min-height: 180px;
    display: flex;
    align-items: flex-end;
    text-decoration: none;
    color: #fff;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
}

.route-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.2) 50%, transparent 100%);
    z-index: 1;
}

.route-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
}

.route-content {
    width: 100%;
    position: relative;
    z-index: 2;
}

.route-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.route-info h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
    text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5);
    line-height: 1.3;
}

.route-info p {
    font-size: 15px;
    font-weight: 600;
    opacity: 1;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
    margin: 0;
}

.route-arrow {
    margin-left: auto;
    font-size: 20px;
}

/* Quick Links Section */
.quick-links-section {
    padding: 60px 20px;
    background: #fff;
}

.quick-links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
}

.quick-link-card {
    background: #fff;
    border: 2px solid #E5E7EB;
    padding: 28px;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
}

.quick-link-card:hover {
    border-color: #FF6B35;
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(255, 107, 53, 0.15);
}

.quick-link-icon {
    width: 60px;
    height: 60px;
    background: #FFF5EB;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    color: #FF6B35;
}

.quick-link-card h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.quick-link-card p {
    font-size: 14px;
    color: #666;
}

/* Tablet */
@media (max-width: 1024px) and (min-width: 769px) {
    .routes-slider .route-card {
        flex: 0 0 calc(50% - 10px);
        min-width: calc(50% - 10px);
    }
}

/* Mobile */
@media (max-width: 768px) {
    .section-title {
        font-size: 24px;
    }
    
    .popular-routes,
    .quick-links-section {
        padding: 40px 20px;
    }
    
    /* Routes Slider Mobile */
    .routes-slider-wrapper {
        padding: 0 50px;
    }
    
    .routes-slider .route-card {
        flex: 0 0 100%;
        min-width: 100%;
    }
    
    .slider-btn {
        width: 40px;
        height: 40px;
    }
    
    .slider-btn i {
        font-size: 16px;
    }
}
</style>

<?php
// Include footer
include 'includes/footer_user.php';
?>

