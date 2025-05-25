        </div> <!-- .content-wrapper -->
        <footer>
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> 教室租借系統 | 版權所有</p>
                <div class="footer-links">
                    <a href="about.php">關於我們</a>
                    <a href="contact.php">聯絡我們</a>
                    <a href="privacy.php">隱私政策</a>
                    <a href="terms.php">使用條款</a>
                </div>
            </div>
        </footer>
        <?php 
        // 判斷當前目錄層級，動態生成正確的腳本路徑
        $scriptPath = '';
        $currentDir = dirname($_SERVER['PHP_SELF']);
        if ($currentDir == '/') {
            $scriptPath = '/js/scripts.js';
        } else if (strpos($currentDir, '/user') !== false || strpos($currentDir, '/admin') !== false) {
            $scriptPath = '../js/scripts.js';
        } else {
            $scriptPath = './js/scripts.js';
        }
        ?>
        <script src="<?php echo $scriptPath; ?>"></script>
    </body>
</html>