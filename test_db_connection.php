<?php
// test_db_connection.php - 測試數據庫連接
define('DB_HOST', 'localhost');
define('DB_NAME', 'rent_classroom');
define('DB_USER', 'root');
define('DB_PASS', '');

header('Content-Type: text/plain');
echo "嘗試連接到數據庫...\n";

try {
    // 嘗試連接到 MySQL，不指定數據庫
    $pdoBase = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdoBase->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "成功連接到 MySQL 伺服器！\n";
    
    // 檢查數據庫是否存在
    $stmt = $pdoBase->query("SHOW DATABASES LIKE '".DB_NAME."'");
    if ($stmt->rowCount() > 0) {
        echo "找到 '".DB_NAME."' 數據庫！\n";
        
        // 嘗試連接到特定數據庫
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "成功連接到 '".DB_NAME."' 數據庫！\n";
            
            // 檢查必要的表是否存在
            $tables = array('users', 'classrooms', 'bookings', 'booking_slots');
            $existingTables = array();
            
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '".$table."'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                    echo "表 '".$table."' 存在\n";
                } else {
                    echo "表 '".$table."' 不存在！\n";
                }
            }
            
            // 檢查 classrooms 表中的記錄
            if (in_array('classrooms', $existingTables)) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM classrooms");
                $count = $stmt->fetchColumn();
                echo "classrooms 表中有 ".$count." 筆記錄\n";
            }
            
        } catch (PDOException $e) {
            echo "連接到 '".DB_NAME."' 數據庫時出錯: " . $e->getMessage() . "\n";
        }
    } else {
        echo "數據庫 '".DB_NAME."' 不存在！\n";
    }
} catch (PDOException $e) {
    echo "連接錯誤: " . $e->getMessage() . "\n";
}
?>
