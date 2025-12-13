<?php
require_once '../../config/session.php';
$conn = require_once '../../config/db.php';
require_once '../../core/auth.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';

requireLogin();
$userId = getCurrentUserId();

// Mark as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $notifId = (int)$_POST['notification_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notifId, $userId);
        $stmt->execute();
        header('Location: ' . appUrl('user/notifications/index.php'));
        exit;
    }
}

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        header('Location: ' . appUrl('user/notifications/index.php'));
        exit;
    }
}

// Get notifications
$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = "user_id = ?";
$params = [$userId];
$types = "i";

if ($filter === 'unread') {
    $whereClause .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $whereClause .= " AND is_read = 1";
}

// Get total count
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE $whereClause");
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalNotifications = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalNotifications / $limit);

// Get notifications
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Thông báo';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bus Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f3f4f6;
        }
        
        .notifications-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 15px;
        }
        
        .notifications-header {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .notifications-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .filter-tab {
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #6b7280;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .filter-tab:hover {
            color: #2563eb;
            border-color: #2563eb;
        }
        
        .filter-tab.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .notifications-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .notification-item {
            padding: 20px 30px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.3s ease;
            display: flex;
            gap: 15px;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background: #f9fafb;
        }
        
        .notification-item.unread {
            background: #eff6ff;
        }
        
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .notification-icon.success { background: #d1fae5; color: #059669; }
        .notification-icon.warning { background: #fef3c7; color: #d97706; }
        .notification-icon.danger { background: #fee2e2; color: #dc2626; }
        .notification-icon.info { background: #dbeafe; color: #2563eb; }
        .notification-icon.system { background: #e5e7eb; color: #6b7280; }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .notification-message {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .notification-time {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .mark-read-btn {
            background: none;
            border: none;
            color: #2563eb;
            font-size: 12px;
            cursor: pointer;
            padding: 4px 0;
        }
        
        .mark-read-btn:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state p {
            font-size: 16px;
            margin: 0;
        }
        
        .pagination-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php require_once '../../includes/header_user.php'; ?>
    
    <div class="notifications-container">
        <div class="notifications-header">
            <h1><i class="fas fa-bell me-2"></i> Thông báo</h1>
            
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    Tất cả (<?php echo $totalNotifications; ?>)
                </a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                    Chưa đọc
                </a>
                <a href="?filter=read" class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                    Đã đọc
                </a>
            </div>
            
            <?php if ($totalNotifications > 0): ?>
                <div class="action-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="notifications-list">
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>Không có thông báo nào</p>
                </div>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon <?php echo $notif['type']; ?>">
                            <?php
                            $icons = [
                                'success' => 'fa-check-circle',
                                'warning' => 'fa-exclamation-triangle',
                                'danger' => 'fa-exclamation-circle',
                                'info' => 'fa-info-circle',
                                'system' => 'fa-cog'
                            ];
                            $icon = $icons[$notif['type']] ?? 'fa-bell';
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        
                        <div class="notification-content">
                            <div class="notification-title"><?php echo e($notif['title']); ?></div>
                            <div class="notification-message"><?php echo e($notif['message']); ?></div>
                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                <?php 
                                $time = strtotime($notif['created_at']);
                                $diff = time() - $time;
                                
                                if ($diff < 60) {
                                    echo 'Vừa xong';
                                } elseif ($diff < 3600) {
                                    echo floor($diff / 60) . ' phút trước';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . ' giờ trước';
                                } elseif ($diff < 604800) {
                                    echo floor($diff / 86400) . ' ngày trước';
                                } else {
                                    echo date('d/m/Y H:i', $time);
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if (!$notif['is_read']): ?>
                            <div class="notification-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">
                                        Đánh dấu đã đọc
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

