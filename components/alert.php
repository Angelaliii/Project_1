<?php
// components/alert.php - 顯示警告或消息的元件

// 獲取並清除 success 消息
function displaySuccess() {
    if (isset($_SESSION['success'])) {
        $message = $_SESSION['success'];
        unset($_SESSION['success']);
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($message) . '</div>';
    }
    
    // 同時支援 GET 參數
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($_GET['success']) . '</div>';
    }
}

// 獲取並清除 error 消息
function displayError() {
    if (isset($_SESSION['error'])) {
        $message = $_SESSION['error'];
        unset($_SESSION['error']);
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($message) . '</div>';
    }
    
    // 同時支援 GET 參數
    if (isset($_GET['error'])) {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_GET['error']) . '</div>';
    }
}

// 獲取並清除 info 消息
function displayInfo() {
    if (isset($_SESSION['info'])) {
        $message = $_SESSION['info'];
        unset($_SESSION['info']);
        echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($message) . '</div>';
    }
    
    // 同時支援 GET 參數
    if (isset($_GET['info'])) {
        echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($_GET['info']) . '</div>';
    }
}

// 獲取並清除 warning 消息
function displayWarning() {
    if (isset($_SESSION['warning'])) {
        $message = $_SESSION['warning'];
        unset($_SESSION['warning']);
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($message) . '</div>';
    }
    
    // 同時支援 GET 參數
    if (isset($_GET['warning'])) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_GET['warning']) . '</div>';
    }
}

// 顯示所有通知消息
function displayAlerts() {
    displaySuccess();
    displayError();
    displayInfo();
    displayWarning();
}