<?php
// 編輯教室的模態視窗組件
require_once __DIR__ . '/../../helpers/security.php';
?>
<!-- 編輯教室的彈出窗口 -->
<div id="editClassroomModal" class="modal fade" tabindex="-1" aria-labelledby="editClassroomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClassroomModalLabel">編輯教室資訊</h5>
                <button type="button" class="btn-close close-modal" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" id="edit_classroom_id" name="classroom_id">
                    <div class="mb-3">
                        <label for="edit_classroom_name" class="form-label">教室名稱 <span class="text-danger">*</span></label>
                        <input type="text" id="edit_classroom_name" name="classroom_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_area" class="form-label">區域 / 樓宇 <span class="text-danger">*</span></label>
                        <input type="text" id="edit_area" name="area" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_classroom_code" class="form-label">教室代碼 <span class="text-danger">*</span></label>
                        <input type="text" id="edit_classroom_code" name="classroom_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">容納人數</label>
                        <input type="number" id="edit_capacity" name="capacity" class="form-control" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="edit_features" class="form-label">設備 / 特性</label>
                        <input type="text" id="edit_features" name="features" class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_recording_system" name="recording_system" value="1">
                        <label class="form-check-label" for="edit_recording_system">具備錄影/錄音系統</label>
                    </div>
                    <div class="mb-3">
                        <p class="form-label mb-2">租借權限設置（多選）</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_perm_student" name="allowed_roles[]" value="student">
                            <label class="form-check-label" for="edit_perm_student">學生</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_perm_teacher" name="allowed_roles[]" value="teacher">
                            <label class="form-check-label" for="edit_perm_teacher">教師</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_perm_dept" name="allowed_roles[]" value="department">
                            <label class="form-check-label" for="edit_perm_dept">系所 / 單位</label>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" id="deleteClassroomBtn">刪除教室</button>
                        <div>
                            <button type="button" class="btn btn-secondary close-modal" data-bs-dismiss="modal">取消</button>
                            <button type="submit" name="update_classroom" class="btn btn-success">儲存</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>