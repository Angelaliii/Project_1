<?php
// 顯示所有PHP錯誤
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 顯示環境信息
echo "<h1>環境信息</h1>";
echo "<pre>";
echo "PHP 版本: " . PHP_VERSION . "\n";
echo "伺服器軟體: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "文檔根目錄: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "請求 URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "腳本名稱: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "腳本文件: " . __FILE__ . "\n";
echo "</pre>";

// 嘗試包含必要的文件
echo "<h1>嘗試載入核心文件</h1>";
try {
    echo "載入配置文件...<br>";
    require_once dirname(__DIR__) . '/app/config/config.php';
    echo "配置文件載入成功<br>";
    
    echo "載入自動載入器...<br>";
    require_once dirname(__DIR__) . '/app/core/Autoloader.php';
    echo "自動載入器載入成功<br>";
    
    echo "載入 Router 類別...<br>";
    require_once dirname(__DIR__) . '/app/core/Router.php';
    echo "Router 類別載入成功<br>";
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "<br>";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}
?>
