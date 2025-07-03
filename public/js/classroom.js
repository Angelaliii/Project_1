// 新增教室彈出視窗 - 使用Bootstrap Modal
const addModal = new bootstrap.Modal(
  document.getElementById('addClassroomModal'),
  {
    keyboard: false,
  }
);
const editPermissionModal = new bootstrap.Modal(
  document.getElementById('editPermissionModal'),
  {
    keyboard: false,
  }
);
const openModalBtn = document.getElementById('openAddClassroomBtn');

// 檢查元素是否存在再綁定事件
if (openModalBtn) {
  openModalBtn.addEventListener('click', () => {
    addModal.show();
  });
}

// 權限編輯按鈕點擊事件
const editPermissionBtns = document.querySelectorAll('.edit-permission-btn');
if (editPermissionBtns.length > 0) {
  editPermissionBtns.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation(); // 防止事件冒泡到行點擊事件
      const classroomId = btn.getAttribute('data-id');
      const roles = btn.getAttribute('data-roles').split(',');

      // 設置表單值
      document.getElementById('edit_classroom_id').value = classroomId;
      document.getElementById('perm_student').checked =
        roles.includes('student');
      document.getElementById('perm_teacher').checked =
        roles.includes('teacher');
      document.getElementById('perm_admin').checked = roles.includes('admin');

      // 顯示彈窗
      editPermissionModal.show();
    });
  });
}

// 管理員點擊行開啟權限編輯功能
const adminRows = document.querySelectorAll('.admin-row');
if (adminRows.length > 0) {
  adminRows.forEach((row) => {
    row.addEventListener('click', (e) => {
      // 如果點擊的是按鈕，不處理
      if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
        return;
      }

      const classroomId = row.getAttribute('data-id');
      const roles = row.getAttribute('data-roles').split(',');

      // 設置表單值
      document.getElementById('edit_classroom_id').value = classroomId;
      document.getElementById('perm_student').checked =
        roles.includes('student');
      document.getElementById('perm_teacher').checked =
        roles.includes('teacher');
      document.getElementById('perm_admin').checked = roles.includes('admin');

      // 顯示彈窗
      editPermissionModal.show();
    });
  });
}

// 關閉按鈕事件處理 - Bootstrap已處理

// 表單提交前驗證 - 權限更新表單
document
  .querySelector('form[name="update_permissions"]')
  ?.addEventListener('submit', function (e) {
    const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
    if (checkboxes.length === 0) {
      e.preventDefault();
      alert('請至少選擇一個角色');
    }
  });

// 添加點擊行時的視覺反饋
function addClassWithTimeout(element, className, timeout) {
  element.classList.add(className);
  setTimeout(() => {
    element.classList.remove(className);
  }, timeout);
}

// 為管理員行添加點擊效果
document.querySelectorAll('.admin-row').forEach((row) => {
  row.addEventListener('click', function (e) {
    // 如果點擊的不是按鈕，添加點擊效果
    if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
      addClassWithTimeout(this, 'row-clicked', 300);
    }
  });
});

// 刪除教室按鈕事件處理
const deleteButtons = document.querySelectorAll('.delete-classroom-btn');
if (deleteButtons.length > 0) {
  deleteButtons.forEach((btn) => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation(); // 防止事件冒泡
      const classroomId = this.getAttribute('data-id');

      if (
        confirm(
          '確定要刪除這個教室嗎？此操作無法撤銷，若教室已有預約記錄將無法刪除。'
        )
      ) {
        // 建立並提交表單
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'classroom_id';
        input.value = classroomId;

        const submitBtn = document.createElement('input');
        submitBtn.type = 'hidden';
        submitBtn.name = 'delete_classroom';
        submitBtn.value = '1';

        form.appendChild(input);
        form.appendChild(submitBtn);
        document.body.appendChild(form);
        form.submit();
      }
    });
  });
}
