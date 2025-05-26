<?php
/**
 * 輔助函數庫 - 包含多個輔助函數
 */

/**
 * 過濾和清理輸入
 *
 * @param mixed $input 需要清理的輸入
 * @return mixed 清理後的輸入
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * 檢查使用者是否已登入
 *
 * @return bool 是否已登入
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 檢查使用者是否為教師
 *
 * @return bool 是否為教師
 */
function isTeacher() {
    return isLoggedIn() && $_SESSION['role'] === 'teacher';
}

/**
 * 檢查使用者是否為管理員
 *
 * @return bool 是否為管理員
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * 檢查使用者是否為學生
 *
 * @return bool 是否為學生
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

/**
 * 檢查使用者是否有特定權限
 *
 * @param string $requiredRole 需要的角色
 * @return bool 是否有權限
 */
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    switch ($requiredRole) {
        case 'admin':
            return isAdmin();
        case 'teacher':
            return isTeacher() || isAdmin();
        case 'student':
            return isStudent() || isTeacher() || isAdmin();
        default:
            return false;
    }
}

/**
 * 生成隨機令牌
 *
 * @param int $length 令牌長度
 * @return string 生成的令牌
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 格式化日期時間
 *
 * @param string $datetime 日期時間字符串
 * @param string $format 格式模式
 * @return string 格式化後的日期時間
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    $timestamp = strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * 獲取預約狀態的顯示文字
 *
 * @param string $status 狀態代碼
 * @return string 顯示文字
 */
function getStatusText($status) {
    $statusTexts = [
        'available' => '可用',
        'booked' => '已預約',
        'in_use' => '使用中',
        'completed' => '已完成',
        'cancelled' => '已取消'
    ];
    
    return $statusTexts[$status] ?? $status;
}

/**
 * 獲取預約狀態的顯示顏色
 *
 * @param string $status 狀態代碼
 * @return string Bootstrap 顏色類別
 */
function getStatusColor($status) {
    $statusColors = [
        'available' => 'success',
        'booked' => 'primary',
        'in_use' => 'info',
        'completed' => 'secondary',
        'cancelled' => 'danger'
    ];
    
    return $statusColors[$status] ?? 'secondary';
}

/**
 * 獲取當前頁面 URL
 *
 * @return string 當前頁面 URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 獲取資產 URL
 *
 * @param string $path 資產路徑
 * @return string 完整的資產 URL
 */
function asset($path) {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * 獲取 URL
 *
 * @param string $path URL 路徑
 * @return string 完整 URL
 */
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * 顯示驗證錯誤
 *
 * @param array $errors 錯誤數組
 * @param string $field 欄位名稱
 * @return string 錯誤訊息 HTML
 */
function displayError($errors, $field) {
    if (isset($errors[$field])) {
        return '<div class="error-message">' . $errors[$field] . '</div>';
    }
    return '';
}

/**
 * 快閃訊息
 *
 * @param string $name 訊息名稱
 * @param string $message 訊息內容
 * @param string $type 訊息類型 (success, info, warning, danger)
 */
function setFlash($name, $message, $type = 'info') {
    $_SESSION['flash'][$name] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * 顯示快閃訊息
 *
 * @param string $name 訊息名稱
 * @return string 訊息 HTML
 */
function flash($name) {
    if (isset($_SESSION['flash'][$name])) {
        $flash = $_SESSION['flash'][$name];
        unset($_SESSION['flash'][$name]);
        return '<div class="alert alert-' . $flash['type'] . '">' . $flash['message'] . '</div>';
    }
    return '';
}

/**
 * 頁面重定向
 *
 * @param string $url 目標 URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * 將秒數轉換為友好的時間格式
 *
 * @param int $seconds 秒數
 * @return string 格式化後的時間
 */
function formatTimeAgo($seconds) {
    if ($seconds < 60) {
        return "剛剛";
    } else if ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return "{$minutes} 分鐘前";
    } else if ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        return "{$hours} 小時前";
    } else if ($seconds < 2592000) {
        $days = floor($seconds / 86400);
        return "{$days} 天前";
    } else if ($seconds < 31536000) {
        $months = floor($seconds / 2592000);
        return "{$months} 個月前";
    } else {
        $years = floor($seconds / 31536000);
        return "{$years} 年前";
    }
}
