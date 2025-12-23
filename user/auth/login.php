<?php

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Get redirect URL if any
$redirectUrl = $_GET['redirect'] ?? '';
// Sanitize redirect URL - only allow relative URLs within the site
if (!empty($redirectUrl) && strpos($redirectUrl, '/') === 0) {
    // Store in session for after login
    $_SESSION['login_redirect'] = $redirectUrl;
} else {
    $redirectUrl = '';
}

// If already logged in, redirect appropriately
if (isLoggedIn()) {
    if (!empty($_SESSION['login_redirect'])) {
        $redirect = $_SESSION['login_redirect'];
        unset($_SESSION['login_redirect']);
        redirect($redirect);
    } else {
        redirect(appUrl());
    }
}

// Set page variables
$pageTitle = 'Đăng nhập - BusBooking';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CSRF Token -->
    <?php echo csrfMetaTag(); ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #1E90FF;
            --primary-dark: #1873CC;
            --primary-light: #4DA3FF;
            --primary-soft: #E6F2FF;
            --text-dark: #0F2857;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
        }
        
        /* Top Header */
        .top-header {
            background: var(--primary-dark);
            padding: 10px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .top-header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-header-left {
            display: flex;
            gap: 20px;
            align-items: center;
            color: #fff;
            font-size: 14px;
        }
        
        .language-selector {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .app-download {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .login-register-btn {
            background: #fff;
            color: var(--primary-dark);
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .login-register-btn:hover {
            background: #eef5ff;
            transform: translateY(-2px);
        }
        
        /* Main Header with Logo */
        .main-header {
            background: #fff;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .main-header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-container {
            text-align: center;
        }
        
        .logo-image {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: 1px;
        }
        
        .logo-subtitle {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            margin-top: 2px;
        }
        
        /* Main Container */
        .login-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            min-height: 500px;
        }
        
        /* Left Side - Illustration */
        .left-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, rgba(30,144,255,0.08) 0%, rgba(24,115,204,0.05) 100%);
        }
        
        .main-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
            line-height: 1.2;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }
        
        .subtitle {
            font-size: 16px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 30px;
        }
        
        .illustration {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }
        
        .illustration img {
            width: 100%;
            height: auto;
        }
        
        /* Right Side - Login Form */
        .login-form-container {
            flex: 1;
            background: #fff;
            padding: 40px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 26px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #E5E7EB;
            margin-bottom: 30px;
            justify-content: center;
        }
        
        .tab-btn {
            flex: 0 0 auto;
            background: none;
            border: none;
            padding: 12px 25px;
            font-size: 15px;
            font-weight: 600;
            color: #6B7280;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .tab-btn.active {
            color: var(--primary);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
            border-radius: 2px 2px 0 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 16px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 40px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #F9FAFB;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.12);
        }
        
        .form-input::placeholder {
            color: #9CA3AF;
            font-size: 14px;
        }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-login {
            width: 100%;
            padding: 13px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(30, 144, 255, 0.25);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
        }
        
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }
        
        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }
        
        /* Footer Text */
        .footer-text {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #E5E7EB;
        }
        
        .footer-text h3 {
            font-size: 17px;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .footer-text p {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .login-container {
                flex-direction: column;
                margin: 20px auto;
            }
            
            .left-side {
                padding: 35px 25px;
            }
            
            .main-title {
                font-size: 30px;
            }
            
            .subtitle {
                font-size: 15px;
            }
            
            .login-form-container {
                padding: 35px 25px;
            }
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 15px auto;
                border-radius: 12px;
            }
            
            .main-title {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: 14px;
            }
            
            .login-form-container {
                padding: 25px 20px;
            }
            
            .form-title {
                font-size: 20px;
            }
            
            .form-input {
                padding: 11px 38px;
                font-size: 13px;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 14px;
            }
            
            .top-header-left {
                font-size: 12px;
                gap: 10px;
            }
            
            .login-register-btn {
                padding: 6px 15px;
                font-size: 13px;
            }
        }
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
                XE TRUNG CHUYỂN<br>
                ĐÓN - TRẢ TẬN NƠI
            </h1>
            <p class="subtitle">Dịch vụ đặt vé xe khách trực tuyến</p>
            
            <div class="illustration">
                <!-- SVG Illustration Phương Trang Style -->
                <svg viewBox="0 0 500 350" xmlns="http://www.w3.org/2000/svg">
                    <!-- Background circles -->
                    <circle cx="80" cy="60" r="40" fill="#E6F2FF" opacity="0.6"/>
                    <circle cx="420" cy="80" r="60" fill="#E9F3FF" opacity="0.5"/>
                    <circle cx="250" cy="40" r="30" fill="#F0F6FF" opacity="0.6"/>
                    
                    <!-- Bus Body -->
                    <g id="bus">
                        <!-- Main body -->
                        <rect x="80" y="140" width="280" height="140" fill="#1E90FF" rx="15"/>
                        
                        <!-- Front accent -->
                        <rect x="80" y="140" width="60" height="140" fill="#4DA3FF" rx="15 0 0 15"/>
                        
                        <!-- Windows -->
                        <rect x="100" y="155" width="70" height="60" fill="#4A5568" rx="8"/>
                        <rect x="190" y="155" width="70" height="60" fill="#4A5568" rx="8"/>
                        <rect x="280" y="155" width="60" height="60" fill="#4A5568" rx="8"/>
                        
                        <!-- Window reflections -->
                        <rect x="105" y="160" width="30" height="25" fill="rgba(255,255,255,0.4)" rx="4"/>
                        <rect x="195" y="160" width="30" height="25" fill="rgba(255,255,255,0.4)" rx="4"/>
                        <rect x="285" y="160" width="25" height="25" fill="rgba(255,255,255,0.4)" rx="4"/>
                        
                        <!-- Front headlight -->
                        <circle cx="95" cy="230" r="8" fill="#FFF5D9"/>
                        <circle cx="95" cy="250" r="8" fill="#FFF5D9"/>
                        
                        <!-- Side details -->
                        <rect x="100" y="235" width="240" height="3" fill="#1873CC"/>
                        <rect x="100" y="243" width="240" height="2" fill="#1873CC"/>
                        
                        <!-- Wheels -->
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
                    
                    <!-- Passenger 2 (Woman with bag) -->
                    <g id="passenger2">
                        <circle cx="430" cy="205" r="22" fill="#F6E05E"/>
                        <!-- Hair -->
                        <path d="M 420 205 Q 415 195 420 190 L 440 190 Q 445 195 440 205 Z" fill="#2D3748"/>
                        <!-- Body -->
                        <ellipse cx="430" cy="240" rx="25" ry="35" fill="#4A90E2"/>
                        <!-- Legs -->
                        <rect x="415" y="265" width="12" height="30" fill="#2D3748" rx="6"/>
                        <rect x="433" y="265" width="12" height="30" fill="#2D3748" rx="6"/>
                        <!-- Bag -->
                        <rect x="455" y="235" width="28" height="35" fill="#1E90FF" rx="4"/>
                        <rect x="460" y="227" width="18" height="12" fill="#1E90FF" rx="3"/>
                    </g>
                    
                    <!-- Ground -->
                    <rect x="0" y="315" width="500" height="3" fill="#CBD5E0"/>
                    
                    <!-- Decorative elements -->
                    <path d="M 50 320 Q 100 310 150 320" stroke="#E2E8F0" stroke-width="2" fill="none"/>
                    <path d="M 350 320 Q 400 310 450 320" stroke="#E2E8F0" stroke-width="2" fill="none"/>
                </svg>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-form-container">
            <!-- Form Header -->
            <div class="form-header">
                <h2 class="form-title">Đăng nhập tài khoản</h2>
            </div>
            
            <!-- Tabs -->
            <div class="form-tabs">
                <button class="tab-btn active">
                    <i class="fas fa-phone"></i> ĐĂNG NHẬP
                </button>
                <a href="<?php echo appUrl('user/auth/register.php'); ?>" class="tab-btn">
                    ĐĂNG KÝ
                </a>
            </div>
            
            <!-- Flash Messages -->
            <?php
            $successMsg = getFlashMessage('success');
            $errorMsg = getFlashMessage('error');
            ?>
            
            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo e($successMsg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($errorMsg); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form action="<?php echo appUrl('api/auth/login.php'); ?>" method="POST" id="loginForm">
                <?php echo csrfField(); ?>
                
                <!-- Email -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-envelope"></i>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Nhập email"
                            required
                            autocomplete="email"
                            value="test@gmail.com"
                        >
                    </div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-lock"></i>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-input" 
                            id="password"
                            placeholder="Nhập mật khẩu"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Login Button -->
                <button type="submit" class="btn-login">
                    Đăng nhập
                </button>
                
                <!-- Forgot Password -->
                <div class="forgot-password">
                    <a href="<?php echo appUrl('user/auth/forgot_password.php'); ?>">Quên mật khẩu</a>
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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
        
        // Custom redirect URL (if coming from booking page)
        const customRedirect = '<?php echo !empty($_SESSION['login_redirect']) ? addslashes($_SESSION['login_redirect']) : ''; ?>';
        
        // Form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-login');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Check if we have a custom redirect (e.g., from booking page)
                    if (customRedirect && result.data.role === 'user') {
                        window.location.href = customRedirect;
                    } else {
                        // Redirect based on user role
                        window.location.href = result.data.redirect || '<?php echo appUrl(); ?>';
                    }
                } else {
                    alert(result.error || 'Đăng nhập thất bại');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Đăng nhập';
                }
            } catch (error) {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Đăng nhập';
            }
        });
    </script>
</body>
</html>

