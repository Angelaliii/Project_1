<?php
// config.php - 資料庫連線設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'rent_classroom');
define('DB_USER', 'root');
define('DB_PASS', '');

// 建立資料庫連接
function connectDB() {
    try {
        // 檢查資料庫是否存在，不存在則創建
        $pdoTest = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 檢查資料庫是否存在
        $stmt = $pdoTest->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
        if ($stmt->rowCount() === 0) {
            // 資料庫不存在，初始化資料庫
            error_log("資料庫 " . DB_NAME . " 不存在，嘗試初始化");
            initializeDB();
            error_log("資料庫初始化完成");
        }
        
        // 連接到資料庫
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // 防止 SQL 注入
        
        // 測試連接是否成功
        $pdo->query("SELECT 1");
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("資料庫連線失敗: " . $e->getMessage() . " 在文件 " . $e->getFile() . " 行 " . $e->getLine());
        // 不要使用 die，讓調用者捕捉異常
        throw new PDOException("資料庫連線失敗: " . $e->getMessage(), (int)$e->getCode());
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
        
        // 用戶表 - 移除管理員角色
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(255) NOT NULL UNIQUE,
            mail VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('student', 'teacher') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // 教室表
        $pdo->exec("CREATE TABLE IF NOT EXISTS classrooms (
            classroom_ID INT AUTO_INCREMENT PRIMARY KEY,
            classroom_name VARCHAR(255) NOT NULL,
            building VARCHAR(255),
            room VARCHAR(255),
            picture BLOB,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // 預約表
        $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
            booking_ID INT AUTO_INCREMENT PRIMARY KEY,
            classroom_ID INT NOT NULL,
            user_ID INT NOT NULL,
            status ENUM('available', 'booked', 'in_use', 'completed', 'cancelled') NOT NULL DEFAULT 'available',
            start_datetime DATETIME NOT NULL,
            end_datetime DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (classroom_ID) REFERENCES classrooms(classroom_ID) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (user_ID) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_classroom_start_end (classroom_ID, start_datetime, end_datetime)
        )");
        
        // 預約時段表
        $pdo->exec("CREATE TABLE IF NOT EXISTS booking_slots (
            slot_ID INT AUTO_INCREMENT PRIMARY KEY,
            booking_ID INT NOT NULL,
            date DATE NOT NULL,
            hour TINYINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_ID) REFERENCES bookings(booking_ID) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        
        // 預設使用者
        $teacherUsername = 'teacher';
        $teacherPassword = password_hash('teacher123', PASSWORD_DEFAULT);
        $teacherEmail = 'teacher@example.com';
        
        // 使用預處理語句插入教師用戶
        $stmt = $pdo->prepare("INSERT INTO users (user_name, mail, password, role) 
                               VALUES (?, ?, ?, 'teacher')
                               ON DUPLICATE KEY UPDATE user_name = user_name");
        $stmt->execute([$teacherUsername, $teacherEmail, $teacherPassword]);
        
        echo "資料庫初始化完成！";
    } catch (PDOException $e) {
        error_log("資料庫初始化失敗: " . $e->getMessage());
        die("資料庫初始化失敗: " . $e->getMessage());
    }
}

// 安全驗證功能
function validateLogin($username, $password) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_name = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("登入驗證錯誤: " . $e->getMessage());
        return false;
    }
}

// 過濾和驗證輸入
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 檢查是否已登入
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 檢查是否為教師
function isTeacher() {
    return isLoggedIn() && $_SESSION['role'] === 'teacher';
}

// 檢查是否為學生
function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

// 檢查用戶是否有特定權限
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    switch ($requiredRole) {
        case 'teacher':
            return isTeacher();
        case 'student':
            return isStudent() || isTeacher(); // 教師也有學生權限
        default:
            return false;
    }
}

// 確保用戶有特定權限，否則重定向
function requirePermission($requiredRole, $redirectUrl = '../login.html') {
    if (!hasPermission($requiredRole)) {
        header("Location: $redirectUrl");
        exit;
    }
}

// 通過ID獲取用戶信息
function getUserById($userId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("獲取用戶信息錯誤: " . $e->getMessage());
        return false;
    }
}

// 啟動會話（在所有頁面頂部包含此文件）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
