<?php
/**
 * Email Test Page - Admin Only
 * Trang kiểm tra cấu hình email
 */

require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../core/helpers.php';
require_once '../core/auth.php';
require_once '../core/EmailService.php';

// Check admin access
if (!isAdmin()) {
    redirect(appUrl('user/auth/login.php'));
}

$testResult = null;
$errorMessage = null;
$configStatus = [];

// Check email configuration
$configStatus['smtp_host'] = defined('SMTP_HOST') && !empty(SMTP_HOST) ? SMTP_HOST : 'Chưa cấu hình';
$configStatus['smtp_port'] = defined('SMTP_PORT') ? SMTP_PORT : 'Chưa cấu hình';
$configStatus['smtp_username'] = defined('SMTP_USERNAME') && !empty(SMTP_USERNAME) ? '✅ Đã cấu hình' : '❌ Chưa cấu hình';
$configStatus['smtp_password'] = defined('SMTP_PASSWORD') && !empty(SMTP_PASSWORD) ? '✅ Đã cấu hình' : '❌ Chưa cấu hình';
$configStatus['smtp_from'] = defined('SMTP_FROM_EMAIL') && !empty(SMTP_FROM_EMAIL) ? SMTP_FROM_EMAIL : 'Chưa cấu hình';

// Check PHPMailer
$phpmailerPath = __DIR__ . '/../vendor/autoload.php';
$configStatus['phpmailer'] = file_exists($phpmailerPath) ? '✅ Đã cài đặt' : '❌ Chưa cài đặt (Chạy: composer install)';

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = trim($_POST['email'] ?? '');
    
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Vui lòng nhập email hợp lệ';
    } else {
        try {
            $subject = 'Test Email - BusBooking System';
            $body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #10b981;">✅ Email hoạt động!</h2>
                    <p>Đây là email test từ hệ thống BusBooking.</p>
                    <p>Thời gian gửi: <strong>' . date('H:i:s d/m/Y') . '</strong></p>
                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
                    <p style="color: #6b7280; font-size: 13px;">Email này được gửi tự động. Vui lòng không reply.</p>
                </div>
            ';
            
            $result = EmailService::send($testEmail, $subject, $body);
            
            if ($result) {
                $testResult = [
                    'success' => true,
                    'message' => "Email test đã được gửi đến {$testEmail}. Vui lòng kiểm tra hộp thư (bao gồm spam/junk)."
                ];
            } else {
                $testResult = [
                    'success' => false,
                    'message' => "Không thể gửi email. Kiểm tra logs để biết chi tiết."
                ];
            }
        } catch (Exception $e) {
            $testResult = [
                'success' => false,
                'message' => "Lỗi: " . $e->getMessage()
            ];
        }
    }
}

$pageTitle = 'Kiểm tra Email';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f3f4f6; }
        .test-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .card { border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 16px 16px 0 0 !important; }
        .config-table td { padding: 12px; }
        .config-label { font-weight: 600; color: #374151; }
        .config-value { font-family: monospace; color: #1e40af; }
        .alert { border-radius: 12px; }
        .btn-test { background: linear-gradient(135deg, #10b981, #059669); border: none; }
        .btn-test:hover { background: linear-gradient(135deg, #059669, #047857); }
    </style>
</head>
<body>
    <div class="test-container">
        <a href="<?php echo appUrl('admin/dashboard.php'); ?>" class="btn btn-secondary mb-4">
            <i class="fas fa-arrow-left"></i> Quay lại Dashboard
        </a>
        
        <div class="card mb-4">
            <div class="card-header py-3">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Cấu hình Email hiện tại</h5>
            </div>
            <div class="card-body">
                <table class="table config-table mb-0">
                    <tr>
                        <td class="config-label">SMTP Host:</td>
                        <td class="config-value"><?php echo $configStatus['smtp_host']; ?></td>
                    </tr>
                    <tr>
                        <td class="config-label">SMTP Port:</td>
                        <td class="config-value"><?php echo $configStatus['smtp_port']; ?></td>
                    </tr>
                    <tr>
                        <td class="config-label">SMTP Username:</td>
                        <td class="config-value"><?php echo $configStatus['smtp_username']; ?></td>
                    </tr>
                    <tr>
                        <td class="config-label">SMTP Password:</td>
                        <td class="config-value"><?php echo $configStatus['smtp_password']; ?></td>
                    </tr>
                    <tr>
                        <td class="config-label">From Email:</td>
                        <td class="config-value"><?php echo $configStatus['smtp_from']; ?></td>
                    </tr>
                    <tr>
                        <td class="config-label">PHPMailer:</td>
                        <td class="config-value"><?php echo $configStatus['phpmailer']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($testResult): ?>
            <div class="alert alert-<?php echo $testResult['success'] ? 'success' : 'danger'; ?> mb-4">
                <i class="fas fa-<?php echo $testResult['success'] ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $testResult['message']; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header py-3">
                <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Gửi Email Test</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email nhận test:</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="your-email@gmail.com" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="test_email" class="btn btn-test text-white">
                        <i class="fas fa-paper-plane"></i> Gửi Email Test
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header py-3 bg-warning">
                <h5 class="mb-0"><i class="fas fa-book"></i> Hướng dẫn cấu hình</h5>
            </div>
            <div class="card-body">
                <h6><strong>Bước 1:</strong> Cài đặt PHPMailer</h6>
                <pre class="bg-dark text-light p-3 rounded">cd Bus_Booking
composer install</pre>
                
                <h6 class="mt-4"><strong>Bước 2:</strong> Tạo file .env tại thư mục gốc</h6>
                <pre class="bg-dark text-light p-3 rounded"># Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-16-char-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME="BusBooking System"
SMTP_REPLY_TO=your-email@gmail.com

# Company Info
COMPANY_NAME="BusBooking"
COMPANY_WEBSITE=http://localhost/Bus_Booking
SUPPORT_EMAIL=support@busbooking.com
SUPPORT_PHONE=1900-xxxx</pre>
                
                <h6 class="mt-4"><strong>Bước 3:</strong> Cấu hình Gmail App Password</h6>
                <ol>
                    <li>Truy cập <a href="https://myaccount.google.com/security" target="_blank">Google Security Settings</a></li>
                    <li>Bật <strong>2-Step Verification</strong></li>
                    <li>Vào <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                    <li>Tạo App Password mới → Copy 16 ký tự → Paste vào SMTP_PASSWORD</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Lưu ý:</strong> Nếu không có Composer, tải từ <a href="https://getcomposer.org/download/" target="_blank">getcomposer.org</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

