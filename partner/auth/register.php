<?php
require_once '../../config/session.php';
$conn = require_once '../../config/db.php';
require_once '../../core/auth.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

$pageTitle = "Đăng ký đối tác - Bus Booking";
$errors = [];
$success = '';

// Upload logo helper
function uploadPartnerLogo(?array $file): ?string {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Tải logo thất bại, vui lòng thử lại.');
    }
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception('Logo phải là JPG, PNG hoặc WebP.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new Exception('Logo tối đa 2MB.');
    }
    $uploadDir = dirname(__DIR__, 2) . '/uploads/partners/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $filename = 'partner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Không thể lưu logo, vui lòng thử lại.');
    }
    return 'uploads/partners/' . $filename;
}

// If user already logged in as partner, redirect to partner dashboard
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
    if ($role === 'partner') {
        header('Location: ' . appUrl('partner/partner_dashboard.php'));
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token xác thực không hợp lệ. Vui lòng thử lại.";
    } else {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $company_name = trim($_POST['company_name'] ?? '');
        $business_license = trim($_POST['business_license'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $logoUrl = null;
        
        // Validation
        if (empty($name)) $errors[] = "Vui lòng nhập họ tên.";
        if (empty($email)) {
            $errors[] = "Vui lòng nhập email.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ.";
        }
        if (empty($phone)) {
            $errors[] = "Vui lòng nhập số điện thoại.";
        } elseif (!preg_match('/^0[0-9]{9}$/', $phone)) {
            $errors[] = "Số điện thoại không hợp lệ (phải là 10 số, bắt đầu bằng 0).";
        }
        if (empty($password)) {
            $errors[] = "Vui lòng nhập mật khẩu.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Mật khẩu xác nhận không khớp.";
        }
        if (empty($company_name)) $errors[] = "Vui lòng nhập tên công ty.";
        if (empty($business_license)) $errors[] = "Vui lòng nhập số giấy phép kinh doanh.";
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Email này đã được đăng ký.";
            }
        }
        
        // Check if phone already exists
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Số điện thoại này đã được đăng ký.";
            }
        }
        
        // If no errors, create account
        if (empty($errors)) {
            $conn->begin_transaction();
            
            try {
                // Upload logo (optional)
                $logoUrl = uploadPartnerLogo($_FILES['logo'] ?? null);

                // Create user account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status, created_at) VALUES (?, ?, ?, ?, 'partner', 'pending', NOW())");
                $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
                $stmt->execute();
                
                // Create partner profile (bảng partners hiện có các cột: name, email, phone, password, logo_url, policy, status, created_at)
                // Lưu tên công ty vào cột name, ghép địa chỉ + GPKD vào policy để lưu kèm
                $policyText = "Địa chỉ: " . $address . " | GPKD: " . $business_license;
                $stmt = $conn->prepare("INSERT INTO partners (name, email, phone, password, logo_url, policy, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->bind_param("ssssss", $company_name, $email, $phone, $hashed_password, $logoUrl, $policyText);
                $stmt->execute();
                
                $conn->commit();
                
                $success = "Đăng ký thành công! Tài khoản của bạn đang chờ phê duyệt. Chúng tôi sẽ liên hệ với bạn trong vòng 24-48 giờ.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = $e->getMessage() ?: "Có lỗi xảy ra khi đăng ký. Vui lòng thử lại sau.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .register-container {
            max-width: 800px;
            width: 100%;
        }
        
        .register-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .register-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .register-header p {
            font-size: 15px;
            opacity: 0.9;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .input-group-text {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #6b7280;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }
        
        .divider span {
            background: white;
            padding: 0 20px;
            color: #9ca3af;
            position: relative;
            font-size: 14px;
        }
        
        .login-link {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .login-link a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-home a:hover {
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-handshake" style="font-size: 64px; margin-bottom: 15px;"></i>
                <h1>Đăng ký đối tác</h1>
                <p>Hợp tác cùng Bus Booking - Mở rộng kinh doanh của bạn</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Có lỗi xảy ra:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong><?php echo htmlspecialchars($success); ?></strong>
                        <div class="mt-3">
                            <a href="<?php echo appUrl('user/auth/login.php'); ?>" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        
                        <!-- Thông tin cá nhân -->
                        <div class="section-title">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Họ và tên <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="name" class="form-control" placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" name="phone" class="form-control" placeholder="0912345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Mật khẩu <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" class="form-control" placeholder="Ít nhất 6 ký tự" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Xác nhận mật khẩu <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thông tin công ty -->
                        <div class="section-title mt-4">
                            <i class="fas fa-building"></i> Thông tin công ty
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tên công ty / Nhà xe <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <input type="text" name="company_name" class="form-control" placeholder="Công ty TNHH XYZ" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số giấy phép kinh doanh <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" name="business_license" class="form-control" placeholder="0123456789" value="<?php echo htmlspecialchars($_POST['business_license'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Logo nhà xe (tùy chọn)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-image"></i></span>
                                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control">
                            </div>
                            <small style="color:#6b7280;">PNG/JPG/WebP, tối đa 2MB</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Địa chỉ công ty</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea name="address" class="form-control" rows="2" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-plus me-2"></i> Đăng ký ngay
                        </button>
                    </form>
                    
                    <div class="divider">
                        <span>hoặc</span>
                    </div>
                    
                    <div class="login-link">
                        Đã có tài khoản? <a href="<?php echo appUrl('user/auth/login.php'); ?>">Đăng nhập tại đây</a>
                    </div>
                <?php endif; ?>
                
                <div class="back-home">
                    <a href="<?php echo appUrl(); ?>">
                        <i class="fas fa-home me-1"></i> Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

