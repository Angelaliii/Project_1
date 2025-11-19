<?php
session_start();

require_once '../config/database.php';
require_once '../models/ClassroomModel.php';
require_once '../helpers/security.php';

// 嘗試以資料庫為準取得目前使用者角色（以避免 session 未更新的情況）
$isAdmin = false;
$isTeacher = false;
$currentRoleFromDb = '';
if (isset($_SESSION['user_id'])) {
    require_once '../models/UserModel.php';
    try {
        $userModelForRole = new UserModel();
        $currentUser = $userModelForRole->findById((int)$_SESSION['user_id']);
        $currentRoleFromDb = isset($currentUser['role']) ? strtolower((string)$currentUser['role']) : '';
        $isAdmin = ($currentRoleFromDb === 'admin') || is_admin();
        $isTeacher = ($currentRoleFromDb === 'teacher') || is_teacher();
    } catch (Exception $e) {
        // 無法取得資料庫角色時退回到 session 判斷
        $isAdmin = is_admin();
        $isTeacher = is_teacher();
    }
} else {
    $isAdmin = false;
    $isTeacher = false;
}

$classroomModel = new ClassroomModel();
$message = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: classroom_list.php');
    exit;
}

// 處理圖片上傳（只允許 teacher 或 admin 上傳）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (!($isTeacher || $isAdmin)) {
        $message = '您沒有上傳圖片的權限';
    } else {
        if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
            $message = '安全驗證失敗，請重新操作';
        } elseif (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $message = '請選擇有效的圖片檔案';
        } else {
            $file = $_FILES['photo'];
            $allowed = ['image/jpeg','image/png','image/gif'];
            if (!in_array($file['type'], $allowed)) {
                $message = '只允許上傳 JPEG/PNG/GIF 圖檔';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $message = '檔案大小不可超過 5MB';
            } else {
                // 準備上傳目錄
                $uploadDir = realpath(__DIR__ . '/../../public/img/classrooms');
                if ($uploadDir === false) {
                    // 如果目錄不存在則嘗試建立
                    $uploadDir = __DIR__ . '/../../public/img/classrooms';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $uploadDir = realpath($uploadDir);
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $newName = $safeBase . '_' . uniqid() . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // 將檔名記錄進資料庫（只存檔名）
                    try {
                        if ($classroomModel->addPhoto($id, $newName)) {
                            $message = '圖片上傳並儲存成功';
                        } else {
                            $message = '圖片儲存到資料庫失敗';
                        }
                    } catch (Exception $e) {
                        $message = '資料庫錯誤: ' . $e->getMessage();
                    }
                } else {
                    $message = '檔案移動失敗，請確認伺服器權限';
                }
            }
        }
    }
}

// 處理 admin 編輯儲存
// 處理 admin 編輯儲存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_classroom'])) {
    if (!$isAdmin) {
        $message = '您沒有編輯教室的權限';
    } else {
        if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
            $message = '安全驗證失敗，請重新操作';
        } else {
            $data = [];
            $data['area'] = trim((string)($_POST['area'] ?? ''));
            $data['classroom_code'] = trim((string)($_POST['classroom_code'] ?? ''));
            $data['classroom_type'] = trim((string)($_POST['classroom_type'] ?? ''));
            $data['capacity'] = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
            $data['recording_system'] = isset($_POST['recording_system']) && $_POST['recording_system'] == '1' ? 1 : 0;
            $data['features'] = trim((string)($_POST['features'] ?? ''));
            $data['available_equipment'] = trim((string)($_POST['available_equipment'] ?? ''));
            $data['available_times'] = trim((string)($_POST['available_times'] ?? ''));

            try {
                $ok = $classroomModel->update($id, $data);
                if ($ok) {
                    $message = '教室資料已更新';
                    // 重新讀取教室資料
                    $classroom = $classroomModel->findById($id);
                } else {
                    $message = '更新失敗';
                }
            } catch (Exception $e) {
                $message = '更新發生錯誤: ' . $e->getMessage();
            }
        }
    }
}

// 讀取教室資料與預約（顯示 upcoming）
try {
    $classroom = $classroomModel->findById($id);
    if (!$classroom) {
        $message = '找不到指定教室';
    }
    $bookings = $classroomModel->getClassroomBookings($id, 'upcoming');
} catch (Exception $e) {
    $message = '讀取教室資料時發生錯誤: ' . $e->getMessage();
    $classroom = null;
    $bookings = [];
}

$pageTitle = '教室詳細資訊';
$pageStyles = ['classroom.css', 'booking.css'];
include_once '../components/header.php';
?>
<main class="content-container p-4">
    <div class="mx-auto" style="max-width: 1000px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-door-open"></i> 教室詳細：<?= htmlspecialchars($classroom['classroom_code'] ?? '') ?></h1>
            <div>
                <a href="classroom_list.php" class="btn btn-outline-secondary">回教室清單</a>
                <?php if ($isAdmin): ?>
                    <button id="edit-classroom-btn" class="btn btn-primary ms-2">編輯教室</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                            <p><strong>區域 / 樓宇：</strong> <?= htmlspecialchars($classroom['area'] ?? '') ?></p>
                            <p><strong>教室代碼：</strong> <?= htmlspecialchars($classroom['classroom_code'] ?? '') ?></p>
                            <p><strong>容量：</strong> <?= htmlspecialchars($classroom['capacity'] ?? '未設定') ?></p>
                            <p><strong>教室類型：</strong> <?= htmlspecialchars($classroom['classroom_type'] ?? '') ?></p>
                            <p><strong>錄影設備：</strong> <?= !empty($classroom['recording_system']) ? '有' : '無' ?></p>
                            <p><strong>可借用設備：</strong> <?= htmlspecialchars($classroom['available_equipment'] ?? '') ?></p>
                            <p><strong>設備 / 備註：</strong> <?= nl2br(htmlspecialchars($classroom['features'] ?? $classroom['classroom_notes'] ?? '')) ?></p>
                            <p><strong>可借用時段：</strong> <?= htmlspecialchars($classroom['available_times'] ?? '') ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="classroom-photos">
                            <?php
                            $photos = [];
                            if (!empty($classroom['classroom_photo'])) {
                                $photos = array_filter(array_map('trim', explode(',', $classroom['classroom_photo'])));
                            }
                            if (count($photos) > 0): ?>
                                <div class="row g-2">
                                <?php foreach ($photos as $p): ?>
                                    <div class="col-6">
                                        <a href="<?= htmlspecialchars($rootPath . 'public/img/classrooms/' . $p) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($rootPath . 'public/img/classrooms/' . $p) ?>" alt="教室圖片" class="img-fluid rounded">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($rootPath . 'public/img/classroom.svg') ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($rootPath . 'public/img/classroom.svg') ?>" alt="教室預設圖片" class="img-fluid rounded">
                                </a>
                            <?php endif; ?>

                            <?php if ($isAdmin): ?>
                                <hr>
                                <div class="row">
                                    <div class="col-12">
                                        <form method="post" enctype="multipart/form-data">
                                            <?= csrf_field() ?>
                                            <div class="mb-2">
                                                <label for="photo" class="form-label">上傳教室圖片（JPEG/PNG/GIF，最大5MB）</label>
                                                <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                                            </div>
                                            <button type="submit" name="upload_photo" class="btn btn-primary">上傳圖片</button>
                                        </form>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <form id="admin-edit-form" method="post" style="display:none;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="save_classroom" value="1">
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label">區域 / 樓宇</label>
                                                    <input type="text" name="area" class="form-control" value="<?= htmlspecialchars($classroom['area'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label">教室代碼</label>
                                                    <input type="text" name="classroom_code" class="form-control" value="<?= htmlspecialchars($classroom['classroom_code'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label class="form-label">容量</label>
                                                    <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($classroom['capacity'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label class="form-label">教室類型</label>
                                                    <input type="text" name="classroom_type" class="form-control" value="<?= htmlspecialchars($classroom['classroom_type'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4 mb-2 form-check">
                                                    <input type="checkbox" class="form-check-input" id="recording_system" name="recording_system" value="1" <?= !empty($classroom['recording_system']) ? 'checked' : '' ?>>
                                                    <label for="recording_system" class="form-check-label">有錄影/錄播系統</label>
                                                </div>
                                                <div class="col-12 mb-2">
                                                    <label class="form-label">可借用設備（逗號分隔）</label>
                                                    <input type="text" name="available_equipment" class="form-control" value="<?= htmlspecialchars($classroom['available_equipment'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 mb-2">
                                                    <label class="form-label">可借用時段（例如 08:00-17:30）</label>
                                                    <input type="text" name="available_times" class="form-control" value="<?= htmlspecialchars($classroom['available_times'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 mb-2">
                                                    <label class="form-label">設備 / 備註</label>
                                                    <textarea name="features" class="form-control"><?= htmlspecialchars($classroom['features'] ?? $classroom['classroom_notes'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-success">儲存變更</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h4>未來預約</h4>
        <div class="card">
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                    <p class="text-muted">目前沒有未來預約。</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($bookings as $b): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div><strong><?= htmlspecialchars($b['user_name']) ?> (<?= htmlspecialchars($b['role']) ?>)</strong></div>
                                    <div class="text-muted small"><?= htmlspecialchars($b['purpose']) ?></div>
                                    <div class="small"><?= htmlspecialchars($b['start_datetime']) ?> — <?= htmlspecialchars($b['end_datetime']) ?></div>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <?php if (!empty($b['requires_recording'])): ?>
                                        <span class="badge bg-warning text-dark mb-1" title="使用者要求錄播">錄播需求</span>
                                    <?php endif; ?>
                                    <?php if (!empty($b['requested_equipment'])): ?>
                                        <span class="badge bg-info text-dark mb-1" title="使用者要求設備">設備: <?= htmlspecialchars($b['requested_equipment']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary text-white"><?= htmlspecialchars($b['status']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php include_once '../components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('edit-classroom-btn');
    const form = document.getElementById('admin-edit-form');
    if (btn && form) {
        btn.addEventListener('click', function() {
            // 顯示表單後捲動
            form.style.display = '';
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    // 若 URL 含 #admin-edit，則自動滾動
    if (window.location.hash === '#admin-edit' && form) {
        setTimeout(() => { form.style.display = ''; form.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 250);
    }
});
</script>
