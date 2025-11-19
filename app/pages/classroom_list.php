<?php
session_start();
require_once '../config/database.php';
require_once '../models/ClassroomModel.php';

$model = new ClassroomModel();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

// 篩選參數
$areaFilter = isset($_GET['area']) ? trim((string)$_GET['area']) : 'all';
$typeFilter = isset($_GET['type']) ? trim((string)$_GET['type']) : 'all';
$recordingFilter = isset($_GET['recording']) ? trim((string)$_GET['recording']) : 'all';
$capacityInput = isset($_GET['capacity']) ? trim((string)$_GET['capacity']) : '';

// 取得可選的 area、type 列表
$conn = getDbConnection();
$areas = $conn->query("SELECT DISTINCT area FROM classrooms WHERE area IS NOT NULL AND area != '' ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);
$types = $conn->query("SELECT DISTINCT classroom_type FROM classrooms WHERE classroom_type IS NOT NULL AND classroom_type != '' ORDER BY classroom_type")->fetchAll(PDO::FETCH_COLUMN);

try {
    // 解析容量輸入：支援 10~20、50以上、20以下、>=50、<=20、單一數字
    $capacityMin = null;
    $capacityMax = null;
    if ($capacityInput !== '') {
        if (preg_match('/^(\d+)\s*~\s*(\d+)$/', $capacityInput, $m)) {
            $capacityMin = (int)$m[1];
            $capacityMax = (int)$m[2];
        } elseif (preg_match('/^>=\s*(\d+)$/', $capacityInput, $m) || preg_match('/^(\d+)\s*以上$/u', $capacityInput, $m)) {
            $capacityMin = (int)$m[1];
        } elseif (preg_match('/^<=\s*(\d+)$/', $capacityInput, $m) || preg_match('/^(\d+)\s*以下$/u', $capacityInput, $m)) {
            $capacityMax = (int)$m[1];
        } elseif (preg_match('/^(\d+)$/', $capacityInput, $m)) {
            $capacityMin = (int)$m[1];
            $capacityMax = (int)$m[1];
        }
    }

    $filters = [
        'area' => $areaFilter,
        'classroom_type' => $typeFilter,
        'recording_system' => $recordingFilter,
        'capacity_min' => $capacityMin,
        'capacity_max' => $capacityMax
    ];
    $result = $model->getClassrooms($search, $page, $perPage, $filters);
    $classrooms = $result['classrooms'];
    $total = $result['total'];
    $totalPages = max(1, ceil($total / $perPage));
} catch (Exception $e) {
    $classrooms = [];
    $total = 0;
    $totalPages = 1;
}

$pageTitle = '教室清單';
$pageStyles = ['classroom.css'];
include_once '../components/header.php';
?>
<main class="content-container p-4">
    <div class="mx-auto" style="max-width: 1100px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-door-open"></i> 教室清單</h1>
                <form method="get" class="d-flex flex-wrap gap-2 align-items-center" style="max-width: 820px;">
                    <input type="search" name="search" class="form-control" placeholder="搜尋教室名稱、區域或代碼" value="<?= htmlspecialchars($search) ?>">
                    <select name="area" class="form-select" style="max-width:160px;">
                        <option value="all">全部場域</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $a === $areaFilter ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="form-select" style="max-width:160px;">
                        <option value="all">全部類型</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $t === $typeFilter ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="capacity" class="form-control" style="max-width:140px;" placeholder="人數 例:10~30、50以上" value="<?= htmlspecialchars($capacityInput) ?>">
                    <select name="recording" class="form-select" style="max-width:120px;">
                        <option value="all">不限錄播</option>
                        <option value="yes" <?= $recordingFilter === 'yes' ? 'selected' : '' ?>>有</option>
                        <option value="no" <?= $recordingFilter === 'no' ? 'selected' : '' ?>>無</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit">篩選</button>
                </form>
        </div>

        <div class="row g-3">
            <?php foreach ((array)$classrooms as $c): ?>
                <?php
                    $photos = [];
                    if (!empty($c['classroom_photo'])) {
                        $photos = array_filter(array_map('trim', explode(',', $c['classroom_photo'])));
                    }
                    $thumb = count($photos) ? ($rootPath . 'public/img/classrooms/' . $photos[0]) : ($rootPath . 'public/img/classroom.svg');
                    $equipment = [];
                    if (!empty($c['available_equipment'])) {
                        $equipment = array_filter(array_map('trim', explode(',', $c['available_equipment'])));
                    }
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="ratio ratio-16x9">
                            <img src="<?= htmlspecialchars($thumb) ?>" class="card-img-top" style="object-fit:cover;" alt="教室圖片">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1"><?= htmlspecialchars($c['classroom_name'] ?? ($c['area'] . ' ' . $c['classroom_code'])) ?></h5>
                            <div class="mb-2 text-muted small"><?= htmlspecialchars($c['area'] ?? '') ?> · <?= htmlspecialchars($c['classroom_code'] ?? '') ?></div>
                            <div class="mb-2">
                                <span class="badge bg-light text-dark me-1">容量: <?= htmlspecialchars($c['capacity'] ?? '—') ?></span>
                                <?php if (!empty($c['recording_system'])): ?>
                                    <span class="badge bg-warning text-dark me-1">錄播可用</span>
                                <?php endif; ?>
                                <?php if (!empty($c['classroom_type'])): ?>
                                    <span class="badge bg-secondary text-white"><?= htmlspecialchars($c['classroom_type']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($equipment)): ?>
                                <div class="mb-2">
                                    <?php foreach ($equipment as $eq): ?>
                                        <span class="badge bg-info text-dark me-1"><?= htmlspecialchars($eq) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-auto d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="classroom_detail.php?id=<?= urlencode($c['classroom_ID']) ?>">查看詳情</a>
                                <?php if (is_admin()): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="classroom_detail.php?id=<?= urlencode($c['classroom_ID']) ?>#admin-edit">編輯</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&area=<?= urlencode($areaFilter) ?>&type=<?= urlencode($typeFilter) ?>&recording=<?= urlencode($recordingFilter) ?>"><?= $i ?></a>
                            </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../components/footer.php'; ?>
