<?php
// test_insert_classrooms.php - 插入測試教室資料
require_once 'config.php';

try {
    $pdo = connectDB();
    
    // 檢查是否已有教室資料
    $stmt = $pdo->query("SELECT COUNT(*) FROM classrooms");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "已經存在 {$count} 筆教室資料，不需要插入測試資料。";
        exit;
    }
    
    // 插入測試教室資料
    $stmt = $pdo->prepare("
        INSERT INTO classrooms (classroom_name, building, room) 
        VALUES 
            (?, ?, ?),
            (?, ?, ?),
            (?, ?, ?),
            (?, ?, ?),
            (?, ?, ?)
    ");
    
    $stmt->execute([
        '資訊科學大樓 301', '資訊科學大樓', '301',
        '資訊科學大樓 302', '資訊科學大樓', '302',
        '資工系館 101', '資工系館', '101',
        '教學大樓 201', '教學大樓', '201',
        '電機資訊學院 501', '電機資訊學院', '501'
    ]);
    
    echo "成功插入 5 筆測試教室資料。";
} catch (Exception $e) {
    echo "插入測試資料時發生錯誤：" . $e->getMessage();
}
?>
