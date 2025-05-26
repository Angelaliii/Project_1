<?php include APPROOT . '/views/layouts/main.php'; ?>

<div class="container mt-4">
    <h1>我的預約</h1>
    
    <!-- 狀態篩選 -->
    <div class="mb-4">
        <form method="get" class="d-flex">
            <select name="status" class="form-select me-2" style="max-width: 200px;">
                <option value="">所有狀態</option>
                <option value="available" <?= $data['status'] === 'available' ? 'selected' : '' ?>>可用</option>
                <option value="booked" <?= $data['status'] === 'booked' ? 'selected' : '' ?>>已預約</option>
                <option value="in_use" <?= $data['status'] === 'in_use' ? 'selected' : '' ?>>使用中</option>
                <option value="completed" <?= $data['status'] === 'completed' ? 'selected' : '' ?>>已完成</option>
                <option value="cancelled" <?= $data['status'] === 'cancelled' ? 'selected' : '' ?>>已取消</option>
            </select>
            <button type="submit" class="btn btn-primary">篩選</button>
        </form>
    </div>
    
    <!-- 預約列表 -->
    <?php if (empty($data['bookings'])) : ?>
        <div class="alert alert-info">您還沒有預約記錄</div>
        <a href="/classroom" class="btn btn-primary">立即預約教室</a>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>教室</th>
                        <th>預約時間</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['bookings'] as $booking) : ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($booking['classroom_name']) ?>
                                <div class="text-muted small"><?= htmlspecialchars($booking['building']) ?> - <?= htmlspecialchars($booking['room']) ?></div>
                            </td>
                            <td>
                                <div><?= formatDateTime($booking['start_datetime'], 'Y-m-d H:i') ?> 至</div>
                                <div><?= formatDateTime($booking['end_datetime'], 'Y-m-d H:i') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-<?= getStatusColor($booking['status']) ?>">
                                    <?= getStatusText($booking['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/booking/viewDetail/<?= $booking['booking_ID'] ?>" class="btn btn-sm btn-outline-primary">詳情</a>
                                    <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') : ?>
                                        <a href="/booking/cancel/<?= $booking['booking_ID'] ?>" class="btn btn-sm btn-outline-danger">取消</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分頁 -->
        <?php if ($data['totalPages'] > 1) : ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $data['totalPages']; $i++) : ?>
                        <li class="page-item <?= $data['page'] === $i ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $data['status'] ? '&status=' . $data['status'] : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include APPROOT . '/views/components/footer.php'; ?>
