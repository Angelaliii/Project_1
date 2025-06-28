// profile.js - 處理個人資料頁面的客戶端邏輯
document.addEventListener('DOMContentLoaded', function () {
  // 獲取元素
  const editButton = document.getElementById('edit-profile-btn');
  const saveButton = document.getElementById('save-profile-btn');
  const cancelButton = document.getElementById('cancel-profile-btn');
  const editForm = document.getElementById('profile-edit-form');
  const viewFields = document.querySelectorAll('.profile-field-display');
  const editFields = document.querySelectorAll('.profile-field-edit');

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
});
