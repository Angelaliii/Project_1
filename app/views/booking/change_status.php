<?php include APPROOT . '/views/layouts/main.php'; ?>

<div class="container mt-4">
    <h1>更改預約狀態</h1>
    
    <?php if (!empty($data['errors'])) : ?>
        <div class="alert alert-danger">
            <?php foreach ($data['errors'] as $error) : ?>
                <div><?= $error ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">預約詳情</h5>
            <p class="card-text">
                <strong>ID：</strong> <?= $data['booking']['booking_ID'] ?><br>
                <strong>用戶：</strong> <?= htmlspecialchars($data['booking']['user_name']) ?><br>
                <strong>教室：</strong> <?= htmlspecialchars($data['booking']['classroom_name']) ?><br>
                <strong>建築：</strong> <?= htmlspecialchars($data['booking']['building']) ?><br>
                <strong>房號：</strong> <?= htmlspecialchars($data['booking']['room']) ?><br>
                <strong>開始時間：</strong> <?= formatDateTime($data['booking']['start_datetime']) ?><br>
                <strong>結束時間：</strong> <?= formatDateTime($data['booking']['end_datetime']) ?><br>
                <strong>目前狀態：</strong> 
                <span class="badge bg-<?= getStatusColor($data['booking']['status']) ?>">
                    <?= getStatusText($data['booking']['status']) ?>
                </span><br>
                <?php if (!empty($data['booking']['purpose'])) : ?>
                    <strong>用途：</strong> <?= htmlspecialchars($data['booking']['purpose']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <form method="post">
        <div class="mb-3">
            <label for="status" class="form-label">新狀態</label>
            <select class="form-select" id="status" name="status" required>
                <option value="" selected disabled>請選擇狀態</option>
                <option value="available">可用</option>
                <option value="booked">已預約</option>
                <option value="in_use">使用中</option>
                <option value="completed">已完成</option>
                <option value="cancelled">已取消</option>
            </select>
            <?php if (isset($data['errors']['status'])) : ?>
                <div class="text-danger"><?= $data['errors']['status'] ?></div>
            <?php endif; ?>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="/booking/manage" class="btn btn-secondary">返回</a>
            <button type="submit" class="btn btn-primary">確認更改</button>
        </div>
    </form>
</div>

<?php include APPROOT . '/views/components/footer.php'; ?>
