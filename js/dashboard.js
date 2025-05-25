// dashboard.js - 用於儀表板頁面的腳本

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

  // 確認操作按鈕
  const confirmButtons = document.querySelectorAll('.confirm-action');
  confirmButtons.forEach((button) => {
    button.addEventListener('click', function (e) {
      const message = this.dataset.confirmMessage || '確定要執行此操作嗎？';
      if (!confirm(message)) {
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

  // 表單驗證
  const forms = document.querySelectorAll('form:not(.no-validate)');
  forms.forEach((form) => {
    form.addEventListener('submit', function (event) {
      const requiredFields = form.querySelectorAll('[required]');
      let isValid = true;

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          isValid = false;
          field.classList.add('error');
        } else {
          field.classList.remove('error');
        }
      });

      if (!isValid) {
        event.preventDefault();
        alert('請填寫所有必填欄位');
      }
    });
  });

  // 日期時間選擇器初始化
  const dateTimeInputs = document.querySelectorAll('.datetime-input');
  dateTimeInputs.forEach((input) => {
    if (input.type !== 'datetime-local') {
      // 如果瀏覽器不支援 datetime-local，則添加日期選擇器功能
      input.addEventListener('focus', function () {
        this.type = 'datetime-local';
      });

      input.addEventListener('blur', function () {
        if (!this.value) {
          this.type = 'text';
        }
      });
    }
  });

  // 資料圖表初始化 (如果頁面有圖表)
  if (typeof Chart !== 'undefined') {
    initializeCharts();
  }

  // 警告訊息自動消失
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

// 初始化圖表函數
function initializeCharts() {
  // 預約統計圖表
  const bookingCtx = document.getElementById('bookingChart');
  if (bookingCtx) {
    new Chart(bookingCtx, {
      type: 'bar',
      data: {
        labels: ['週一', '週二', '週三', '週四', '週五', '週六', '週日'],
        datasets: [
          {
            label: '本週預約數',
            data: [12, 19, 8, 15, 10, 3, 0],
            backgroundColor: 'rgba(66, 133, 244, 0.7)',
            borderColor: '#4285f4',
            borderWidth: 1,
          },
        ],
      },
      options: {
        scales: {
          y: {
            beginAtZero: true,
            precision: 0,
          },
        },
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    });
  }

  // 教室使用率圖表
  const usageCtx = document.getElementById('usageChart');
  if (usageCtx) {
    new Chart(usageCtx, {
      type: 'doughnut',
      data: {
        labels: ['已使用', '可用'],
        datasets: [
          {
            data: [70, 30],
            backgroundColor: [
              'rgba(52, 168, 83, 0.7)',
              'rgba(234, 67, 53, 0.7)',
            ],
            borderColor: ['#34a853', '#ea4335'],
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      },
    });
  }
}
