<?php
// 新增教室的模態視窗組件
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
                    <div class="mb-3">
                        <label for="classroom_name" class="form-label">教室名稱 <span class="text-danger">*</span></label>
                        <input type="text" id="classroom_name" name="classroom_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="building" class="form-label">樓宇 <span class="text-danger">*</span></label>
                        <input type="text" id="building" name="building" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="room" class="form-label">房間號碼</label>
                        <input type="text" id="room" name="room" class="form-control">
                    </div>
                    <div class="mb-3">
                        <p class="form-label mb-2">租借權限設置</p>
                        <div class="d-flex align-items-center mb-2">
                            <span class="me-3">學生租借權限：</span>
                            <div class="form-check form-switch d-flex align-items-center">
                                <input class="form-check-input me-2" type="checkbox" name="allowed_roles[]" value="student" checked id="role-student" style="width: 3em; height: 1.5em;">
                                <label class="form-check-label" for="role-student">開啟</label>
                            </div>
                        </div>
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