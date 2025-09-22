// profile.js - 個人資料頁面邏輯
document.addEventListener('DOMContentLoaded', function () {
  // ====== 工具函式 ======
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const show = (el, display = 'block') => {
    if (el) el.style.display = display;
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
  editFields.forEach((f) => hide(f));
  hide(saveButton);
  hide(cancelButton);

  // ====== 切換到編輯模式 ======
  if (editButton) {
    editButton.addEventListener('click', function (e) {
      e.preventDefault();
      viewFields.forEach((f) => hide(f));
      editFields.forEach((f) => show(f));
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
      viewFields.forEach((f) => show(f));
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

  // ====== 眼睛按鈕：事件委派（點 <span> 或 <i> 都可） + 鍵盤可操作 ======
  function setIcon(iconEl, show) {
    if (!iconEl) return;
    iconEl.classList.remove('fa', 'fas', 'far', 'fa-eye', 'fa-eye-slash');
    iconEl.classList.add(show ? 'fa-eye-slash' : 'fa-eye');
  }

  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('.toggle-password');
    if (!toggle) return;

    const targetId = toggle.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (!input) return;

    const icon = toggle.querySelector('i');
    const willShow = input.type === 'password';

    input.type = willShow ? 'text' : 'password';
    setIcon(icon, willShow);

    // 讓焦點回到輸入框，提升體驗
    input.focus({ preventScroll: true });
  });

  document.addEventListener('keydown', function (e) {
    const toggle = e.target.closest('.toggle-password');
    if (!toggle) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggle.click();
    }
  });

  // 建議：為每個 toggle 加上 role / tabindex（若 HTML 未加）
  $$('.toggle-password').forEach((el) => {
    if (!el.hasAttribute('role')) el.setAttribute('role', 'button');
    if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
    if (!el.hasAttribute('aria-label'))
      el.setAttribute('aria-label', '顯示/隱藏密碼');
  });

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
