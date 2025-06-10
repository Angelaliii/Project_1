<?php
/**
 * 頁面底部元件 - 用於添加共用的頁尾內容
 *
 * 使用方法：
 * 在頁面底部 include 此檔案
 *
 * 例如：
 * <?php include_once '../components/footer.php'; ?>
 */

// 設置根路徑（如果未定義）
$rootPath = isset($rootPath) ? $rootPath : '../../';
?>

</div> <!-- 關閉 content-container -->

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="<?php echo $rootPath; ?>public/img/FJU_logo.png" alt="教室租借系統">
                <span>教室租借系統</span>
            </div>
            <div class="footer-links">
                <div class="footer-section">
                    <h3>快速連結</h3>
                    <ul>
                        <li><a href="<?php echo $rootPath; ?>index.php">首頁</a></li>
                        <li><a href="<?php echo $rootPath; ?>app/pages/about.php">關於我們</a></li>
                        <li><a href="<?php echo $rootPath; ?>app/pages/contact.php">聯絡我們</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>幫助</h3>
                    <ul>
                        <li><a href="<?php echo $rootPath; ?>app/pages/faq.php">常見問題</a></li>
                        <li><a href="<?php echo $rootPath; ?>app/pages/terms.php">使用條款</a></li>
                        <li><a href="<?php echo $rootPath; ?>app/pages/privacy.php">隱私政策</a></li>
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
            <p>&copy; <?php echo date('Y'); ?> 教室租借系統. 版權所有。</p>
        </div>
    </div>
</footer>

</body>
</html>
