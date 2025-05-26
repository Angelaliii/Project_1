<?php include APPROOT . '/views/layouts/main.php'; ?>

<div class="container mt-4">
    <h1>預約詳情</h1>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($data['booking']['classroom_name'] ?? '未知教室') ?></h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>預約ID:</strong> <?= $data['booking']['booking_ID'] ?></p>
                    <p class="mb-2"><strong>地點:</strong> <?= htmlspecialchars($data['booking']['building'] ?? '') ?> - <?= htmlspecialchars($data['booking']['room'] ?? '') ?></p>
                    <p class="mb-2"><strong>用戶:</strong> <?= htmlspecialchars($data['booking']['user_name'] ?? '') ?></p>
                    <p class="mb-2">
                        <strong>狀態:</strong> 
                        <span class="badge bg-<?= getStatusColor($data['booking']['status']) ?>">
                            <?= getStatusText($data['booking']['status']) ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong>開始時間:</strong> <?= formatDateTime($data['booking']['start_datetime'] ?? '') ?></p>
                    <p class="mb-2"><strong>結束時間:</strong> <?= formatDateTime($data['booking']['end_datetime'] ?? '') ?></p>
                    <p class="mb-2"><strong>預約時間:</strong> <?= formatDateTime($data['booking']['created_at'] ?? '') ?></p>
                    <?php if (!empty($data['booking']['updated_at'])): ?>
                        <p class="mb-2"><strong>上次更新:</strong> <?= formatDateTime($data['booking']['updated_at']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($data['booking']['purpose'])): ?>
                <div class="mt-3">
                    <h6>用途說明:</h6>
                    <p><?= nl2br(htmlspecialchars($data['booking']['purpose'])) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="/booking" class="btn btn-secondary">返回預約列表</a>
                
                <?php if ($data['booking']['status'] !== 'cancelled' && $data['booking']['status'] !== 'completed'): ?>
                    <?php if ($data['booking']['user_ID'] == $_SESSION['user_id'] || isTeacher() || isAdmin()): ?>
                        <a href="/booking/cancel/<?= $data['booking']['booking_ID'] ?>" class="btn btn-danger">取消預約</a>
                    <?php endif; ?>
                    
                    <?php if (isTeacher() || isAdmin()): ?>
                        <a href="/booking/change_status/<?= $data['booking']['booking_ID'] ?>" class="btn btn-primary">更改狀態</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include APPROOT . '/views/components/footer.php'; ?>
