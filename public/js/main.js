// main.js - 網站的主要 JavaScript 功能

/**
 * 當文檔加載完成時執行的初始化函數
 */
document.addEventListener('DOMContentLoaded', function () {
  // 為所有登出按鈕添加事件監聽器
  setupLogoutButtons();

  // 其他初始化工作...
});

/**
 * 設置所有登出按鈕的事件處理
 */
function setupLogoutButtons() {
  // 查找所有登出按鈕或連結
  const logoutLinks = document.querySelectorAll(
    'a[href*="logout.php"], .logout-btn'
  );

  logoutLinks.forEach((link) => {
    link.addEventListener('click', function (e) {
      // 防止默認跳轉行為
      e.preventDefault();

      // 顯示確認對話框
      if (confirm('確定要登出嗎？')) {
        // 如果 window.api 存在且有定義 logout 函數則使用 API
        if (window.api && typeof window.api.logout === 'function') {
          window.api.logout().catch((error) => {
            console.error('登出失敗:', error);
            // 失敗也跳轉到登入頁面
            window.location.href = this.getAttribute('href');
          });
        } else {
          // 如果 API 函數不可用，直接跳轉到登出頁面
          window.location.href = this.getAttribute('href');
        }
      }
    });
  });
}

/**
 * 顯示通知訊息
 * @param {string} message 通知訊息
 * @param {string} type 通知類型 (success, error, warning, info)
 * @param {number} duration 顯示時長，單位毫秒
 */
function showNotification(message, type = 'info', duration = 3000) {
  // 創建通知元素
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;

  // 添加到頁面
  document.body.appendChild(notification);

  // 添加顯示類別以觸發動畫
  setTimeout(() => notification.classList.add('show'), 10);

  // 設定自動消失
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => notification.remove(), 300); // 等待淡出動畫完成
  }, duration);
}
