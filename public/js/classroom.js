// classroom.js - 教室管理頁面的核心 JavaScript 腳本
document.addEventListener('DOMContentLoaded', function () {
  try {
    console.log('初始化教室管理頁面...');

    // 點擊行時的視覺反饋 (保留通用功能)
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row) => {
      row.addEventListener('click', function () {
        addClassWithTimeout(this, 'table-active', 200);
      });
    });

    // 處理搜尋框清除按鈕功能
    const clearSearchBtn = document.querySelector('.search-clear-btn');
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function () {
        // 添加點擊動畫效果
        this.classList.add('pressed');

        // 重置搜尋欄位
        const searchInput = document.getElementById('auto-search-input');
        if (searchInput) {
          searchInput.value = '';
          // 聚焦到搜尋欄位
          searchInput.focus();
        }

        // 短暫延遲後跳轉，讓使用者能看到動畫效果
        setTimeout(() => {
          window.location.href = 'classroom_management.php';
        }, 150);
      });
    }
  } catch (error) {
    console.error('初始化教室管理頁面時發生錯誤:', error);
  }
});

// 添加點擊行時的視覺反饋
function addClassWithTimeout(element, className, timeout) {
  element.classList.add(className);
  setTimeout(() => {
    element.classList.remove(className);
  }, timeout);
}
