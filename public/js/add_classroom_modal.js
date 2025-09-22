// add_classroom_modal.js - 新增教室模態框的 JavaScript 功能

document.addEventListener('DOMContentLoaded', function () {
  try {
    console.log('初始化新增教室模態框...');

    // 初始化模態框
    let addModal;

    const addModalEl = document.getElementById('addClassroomModal');
    if (addModalEl) {
      addModal = new bootstrap.Modal(addModalEl);
      console.log('新增教室模態框初始化成功');
    } else {
      console.error('找不到新增教室模態框元素');
    }

    // 新增教室按鈕事件
    const openModalBtn = document.getElementById('openAddClassroomBtn');
    if (openModalBtn) {
      openModalBtn.addEventListener('click', function () {
        if (addModal) {
          addModal.show();
          console.log('顯示新增教室模態框');
        } else {
          console.error('無法顯示新增教室模態框：模態框未初始化');
        }
      });
    }

    // 學生權限開關事件 - 新增模式
    const roleStudentElem = document.getElementById('role-student');
    if (roleStudentElem) {
      roleStudentElem.addEventListener('change', function () {
        const statusElem = document.getElementById('add_student_status');
        if (statusElem) {
          statusElem.textContent = this.checked ? '開啟' : '關閉';
        }
      });
    }
  } catch (error) {
    console.error('初始化新增教室模態框時發生錯誤:', error);
  }
});
