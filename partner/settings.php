<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$operator_id = getCurrentOperator();

$message = '';
$message_type = '';

// Lấy thông tin nhà xe
$query = "SELECT * FROM partners WHERE partner_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$operator_id]);
$operator = $stmt->fetch(PDO::FETCH_ASSOC);

// Cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update_info') {
    $company_name = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $policies = trim($_POST['policies']);
    
    try {
        $query = "UPDATE partners SET name = ?, email = ?, phone = ?, policy = ? WHERE partner_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$company_name, $email, $phone, $policies, $operator_id]);
        
        $_SESSION['company_name'] = $company_name;
        $message = 'Cập nhật thông tin thành công!';
        $message_type = 'success';
        
        $stmt = $db->prepare("SELECT * FROM partners WHERE partner_id = ?");
        $stmt->execute([$operator_id]);
        $operator = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Cập nhật quy định
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update_policy') {
    $policy = trim($_POST['policy']);
    
    if (empty($policy)) {
        $message = 'Vui lòng nhập quy định nhà xe!';
        $message_type = 'danger';
    } else {
        try {
            $query = "UPDATE partners SET policy = ? WHERE partner_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$policy, $operator_id]);
            
            $message = 'Cập nhật quy định thành công!';
            $message_type = 'success';
            
            // Reload operator data
            $stmt = $db->prepare("SELECT * FROM partners WHERE partner_id = ?");
            $stmt->execute([$operator_id]);
            $operator = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Upload logo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'upload_logo' && isset($_FILES['logo'])) {
    // Đồng nhất đường dẫn: uploads/partners/ (không có subfolder logos)
    $uploadDir = __DIR__ . '/../uploads/partners/';
    
    // Tạo thư mục nếu chưa có
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['logo'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $hasError = false;
    
    // Validate file
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = 'Lỗi upload file. Vui lòng thử lại.';
        $message_type = 'danger';
        $hasError = true;
    } elseif (($file['size'] ?? 0) <= 0) {
        $message = 'File rỗng. Vui lòng chọn lại ảnh.';
        $message_type = 'danger';
        $hasError = true;
    } elseif ($file['size'] > $maxSize) {
        $message = 'File quá lớn. Kích thước tối đa: 5MB.';
        $message_type = 'danger';
        $hasError = true;
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExt, true)) {
            $message = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP).';
            $message_type = 'danger';
            $hasError = true;
        }
    }
    
    if (!$hasError) {
        try {
            // Generate unique filename
            $filename = 'partner_' . $operator_id . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Relative path for database (đồng nhất với admin và register)
                $relativePath = 'uploads/partners/' . $filename;
                
                // Delete old logo if exists (kiểm tra cả 2 đường dẫn cũ)
                if (!empty($operator['logo_url']) && !preg_match('/^https?:\/\//i', $operator['logo_url'])) {
                    $oldPath = __DIR__ . '/../' . $operator['logo_url'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE partners SET logo_url = ? WHERE partner_id = ?");
                $stmt->execute([$relativePath, $operator_id]);
                
                // Reload operator data
                $stmt = $db->prepare("SELECT * FROM partners WHERE partner_id = ?");
                $stmt->execute([$operator_id]);
                $operator = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $message = 'Upload logo thành công!';
                $message_type = 'success';
            } else {
                $message = 'Không thể lưu file. Vui lòng kiểm tra quyền ghi thư mục.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'change_password') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if ($new !== $confirm) {
        $message = 'Mật khẩu xác nhận không khớp!';
        $message_type = 'danger';
    } elseif (!password_verify($current, $operator['password'])) {
        $message = 'Mật khẩu hiện tại không đúng!';
        $message_type = 'danger';
    } else {
        try {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE partners SET password = ? WHERE partner_id = ?");
            $stmt->execute([$hashed, $operator_id]);
            $message = 'Đổi mật khẩu thành công!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cài đặt - <?= htmlspecialchars($_SESSION['company_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1E90FF;
      --primary-hover: #1873CC;
      --secondary: #17a2b8;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --info: #0dcaf0;
      --dark: #1f2937;
      --light: #f8fafc;
      --gray: #94a3b8;
      --border: #e2e8f0;
    }

    * { font-family: 'Inter', sans-serif; }
    body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 280px;
      height: 100vh;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0,0,0,0.1);
    }

    .brand {
      padding: 1.8rem 1.5rem;
      font-weight: 700;
      font-size: 1.4rem;
      border-bottom: 1px solid rgba(255,255,255,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .nav-link {
      color: rgba(255,255,255,0.85);
      padding: 0.9rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.25s ease;
      border-left: 3px solid transparent;
    }

    .nav-link:hover, .nav-link.active {
      background: rgba(255,255,255,0.15);
      color: white;
      border-left-color: white;
      transform: translateX(4px);
    }

    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    /* Main */
    .main-content {
      margin-left: 280px;
      padding: 2rem;
    }

    .top-bar {
      background: white;
      padding: 1.5rem 2rem;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
    }

    .page-title {
      font-weight: 700;
      color: var(--dark);
      font-size: 1.6rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Profile Card */
    .profile-card {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .profile-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 2.2rem;
      color: white;
      overflow: hidden;
      border: 3px solid white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .profile-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .logo-upload-section {
      background: #ffffff;
      border-radius: 16px;
      padding: 1.75rem;
      margin: 0 auto 1.5rem;
      max-width: 760px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }
    
    .logo-upload-section h6 {
      font-weight: 700;
      color: #0f172a;
    }
    
    .logo-preview {
      width: 140px;
      height: 140px;
      border-radius: 16px;
      overflow: hidden;
      margin: 0 auto 1rem;
      border: 2px dashed var(--border);
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }
    
    .logo-preview:hover {
      border-color: var(--primary);
      box-shadow: 0 10px 24px rgba(32, 201, 151, 0.12);
    }
    
    .logo-preview img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    
    .logo-preview .placeholder {
      color: var(--gray);
      font-size: 3rem;
    }

    .logo-upload-form {
      max-width: 520px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .logo-upload-actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
      align-items: center;
    }
    
    .file-input-wrapper {
      position: relative;
      display: inline-block;
      width: 100%;
    }
    
    .file-input-wrapper input[type="file"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .file-input-label {
      display: block;
      padding: 0.9rem 1.5rem;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 12px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 700;
      max-width: 460px;
      margin: 0 auto;
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: 0 8px 18px rgba(32, 201, 151, 0.25);
    }
    
    .file-input-label:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
    }

    .profile-name {
      font-weight: 700;
      font-size: 1.4rem;
      color: var(--dark);
      margin-bottom: 0.5rem;
    }

    .profile-email {
      color: #64748b;
      margin-bottom: 0.75rem;
    }

    /* Tabs */
    .nav-tabs {
      border: none;
      padding: 0 1rem;
      background: white;
      border-radius: 16px 16px 0 0;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }

    .nav-tabs .nav-link {
      color: #64748b;
      border: none;
      padding: 1rem 1.5rem;
      font-weight: 500;
      border-radius: 12px 12px 0 0;
      margin-bottom: -1px;
    }

    .nav-tabs .nav-link:hover {
      background: #f1f5f9;
    }

    .nav-tabs .nav-link.active {
      color: var(--primary);
      background: #f8fafc;
      border-bottom: 3px solid var(--primary);
    }

    .tab-content {
      background: white;
      border-radius: 0 0 16px 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      overflow: hidden;
    }

    .tab-pane {
      padding: 2rem;
    }

    .tab-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      padding: 1.2rem 2rem;
      margin: -2rem -2rem 1.5rem -2rem;
      border-radius: 16px 16px 0 0;
    }

    .tab-header h5 {
      margin: 0;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Form */
    .form-label {
      font-weight: 600;
      color: var(--dark);
    }

    .form-control, .form-select {
      border-radius: 12px;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border);
    }

    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.2);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: none;
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #1baa80, #138496);
      transform: translateY(-2px);
    }

    /* Info List */
    .info-list {
      background: #f8fafc;
      border-radius: 12px;
      padding: 1.5rem;
      margin-top: 1rem;
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid #e2e8f0;
    }

    .info-item:last-child {
      border-bottom: none;
    }

    .info-label {
      font-weight: 600;
      color: var(--dark);
    }

    .info-value {
      color: #64748b;
    }

    /* Animation */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .fade-in-up {
      animation: fadeInUp 0.6s ease-out;
    }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar { width: 80px; }
      .sidebar .brand, .nav-link span { display: none; }
      .nav-link { justify-content: center; }
      .main-content { margin-left: 80px; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="brand">
      <i class="fas fa-bus"></i>
      <span><?= htmlspecialchars($_SESSION['company_name']) ?></span>
    </div>
    <nav class="nav flex-column mt-3">
      <a class="nav-link" href="../partner/dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a>
      <a class="nav-link" href="../partner/trips.php"><i class="fas fa-route"></i><span>Chuyến xe</span></a>
      <a class="nav-link" href="../partner/tickets.php"><i class="fas fa-ticket-alt"></i><span>Đặt vé</span></a>
      <a class="nav-link" href="../partner/operations.php"><i class="fas fa-cogs"></i><span>Vận hành</span></a>
      <a class="nav-link" href="../partner/reports.php"><i class="fas fa-chart-bar"></i><span>Báo cáo</span></a>
      <a class="nav-link" href="../partner/feedback.php"><i class="fas fa-star"></i><span>Phản hồi</span></a>
      <a class="nav-link" href="../partner/notifications.php"><i class="fas fa-bell"></i><span>Thông báo</span></a>
      <a class="nav-link active" href="../partner/settings.php"><i class="fas fa-cog"></i><span>Cài đặt</span></a>
      
      <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
      <div style="padding: 0 15px; margin-bottom: 10px;">
        <small style="color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Chuyển giao diện</small>
      </div>
      <a class="nav-link" href="../index.php" style="background: rgba(59, 130, 246, 0.1);"><i class="fas fa-home"></i><span>Giao diện User</span></a>
      
      <a class="nav-link" href="<?php echo appUrl('partner/auth/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="top-bar fade-in-up">
      <div>
        <h1 class="page-title"><i class="fas fa-cog"></i> Cài đặt</h1>
        <p class="text-muted mb-0">Quản lý thông tin và bảo mật tài khoản</p>
      </div>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show fade-in-up" role="alert">
      <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Profile Card -->
    <div class="profile-card fade-in-up">
      <div class="profile-avatar">
        <?php
        $logoUrl = $operator['logo_url'] ?? '';
        if ($logoUrl) {
            // Nếu là URL tuyệt đối, dùng trực tiếp; nếu là đường dẫn tương đối, tạo URL đầy đủ
            if (preg_match('/^https?:\/\//i', $logoUrl)) {
                $logoDisplay = $logoUrl;
            } else {
                // Lấy base URL từ constants hoặc tạo từ __DIR__
                $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));
                $logoDisplay = $baseUrl . '/' . ltrim($logoUrl, '/');
            }
            echo '<img src="' . htmlspecialchars($logoDisplay) . '" alt="' . htmlspecialchars($operator['name']) . '">';
        } else {
            echo '<i class="fas fa-building"></i>';
        }
        ?>
      </div>
      <div class="profile-name"><?= htmlspecialchars($operator['name']) ?></div>
      <div class="profile-email"><?= htmlspecialchars($operator['email']) ?></div>
      <?php
      $status = $operator['status'];
      $badge = [
        'approved' => '<span class="badge bg-success">Đã duyệt</span>',
        'pending' => '<span class="badge bg-warning text-dark">Chờ duyệt</span>',
        'suspended' => '<span class="badge bg-danger">Tạm khóa</span>'
      ];
      echo $badge[$status] ?? '';
      ?>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs fade-in-up" id="settingsTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
          <i class="fas fa-building"></i> Thông tin công ty
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">
          <i class="fas fa-lock"></i> Đổi mật khẩu
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="policy-tab" data-bs-toggle="tab" data-bs-target="#policy" type="button">
          <i class="fas fa-file-contract"></i> Quy định nhà xe
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">
          <i class="fas fa-info-circle"></i> Hệ thống
        </button>
      </li>
    </ul>

    <div class="tab-content fade-in-up">
      <!-- Profile Tab -->
      <div class="tab-pane fade show active" id="profile">
        <div class="tab-header">
          <h5><i class="fas fa-building"></i> Cập nhật thông tin công ty</h5>
        </div>
        
        <!-- Logo Upload Section -->
        <div class="logo-upload-section mb-4">
          <h6 class="text-center mb-1"><i class="fas fa-image"></i> Logo công ty</h6>
          <p class="text-center text-muted mb-3" style="font-size: 13px;">Khuyến nghị: ảnh vuông 1:1, nền sáng, kích thước &lt; 5MB (JPG/PNG/WEBP)</p>
          <div class="logo-preview" id="logoPreview">
            <?php
            $logoUrl = $operator['logo_url'] ?? '';
            if ($logoUrl) {
                if (preg_match('/^https?:\/\//i', $logoUrl)) {
                    $logoDisplay = $logoUrl;
                } else {
                    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));
                    $logoDisplay = $baseUrl . '/' . ltrim($logoUrl, '/');
                }
                echo '<img src="' . htmlspecialchars($logoDisplay) . '" alt="Logo" id="logoPreviewImg">';
            } else {
                echo '<div class="placeholder"><i class="fas fa-image"></i></div>';
            }
            ?>
          </div>
          <form method="POST" enctype="multipart/form-data" id="logoUploadForm" class="logo-upload-form">
            <input type="hidden" name="action" value="upload_logo">
            <div class="file-input-wrapper">
              <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
              <label for="logoInput" class="file-input-label">
                <i class="fas fa-upload"></i> Chọn ảnh logo (JPG, PNG, GIF, WEBP - Tối đa 5MB)
              </label>
            </div>
            <div class="logo-upload-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Logo
              </button>
              <small class="text-muted d-block text-center">
                Logo sẽ hiển thị trong kết quả tìm kiếm chuyến xe. Nếu chưa có logo, hệ thống sẽ dùng logo mặc định.
              </small>
            </div>
          </form>
        </div>
        
        <form method="POST" class="p-3">
          <input type="hidden" name="action" value="update_info">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tên công ty *</label>
              <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($operator['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($operator['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Số điện thoại</label>
              <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($operator['phone']) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Chính sách</label>
              <textarea class="form-control" name="policies" rows="4" placeholder="Nhập chính sách..."><?= htmlspecialchars($operator['policy']) ?></textarea>
              <small class="text-muted">Chính sách này sẽ hiển thị trong thông tin công ty</small>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Cập nhật
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Password Tab -->
      <div class="tab-pane fade" id="password">
        <div class="tab-header">
          <h5><i class="fas fa-lock"></i> Đổi mật khẩu</h5>
        </div>
        <form method="POST" class="p-3">
          <input type="hidden" name="action" value="change_password">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Mật khẩu hiện tại *</label>
              <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mật khẩu mới *</label>
              <input type="password" class="form-control" name="new_password" id="new_password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Xác nhận *</label>
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i> Đổi mật khẩu
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Policy Tab -->
      <div class="tab-pane fade" id="policy">
        <div class="tab-header">
          <h5><i class="fas fa-file-contract"></i> Quy định nhà xe</h5>
        </div>
        <form method="POST" class="p-3">
          <input type="hidden" name="action" value="update_policy">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Quy định nhà xe *</label>
              <textarea class="form-control" name="policy" id="policyTextarea" rows="15" placeholder="Nhập quy định của nhà xe... (Mỗi dòng là một quy định, có thể dùng * để đánh dấu đầu dòng)"><?= htmlspecialchars($operator['policy'] ?? '') ?></textarea>
              <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle"></i> Quy định này sẽ hiển thị trong modal khi khách hàng chọn chuyến xe của bạn.
                <br>Mỗi dòng là một quy định riêng. Bạn có thể dùng dấu * ở đầu dòng để đánh dấu.
                <br>Ví dụ:
                <pre style="background: #f8fafc; padding: 10px; border-radius: 8px; margin-top: 10px; font-size: 12px;">* Hành khách đặt vé có điểm trả Hàng Xanh trong khung giờ 6:00 - 22:00 sẽ được trả trên Quốc Lộ 1A và đi xe trung chuyển vào.
* Hành khách đặt xe tại Văn Phòng Lê Hồng Phong sẽ được đưa đón bằng xe trung chuyển ra bãi xe lớn để khởi hành.
* Hành khách nước ngoài có nhu cầu đón tại bến xe xin vui lòng cung cấp Số điện thoại Việt Nam để tiện hỗ trợ kịp thời.

Thời gian trung chuyển sẽ được sắp xếp phù hợp với lịch khởi hành của chuyến xe.</pre>
              </small>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Lưu quy định
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Info Tab -->
      <div class="tab-pane fade" id="info">
        <div class="tab-header">
          <h5><i class="fas fa-info-circle"></i> Thông tin hệ thống</h5>
        </div>
        <div class="info-list">
          <div class="info-item">
            <span class="info-label">Phiên bản</span>
            <span class="info-value">v1.0.0</span>
          </div>
          <div class="info-item">
            <span class="info-label">Ngày tạo</span>
            <span class="info-value"><?= date('d/m/Y H:i', strtotime($operator['created_at'])) ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Trạng thái</span>
            <span class="info-value"><?= strip_tags($badge[$status] ?? '') ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Tài khoản</span>
            <span class="info-value"><span class="badge bg-success">Hoạt động</span></span>
          </div>
          <div class="info-item">
            <span class="info-label">Hỗ trợ</span>
            <span class="info-value">support@busoperator.com</span>
          </div>
          <div class="info-item">
            <span class="info-label">Hotline</span>
            <span class="info-value">1900-xxxx</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Validate confirm password
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');

    if (newPass && confirmPass) {
      confirmPass.addEventListener('input', function() {
        this.setCustomValidity(newPass.value !== this.value ? 'Mật khẩu không khớp' : '');
      });

      newPass.addEventListener('input', function() {
        if (confirmPass.value) confirmPass.dispatchEvent(new Event('input'));
      });
    }
    
    // Logo preview
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    
    if (logoInput && logoPreview) {
      logoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          // Validate file type
          const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
          if (!allowedTypes.includes(file.type)) {
            alert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)');
            e.target.value = '';
            return;
          }
          
          // Validate file size (5MB)
          if (file.size > 5 * 1024 * 1024) {
            alert('File quá lớn. Kích thước tối đa: 5MB');
            e.target.value = '';
            return;
          }
          
          // Show preview
          const reader = new FileReader();
          reader.onload = function(e) {
            logoPreview.innerHTML = '<img src="' + e.target.result + '" alt="Logo preview" id="logoPreviewImg">';
          };
          reader.readAsDataURL(file);
        }
      });
    }
  </script>
</body>
</html>