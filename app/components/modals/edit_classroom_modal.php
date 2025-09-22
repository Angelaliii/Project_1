<?php
// 編輯教室的模態視窗組件
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
                    <input type="hidden" id="edit_classroom_id" name="classroom_id">
                    <div class="mb-3">
                        <label for="edit_classroom_name" class="form-label">教室名稱 <span class="text-danger">*</span></label>
                        <input type="text" id="edit_classroom_name" name="classroom_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_building" class="form-label">樓宇 <span class="text-danger">*</span></label>
                        <input type="text" id="edit_building" name="building" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_room" class="form-label">房間號碼</label>
                        <input type="text" id="edit_room" name="room" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit_permissions" class="form-label">租借權限設置</label>
                        <div class="d-flex align-items-center mb-2">
                            <span class="me-3">學生租借權限：</span>
                            <div class="form-check form-switch d-flex align-items-center">
                                <input class="form-check-input me-2" type="checkbox" id="edit_perm_student" name="allowed_roles[]" value="student" style="width: 3em; height: 1.5em;">
                                <label class="form-check-label" for="edit_perm_student" id="student_status">關閉</label>
                            </div>
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