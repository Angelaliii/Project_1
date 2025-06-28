<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 引入資料庫配置文件
require_once '../config/database.php';

$conn = getDbConnection(); // 使用 config 中封裝好的 PDO 方法

$stmt = $conn->query("SELECT * FROM classrooms");
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 獲取用戶數據
$username = $_SESSION['username'];
$userRole = '學生';
if ($_SESSION['role'] == 'teacher') {
    $userRole = '教師';
} elseif ($_SESSION['role'] == 'admin') {
    $userRole = '管理員';
}

// 設定頁面標題和樣式
$pageTitle = '瀏覽教室';
$pageStyles = ['classroom.css'];

include_once '../components/header.php';
?>


<main class="content-container">
    <div class="booking-container">
        <h1>教室清單</h1>
            <table>
                <thead>
                    <tr>
                        <th>教室ID</th>
                        <th>教室名稱</th>
                        <th>樓宇</th>
                        <th>房間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classrooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['classroom_ID']) ?></td>
                            <td><?= htmlspecialchars($room['classroom_name']) ?></td>
                            <td><?= htmlspecialchars($room['building']) ?></td>
                            <td><?= htmlspecialchars($room['room']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    </div>
</main>

<?php include_once '../components/footer.php'; ?>
