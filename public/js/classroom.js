// classroom.js - 教室管理頁面的核心 JavaScript 腳本
document.addEventListener('DOMContentLoaded', function () {
  try {
    console.log('初始化教室管理頁面...');

    // 通用功能

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
        window.location.href = 'classroom_management.php';
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
