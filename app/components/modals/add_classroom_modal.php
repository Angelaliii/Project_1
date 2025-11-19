<?php
// 新增教室的模態視窗組件
require_once __DIR__ . '/../../helpers/security.php';
?>
<!-- 新增教室的彈出窗口 -->
<div id="addClassroomModal" class="modal fade" tabindex="-1" aria-labelledby="addClassroomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClassroomModalLabel">新增教室</h5>
                <button type="button" class="btn-close close-modal" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="add-classroom-form" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                    <div class="mb-3">
                        <label for="area" class="form-label">區域 / 樓宇 <span class="text-danger">*</span></label>
                        <input type="text" id="area" name="area" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="classroom_code" class="form-label">教室代碼 <span class="text-danger">*</span></label>
                        <input type="text" id="classroom_code" name="classroom_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">容納人數</label>
                        <input type="number" id="capacity" name="capacity" class="form-control" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="features" class="form-label">設備 / 特性</label>
                        <input type="text" id="features" name="features" class="form-control" placeholder="例如：投影、錄影系統、電腦">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="recording_system" name="recording_system" value="1">
                        <label class="form-check-label" for="recording_system">具備錄影/錄音系統</label>
                    </div>
                    <div class="mb-3">
                        <p class="form-label mb-2">租借權限設置（多選）</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="student" checked id="role-student">
                            <label class="form-check-label" for="role-student">學生</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="teacher" id="role-teacher">
                            <label class="form-check-label" for="role-teacher">教師</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="department" id="role-dept">
                            <label class="form-check-label" for="role-dept">系所 / 單位</label>
                        </div>
                                <label class="form-check-label" for="role-student">開啟</label>
                            </div>
                        </div>
                        <!-- 隱藏的除錯欄位 -->
                        <input type="hidden" name="debug_info" value="表單提交時間: <?= date('Y-m-d H:i:s') ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal" data-bs-dismiss="modal">取消</button>
                        <button type="submit" name="add_classroom" class="btn btn-success">新增</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>