<?php
// error_log_test.php - 診斷 API 錯誤
header('Content-Type: application/json');

// 記錄所有錯誤到文件
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/php_errors.log');
error_log("開始測試 API 錯誤診斷");

// 捕獲並記錄 API 調用過程
try {
    // 確認數據庫連接
    require_once dirname(__DIR__) . '/config.php';
    
    // 記錄連接訊息
    error_log("嘗試連接數據庫");
    
    try {
        $pdo = connectDB();
        error_log("成功連接到數據庫");
        
        // 測試查詢教室表
        error_log("嘗試查詢 classrooms 表");
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        error_log("classrooms 表中有 " . $count . " 筆記錄");
        
        // 獲取所有教室記錄
        error_log("嘗試獲取所有教室記錄");
        $stmt = $pdo->prepare("SELECT classroom_ID, classroom_name, building, room, created_at, updated_at FROM classrooms ORDER BY classroom_ID");
        $stmt->execute();
        $classrooms = $stmt->fetchAll();
        
        // 返回查詢結果
        echo json_encode([
            'status' => 'success',
            'message' => '診斷完成',
            'classroom_count' => $count,
            'classrooms' => $classrooms
        ]);
        
    } catch (PDOException $e) {
        error_log("數據庫錯誤: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => '數據庫連接或查詢失敗',
            'error' => $e->getMessage()
        ]);
    }
    
} catch (Throwable $e) {
    error_log("嚴重錯誤: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => '發生嚴重錯誤',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
