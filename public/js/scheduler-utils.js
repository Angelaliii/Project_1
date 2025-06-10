// scheduler-utils.js - 排程系統的工具函數

/**
 * 格式化日期為 YYYY-MM-DD
 * @param {Date} date 日期對象
 * @returns {string} 格式化的日期字符串
 */
function formatDate(date) {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

/**
 * 格式化日期為顯示格式 YYYY/MM/DD (週幾)
 * @param {Date} date 日期對象
 * @returns {string} 格式化的日期字符串
 */
function formatDateDisplay(date) {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const weekdays = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
  const weekday = weekdays[d.getDay()];
  return `${year}/${month}/${day} (${weekday})`;
}

/**
 * 格式化日期為表頭顯示 MM/DD (週幾)
 * @param {Date} date 日期對象
 * @returns {string} 格式化的日期字符串
 */
function formatDateHeader(date) {
  const d = new Date(date);
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const weekdays = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
  const weekday = weekdays[d.getDay()];
  return `${month}/${day} (${weekday})`;
}

/**
 * 格式化日期時間為API格式
 * @param {Date} date 日期對象
 * @returns {string} 格式化的日期時間字符串
 */
function formatDateTimeForAPI(date) {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  const seconds = String(d.getSeconds()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

/**
 * 顯示提示訊息
 * @param {string} type 訊息類型 (success, error, warning, info)
 * @param {string} message 訊息內容
 * @param {number} duration 顯示持續時間（毫秒）
 */
function showAlert(type, message, duration = 3000) {
  // 確保頁面已經載入
  if (!document.body) {
    console.error('頁面DOM尚未完全載入，無法顯示警告');
    console.error(message);
    return;
  }

  let alertOverlay = document.querySelector('.alert-overlay');
  if (!alertOverlay) {
    alertOverlay = document.createElement('div');
    alertOverlay.className = 'alert-overlay';
    document.body.appendChild(alertOverlay);
  }

  const alertBox = document.createElement('div');
  alertBox.className = `alert-box alert-${type}`;
  alertBox.innerHTML = `
        ${message}
        <button class="alert-close">&times;</button>
    `;

  alertOverlay.appendChild(alertBox);

  // 綁定關閉按鈕
  const closeBtn = alertBox.querySelector('.alert-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      alertBox.remove();
    });
  }

  // 自動消失
  setTimeout(() => {
    alertBox.style.opacity = '0';
    setTimeout(() => alertBox.remove(), 300);
  }, duration);
}

/**
 * 顯示/隱藏加載動畫
 * @param {boolean} show 是否顯示
 */
function showLoading(show) {
  let loader = document.querySelector('.scheduler-loading');

  if (show) {
    if (!loader) {
      loader = document.createElement('div');
      loader.className = 'scheduler-loading';
      loader.innerHTML = '<div class="spinner"></div>';
      document.querySelector('.scheduler-grid-container').appendChild(loader);
    }
    loader.style.display = 'flex';
  } else if (loader) {
    loader.style.display = 'none';
  }
}

// 導出函數
window.SchedulerUtils = {
  formatDate,
  formatDateDisplay,
  formatDateHeader,
  formatDateTimeForAPI,
  showAlert,
  showLoading,
};
