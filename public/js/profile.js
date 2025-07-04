// profile.js - 處理個人資料頁面的客戶端邏輯
document.addEventListener('DOMContentLoaded', function () {
  // 獲取元素
  const editButton = document.getElementById('edit-profile-btn');
  const saveButton = document.getElementById('save-profile-btn');
  const cancelButton = document.getElementById('cancel-profile-btn');
  const editForm = document.getElementById('profile-edit-form');
  const viewFields = document.querySelectorAll('.profile-field-display');
  const editFields = document.querySelectorAll('.profile-field-edit');
  const passwordForm = document.getElementById('change-password-form');

  // 初始化隱藏編輯區域和按鈕，顯示檢視區域
  editFields.forEach((field) => (field.style.display = 'none'));
  if (saveButton) saveButton.style.display = 'none';
  if (cancelButton) cancelButton.style.display = 'none';

  // 切換到編輯模式
  if (editButton) {
    editButton.addEventListener('click', function (e) {
      e.preventDefault();

      // 隱藏檢視區域，顯示編輯區域
      viewFields.forEach((field) => (field.style.display = 'none'));
      editFields.forEach((field) => (field.style.display = 'block'));

      // 隱藏編輯按鈕，顯示保存和取消按鈕
      editButton.style.display = 'none';
      if (saveButton) saveButton.style.display = 'inline-block';
      if (cancelButton) cancelButton.style.display = 'inline-block';
    });
  }

  // 取消編輯
  if (cancelButton) {
    cancelButton.addEventListener('click', function (e) {
      e.preventDefault();

      // 顯示檢視區域，隱藏編輯區域
      viewFields.forEach((field) => (field.style.display = 'block'));
      editFields.forEach((field) => (field.style.display = 'none'));

      // 顯示編輯按鈕，隱藏保存和取消按鈕
      if (editButton) editButton.style.display = 'inline-block';
      if (saveButton) saveButton.style.display = 'none';
      if (cancelButton) cancelButton.style.display = 'none';
    });
  }

  // 處理表單提交
  if (editForm) {
    editForm.addEventListener('submit', function (e) {
      // 驗證表單
      const username = document.getElementById('username').value.trim();
      if (!username) {
        e.preventDefault();
        if (typeof notificationSystem !== 'undefined') {
          notificationSystem.showError('用戶名不能為空');
        } else {
          alert('用戶名不能為空');
        }
      }
    });
  }

  // 處理密碼表單提交
  if (passwordForm) {
    passwordForm.addEventListener('submit', function (e) {
      const currentPassword = document.getElementById('current_password').value;
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      // 驗證密碼
      if (!currentPassword || !newPassword || !confirmPassword) {
        e.preventDefault();
        if (typeof notificationSystem !== 'undefined') {
          notificationSystem.showError('所有密碼欄位都是必填的');
        } else {
          alert('所有密碼欄位都是必填的');
        }
        return;
      }

      // 驗證新密碼格式
      const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
      if (!passwordPattern.test(newPassword)) {
        e.preventDefault();
        if (typeof notificationSystem !== 'undefined') {
          notificationSystem.showError(
            '新密碼必須至少8個字符，且包含大小寫字母和數字'
          );
        } else {
          alert('新密碼必須至少8個字符，且包含大小寫字母和數字');
        }
        return;
      }

      // 檢查密碼是否匹配
      if (newPassword !== confirmPassword) {
        e.preventDefault();
        if (typeof notificationSystem !== 'undefined') {
          notificationSystem.showError('新密碼與確認密碼不一致');
        } else {
          alert('新密碼與確認密碼不一致');
        }
      }
    });
  }
});
