<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="<?php echo url('assets/images/FJU_logo.png'); ?>" alt="<?php echo SITE_NAME; ?>">
                <span><?php echo SITE_NAME; ?></span>
            </div>
            <div class="footer-links">
                <div class="footer-section">
                    <h3>快速連結</h3>
                    <ul>
                        <li><a href="<?php echo url(''); ?>">首頁</a></li>
                        <li><a href="<?php echo url('home/about'); ?>">關於我們</a></li>
                        <li><a href="<?php echo url('home/contact'); ?>">聯絡我們</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>幫助</h3>
                    <ul>
                        <li><a href="<?php echo url('home/faq'); ?>">常見問題</a></li>
                        <li><a href="<?php echo url('home/terms'); ?>">使用條款</a></li>
                        <li><a href="<?php echo url('home/privacy'); ?>">隱私政策</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>聯絡資訊</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 新北市新莊區中正路510號</p>
                    <p><i class="fas fa-envelope"></i> info@example.com</p>
                    <p><i class="fas fa-phone"></i> (02) 2905-2000</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 版權所有。</p>
        </div>
    </div>
</footer>
