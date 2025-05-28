// DOM 元素加載完成後執行
document.addEventListener('DOMContentLoaded', function () {
  // 表單驗證
  const forms = document.querySelectorAll('form');
  forms.forEach((form) => {
    form.addEventListener('submit', function (event) {
      const requiredFields = form.querySelectorAll('[required]');
      let isValid = true;

      requiredFields.forEach((field) => {
        // 檢查必填欄位是否為空
        if (!field.value.trim()) {
          isValid = false;
          showValidationError(field, '此欄位為必填');
        } else if (
          field.pattern &&
          !new RegExp(field.pattern).test(field.value)
        ) {
          // 檢查是否符合 pattern 規則
          isValid = false;
          showValidationError(field, field.title || '格式不符合要求');
        } else {
          clearValidationError(field);
        }

        // 密碼確認欄位檢查
        if (field.name === 'confirm_password') {
          const passwordField = form.querySelector('[name="password"]');
          if (passwordField && field.value !== passwordField.value) {
            isValid = false;
            showValidationError(field, '兩次輸入的密碼不一致');
          }
        }
      });

      // 如果有錯誤，阻止表單提交
      if (!isValid) {
        event.preventDefault();
      }
    });
  });

  // 用戶角色選擇
  const roleOptions = document.querySelectorAll(
    '.role-selector input[type="radio"]'
  );
  roleOptions.forEach((option) => {
    option.addEventListener('change', function () {
      const roleSelector = this.closest('.role-selector');
      const options = roleSelector.querySelectorAll('.role-option');

      options.forEach((opt) => {
        const radio = opt.querySelector('input[type="radio"]');
        if (radio.checked) {
          opt.classList.add('active');
        } else {
          opt.classList.remove('active');
        }
      });
    });
  });

  // 密碼顯示/隱藏切換
  const passwordTogglers = document.querySelectorAll('.password-toggle');
  passwordTogglers.forEach((toggler) => {
    toggler.addEventListener('click', function () {
      const passwordField = this.previousElementSibling;

      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        passwordField.type = 'password';
        this.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  });

  // 顯示驗證錯誤
  function showValidationError(field, message) {
    // 清除舊的錯誤訊息
    clearValidationError(field);

    // 創建錯誤訊息元素
    const errorMessage = document.createElement('div');
    errorMessage.className = 'validation-error';
    errorMessage.textContent = message;

    // 添加到欄位後面
    field.classList.add('error');
    field.parentNode.appendChild(errorMessage);
  }

  // 清除驗證錯誤
  function clearValidationError(field) {
    field.classList.remove('error');

    // 移除欄位後面的錯誤訊息
    const errorMessage = field.parentNode.querySelector('.validation-error');
    if (errorMessage) {
      errorMessage.remove();
    }
  }
});

// 管理員儀表板腳本
document.addEventListener('DOMContentLoaded', function () {
  // 側邊欄切換按鈕 (針對小屏幕)
  const sidebarToggler = document.getElementById('sidebarToggler');
  if (sidebarToggler) {
    sidebarToggler.addEventListener('click', function () {
      const sidebar = document.querySelector('.admin-sidebar');
      sidebar.classList.toggle('show');
    });
  }

  // 確認刪除操作
  const deleteButtons = document.querySelectorAll('.delete-btn');
  deleteButtons.forEach((button) => {
    button.addEventListener('click', function (e) {
      if (!confirm('確定要刪除此項目嗎？此操作無法撤銷。')) {
        e.preventDefault();
      }
    });
  });

  // 表格排序
  const sortHeaders = document.querySelectorAll('.sort-header');
  sortHeaders.forEach((header) => {
    header.addEventListener('click', function () {
      const table = this.closest('table');
      const index = Array.from(this.parentNode.children).indexOf(this);
      const rows = Array.from(table.querySelectorAll('tbody tr'));

      // 切換排序方向
      const isAscending = this.classList.contains('asc');

      // 更新表頭排序狀態
      sortHeaders.forEach((h) => h.classList.remove('asc', 'desc'));
      this.classList.add(isAscending ? 'desc' : 'asc');

      // 排序表格行
      rows.sort((a, b) => {
        const cellA = a.children[index].textContent.trim();
        const cellB = b.children[index].textContent.trim();

        if (!isNaN(cellA) && !isNaN(cellB)) {
          return isAscending ? cellB - cellA : cellA - cellB;
        } else {
          return isAscending
            ? cellB.localeCompare(cellA, 'zh-TW')
            : cellA.localeCompare(cellB, 'zh-TW');
        }
      });

      // 重新插入排序後的行
      const tbody = table.querySelector('tbody');
      rows.forEach((row) => tbody.appendChild(row));
    });
  });

  // 動態搜索過濾
  const searchInputs = document.querySelectorAll('.search-input');
  searchInputs.forEach((input) => {
    input.addEventListener('input', function () {
      const searchTerm = this.value.toLowerCase();
      const tableRows =
        this.closest('.admin-card').querySelectorAll('tbody tr');

      tableRows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  });

  // 日期選擇器初始化
  const dateInputs = document.querySelectorAll('.date-picker');
  if (dateInputs.length > 0) {
    dateInputs.forEach((input) => {
      input.addEventListener('input', function () {
        this.classList.add('has-value');
      });

      if (input.value) {
        input.classList.add('has-value');
      }
    });
  }

  // 動態表單欄位
  const addFieldButtons = document.querySelectorAll('.add-field');
  addFieldButtons.forEach((button) => {
    button.addEventListener('click', function () {
      const template = this.previousElementSibling.cloneNode(true);
      const inputs = template.querySelectorAll('input, select, textarea');

      inputs.forEach((input) => {
        input.value = '';
        input.name = input.name.replace(/\[\d+\]/, `[${Date.now()}]`);
      });

      this.parentNode.insertBefore(template, this);
    });
  });

  // 動態刪除按鈕
  document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('remove-field')) {
      e.target.closest('.form-group').remove();
    }
  });

  // 表單成功提交後淡出提示
  const alerts = document.querySelectorAll('.admin-alert');
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => {
        alert.remove();
      }, 500);
    }, 5000);
  });
});
