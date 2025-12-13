<?php
/**
 * User Register Page - FUTA Style
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

if (isLoggedIn()) {
    redirect(appUrl());
}

$pageTitle = 'Đăng ký - BusBooking';

// Include same CSS as login page
$loginPagePath = __DIR__ . '/login.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <?php echo csrfMetaTag(); ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        <?php
        // Include same CSS from login.php
        if (file_exists($loginPagePath)) {
            $content = file_get_contents($loginPagePath);
            preg_match('/<style>(.*?)<\/style>/s', $content, $matches);
            if (!empty($matches[1])) {
                echo $matches[1];
            }
        }
        ?>
    </style>
</head>
<body>
    <!-- Top Strip -->
    <header style="height:44px; background:#1E90FF;"></header>
    
    <!-- Main Header with Logo -->
    <header class="main-header">
        <div class="main-header-container">
            <div class="logo-container">
                <div class="logo-image">🚌 4F Bus Booking</div>
                <div class="logo-subtitle">CHẤT LƯỢNG LÀ DANH DỰ</div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="login-container">
        <!-- Left Side - Illustration -->
        <div class="left-side">
            <h1 class="main-title">
                ĐĂNG KÝ NGAY<br>
                ĐỂ NHẬN ƯU ĐÃI
            </h1>
            <p class="subtitle">Trở thành thành viên và nhận nhiều khuyến mãi hấp dẫn</p>
            
            <div class="illustration">
                <!-- SVG Illustration -->
                <svg viewBox="0 0 500 350" xmlns="http://www.w3.org/2000/svg">
                    <!-- Background circles -->
                    <circle cx="80" cy="60" r="40" fill="#E6F2FF" opacity="0.6"/>
                    <circle cx="420" cy="80" r="60" fill="#E9F3FF" opacity="0.5"/>
                    <circle cx="250" cy="40" r="30" fill="#F0F6FF" opacity="0.6"/>
                    
                    <!-- Bus Body -->
                    <g id="bus">
                        <rect x="80" y="140" width="280" height="140" fill="#1E90FF" rx="15"/>
                        <rect x="80" y="140" width="60" height="140" fill="#4DA3FF" rx="15 0 0 15"/>
                        <rect x="100" y="155" width="70" height="60" fill="#4A5568" rx="8"/>
                        <rect x="190" y="155" width="70" height="60" fill="#4A5568" rx="8"/>
                        <rect x="280" y="155" width="60" height="60" fill="#4A5568" rx="8"/>
                        <rect x="105" y="160" width="30" height="25" fill="rgba(255,255,255,0.4)" rx="4"/>
                        <rect x="195" y="160" width="30" height="25" fill="rgba(255,255,255,0.4)" rx="4"/>
                        <rect x="285" y="160" width="25" height="25" fill="rgba(255,255,255,0.4)" rx="4"/>
                        <circle cx="95" cy="230" r="8" fill="#FFF5D9"/>
                        <circle cx="95" cy="250" r="8" fill="#FFF5D9"/>
                        <rect x="100" y="235" width="240" height="3" fill="#CC5530"/>
                        <rect x="100" y="243" width="240" height="2" fill="#CC5530"/>
                        <g id="wheel1">
                            <circle cx="140" cy="285" r="30" fill="#2D3748"/>
                            <circle cx="140" cy="285" r="18" fill="#4A5568"/>
                            <circle cx="140" cy="285" r="8" fill="#718096"/>
                        </g>
                        <g id="wheel2">
                            <circle cx="310" cy="285" r="30" fill="#2D3748"/>
                            <circle cx="310" cy="285" r="18" fill="#4A5568"/>
                            <circle cx="310" cy="285" r="8" fill="#718096"/>
                        </g>
                    </g>
                    
                    <!-- Driver -->
                    <g id="driver">
                        <circle cx="120" cy="185" r="18" fill="#F6E05E"/>
                        <ellipse cx="120" cy="205" rx="20" ry="25" fill="#1E90FF"/>
                        <rect x="108" y="210" width="10" height="20" fill="#4A90E2" rx="2"/>
                        <rect x="122" y="210" width="10" height="20" fill="#4A90E2" rx="2"/>
                    </g>
                    
                    <!-- Passenger 1 -->
                    <g id="passenger1">
                        <circle cx="220" cy="185" r="16" fill="#F6E05E"/>
                        <ellipse cx="220" cy="203" rx="18" ry="22" fill="#E53E3E"/>
                    </g>
                    
                    <!-- Passenger 2 -->
                    <g id="passenger2">
                        <circle cx="430" cy="205" r="22" fill="#F6E05E"/>
                        <path d="M 420 205 Q 415 195 420 190 L 440 190 Q 445 195 440 205 Z" fill="#2D3748"/>
                        <ellipse cx="430" cy="240" rx="25" ry="35" fill="#4A90E2"/>
                        <rect x="415" y="265" width="12" height="30" fill="#2D3748" rx="6"/>
                        <rect x="433" y="265" width="12" height="30" fill="#2D3748" rx="6"/>
                        <rect x="455" y="235" width="28" height="35" fill="#1E90FF" rx="4"/>
                        <rect x="460" y="227" width="18" height="12" fill="#1E90FF" rx="3"/>
                    </g>
                    
                    <!-- Ground -->
                    <rect x="0" y="315" width="500" height="3" fill="#CBD5E0"/>
                    <path d="M 50 320 Q 100 310 150 320" stroke="#E2E8F0" stroke-width="2" fill="none"/>
                    <path d="M 350 320 Q 400 310 450 320" stroke="#E2E8F0" stroke-width="2" fill="none"/>
                </svg>
            </div>
        </div>
        
        <!-- Right Side - Register Form -->
        <div class="login-form-container">
            <!-- Form Header -->
            <div class="form-header">
                <h2 class="form-title">Đăng ký tài khoản</h2>
            </div>
            
            <!-- Tabs -->
            <div class="form-tabs">
                <a href="<?php echo appUrl('user/auth/login.php'); ?>" class="tab-btn">
                    <i class="fas fa-phone"></i> ĐĂNG NHẬP
                </a>
                <button class="tab-btn active">
                    <i class="fas fa-user-plus"></i> ĐĂNG KÝ
                </button>
            </div>
            
            <!-- Register Form -->
            <form action="<?php echo appUrl('api/auth/register.php'); ?>" method="POST" id="registerForm">
                <?php echo csrfField(); ?>
                
                <!-- Name -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-user"></i>
                        <input type="text" name="fullname" class="form-input" placeholder="Họ và tên" required autocomplete="name">
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-phone"></i>
                        <input type="tel" name="phone" class="form-input" placeholder="Số điện thoại" required autocomplete="tel">
                    </div>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-envelope"></i>
                        <input type="email" name="email" class="form-input" placeholder="Email" required autocomplete="email">
                    </div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-lock"></i>
                        <input type="password" name="password" class="form-input" id="password" placeholder="Mật khẩu" required autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-lock"></i>
                        <input type="password" name="confirm_password" class="form-input" id="confirm_password" placeholder="Xác nhận mật khẩu" required autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Register Button -->
                <button type="submit" class="btn-login">
                    Đăng ký
                </button>
                
                <!-- Login Link -->
                <div class="forgot-password">
                    Đã có tài khoản? <a href="<?php echo appUrl('user/auth/login.php'); ?>">Đăng nhập ngay</a>
                </div>
            </form>
            
            <!-- Footer Text -->
            <div class="footer-text">
                <h3>Kết nối 4F Bus Booking</h3>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-login');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Đăng ký thành công! Đang chuyển đến trang đăng nhập...');
                    window.location.href = '<?php echo appUrl('user/auth/login.php'); ?>';
                } else {
                    alert(result.error || 'Đăng ký thất bại');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Đăng ký';
                }
            } catch (error) {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Đăng ký';
            }
        });
    </script>
</body>
</html>
