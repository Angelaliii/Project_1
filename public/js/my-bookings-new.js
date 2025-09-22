/**
 * my-bookings.js - 預約系統前端功能
 */
document.addEventListener('DOMContentLoaded', function () {
  // 初始化
  initViewToggle();
  initDateRangeFilter();
  initSearchFilter();
  initSortControl();
  initCardExpansion();
  initGroupHeaders(); // 保留呼叫但移除收合功能

  // 顯示初始化完成訊息
  console.log('My Bookings initialized');
});

/**
 * 初始化視圖切換功能 - 已移除精簡視圖功能
 */
function initViewToggle() {
  // 已移除視圖切換功能，直接使用卡片視圖
  const bookingList = document.querySelector('.booking-list');
  if (bookingList) {
    bookingList.classList.remove('list-view');
  }

  // 清除本地儲存的視圖模式設置
  localStorage.removeItem('booking-view-mode');
}

/**
 * 初始化日期範圍篩選器
 */
function initDateRangeFilter() {
  const dateRangeSelector = document.getElementById('date-range-selector');
  if (!dateRangeSelector) return;

  // 設置當前日期為自定義日期範圍的默認值
  const today = new Date();
  const formatDateInput = (date) => {
    return date.toISOString().split('T')[0];
  };

  const customStartDate = document.getElementById('custom-start-date');
  const customEndDate = document.getElementById('custom-end-date');

  if (customStartDate && customEndDate) {
    customStartDate.value = formatDateInput(today);

    const nextMonth = new Date(today);
    nextMonth.setMonth(today.getMonth() + 1);
    customEndDate.value = formatDateInput(nextMonth);
  }

  dateRangeSelector.addEventListener('change', function () {
    const range = this.value;
    let startDate, endDate;

    // 計算日期範圍
    switch (range) {
      case 'today':
        startDate = today;
        endDate = today;
        break;
      case 'week':
        // 計算本週的開始（星期日）和結束（星期六）
        const dayOfWeek = today.getDay(); // 0 = 星期日, 6 = 星期六
        startDate = new Date(today);
        startDate.setDate(today.getDate() - dayOfWeek); // 回到本週星期日
        endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6); // 加 6 天到星期六
        break;
      case 'month':
        // 計算本月的開始和結束
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        break;
      case 'custom':
        // 顯示自定義日期選擇器
        document.getElementById('custom-date-range').style.display = 'flex';
        if (customStartDate.value && customEndDate.value) {
          filterBookingsByDate(customStartDate.value, customEndDate.value);
        }
        return;
      default:
        // 全部 - 不進行日期篩選
        filterBookingsByDate();
        document.getElementById('custom-date-range').style.display = 'none';
        return;
    }

    // 如果不是自定義，隱藏自定義日期選擇器
    if (range !== 'custom') {
      document.getElementById('custom-date-range').style.display = 'none';
    }

    // 格式化日期為 YYYY-MM-DD
    const formatDate = (date) => {
      return date.toISOString().split('T')[0];
    };

    filterBookingsByDate(formatDate(startDate), formatDate(endDate));
  });

  // 自定義日期範圍變更處理
  if (customStartDate && customEndDate) {
    [customStartDate, customEndDate].forEach((input) => {
      input.addEventListener('change', function () {
        if (customStartDate.value && customEndDate.value) {
          // 確保選擇的是"自定義"選項
          dateRangeSelector.value = 'custom';
          filterBookingsByDate(customStartDate.value, customEndDate.value);
        }
      });
    });
  }
}

/**
 * 根據日期篩選預約
 * @param {string} startDate - 開始日期 (YYYY-MM-DD)
 * @param {string} endDate - 結束日期 (YYYY-MM-DD)
 */
function filterBookingsByDate(startDate, endDate) {
  const bookingCards = document.querySelectorAll('.booking-card');
  let filteredCount = 0;
  let totalCount = 0;

  bookingCards.forEach((card) => {
    totalCount++;
    const bookingDate = card.dataset.startTime.split(' ')[0]; // 獲取日期部分

    if (!startDate || !endDate) {
      // 沒有指定日期範圍，顯示所有
      card.style.display = '';
      filteredCount++;
      return;
    }

    // 比較日期
    if (bookingDate >= startDate && bookingDate <= endDate) {
      card.style.display = '';
      filteredCount++;
    } else {
      card.style.display = 'none';
    }
  });

  // 更新群組顯示
  updateGroupsVisibility();

  // 顯示篩選結果數量
  if (startDate && endDate && filteredCount < totalCount) {
    // 格式化日期顯示
    const formatDisplayDate = (dateStr) => {
      const date = new Date(dateStr);
      return `${date.getFullYear()}/${(date.getMonth() + 1)
        .toString()
        .padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}`;
    };

    const message = `已篩選顯示 ${filteredCount} 筆預約（${formatDisplayDate(
      startDate
    )} - ${formatDisplayDate(endDate)}）`;
    notificationSystem.showSuccess(message, '日期篩選');
  }
}

/**
 * 初始化搜尋篩選
 */
function initSearchFilter() {
  const searchInput = document.getElementById('booking-search');
  if (!searchInput) return;

  // 添加搜尋圖標和清除按鈕
  const searchContainer = searchInput.closest('.search-box');
  if (searchContainer) {
    // 添加清除按鈕
    const clearButton = document.createElement('button');
    clearButton.innerHTML = '✕';
    clearButton.className = 'search-clear';
    clearButton.style.display = 'none';
    clearButton.setAttribute('aria-label', '清除搜尋');
    searchContainer.appendChild(clearButton);

    // 清除按鈕點擊處理
    clearButton.addEventListener('click', () => {
      searchInput.value = '';
      clearButton.style.display = 'none';
      searchInput.focus();
      // 觸發搜尋處理
      performSearch('');
    });
  }

  // 使用防抖函數減少不必要的搜尋
  let debounceTimeout;
  searchInput.addEventListener('input', function () {
    clearTimeout(debounceTimeout);

    // 控制清除按鈕顯示
    const clearButton = document.querySelector('.search-clear');
    if (clearButton) {
      clearButton.style.display = this.value.length > 0 ? 'block' : 'none';
    }

    debounceTimeout = setTimeout(() => {
      const searchTerm = this.value.toLowerCase().trim();
      performSearch(searchTerm);
    }, 300);
  });

  // 按 ESC 鍵清除搜尋
  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      this.value = '';
      const clearButton = document.querySelector('.search-clear');
      if (clearButton) clearButton.style.display = 'none';
      performSearch('');
    }
  });
}

/**
 * 執行搜尋
 * @param {string} searchTerm - 搜尋關鍵字
 */
function performSearch(searchTerm) {
  const bookingCards = document.querySelectorAll('.booking-card');
  let matchCount = 0;
  let totalCount = bookingCards.length;

  bookingCards.forEach((card) => {
    // 檢查各種可能匹配的元素
    const classroomName = card
      .querySelector('.booking-title')
      .textContent.toLowerCase();
    const purpose =
      card
        .querySelector('.info-row:nth-child(4) .info-value')
        ?.textContent.toLowerCase() || '';
    const location =
      card
        .querySelector('.info-row:nth-child(1) .info-value')
        ?.textContent.toLowerCase() || '';

    // 組合所有可搜尋文本
    const cardText = `${classroomName} ${purpose} ${location}`.toLowerCase();

    // 檢查是否匹配搜尋詞
    if (cardText.includes(searchTerm) || searchTerm === '') {
      card.style.display = '';
      card.classList.add('search-match');
      matchCount++;
    } else {
      card.style.display = 'none';
      card.classList.remove('search-match');
    }
  });

  // 更新群組顯示
  updateGroupsVisibility();

  // 顯示搜尋結果數量
  if (searchTerm && matchCount < totalCount) {
    notificationSystem.showSuccess(
      `找到 ${matchCount} 筆符合「${searchTerm}」的預約`,
      '搜尋結果'
    );
  }
}

/**
 * 初始化排序控制
 */
function initSortControl() {
  const sortSelect = document.getElementById('sort-select');
  if (!sortSelect) return;

  sortSelect.addEventListener('change', function () {
    const sortType = this.value;
    const groups = document.querySelectorAll('.booking-group');

    // 每個群組內的卡片單獨排序
    groups.forEach((group) => {
      const groupContent = group.querySelector('.group-content');
      const bookingCards = Array.from(group.querySelectorAll('.booking-card'));

      // 根據選擇的排序方式進行排序
      bookingCards.sort((a, b) => {
        switch (sortType) {
          case 'date-asc':
            return a.dataset.startTime.localeCompare(b.dataset.startTime);
          case 'date-desc':
            return b.dataset.startTime.localeCompare(a.dataset.startTime);
          case 'classroom-asc': {
            // 從卡片標題中提取教室名稱（第一個 · 前的文本）
            const getClassroomName = (card) => {
              const title = card.querySelector('.booking-title').textContent;
              return title.split('·')[0].trim();
            };
            return getClassroomName(a).localeCompare(getClassroomName(b));
          }
          case 'building-asc': {
            const buildingA =
              a.querySelector('.info-row:nth-child(1) .info-value')
                ?.textContent || '';
            const buildingB =
              b.querySelector('.info-row:nth-child(1) .info-value')
                ?.textContent || '';
            return buildingA.localeCompare(buildingB);
          }
          default:
            return 0;
        }
      });

      // 重新附加排序後的卡片
      bookingCards.forEach((card) => {
        groupContent.appendChild(card);
      });
    });

    // 顯示排序完成通知
    const sortOptions = {
      'date-asc': '日期（舊 → 新）',
      'date-desc': '日期（新 → 舊）',
      'classroom-asc': '教室名稱 A→Z',
      'building-asc': '建物名稱 A→Z',
    };

    notificationSystem.showSuccess(
      `已按「${sortOptions[sortType]}」排序`,
      '排序成功'
    );
  });
}

/**
 * 初始化卡片展開/收合功能
 */
function initCardExpansion() {
  const bookingCards = document.querySelectorAll('.booking-card');

  bookingCards.forEach((card) => {
    // 預設展開狀態
    card.classList.add('collapsed');

    // 點擊卡片標題區域展開/收合
    const header = card.querySelector('.booking-header');
    header.style.cursor = 'pointer';

    header.addEventListener('click', (e) => {
      // 確保不是點擊取消按鈕或狀態標籤
      if (
        e.target.closest('.booking-status') ||
        e.target.closest('.cancel-btn')
      ) {
        return;
      }

      // 卡片視圖下切換展開狀態
      card.classList.toggle('expanded');
      card.classList.toggle('collapsed');
    });

    // 監聽鍵盤操作以提高無障礙體驗
    header.setAttribute('tabindex', '0');
    header.addEventListener('keydown', (e) => {
      // 按下 Enter 或空格時展開/收合卡片
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        header.click();
      }
    });
  });
}

/**
 * 初始化群組標題 - 已移除可收合功能
 */
function initGroupHeaders() {
  // 清除所有已儲存的群組收合狀態
  Object.keys(localStorage).forEach((key) => {
    if (key.startsWith('booking-group-') && key.endsWith('-collapsed')) {
      localStorage.removeItem(key);
    }
  });

  // 確保所有群組內容都是可見的
  const groupContainers = document.querySelectorAll('.group-content');
  groupContainers.forEach((container) => {
    container.style.overflow = 'visible';
    container.style.maxHeight = 'none';
  });
}

/**
 * 更新群組顯示狀態
 * 如果群組內沒有可見的卡片，則隱藏群組標題
 */
function updateGroupsVisibility() {
  const groups = document.querySelectorAll('.booking-group');

  groups.forEach((group) => {
    const cards = group.querySelectorAll('.booking-card');
    const visibleCards = Array.from(cards).filter(
      (card) => card.style.display !== 'none'
    );

    // 群組標題
    const header = group.querySelector('.booking-group-title');
    if (header) {
      header.style.display = visibleCards.length > 0 ? '' : 'none';
    }

    // 如果沒有可見卡片，隱藏整個群組
    group.style.display = visibleCards.length > 0 ? '' : 'none';
  });

  // 檢查是否所有群組都被隱藏
  const visibleGroups = Array.from(groups).filter(
    (group) => group.style.display !== 'none'
  );

  // 如果沒有可見的群組，顯示空狀態
  const emptyState = document.getElementById('empty-filter-state');
  if (emptyState) {
    emptyState.style.display = visibleGroups.length === 0 ? 'flex' : 'none';
  }
}

/**
 * 將預約按日期分組顯示
 */
function organizeBookingsByGroups() {
  // 這個函數會在未來實作，根據日期或其他條件將預約分組
  console.log('Organizing bookings by groups - to be implemented');
}

/**
 * 格式化日期顯示
 * @param {Date} date - 日期物件
 * @returns {string} - 格式化後的日期字符串
 */
function formatDateShort(date) {
  // 取得當地日期格式（中文）
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  // 取得星期幾（中文）
  const weekDay = ['日', '一', '二', '三', '四', '五', '六'][date.getDay()];

  return `${month}/${day}（${weekDay}）`;
}

/**
 * 更新預約卡片的標題顯示格式
 * 格式：教室名 · 日期 · 時段 · 狀態
 */
function updateCardTitles() {
  const bookingCards = document.querySelectorAll('.booking-card');

  bookingCards.forEach((card) => {
    const classroomName = card
      .querySelector('.booking-title')
      .textContent.trim();
    const startDateTime = new Date(card.dataset.startTime);

    // 獲取日期和時間資訊
    const dateStr = formatDateShort(startDateTime);

    // 獲取時間資訊（從卡片上找）
    const timeElement = card.querySelector(
      '.info-row:nth-child(3) .info-value'
    );
    const timeStr = timeElement ? timeElement.textContent.trim() : '';

    // 更新卡片標題
    const title = card.querySelector('.booking-title');
    title.innerHTML = `
      ${classroomName}
      <span class="separator">·</span>
      ${dateStr}
      <span class="separator">·</span>
      ${timeStr}
    `;
  });
}
