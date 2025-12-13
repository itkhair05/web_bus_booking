<?php
require_once '../config/database.php';
require_once '../includes/session.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // 1) Thử đăng nhập nhà xe (partners) theo email hoặc SĐT, yêu cầu status = approved
        $partnerQuery = "SELECT partner_id, name, email, phone, password, status
                         FROM partners
                         WHERE (email = ? OR phone = ?) AND status = 'approved'";
        $pstmt = $db->prepare($partnerQuery);
        $pstmt->execute([$username, $username]);

        $authenticated = false;

        if ($pstmt->rowCount() > 0) {
            $partner = $pstmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $partner['password'])) {
                $_SESSION['user_type']   = 'partner';
                $_SESSION['operator_id'] = $partner['partner_id'];
                $_SESSION['company_name']= $partner['name'];
                $_SESSION['username']    = !empty($partner['email']) ? $partner['email'] : $partner['phone'];
                header('Location: ../partner/dashboard.php');
                exit();
            }
        }

        // 2) Nếu không phải partner hợp lệ, thử đăng nhập admin trong bảng users
        $adminQuery = "SELECT user_id, name, email, password, role, status
                       FROM users
                       WHERE email = ? AND role = 'admin' AND status = 'active'";
        $ustmt = $db->prepare($adminQuery);
        $ustmt->execute([$username]);
        if ($ustmt->rowCount() > 0) {
            $admin = $ustmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $admin['password'])) {
                $_SESSION['user_type']  = 'admin';
                $_SESSION['admin_id']   = $admin['user_id'];
                $_SESSION['company_name']= 'Admin Panel';
                $_SESSION['username']   = $admin['email'];
                header('Location: ../admin/admin_dashboard.php');
                exit();
            }
        }

        // Nếu đến đây là thất bại
        $error = 'Tài khoản không tồn tại hoặc chưa được duyệt!';
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Nhà xe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(32, 201, 151, 0.4);
        }
        .bus-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-bus bus-icon"></i>
                        <h3>Đăng nhập Nhà xe</h3>
                        <p class="mb-0">Quản lý chuyến đi và doanh thu</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Tên đăng nhập (Email hoặc SĐT)
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Mật khẩu
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Hệ thống bảo mật cao
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
