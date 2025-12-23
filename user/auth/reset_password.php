<?php
/**
 * Reset Password Page
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

// Get token from URL
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    redirect(appUrl('user/auth/forgot_password.php'));
}

// Verify token exists and not expired
// Use PHP time instead of MySQL NOW() to avoid timezone mismatch
$currentTime = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    SELECT pr.reset_id, u.email, u.fullname, pr.expires_at
    FROM password_resets pr
    JOIN users u ON pr.user_id = u.user_id
    WHERE pr.token = ? AND pr.expires_at > ?
");
$stmt->bind_param("ss", $token, $currentTime);
$stmt->execute();
$result = $stmt->get_result();

$tokenInvalid = $result->num_rows === 0;
$resetData = $tokenInvalid ? null : $result->fetch_assoc();

// In debug mode, try to detect nguyên nhân token sai/hết hạn để hỗ trợ dev
$debugTokenExpired = false;
$debugTokenFound = false;
if ($tokenInvalid && defined('APP_DEBUG') && APP_DEBUG === true && strlen($token) >= 16) {
    $stmt = $conn->prepare("
        SELECT expires_at FROM password_resets WHERE token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $debugResult = $stmt->get_result();
    if ($debugResult->num_rows > 0) {
        $debugTokenFound = true;
        $debugRow = $debugResult->fetch_assoc();
        // Nếu tìm thấy nhưng hết hạn thì đánh dấu để hiển thị gợi ý
        $debugTokenExpired = (strtotime($debugRow['expires_at']) <= time());
    }
}

$pageTitle = 'Đặt lại mật khẩu - BusBooking';
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
        /* Base layout & theme (reuse màu xanh hệ thống) */
        :root {
            --primary: #1E90FF;
            --primary-dark: #1873CC;
            --primary-light: #4DA3FF;
            --primary-soft: #E6F2FF;
            --text-dark: #0F2857;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            color: #0f172a;
        }

        .top-header {
            background: var(--primary-dark);
            color: #fff;
            padding: 12px 0;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-back:hover {
            opacity: 0.9;
        }

        .header-actions a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-container {
            display: flex;
            justify-content: center;
            padding: 40px 16px 60px;
        }

        .auth-card {
            width: 100%;
            max-width: 540px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.12);
            padding: 32px;
        }

        .auth-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 12px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-title {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .auth-subtitle {
            margin: 0;
            color: #4b5563;
            font-size: 14px;
        }

        .auth-form .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .password-input {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 44px 12px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.15);
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 15px;
        }

        .form-text {
            color: #6b7280;
            font-size: 12px;
        }

        .btn-submit {
            width: 100%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 10px 24px rgba(30,144,255,0.25);
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 18px;
        }

        .link-primary {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .link-primary:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f7ff;
            border: 1px solid #b6e0ff;
            color: #0f4f94;
        }

        .alert-danger {
            background: #fff2f0;
            border: 1px solid #ffcdd2;
            color: #b91c1c;
        }

        @media (max-width: 640px) {
            .auth-card {
                padding: 24px;
            }
            .auth-title {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="<?php echo appUrl(); ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Quay lại trang chủ</span>
                </a>
                <div class="header-actions">
                    <a href="<?php echo appUrl('user/auth/login.php'); ?>" class="btn-link">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="auth-container">
        <div class="auth-card">
            <!-- Logo -->
            <div class="auth-logo">
                <i class="fas fa-bus"></i>
                <span>BusBooking</span>
            </div>

            <?php if ($tokenInvalid): ?>
                <!-- Token Invalid -->
                <div class="auth-header">
                    <h1 class="auth-title">Link không hợp lệ</h1>
                    <p class="auth-subtitle">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                        Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn
                    </p>
                </div>

                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Link không hợp lệ</strong>
                        <p>Link đặt lại mật khẩu đã hết hạn hoặc đã được sử dụng. Vui lòng yêu cầu link mới.</p>
                        <?php if (defined('APP_DEBUG') && APP_DEBUG === true): ?>
                            <?php if ($debugTokenFound && $debugTokenExpired): ?>
                                <p style="margin-top:8px;color:#ef4444;">(DEV) Token có tồn tại nhưng đã hết hạn.</p>
                            <?php elseif ($debugTokenFound): ?>
                                <p style="margin-top:8px;color:#ef4444;">(DEV) Token có tồn tại nhưng không đạt điều kiện thời gian.</p>
                            <?php else: ?>
                                <p style="margin-top:8px;color:#ef4444;">(DEV) Không tìm thấy token trong bảng password_resets.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="<?php echo appUrl('user/auth/forgot_password.php'); ?>" class="btn-submit">
                    <i class="fas fa-redo"></i>
                    <span>Yêu cầu link mới</span>
                </a>
            <?php else: ?>
                <!-- Reset Password Form -->
                <div class="auth-header">
                    <h1 class="auth-title">Đặt lại mật khẩu</h1>
                    <p class="auth-subtitle">
                        <i class="fas fa-user"></i>
                        Đặt lại mật khẩu cho: <strong><?php echo e($resetData['email']); ?></strong>
                    </p>
                </div>

                <!-- Alert Message -->
                <div id="alertMessage" class="alert" style="display: none;"></div>

                <form id="resetPasswordForm" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Mật khẩu mới
                        </label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                name="password" 
                                class="form-control" 
                                id="password"
                                placeholder="Nhập mật khẩu mới"
                                required
                                minlength="6"
                                autofocus
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text">Mật khẩu phải có ít nhất 6 ký tự</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Xác nhận mật khẩu
                        </label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                name="confirm_password" 
                                class="form-control" 
                                id="confirmPassword"
                                placeholder="Nhập lại mật khẩu"
                                required
                                minlength="6"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-check"></i>
                        <span>Đặt lại mật khẩu</span>
                    </button>
                </form>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="auth-footer">
                <p class="text-center" style="margin-top: 24px;">
                    <a href="<?php echo appUrl('user/auth/login.php'); ?>" class="link-primary">
                        <i class="fas fa-sign-in-alt"></i> Quay lại đăng nhập
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = event.target.closest('button').querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    <?php if (!$tokenInvalid): ?>
    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('alertMessage');
        const password = this.password.value;
        const confirmPassword = this.confirm_password.value;
        
        // Validate password match
        if (password !== confirmPassword) {
            alert.className = 'alert alert-danger';
            alert.style.display = 'block';
            alert.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Lỗi!</strong>
                    <p>Mật khẩu xác nhận không khớp</p>
                </div>
            `;
            return;
        }
        
        // Disable button
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Đang xử lý...</span>';
        
        try {
            const response = await fetch('<?php echo appUrl('api/auth/reset_password.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: new URLSearchParams({
                    token: this.token.value,
                    password: password,
                    confirm_password: confirmPassword,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || ''
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                alert.className = 'alert alert-success';
                alert.style.display = 'block';
                alert.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Thành công!</strong>
                        <p>${data.message}</p>
                        <p style="margin-top: 8px;">Đang chuyển đến trang đăng nhập...</p>
                    </div>
                `;
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = '<?php echo appUrl('user/auth/login.php'); ?>';
                }, 2000);
            } else {
                // Show error
                alert.className = 'alert alert-danger';
                alert.style.display = 'block';
                alert.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Lỗi!</strong>
                        <p>${data.message || 'Có lỗi xảy ra. Vui lòng thử lại!'}</p>
                    </div>
                `;
                
                // Re-enable button
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> <span>Đặt lại mật khẩu</span>';
            }
        } catch (error) {
            alert.className = 'alert alert-danger';
            alert.style.display = 'block';
            alert.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Lỗi!</strong>
                    <p>Không thể kết nối đến server. Vui lòng thử lại!</p>
                </div>
            `;
            
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> <span>Đặt lại mật khẩu</span>';
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>

