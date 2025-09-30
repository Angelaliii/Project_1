// add_classroom_modal.js - 新增教室模態框的 JavaScript 功能

document.addEventListener('DOMContentLoaded', function () {
  try {
    // 初始化模態框
    let addModal;

    const addModalEl = document.getElementById('addClassroomModal');
    if (addModalEl) {
      addModal = new bootstrap.Modal(addModalEl);

      // 附加表單提交事件跟蹤
      const addForm = document.getElementById('add-classroom-form');
      if (!addForm) {
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
        e.preventDefault();

        // 將表單提交到當前頁面
        const formData = new FormData(this);

        // 確保表單包含 add_classroom 欄位（提交按鈕有時不會自動加入）
        if (!formData.has('add_classroom')) {
          formData.append('add_classroom', '1');
        }

        console.log('提交新增教室表單，資料：', Object.fromEntries(formData));

        // 使用 fetch 提交表單，並傳遞 cookie 以維持 session
        fetch(window.location.href, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        })
          .then((response) => {
            console.log('收到回應：', response.status, response.statusText);

            // 檢查回應狀態
            if (!response.ok && !response.redirected) {
              return response.text().then((text) => {
                throw new Error(`伺服器錯誤 (${response.status}): ${text}`);
              });
            }

            // 不管是否重定向，都將結果作為文本處理
            return response.text().then((text) => {
              return {
                text: text,
                redirected: response.redirected,
                url: response.url,
              };
            });
          })
          .then((result) => {
            if (result) {
              console.log('表單提交成功');

              // 關閉模態視窗
              const addModalEl = document.getElementById('addClassroomModal');
              const addModal = bootstrap.Modal.getInstance(addModalEl);
              if (addModal) {
                addModal.hide();
              }

              // 顯示成功通知
              if (window.notificationSystem) {
                window.notificationSystem.showSuccess('教室新增成功！');
              }

              // 如果頁面需要重新載入（例如，顯示新增的教室）
              setTimeout(() => {
                location.reload();
              }, 500);
            }
          })
          .catch((error) => {
            console.error('提交表單出錯:', error);
            if (window.notificationSystem) {
              window.notificationSystem.showError(
                '新增教室時發生錯誤: ' + error.message
              );
            } else {
              alert('新增教室時發生錯誤\n' + error.message);
            }
          });
      });
    }
  } catch (error) {
    console.error('初始化新增教室模態框時發生錯誤:', error);
  }
});
