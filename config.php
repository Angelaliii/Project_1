<?php
// config.php - 資料庫連線設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'classroom_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

// 建立資料庫連接
function connectDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("資料庫連線失敗: " . $e->getMessage());
    }
}

// 初始化資料庫結構
function initializeDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 創建資料庫
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // 用戶表
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            department VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // 管理員表
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // 教室表
        $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_name VARCHAR(50) NOT NULL UNIQUE,
            capacity INT NOT NULL,
            location VARCHAR(100) NOT NULL,
            description TEXT,
            facilities TEXT,
            status ENUM('available', 'maintenance') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // 預約表
        $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            room_id INT NOT NULL,
            booking_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            purpose TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        )");
        
        // 權限表
        $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            room_id INT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            UNIQUE KEY unique_permission (user_id, room_id)
        )");
        
        // 插入預設管理員
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO admins (username, password, email, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $adminPassword, 'admin@example.com', '系統管理員']);
        
        echo "資料庫初始化完成！";
    } catch (PDOException $e) {
        die("資料庫初始化失敗: " . $e->getMessage());
    }
}

// 共用函數
function validateLogin($username, $password, $isAdmin = false) {
    try {
        $pdo = connectDB();
        $table = $isAdmin ? 'admins' : 'users';
        $stmt = $pdo->prepare("SELECT id, username, password FROM $table WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    } catch (PDOException $e) {
        die("登入驗證失敗: " . $e->getMessage());
    }
}

function getUserById($userId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        die("獲取用戶信息失敗: " . $e->getMessage());
    }
}

function getAdminById($adminId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        die("獲取管理員信息失敗: " . $e->getMessage());
    }
}

function checkMonthlyBookingLimit($userId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM bookings 
            WHERE user_id = ? 
            AND booking_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
            AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] < 4; // 每月最多預約4次
    } catch (PDOException $e) {
        die("檢查預約限制失敗: " . $e->getMessage());
    }
}

function hasRoomPermission($userId, $roomId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM permissions WHERE user_id = ? AND room_id = ?");
        $stmt->execute([$userId, $roomId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (PDOException $e) {
        die("檢查教室權限失敗: " . $e->getMessage());
    }
}

// 啟動會話（在所有頁面頂部包含此文件）
session_start();
?>