<?php
/**
 * 資料庫連接類別
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * 私有建構函式，防止直接創建物件
     */
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        }
    }
    
    /**
     * 處理資料庫錯誤
     */
    private function handleDatabaseError($e) {
        // 記錄錯誤
        error_log("資料庫錯誤: " . $e->getMessage());
        
        if (APP_DEBUG) {
            die("資料庫連線錯誤: " . $e->getMessage());
        } else {
            die("資料庫連線錯誤，請聯絡管理員");
        }
    }
    
    /**
     * 獲取單例實例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 獲取 PDO 物件
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * 準備查詢
     */
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    /**
     * 執行查詢
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        }
    }
    
    /**
     * 獲取單一結果
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * 獲取所有結果
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * 插入資料
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 更新資料
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setClauses) . " WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
        
        return true;
    }
    
    /**
     * 刪除資料
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
        
        return true;
    }
    
    /**
     * 檢查資料庫是否存在，如果不存在則初始化
     */
    public static function checkDatabase() {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 檢查資料庫是否存在
            $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
            if ($stmt->rowCount() === 0) {
                // 資料庫不存在，創建資料庫
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // 連接到新創建的資料庫
                $pdo->exec("USE `" . DB_NAME . "`");
                
                // 初始化資料表
                self::initializeTables($pdo);
                
                return true;
            }
        } catch (PDOException $e) {
            error_log("檢查資料庫時發生錯誤: " . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * 初始化資料表
     */
    private static function initializeTables($pdo) {
        try {
            // 用戶表
            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `user_id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_name` VARCHAR(255) NOT NULL UNIQUE,
                `mail` VARCHAR(255) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('student', 'teacher', 'admin') NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // 教室表
            $pdo->exec("CREATE TABLE IF NOT EXISTS `classrooms` (
                `classroom_ID` INT AUTO_INCREMENT PRIMARY KEY,
                `classroom_name` VARCHAR(255) NOT NULL,
                `building` VARCHAR(255),
                `room` VARCHAR(255),
                `capacity` INT,
                `picture` LONGBLOB,
                `description` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // 預約表
            $pdo->exec("CREATE TABLE IF NOT EXISTS `bookings` (
                `booking_ID` INT AUTO_INCREMENT PRIMARY KEY,
                `classroom_ID` INT NOT NULL,
                `user_ID` INT NOT NULL,
                `status` ENUM('available', 'booked', 'in_use', 'completed', 'cancelled') NOT NULL DEFAULT 'available',
                `start_datetime` DATETIME NOT NULL,
                `end_datetime` DATETIME NOT NULL,
                `purpose` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`classroom_ID`) REFERENCES `classrooms`(`classroom_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (`user_ID`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX `idx_classroom_start_end` (`classroom_ID`, `start_datetime`, `end_datetime`)
            )");
            
            // 新增預設管理員和教師帳號
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $teacherPassword = password_hash('teacher123', PASSWORD_DEFAULT);
            
            $pdo->exec("INSERT INTO `users` (`user_name`, `mail`, `password`, `role`) 
                VALUES ('admin', 'admin@example.com', '{$adminPassword}', 'admin'),
                       ('teacher', 'teacher@example.com', '{$teacherPassword}', 'teacher')
                ON DUPLICATE KEY UPDATE `user_name`=`user_name`");
            
            return true;
        } catch (PDOException $e) {
            error_log("初始化資料表時發生錯誤: " . $e->getMessage());
            return false;
        }
    }
}
