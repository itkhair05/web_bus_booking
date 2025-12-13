<?php
/**
 * Change Password Page
 * Trang đổi mật khẩu
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
$conn = require_once '../../config/db.php';
require_once '../../core/helpers.php';
require_once '../../core/auth.php';
require_once '../../core/csrf.php';

// Require login
requireLogin();

$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = 'Không tìm thấy thông tin user';
    redirect(appUrl('user/auth/login.php'));
}

$pageTitle = 'Đổi mật khẩu - Bus Booking';
$currentPage = 'profile';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
/* Reuse styles from index.php */
:root {
    --primary: #1E90FF;
    --primary-dark: #1873CC;
    --success: #10B981;
    --danger: #EF4444;
    --warning: #F59E0B;
    --gray-50: #F9FAFB;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --gray-500: #6B7280;
    --gray-600: #4B5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

.profile-page {
    background: var(--gray-50);
    min-height: calc(100vh - 100px);
    padding: 40px 20px;
}

.profile-container {
    max-width: 600px;
    margin: 0 auto;
}

.password-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.card-header {
    text-align: center;
    margin-bottom: 40px;
}

.card-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: white;
}

.card-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 10px;
}

.card-subtitle {
    font-size: 15px;
    color: var(--gray-500);
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 8px;
}

.form-label .required {
    color: var(--danger);
}

.password-input-wrapper {
    position: relative;
}

.form-control {
    width: 100%;
    padding: 12px 45px 12px 16px;
    border: 2px solid var(--gray-300);
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.1);
}

.form-control.error {
    border-color: var(--danger);
}

.form-control.success {
    border-color: var(--success);
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 8px;
}

.toggle-password:hover {
    color: var(--primary);
}

.form-hint {
    font-size: 13px;
    color: var(--gray-500);
    margin-top: 5px;
}

.form-error {
    color: var(--danger);
    font-size: 13px;
    margin-top: 5px;
    display: none;
}

.form-error.show {
    display: block;
}

/* Password Strength */
.password-strength {
    margin-top: 10px;
}

.strength-label {
    font-size: 13px;
    color: var(--gray-600);
    margin-bottom: 5px;
}

.strength-bar {
    height: 8px;
    background: var(--gray-200);
    border-radius: 4px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s;
    border-radius: 4px;
}

.strength-fill.weak {
    width: 33%;
    background: var(--danger);
}

.strength-fill.medium {
    width: 66%;
    background: var(--warning);
}

.strength-fill.strong {
    width: 100%;
    background: var(--success);
}

.strength-text {
    font-size: 12px;
    margin-top: 5px;
    font-weight: 600;
}

.strength-text.weak {
    color: var(--danger);
}

.strength-text.medium {
    color: var(--warning);
}

.strength-text.strong {
    color: var(--success);
}

/* Alert Box */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: start;
    gap: 12px;
}

.alert-warning {
    background: #FEF3C7;
    border-left: 4px solid var(--warning);
    color: #92400E;
}

.alert-icon {
    font-size: 20px;
    margin-top: 2px;
}

.alert-content {
    flex: 1;
    font-size: 14px;
    line-height: 1.6;
}

/* Buttons */
.btn-group {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    flex: 1;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 144, 255, 0.3);
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast */
.toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: white;
    padding: 20px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    max-width: 400px;
}

.toast.success {
    border-left: 4px solid var(--success);
}

.toast.error {
    border-left: 4px solid var(--danger);
}

.toast-icon {
    font-size: 24px;
}

.toast.success .toast-icon {
    color: var(--success);
}

.toast.error .toast-icon {
    color: var(--danger);
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 700;
    margin-bottom: 4px;
}

.toast-message {
    font-size: 14px;
    color: var(--gray-600);
}

@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}

/* Responsive */
@media (max-width: 768px) {
    .password-card {
        padding: 30px 20px;
    }
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<div class="profile-page">
    <div class="profile-container">
        
        <div class="password-card">
            
            <!-- Header -->
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="card-title">Đổi mật khẩu</h1>
                <p class="card-subtitle">Đảm bảo tài khoản của bạn được bảo mật</p>
            </div>
            
            <!-- Alert -->
            <div class="alert alert-warning">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-content">
                    <strong>Lưu ý:</strong> Sau khi đổi mật khẩu thành công, bạn sẽ được tự động đăng xuất để đảm bảo bảo mật.
                </div>
            </div>
            
            <!-- Form -->
            <form id="changePasswordForm" onsubmit="return handleSubmit(event)">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <!-- Old Password -->
                <div class="form-group">
                    <label class="form-label">
                        Mật khẩu hiện tại <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" 
                               name="old_password" 
                               id="oldPassword"
                               class="form-control" 
                               required
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('oldPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-error" id="oldPasswordError"></div>
                </div>
                
                <!-- New Password -->
                <div class="form-group">
                    <label class="form-label">
                        Mật khẩu mới <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" 
                               name="new_password" 
                               id="newPassword"
                               class="form-control" 
                               required
                               minlength="6"
                               autocomplete="new-password"
                               oninput="checkPasswordStrength()">
                        <button type="button" class="toggle-password" onclick="togglePassword('newPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-hint">Ít nhất 6 ký tự</div>
                    
                    <!-- Password Strength -->
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-label">Độ mạnh mật khẩu:</div>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                    
                    <div class="form-error" id="newPasswordError"></div>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label">
                        Xác nhận mật khẩu mới <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" 
                               name="confirm_password" 
                               id="confirmPassword"
                               class="form-control" 
                               required
                               autocomplete="new-password"
                               oninput="checkPasswordMatch()">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-error" id="confirmPasswordError"></div>
                </div>
                
                <!-- Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-key"></i>
                        Đổi mật khẩu
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Quay lại
                    </a>
                </div>
                
            </form>
            
        </div>
        
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.currentTarget.querySelector('i');
    
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

// Check Password Strength
function checkPasswordStrength() {
    const password = document.getElementById('newPassword').value;
    const strengthDiv = document.getElementById('passwordStrength');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    if (password.length === 0) {
        strengthDiv.style.display = 'none';
        return;
    }
    
    strengthDiv.style.display = 'block';
    
    let strength = 0;
    
    // Length
    if (password.length >= 6) strength += 1;
    if (password.length >= 10) strength += 1;
    
    // Has number
    if (/[0-9]/.test(password)) strength += 1;
    
    // Has lowercase and uppercase
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
    
    // Has special character
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    // Apply strength
    strengthFill.className = 'strength-fill';
    strengthText.className = 'strength-text';
    
    if (strength <= 2) {
        strengthFill.classList.add('weak');
        strengthText.classList.add('weak');
        strengthText.textContent = 'Yếu';
    } else if (strength <= 3) {
        strengthFill.classList.add('medium');
        strengthText.classList.add('medium');
        strengthText.textContent = 'Trung bình';
    } else {
        strengthFill.classList.add('strong');
        strengthText.classList.add('strong');
        strengthText.textContent = 'Mạnh';
    }
}

// Check Password Match
function checkPasswordMatch() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const confirmInput = document.getElementById('confirmPassword');
    const errorDiv = document.getElementById('confirmPasswordError');
    
    if (confirmPassword.length === 0) {
        confirmInput.classList.remove('error', 'success');
        errorDiv.classList.remove('show');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        confirmInput.classList.add('error');
        confirmInput.classList.remove('success');
        errorDiv.textContent = 'Mật khẩu không khớp';
        errorDiv.classList.add('show');
    } else {
        confirmInput.classList.remove('error');
        confirmInput.classList.add('success');
        errorDiv.classList.remove('show');
    }
}

// Handle Form Submit
async function handleSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Final validation
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        showToast('error', 'Lỗi!', 'Mật khẩu xác nhận không khớp');
        return false;
    }
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    
    try {
        const response = await fetch('<?php echo appUrl("api/user/change_password.php"); ?>', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Thành công!', 'Mật khẩu đã được thay đổi. Đang đăng xuất...');
            
            // Redirect to login after 2 seconds
            setTimeout(() => {
                window.location.href = '<?php echo appUrl("user/auth/logout.php"); ?>';
            }, 2000);
        } else {
            showToast('error', 'Lỗi!', result.message || 'Không thể đổi mật khẩu');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        showToast('error', 'Lỗi!', 'Có lỗi xảy ra. Vui lòng thử lại');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
    
    return false;
}

// Show Toast
function showToast(type, title, message) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php include '../../includes/footer_user.php'; ?>

