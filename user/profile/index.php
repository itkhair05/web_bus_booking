<?php
/**
 * User Profile Page
 * Xem và chỉnh sửa thông tin cá nhân
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

// Get booking statistics
$userId = getCurrentUserId();
$stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$pageTitle = 'Tài khoản của tôi - Bus Booking';
$currentPage = 'profile';
?>

<?php include '../../includes/header_user.php'; ?>

<style>
/* Profile Page Styles */
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
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
}

/* Sidebar */
.profile-sidebar {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.profile-avatar {
    text-align: center;
    margin-bottom: 25px;
}

.avatar-wrapper {
    width: 120px;
    height: 120px;
    margin: 0 auto 15px;
    position: relative;
}

.avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--gray-200);
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    color: var(--primary);
}

.avatar-upload {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    border: 3px solid white;
    transition: all 0.3s;
}

.avatar-upload:hover {
    background: var(--primary-dark);
    transform: scale(1.1);
}

.avatar-upload input {
    display: none;
}

.profile-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 5px;
}

.profile-email {
    font-size: 14px;
    color: var(--gray-500);
}

.profile-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-menu li {
    margin-bottom: 5px;
}

.profile-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: var(--gray-700);
    font-weight: 500;
    transition: all 0.3s;
}

.profile-menu a:hover {
    background: var(--gray-100);
    color: var(--primary);
}

.profile-menu a.active {
    background: var(--primary);
    color: white;
}

.profile-menu a i {
    width: 20px;
    text-align: center;
}

/* Main Content */
.profile-main {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.section-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--gray-200);
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.section-subtitle {
    font-size: 14px;
    color: var(--gray-500);
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 25px;
    border-radius: 12px;
    color: white;
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 10px;
    opacity: 0.9;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

/* Profile Info */
.profile-info {
    display: none;
}

.profile-info.active {
    display: block;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.info-item {
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
    border-left: 4px solid var(--primary);
}

.info-label {
    font-size: 13px;
    color: var(--gray-500);
    margin-bottom: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    color: var(--gray-900);
    font-weight: 600;
}

/* Edit Form */
.edit-form {
    display: none;
}

.edit-form.active {
    display: block;
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

.form-control {
    width: 100%;
    padding: 12px 16px;
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

.form-error {
    color: var(--danger);
    font-size: 13px;
    margin-top: 5px;
    display: none;
}

.form-error.show {
    display: block;
}

.form-hint {
    font-size: 13px;
    color: var(--gray-500);
    margin-top: 5px;
}

/* Buttons */
.btn-group {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
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

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #DC2626;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast Notification */
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
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .profile-sidebar {
        position: relative;
        top: 0;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="profile-page">
    <div class="profile-container">
        
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <div class="avatar-wrapper">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo appUrl($user['avatar']); ?>" alt="Avatar" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-img">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <label class="avatar-upload" title="Đổi ảnh đại diện">
                        <i class="fas fa-camera"></i>
                        <input type="file" id="avatarInput" accept="image/*">
                    </label>
                </div>
                
                <div class="profile-name"><?php echo e($user['name']); ?></div>
                <div class="profile-email"><?php echo e($user['email']); ?></div>
            </div>
            
            <ul class="profile-menu">
                <li>
                    <a href="#" class="active" data-section="info">
                        <i class="fas fa-user"></i>
                        Thông tin cá nhân
                    </a>
                </li>
                <li>
                    <a href="change_password.php">
                        <i class="fas fa-lock"></i>
                        Đổi mật khẩu
                    </a>
                </li>
                <li>
                    <a href="<?php echo appUrl('user/tickets/my_tickets.php'); ?>">
                        <i class="fas fa-ticket-alt"></i>
                        Vé của tôi
                    </a>
                </li>
                <li>
                    <a href="<?php echo appUrl('user/auth/logout.php'); ?>" class="text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Đăng xuất
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="profile-main">
            
            <!-- Section Header -->
            <div class="section-header">
                <h1 class="section-title">Thông tin cá nhân</h1>
                <p class="section-subtitle">Quản lý thông tin tài khoản của bạn</p>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                    <div class="stat-label">Tổng số vé đã đặt</div>
                </div>
                
            </div>
            
            <!-- Profile Info (View Mode) -->
            <div class="profile-info active" id="profileInfo">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Họ và tên</div>
                        <div class="info-value"><?php echo e($user['name']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo e($user['email']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Số điện thoại</div>
                        <div class="info-value"><?php echo e($user['phone'] ?? 'Chưa cập nhật'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Địa chỉ</div>
                        <div class="info-value"><?php echo e($user['address'] ?? 'Chưa cập nhật'); ?></div>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="toggleEditMode()">
                        <i class="fas fa-edit"></i>
                        Chỉnh sửa thông tin
                    </button>
                </div>
            </div>
            
            <!-- Edit Form -->
            <div class="edit-form" id="editForm">
                <form id="profileForm" onsubmit="return handleSubmit(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">
                            Họ và tên <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               class="form-control" 
                               value="<?php echo e($user['name']); ?>"
                               required
                               minlength="2">
                        <div class="form-error" id="nameError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Email <span class="required">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo e($user['email']); ?>"
                               required>
                        <div class="form-hint">Email dùng để đăng nhập</div>
                        <div class="form-error" id="emailError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" 
                               name="phone" 
                               class="form-control" 
                               value="<?php echo e($user['phone'] ?? ''); ?>"
                               pattern="[0-9]{10,11}">
                        <div class="form-hint">10-11 chữ số</div>
                        <div class="form-error" id="phoneError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Địa chỉ</label>
                        <textarea name="address" 
                                  class="form-control" 
                                  rows="3"><?php echo e($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save"></i>
                            Lưu thay đổi
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleEditMode()">
                            <i class="fas fa-times"></i>
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
        
    </div>
</div>

<script>
// Toggle Edit Mode
function toggleEditMode() {
    const infoSection = document.getElementById('profileInfo');
    const editSection = document.getElementById('editForm');
    
    infoSection.classList.toggle('active');
    editSection.classList.toggle('active');
}

// Handle Form Submit
async function handleSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.innerHTML;
    
    // Disable button
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    
    try {
        const response = await fetch('<?php echo appUrl("api/user/update_profile.php"); ?>', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Thành công!', 'Thông tin đã được cập nhật');
            
            // Update UI with new data
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast('error', 'Lỗi!', result.message || 'Không thể cập nhật thông tin');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    } catch (error) {
        showToast('error', 'Lỗi!', 'Có lỗi xảy ra. Vui lòng thử lại');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

// Avatar Upload
document.getElementById('avatarInput')?.addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate
    if (!file.type.match('image.*')) {
        showToast('error', 'Lỗi!', 'Vui lòng chọn file ảnh');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        showToast('error', 'Lỗi!', 'Ảnh không được vượt quá 2MB');
        return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
    
    try {
        const response = await fetch('<?php echo appUrl("api/user/upload_avatar.php"); ?>', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Thành công!', 'Ảnh đại diện đã được cập nhật');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', 'Lỗi!', result.message || 'Không thể upload ảnh');
        }
    } catch (error) {
        showToast('error', 'Lỗi!', 'Có lỗi xảy ra khi upload ảnh');
    }
});

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

