// profile.js - 個人資料頁面邏輯
console.log('Profile.js loaded - ' + new Date().toISOString()); // 確認腳本載入

document.addEventListener('DOMContentLoaded', function () {
  // ====== 工具函式 ======
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const show = (el, display = 'block') => {
    if (!el) return;

    if (
      el.classList.contains('profile-field') ||
      el.classList.contains('profile-field-display')
    ) {
      el.style.removeProperty('display');
    } else {
      el.style.display = display;
    }
  };
  const hide = (el) => {
    if (el) el.style.display = 'none';
  };
  const text = (s) => (s == null ? '' : String(s));

  function toastError(msg) {
    if (typeof window.notificationSystem !== 'undefined') {
      window.notificationSystem.showError(msg);
    } else {
      alert(msg);
    }
  }
  // 雖然目前未使用，但保留供未來可能的成功訊息顯示
  function toastSuccess(msg) {
    if (typeof window.notificationSystem !== 'undefined') {
      window.notificationSystem.showSuccess(msg);
    } else {
      alert(msg);
    }
  }

  // ====== 取得主要節點 ======
  const editButton = $('#edit-profile-btn');
  const saveButton = $('#save-profile-btn');
  const cancelButton = $('#cancel-profile-btn');
  const editForm = $('#profile-edit-form');
  const viewFields = $$('.profile-field-display');
  const editFields = $$('.profile-field-edit');
  const passwordForm = $('#change-password-form');

  // 供「取消」時還原
  const initialValues = {
    username: $('#username') ? $('#username').value : '',
  };

  // ====== 初始狀態：檢視模式 ======
  console.log('Initializing profile display mode');

  // 確保所有元素都移除可能的內嵌樣式（例如：從先前頁面載入的內嵌樣式）
  document
    .querySelectorAll('.profile-field, .profile-field-display')
    .forEach((el) => {
      el.style.removeProperty('display');
    });

  // 正常設置初始顯示
  editFields.forEach((f) => hide(f));
  viewFields.forEach((f) => {
    // 移除內嵌樣式，讓 CSS 樣式自動生效
    f.style.removeProperty('display');
  });
  hide(saveButton);
  hide(cancelButton);

  // 設置可見性，確保元素按需顯示
  if (editButton) editButton.style.visibility = 'visible';

  // ====== 切換到編輯模式 ======
  if (editButton) {
    editButton.addEventListener('click', function (e) {
      e.preventDefault();
      // 隱藏檢視欄位
      viewFields.forEach((f) => hide(f));
      // 顯示編輯欄位
      editFields.forEach((f) => {
        // 編輯欄位使用 block 顯示即可
        f.style.display = 'block';
      });
      hide(editButton);
      show(saveButton, 'inline-block');
      show(cancelButton, 'inline-block');
      // 讓游標直接到使用者名稱
      const userInput = $('#username');
      if (userInput) userInput.focus();
    });
  }

  // ====== 取消編輯：還原欄位、回到檢視模式 ======
  if (cancelButton) {
    cancelButton.addEventListener('click', function (e) {
      e.preventDefault();

      // 還原欄位值
      const userInput = $('#username');
      if (userInput) userInput.value = initialValues.username;

      // 顯示檢視區、隱藏編輯區
      viewFields.forEach((f) => {
        // 直接清除內嵌樣式，讓 CSS 樣式生效
        f.style.removeProperty('display');
      });
      editFields.forEach((f) => hide(f));

      show(editButton, 'inline-block');
      hide(saveButton);
      hide(cancelButton);
    });
  }

  // ====== 編輯表單提交驗證 ======
  if (editForm) {
    let submitting = false;
    editForm.addEventListener('submit', function (e) {
      if (submitting) return; // 防重複送出
      const usernameEl = $('#username');
      const username = usernameEl ? usernameEl.value.trim() : '';

      if (!username) {
        e.preventDefault();
        toastError('用戶名不能為空');
        if (usernameEl) usernameEl.focus();
        return;
      }
      if (username.length > 50) {
        e.preventDefault();
        toastError('用戶名長度不可超過 50 個字元');
        if (usernameEl) usernameEl.focus();
        return;
      }
      // 通過後防連點
      submitting = true;
      if (saveButton) {
        saveButton.disabled = true;
        saveButton.classList.add('disabled');
      }
    });
  }

  // ====== 修改密碼表單提交驗證 ======
  if (passwordForm) {
    let submittingPwd = false;
    passwordForm.addEventListener('submit', function (e) {
      if (submittingPwd) return;

      const currentPassword = text($('#current_password')?.value).trim();
      const newPassword = text($('#new_password')?.value).trim();
      const confirmPassword = text($('#confirm_password')?.value).trim();

      if (!currentPassword || !newPassword || !confirmPassword) {
        e.preventDefault();
        toastError('所有密碼欄位都是必填的');
        return;
      }

      // 至少 8 碼，含大小寫與數字
      const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
      if (!passwordPattern.test(newPassword)) {
        e.preventDefault();
        toastError('新密碼必須至少 8 個字元，且包含大小寫字母和數字');
        const el = $('#new_password');
        if (el) el.focus();
        return;
      }

      if (newPassword !== confirmPassword) {
        e.preventDefault();
        toastError('新密碼與確認密碼不一致');
        const el = $('#confirm_password');
        if (el) el.focus();
        return;
      }

      // 通過後防連點
      submittingPwd = true;
      const submitBtn = passwordForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('disabled');
      }
    });
  }
});
