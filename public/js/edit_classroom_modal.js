// edit_classroom_modal.js - 編輯教室模態框的 JavaScript 功能

document.addEventListener('DOMContentLoaded', function () {
  try {
    // 初始化模態框
    let editClassroomModal;

    const editClassroomModalEl = document.getElementById('editClassroomModal');
    if (editClassroomModalEl) {
      editClassroomModal = new bootstrap.Modal(editClassroomModalEl);
    } else {
      console.error('找不到編輯教室模態框元素');
    }

    // 學生權限開關事件 - 編輯模式
    const editPermStudentElem = document.getElementById('edit_perm_student');
    if (editPermStudentElem) {
      editPermStudentElem.addEventListener('change', function () {
        const studentStatusElem = document.getElementById('student_status');
        if (studentStatusElem) {
          studentStatusElem.textContent = this.checked ? '開啟' : '關閉';
        }
      });
    }

    // 編輯教室按鈕點擊事件
    const editBtns = document.querySelectorAll('.edit-classroom-btn');
    if (editBtns.length > 0) {
      editBtns.forEach((btn) => {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();

          // 獲取資料
          const classroomId = this.getAttribute('data-id');
          const roles = this.getAttribute('data-roles').split(',');
          const name = this.getAttribute('data-name');
          const building = this.getAttribute('data-building');
          const room = this.getAttribute('data-room');

          // 已取得教室資料

          try {
            // 設置表單值
            document.getElementById('edit_classroom_id').value = classroomId;
            document.getElementById('edit_classroom_name').value = name;
            document.getElementById('edit_building').value = building;
            document.getElementById('edit_room').value = room;

            // 設置學生權限開關
            const studentAllowed = roles.includes('student');
            document.getElementById('edit_perm_student').checked =
              studentAllowed;
            document.getElementById('student_status').textContent =
              studentAllowed ? '開啟' : '關閉';

            // 顯示彈窗
            if (editClassroomModal) {
              editClassroomModal.show();
            } else {
              console.error('無法顯示編輯教室模態框：模態框未初始化');
            }
          } catch (err) {
            console.error('處理編輯操作時發生錯誤：', err);
          }
        });
      });
    } else {
      console.warn('未找到任何編輯教室按鈕');
    }

    // 刪除教室按鈕事件
    const deleteBtn = document.getElementById('deleteClassroomBtn');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        const classroomId = document.getElementById('edit_classroom_id').value;
        const classroomName = document.getElementById(
          'edit_classroom_name'
        ).value;

        if (
          confirm(
            `確定要刪除教室 "${classroomName}" 嗎？\n\n此操作會同時刪除該教室的所有預約記錄。\n\n請再次確認您的操作。`
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
    }

    // 表單提交前驗證 - 教室編輯表單
    const updateForm = document.querySelector('#editClassroomModal form');
    if (updateForm) {
      updateForm.addEventListener('submit', function (e) {
        e.preventDefault(); // 阻止表單的默認提交行為

        // 檢查必填欄位
        const classroomName = document
          .getElementById('edit_classroom_name')
          .value.trim();
        const building = document.getElementById('edit_building').value.trim();

        if (classroomName === '') {
          notificationSystem.showError('教室名稱為必填欄位', '表單驗證失敗');
          return;
        } else if (building === '') {
          notificationSystem.showError('樓宇為必填欄位', '表單驗證失敗');
          return;
        }

        // 使用 fetch API 提交表單
        const formData = new FormData(this);
        console.log('提交編輯教室表單，資料：', Object.fromEntries(formData));

        fetch(window.location.href, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        })
          .then((response) => {
            console.log('收到回應：', response.status, response.statusText);

            if (!response.ok && !response.redirected) {
              return response.text().then((text) => {
                throw new Error(`伺服器錯誤 (${response.status}): ${text}`);
              });
            }

            return response.text().then((text) => {
              return {
                text,
                redirected: response.redirected,
                url: response.url,
              };
            });
          })
          .then((result) => {
            if (result) {
              console.log('表單提交成功');

              // 關閉模態視窗
              const editModalEl = document.getElementById('editClassroomModal');
              const editModal = bootstrap.Modal.getInstance(editModalEl);
              if (editModal) {
                editModal.hide();
              }

              // 顯示成功通知
              if (window.notificationSystem) {
                window.notificationSystem.showSuccess('教室資訊已成功更新！');
              }

              // 重新載入頁面以顯示更新後的資料
              setTimeout(() => {
                location.reload();
              }, 500);
            }
          })
          .catch((error) => {
            console.error('提交表單出錯:', error);
            if (window.notificationSystem) {
              window.notificationSystem.showError(
                '更新教室資訊時發生錯誤: ' + error.message
              );
            } else {
              alert('更新教室資訊時發生錯誤\n' + error.message);
            }
          });
      });
    }
  } catch (error) {
    console.error('初始化編輯教室模態框時發生錯誤:', error);
  }
});
