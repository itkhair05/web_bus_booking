<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $pageTitle ?? 'Bus Booking - Đặt vé xe online'; ?></title>
    
    <!-- CSRF Token -->
    <?php echo csrfMetaTag(); ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo IMG_URL; ?>/favicon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/user.css">
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header VeXeRe Style -->
    <header class="header-vexere">
        <div class="header-top">
            <div class="header-container">
                <div class="header-left">
                    <a href="<?php echo appUrl(); ?>" class="header-logo">
                        <img src="<?php echo IMG_URL; ?>/logo5.png" alt="4F Bus Booking Logo">
                    </a>
                    <div class="header-promo">
                        <i class="fas fa-gift"></i>
                        Cam kết hoàn 150% nếu nhà xe không cung cấp dịch vụ vận chuyển (*) 
                        <i class="fas fa-info-circle"></i>
                    </div>
                </div>
                <div class="header-right">
                    <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>">
                        <i class="fas fa-list"></i> Đơn hàng của tôi
                    </a>
                    <span class="divider">|</span>
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" role="button">
                            <i class="fas fa-handshake"></i> Trở thành đối tác <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: 4px;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#partnerModal">
                                    <i class="fas fa-info-circle"></i> Tìm hiểu thêm
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo appUrl('partner/auth/register.php'); ?>">
                                    <i class="fas fa-user-plus"></i> Đăng ký ngay
                                </a>
                            </li>
                        </ul>
                    </div>
                    <span class="divider">|</span>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#hotlineModal" class="btn-hotline-header">
                        <i class="fas fa-headset"></i> Hotline 24/7
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <?php
                        // Get unread notifications count
                        $unreadCount = 0;
                        if (isset($conn) && $conn) {
                            try {
                                $userId = getCurrentUserId();
                                $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
                                $stmt->bind_param("i", $userId);
                                $stmt->execute();
                                $notifResult = $stmt->get_result()->fetch_assoc();
                                $unreadCount = $notifResult['unread'] ?? 0;
                            } catch (Exception $e) {
                                $unreadCount = 0;
                            }
                        }
                        ?>
                        <span class="divider">|</span>
                        <a href="<?php echo appUrl('user/notifications/index.php'); ?>" class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="notification-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <span class="divider">|</span>
                        <?php 
                        $user = getCurrentUser();
                        if (!$user) {
                            $user = ['name' => 'User', 'avatar' => ''];
                        }
                        ?>
                        <div class="dropdown user-dropdown">
                            <button class="user-dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?php echo appUrl($user['avatar']); ?>" alt="Avatar" class="user-avatar-small">
                                <?php else: ?>
                                    <div class="user-avatar-small">
                                        <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <?php echo e($user['name'] ?? 'User'); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo appUrl('user/dashboard.php'); ?>">
                                        <i class="fas fa-chart-line"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo appUrl('user/profile/index.php'); ?>">
                                        <i class="fas fa-user"></i> Tài khoản của tôi
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>">
                                        <i class="fas fa-ticket-alt"></i> Vé của tôi
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo appUrl('user/payments/history.php'); ?>">
                                        <i class="fas fa-receipt"></i> Lịch sử giao dịch
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo appUrl('user/profile/change_password.php'); ?>">
                                        <i class="fas fa-lock"></i> Đổi mật khẩu
                                    </a>
                                </li>
                                
                                <?php 
                                $userRole = $userRole ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
                                if ($userRole === 'admin' || $userRole === 'partner'): 
                                ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">
                                        <small class="text-muted">CHUYỂN ĐỔI GIAO DIỆN</small>
                                    </li>
                                    <?php if ($userRole === 'admin'): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo appUrl('admin/admin_dashboard.php'); ?>">
                                                <i class="fas fa-user-shield text-danger"></i> Admin Panel
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($userRole === 'partner'): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo appUrl('partner/dashboard.php'); ?>">
                                                <i class="fas fa-briefcase text-success"></i> Partner Panel
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo appUrl('user/auth/logout.php'); ?>">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo appUrl('user/auth/login.php'); ?>" class="btn-login-header">
                            <i class="fas fa-user"></i> Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Flash Messages -->
    <?php
    $successMsg = getFlashMessage('success');
    $errorMsg = getFlashMessage('error');
    $warningMsg = getFlashMessage('warning');
    $infoMsg = getFlashMessage('info');
    ?>
    
    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo e($successMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo e($errorMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($warningMsg): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo e($warningMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($infoMsg): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i> <?php echo e($infoMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content">
    
    <!-- Partner Registration Modal -->
    <div class="modal fade" id="partnerModal" tabindex="-1" aria-labelledby="partnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header" style="border-bottom: 2px solid #f3f4f6; padding: 25px 30px;">
                    <h5 class="modal-title" id="partnerModalLabel" style="font-weight: 700; color: #1f2937; font-size: 22px;">
                        <i class="fas fa-handshake text-primary"></i> Trở thành đối tác
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 30px;">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <i class="fas fa-bus" style="font-size: 64px; color: #2563eb; margin-bottom: 15px;"></i>
                        <h4 style="color: #1f2937; font-weight: 700; margin-bottom: 10px;">Hợp tác cùng Bus Booking</h4>
                        <p style="color: #6b7280; font-size: 15px;">Mở rộng kinh doanh, tăng doanh thu cùng nền tảng của chúng tôi</p>
                    </div>
                    
                    <div style="background: #f9fafb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                        <h6 style="font-weight: 700; color: #1f2937; margin-bottom: 15px;">
                            <i class="fas fa-star text-warning"></i> Lợi ích khi trở thành đối tác:
                        </h6>
                        <ul style="color: #4b5563; line-height: 2; margin-bottom: 0;">
                            <li>✓ Tiếp cận hàng nghìn khách hàng tiềm năng</li>
                            <li>✓ Quản lý đặt vé hiện đại, chuyên nghiệp</li>
                            <li>✓ Thanh toán nhanh chóng, minh bạch</li>
                            <li>✓ Hỗ trợ marketing & quảng bá thương hiệu</li>
                            <li>✓ Đội ngũ hỗ trợ 24/7</li>
                        </ul>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: stretch; width: 100%;">
                        <a href="<?php echo appUrl('partner/auth/register.php'); ?>" class="btn btn-primary" style="flex: 1; padding: 12px; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; border: none;">
                            <i class="fas fa-user-plus"></i> Đăng ký ngay
                        </a>
                        <a href="<?php echo appUrl('user/auth/login.php'); ?>" class="btn btn-outline-primary" style="flex: 1; padding: 12px; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; background: white; color: #1E90FF; border: 2px solid #1E90FF;">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </div>
                    
                    <p style="text-align: center; color: #9ca3af; font-size: 13px; margin-top: 15px; margin-bottom: 0;">
                        Đã có tài khoản? <a href="<?php echo appUrl('user/auth/login.php'); ?>" style="color: #2563eb; font-weight: 600;">Đăng nhập tại đây</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hotline Modal -->
    <div class="modal fade" id="hotlineModal" tabindex="-1" aria-labelledby="hotlineModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header" style="border-bottom: 2px solid #f3f4f6; padding: 25px 30px;">
                    <h5 class="modal-title" id="hotlineModalLabel" style="font-weight: 700; color: #1f2937; font-size: 22px;">
                        <i class="fas fa-headset text-success"></i> Hotline hỗ trợ 24/7
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 30px;">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <i class="fas fa-phone-volume" style="font-size: 64px; color: #10b981; margin-bottom: 15px;"></i>
                        <h4 style="color: #1f2937; font-weight: 700; margin-bottom: 10px;">Liên hệ với chúng tôi</h4>
                        <p style="color: #6b7280; font-size: 15px;">Chúng tôi luôn sẵn sàng hỗ trợ bạn mọi lúc, mọi nơi</p>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                        <!-- Hotline chính -->
                        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 15px; padding: 20px; color: white; text-align: center;">
                            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Hotline chính</div>
                            <div style="font-size: 32px; font-weight: 700; letter-spacing: 2px;">1900-xxxx</div>
                            <div style="font-size: 13px; opacity: 0.9; margin-top: 5px;">Miễn phí từ 8:00 - 22:00</div>
                        </div>
                        
                        <!-- Các kênh liên hệ khác -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <a href="mailto:support@busbooking.vn" style="text-decoration: none; background: #eff6ff; border-radius: 12px; padding: 15px; text-align: center; transition: all 0.3s;">
                                <i class="fas fa-envelope" style="font-size: 28px; color: #2563eb; margin-bottom: 8px;"></i>
                                <div style="font-size: 13px; color: #1f2937; font-weight: 600;">Email</div>
                                <div style="font-size: 11px; color: #6b7280;">support@busbooking.vn</div>
                            </a>
                            <a href="#" style="text-decoration: none; background: #f0fdf4; border-radius: 12px; padding: 15px; text-align: center; transition: all 0.3s;">
                                <i class="fab fa-facebook-messenger" style="font-size: 28px; color: #10b981; margin-bottom: 8px;"></i>
                                <div style="font-size: 13px; color: #1f2937; font-weight: 600;">Messenger</div>
                                <div style="font-size: 11px; color: #6b7280;">Chat ngay</div>
                            </a>
                        </div>
                    </div>
                    
                    <div style="background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 15px;">
                        <div style="color: #92400e; font-size: 14px; font-weight: 600; margin-bottom: 5px;">
                            <i class="fas fa-info-circle"></i> Thời gian hỗ trợ
                        </div>
                        <div style="color: #78350f; font-size: 13px; line-height: 1.6;">
                            • <strong>Hotline:</strong> 24/7 (cả ngày lễ, Tết)<br>
                            • <strong>Email:</strong> Phản hồi trong 2-4 giờ<br>
                            • <strong>Messenger:</strong> Online 8:00 - 22:00
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

