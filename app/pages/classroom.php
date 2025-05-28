<div class="page-header">
    <h1>瀏覽教室</h1>
    <p>查看所有可租借的教室資訊</p>
</div>

<div class="filter-container">
    <form action="<?php echo url('classroom'); ?>" method="get" class="filter-form">
        <div class="filter-item">
            <label for="building">建築物：</label>
            <select id="building" name="building" class="filter-select">
                <option value="">所有建築物</option>
                <?php foreach ($buildings as $b): ?>
                    <option value="<?= htmlspecialchars($b['building']) ?>" <?= isset($_GET['building']) && $_GET['building'] == $b['building'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['building']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">篩選</button>
        <a href="<?php echo url('classroom'); ?>" class="btn btn-secondary">重置</a>
    </form>
</div>

<div class="classrooms-grid">
    <?php if (empty($classrooms)): ?>
        <div class="empty-state">
            <i class="fas fa-school empty-icon"></i>
            <h2>沒有找到教室</h2>
            <p>請嘗試不同的篩選條件</p>
        </div>
    <?php else: ?>
        <?php foreach ($classrooms as $classroom): ?>
            <div class="classroom-card">
                <div class="classroom-image">
                    <?php if (!empty($classroom['picture'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($classroom['picture']) ?>" alt="<?= htmlspecialchars($classroom['classroom_name']) ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="classroom-info">
                    <h3><?= htmlspecialchars($classroom['classroom_name']) ?></h3>
                    <p><i class="fas fa-building"></i> <?= htmlspecialchars($classroom['building']) ?> <?= htmlspecialchars($classroom['room']) ?></p>
                    <div class="classroom-actions">
                        <a href="<?php echo url('booking/create/' . $classroom['classroom_ID']); ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-plus"></i> 預約
                        </a>
                        <a href="<?php echo url('classroom/viewDetails/' . $classroom['classroom_ID']); ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-info-circle"></i> 詳情
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 分頁導覽 -->
<?php if ($totalClassrooms > $pageSize): ?>
<div class="pagination-container">
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?php echo url('classroom?page=' . ($page-1) . ($building ? '&building='.urlencode($building) : '')); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> 上一頁
            </a>
        <?php endif; ?>
        
        <?php 
        // 顯示頁碼
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        // 顯示第一頁
        if ($startPage > 1): ?>
            <a href="<?php echo url('classroom?page=1' . ($building ? '&building='.urlencode($building) : '')); ?>" class="page-link">1</a>
            <?php if ($startPage > 2): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="<?php echo url('classroom?page=' . $i . ($building ? '&building='.urlencode($building) : '')); ?>" 
               class="page-link <?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php 
        // 顯示最後一頁
        if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?php echo url('classroom?page=' . $totalPages . ($building ? '&building='.urlencode($building) : '')); ?>" class="page-link">
                <?= $totalPages ?>
            </a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="<?php echo url('classroom?page=' . ($page+1) . ($building ? '&building='.urlencode($building) : '')); ?>" class="page-link">
                下一頁 <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
