<?php include APPROOT . '/views/layouts/main.php'; ?>

<div class="container mt-4">
    <h1>預約教室：<?= htmlspecialchars($data['classroom']['classroom_name'] ?? '未知教室') ?></h1>
    
    <?php if (!empty($data['errors'])) : ?>
        <div class="alert alert-danger">
            <?php foreach ($data['errors'] as $error) : ?>
                <div><?= $error ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">教室信息</h5>
            <p class="card-text">
                <strong>建築：</strong> <?= htmlspecialchars($data['classroom']['building'] ?? '') ?><br>
                <strong>房號：</strong> <?= htmlspecialchars($data['classroom']['room'] ?? '') ?>
            </p>
        </div>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="start_date" class="form-label">開始日期</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?= htmlspecialchars($data['start_date']) ?>" required>
                <?php if (isset($data['errors']['start_datetime'])) : ?>
                    <div class="text-danger"><?= $data['errors']['start_datetime'] ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="start_time" class="form-label">開始時間</label>
                <input type="time" class="form-control" id="start_time" name="start_time" 
                       value="<?= htmlspecialchars($data['start_time']) ?>" required>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="end_date" class="form-label">結束日期</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?= htmlspecialchars($data['end_date']) ?>" required>
                <?php if (isset($data['errors']['end_datetime'])) : ?>
                    <div class="text-danger"><?= $data['errors']['end_datetime'] ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="end_time" class="form-label">結束時間</label>
                <input type="time" class="form-control" id="end_time" name="end_time" 
                       value="<?= htmlspecialchars($data['end_time']) ?>" required>
            </div>
        </div>
        
        <?php if (isset($data['errors']['datetime'])) : ?>
            <div class="text-danger mb-3"><?= $data['errors']['datetime'] ?></div>
        <?php endif; ?>
        
        <div class="mb-3">
            <label for="purpose" class="form-label">使用目的</label>
            <textarea class="form-control" id="purpose" name="purpose" rows="3"><?= htmlspecialchars($data['purpose'] ?? '') ?></textarea>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="/classroom" class="btn btn-secondary">返回</a>
            <button type="submit" class="btn btn-primary">提交預約</button>
        </div>
    </form>
</div>

<?php include APPROOT . '/views/components/footer.php'; ?>
