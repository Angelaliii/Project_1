<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../models/ClassroomModel.php';

$classroomModel = new ClassroomModel();
$message = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $message = '無效的教室 ID';
}

$classroom = null;
$bookings = [];
try {
    if (empty($message)) {
        $classroom = $classroomModel->findById($id);
        if (!$classroom) {
            $message = '找不到指定的教室';
        } else {
            // 取得近期預約（示範：upcoming）
            $bookings = $classroomModel->getClassroomBookings($id, 'upcoming');
        }
    }
} catch (Exception $e) {
    $message = '載入教室資料失敗：' . $e->getMessage();
}

$pageTitle = '教室詳細資料';
$pageStyles = ['classroom.css', 'profile.css'];
include_once '../components/header.php';
?>

<main class="content-container p-4">
    <div class="mx-auto" style="max-width: 1000px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-door-open"></i> 教室詳細</h1>
            <a href="classroom_management.php" class="btn btn-outline-secondary">回到教室列表</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
        <?php else: ?>
            <div class="card mb-3 p-3">
                <div class="row g-3">
                    <div class="col-md-4 d-flex align-items-center justify-content-center">
                        <div class="w-100 text-center">
                            <?php if (!empty($classroom['classroom_photo'])): ?>
                                <img src="<?= htmlspecialchars($classroom['classroom_photo']) ?>" alt="教室照片" class="img-fluid rounded">
                            <?php else: ?>
                                <div class="placeholder bg-light p-5 border rounded">無照片</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h3><?= htmlspecialchars($classroom['classroom_name']) ?> <small class="text-muted">(<?= htmlspecialchars($classroom['classroom_code'] ?? '') ?>)</small></h3>
                        <p class="mb-1"><strong>區域：</strong> <?= htmlspecialchars($classroom['area'] ?? '') ?></p>
                        <p class="mb-1"><strong>容納人數：</strong> <?= htmlspecialchars($classroom['capacity'] ?? '未知') ?></p>
                        <p class="mb-1"><strong>設備/特性：</strong> <?= htmlspecialchars($classroom['features'] ?? '') ?></p>
                        <p class="mb-1"><strong>錄影系統：</strong> <?= (!empty($classroom['recording_system']) ? '有' : '無') ?></p>
                        <p class="mb-1"><strong>教室類型：</strong> <?= htmlspecialchars($classroom['classroom_type'] ?? '') ?></p>
                        <p class="mt-3"><strong>租借權限：</strong> <?= htmlspecialchars($classroom['allowed_roles'] ?? 'student,teacher') ?></p>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="classroom_management.php" class="btn btn-sm btn-primary mt-2">管理/編輯此教室</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card p-3">
                <h5>近期預約</h5>
                <?php if (empty($bookings)): ?>
                    <div class="text-muted">目前沒有近期預約。</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>預約ID</th>
                                    <th>申請人</th>
                                    <th>角色</th>
                                    <th>開始</th>
                                    <th>結束</th>
                                    <th>狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($b['booking_ID']) ?></td>
                                        <td><?= htmlspecialchars($b['user_name']) ?></td>
                                        <td><?= htmlspecialchars($b['role']) ?></td>
                                        <td><?= htmlspecialchars($b['start_datetime']) ?></td>
                                        <td><?= htmlspecialchars($b['end_datetime']) ?></td>
                                        <td><?= htmlspecialchars($b['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include_once '../components/footer.php'; ?>
