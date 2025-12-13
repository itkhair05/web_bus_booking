    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-row">
                <div class="footer-col">
                    <h4>Về chúng tôi</h4>
                    <ul>
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="#">Tuyển dụng</a></li>
                        <li><a href="#">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Hỗ trợ</h4>
                    <ul>
                        <li><a href="#">Hướng dẫn đặt vé</a></li>
                        <li><a href="#">Chính sách & Quy định</a></li>
                        <li><a href="#">Câu hỏi thường gặp</a></li>
                        <li><a href="#">Điều khoản sử dụng</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Đối tác</h4>
                    <ul>
                        <li><a href="#">Dành cho nhà xe</a></li>
                        <li><a href="#">Đăng ký hợp tác</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Kết nối với chúng tôi</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                    <div class="contact-info">
                        <p><i class="fas fa-phone"></i> Hotline: 1900 xxxx</p>
                        <p><i class="fas fa-envelope"></i> support@busbooking.com</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> BusBooking. All rights reserved.</p>
                <p>Made with <i class="fas fa-heart text-danger"></i> in Vietnam</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="<?php echo JS_URL; ?>/main.js"></script>
    
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
    /* Footer Styles */
    .footer {
        background: var(--gray-900);
        color: var(--gray-300);
        padding: 60px 20px 20px;
        margin-top: 80px;
    }
    
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .footer-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        margin-bottom: 40px;
    }
    
    .footer-col h4 {
        color: var(--white);
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    .footer-col ul {
        list-style: none;
        padding: 0;
    }
    
    .footer-col ul li {
        margin-bottom: 12px;
    }
    
    .footer-col ul li a {
        color: var(--gray-400);
        transition: var(--transition);
    }
    
    .footer-col ul li a:hover {
        color: var(--primary-color);
        padding-left: 5px;
    }
    
    .social-links {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .social-links a {
        width: 40px;
        height: 40px;
        background: var(--gray-800);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 18px;
        transition: var(--transition);
    }
    
    .social-links a:hover {
        background: var(--primary-color);
        transform: translateY(-3px);
    }
    
    .contact-info p {
        margin-bottom: 10px;
        color: var(--gray-400);
    }
    
    .contact-info i {
        margin-right: 8px;
        color: var(--primary-color);
    }
    
    .footer-bottom {
        border-top: 1px solid var(--gray-800);
        padding-top: 20px;
        text-align: center;
        color: var(--gray-500);
    }
    
    .footer-bottom p {
        margin-bottom: 5px;
    }
    
    /* User dropdown */
    .user-dropdown-toggle {
        background: transparent;
        border: none;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }
    
    .user-dropdown-toggle:hover {
        background: var(--gray-100);
    }
    
    .user-avatar-placeholder {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-color);
        color: var(--white);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 16px;
    }
    
    .user-name {
        font-weight: 500;
        color: var(--gray-800);
    }
    
    .auth-buttons {
        display: flex;
        gap: 12px;
    }
    
    /* Alert positioning */
    .alert {
        position: fixed;
        top: 80px;
        right: 20px;
        max-width: 400px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>
</body>
</html>

