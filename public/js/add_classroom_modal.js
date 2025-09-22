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

      // 附加表單提交事件跟蹤
      const addForm = document.getElementById('add-classroom-form');
      if (addForm) {
        addForm.addEventListener('submit', function (e) {
          console.log('提交新增教室表單');
        });
      } else {
        console.error('找不到新增教室表單');
      }
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
        const statusLabel = this.nextElementSibling;
        if (statusLabel) {
          statusLabel.textContent = this.checked ? '開啟' : '關閉';
        }
      });
    }

    // 監聽表單提交
    const addForm = document.getElementById('add-classroom-form');
    if (addForm) {
      addForm.addEventListener('submit', function (e) {
        e.preventDefault(); // 阻止默認提交
        console.log('正在提交新增教室表單');

        // 將表單提交到當前頁面
        const formData = new FormData(this);

        // 使用 fetch 提交表單
        fetch(window.location.href, {
          method: 'POST',
          body: formData,
        })
          .then((response) => {
            if (response.redirected) {
              window.location.href = response.url;
            } else {
              return response.text();
            }
          })
          .then((html) => {
            if (html) {
              // 刷新頁面
              location.reload();
            }
          })
          .catch((error) => {
            console.error('提交表單出錯:', error);
            alert('提交表單時發生錯誤\n' + error.message);
          });
      });
    }
  } catch (error) {
    console.error('初始化新增教室模態框時發生錯誤:', error);
  }
});
