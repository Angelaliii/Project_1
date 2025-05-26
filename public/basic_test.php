<?php
// 定義根目錄常量
define('ROOT_PATH', dirname(__DIR__));

// 顯示所有PHP錯誤
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>基本測試</h1>";
echo "<p>如果您看到此消息，那麼基本 PHP 解析正常工作。</p>";

echo "<h2>測試路徑</h2>";
echo "<pre>";
echo "ROOT_PATH: " . ROOT_PATH . "\n";
echo "當前文件: " . __FILE__ . "\n";
echo "</pre>";

echo "<h2>測試配置載入</h2>";
try {
    require_once ROOT_PATH . '/app/config/config.php';
    echo "<p style='color:green'>配置檔案載入成功！</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>錯誤: " . $e->getMessage() . "</p>";
}
?>
