<?php
/**
 * Forgot Password Page - FUTA Style
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect(appUrl());
}

$pageTitle = 'Quên mật khẩu - BusBooking';

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
        
        /* Additional styles for forgot password */
        .forgot-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #1E90FF 0%, #1873CC 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 10px 30px rgba(30, 144, 255, 0.25);
        }
        
        .reset-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .reset-success i {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .reset-link-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            word-break: break-all;
        }
        
        .reset-link-box a {
            color: #1E90FF;
            text-decoration: none;
            font-weight: 600;
        }
        
        .reset-link-box a:hover {
            text-decoration: underline;
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
                <div class="logo-image">🚌 FUTA Bus Lines</div>
                <div class="logo-subtitle">CHẤT LƯỢNG LÀ DANH DỰ</div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="login-container">
        <!-- Left Side - Illustration -->
        <div class="left-side">
            <h1 class="main-title">
                QUÊN MẬT KHẨU?<br>
                ĐỪNG LO LẮNG!
            </h1>
            <p class="subtitle">Chúng tôi sẽ giúp bạn lấy lại quyền truy cập</p>
            
            <div class="illustration">
                <!-- SVG Illustration - Key and Lock -->
                <svg viewBox="0 0 500 350" xmlns="http://www.w3.org/2000/svg">
                    <!-- Background circles -->
                    <circle cx="80" cy="60" r="40" fill="#E6F2FF" opacity="0.6"/>
                    <circle cx="420" cy="80" r="60" fill="#E9F3FF" opacity="0.5"/>
                    <circle cx="250" cy="40" r="30" fill="#F0F6FF" opacity="0.6"/>
                    
                    <!-- Lock -->
                    <g id="lock">
                        <path d="M 200 150 Q 200 100 250 100 Q 300 100 300 150" 
                              stroke="#1E90FF" stroke-width="20" fill="none" stroke-linecap="round"/>
                        <rect x="170" y="150" width="160" height="120" fill="#1E90FF" rx="20"/>
                        <circle cx="250" cy="210" r="20" fill="#FFF"/>
                        <rect x="245" y="210" width="10" height="30" fill="#FFF" rx="5"/>
                    </g>
                    
                    <!-- Key -->
                    <g id="key" transform="translate(0, 0)">
                        <circle cx="380" cy="200" r="30" fill="#4A90E2"/>
                        <circle cx="380" cy="200" r="12" fill="white"/>
                        <rect x="300" y="195" width="80" height="10" fill="#4A90E2" rx="5"/>
                        <rect x="295" y="188" width="10" height="10" fill="#4A90E2"/>
                        <rect x="295" y="197" width="10" height="10" fill="#4A90E2"/>
                        <rect x="285" y="193" width="10" height="10" fill="#4A90E2"/>
                    </g>
                    
                    <!-- Email Icon -->
                    <g id="email">
                        <rect x="100" y="260" width="80" height="60" fill="#E8F4F8" rx="8"/>
                        <path d="M 100 260 L 140 290 L 180 260" stroke="#4A90E2" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="155" cy="275" r="8" fill="#1E90FF"/>
                        <circle cx="155" cy="275" r="3" fill="white"/>
                    </g>
                    
                    <!-- Ground -->
                    <rect x="0" y="330" width="500" height="3" fill="#CBD5E0"/>
                </svg>
            </div>
        </div>
        
        <!-- Right Side - Forgot Password Form -->
        <div class="login-form-container">
            <!-- Icon -->
            <div class="forgot-icon">
                <i class="fas fa-key"></i>
            </div>
            
            <!-- Form Header -->
            <div class="form-header">
                <h2 class="form-title">Quên mật khẩu?</h2>
                <p style="color: #666; margin-top: 10px; font-size: 14px;">
                    Nhập email của bạn và chúng tôi sẽ gửi link đặt lại mật khẩu
                </p>
            </div>
            
            <!-- Alert Message -->
            <div id="alertMessage" style="display: none;"></div>
            
            <!-- Forgot Password Form -->
            <form id="forgotPasswordForm" style="margin-top: 30px;">
                <!-- Email -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-envelope"></i>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Nhập email của bạn"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Gửi link đặt lại mật khẩu
                </button>
                
                <!-- Back to Login -->
                <div class="forgot-password">
                    Đã nhớ mật khẩu? <a href="<?php echo appUrl('user/auth/login.php'); ?>">Đăng nhập ngay</a>
                </div>
            </form>
            
            <!-- Footer Text -->
            <div class="footer-text">
                <h3>Kết nối 4F BusBooking</h3>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('alertMessage');
        const email = this.email.value;
        
        // Disable button
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        // Hide previous alerts
        alert.style.display = 'none';
        
        try {
            const response = await fetch('<?php echo appUrl('api/auth/forgot_password.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: new URLSearchParams({
                    email: email,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || ''
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                alert.className = 'reset-success';
                alert.style.display = 'block';
                alert.innerHTML = `
                    <div style="display: flex; align-items: start;">
                        <i class="fas fa-check-circle" style="margin-top: 3px;"></i>
                        <div style="flex: 1;">
                            <strong style="font-size: 16px;">Thành công!</strong>
                            <p style="margin: 8px 0 0 0;">${data.message}</p>
                            ${data.data?.reset_link ? `
                                <div class="reset-link-box">
                                    <strong>🔗 Link đặt lại mật khẩu:</strong><br>
                                    <a href="${data.data.reset_link}" target="_blank">${data.data.reset_link}</a>
                                    <p style="margin: 10px 0 0 0; font-size: 12px; color: #856404;">
                                        <i class="fas fa-clock"></i> Link có hiệu lực trong 1 giờ
                                    </p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                // Clear form
                this.reset();
                
                // Auto redirect after 8 seconds if link is shown
                if (data.data?.reset_link) {
                    setTimeout(() => {
                        window.location.href = data.data.reset_link;
                    }, 8000);
                }
            } else {
                // Show error
                alert.className = 'alert alert-danger';
                alert.style.display = 'block';
                alert.style.background = '#f8d7da';
                alert.style.border = '1px solid #f5c6cb';
                alert.style.color = '#721c24';
                alert.style.padding = '15px';
                alert.style.borderRadius = '8px';
                alert.style.marginBottom = '20px';
                alert.innerHTML = `
                    <div style="display: flex; align-items: start;">
                        <i class="fas fa-exclamation-circle" style="font-size: 20px; margin-right: 10px; margin-top: 3px;"></i>
                        <div>
                            <strong>Lỗi!</strong>
                            <p style="margin: 5px 0 0 0;">${data.message || 'Có lỗi xảy ra. Vui lòng thử lại!'}</p>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Forgot password error:', error);
            alert.className = 'alert alert-danger';
            alert.style.display = 'block';
            alert.style.background = '#f8d7da';
            alert.style.border = '1px solid #f5c6cb';
            alert.style.color = '#721c24';
            alert.style.padding = '15px';
            alert.style.borderRadius = '8px';
            alert.style.marginBottom = '20px';
            alert.innerHTML = `
                <div style="display: flex; align-items: start;">
                    <i class="fas fa-exclamation-circle" style="font-size: 20px; margin-right: 10px; margin-top: 3px;"></i>
                    <div>
                        <strong>Lỗi!</strong>
                        <p style="margin: 5px 0 0 0;">Không thể kết nối đến server. Vui lòng kiểm tra kết nối và thử lại!</p>
                    </div>
                </div>
            `;
        } finally {
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi link đặt lại mật khẩu';
        }
    });
    </script>
</body>
</html>
