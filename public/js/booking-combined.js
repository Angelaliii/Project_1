// booking-combined.js - 整合後的教室預約邏輯
(function () {
  const HOURS_START = 8;
  const HOURS_END = 21;

  // 檢測行動裝置
  const isMobile =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );

  // DOM載入完成後執行初始化
  document.addEventListener('DOMContentLoaded', function () {
    // 初始化教室預約系統
    initBookingSystem();
  });

  // 初始化預約系統
  function initBookingSystem() {
    // 初始化預約目的同步
    initPurposeSync();

    // 增強所有格子的視覺效果
    enhanceVisualEffects();

    // 標記已過時間與已預約格子
    markDisabledCells();

    // 初始化提示工具
    initTooltips();

    // 綁定點擊事件
    attachClickEvents();

    // 初始化篩選系統
    initFilterSystem();
  }

  // 輔助函式：判斷是否為今天
  function isBookingDateToday(dateStr) {
    if (!dateStr) return false;
    const [y, m, d] = dateStr.split('-').map((x) => parseInt(x, 10));
    if (!y || !m || !d) return false;

    const today = new Date();
    // 注意: 月份從0開始，所以要減1
    const bookingDate = new Date(y, m - 1, d);

    // 使用UTC格式進行日期比較，避免時區問題
    return (
      today.getFullYear() === bookingDate.getFullYear() &&
      today.getMonth() === bookingDate.getMonth() &&
      today.getDate() === bookingDate.getDate()
    );
  }

  // 初始化篩選系統
  function initFilterSystem() {
    // 確保所有篩選條件變更時能自動提交表單
    const filterElements = document.querySelectorAll('.auto-submit');
    filterElements.forEach((element) => {
      element.addEventListener('change', function () {
        document.getElementById('filter-form').submit();
      });
    });
  }

  // 增強所有格子視覺效果
  function enhanceVisualEffects() {
    // 可預約時段
    const availableCells = document.querySelectorAll('div.time-slot-available');
    availableCells.forEach((cell) => {
      // 確保內容可見
      if (!cell.querySelector('.cell-content')) {
        const content = document.createElement('div');
        content.className = 'cell-content';
        content.textContent = '可預約';
        cell.appendChild(content);
      }

      // 鼠標懸停效果
      cell.addEventListener('mouseenter', function () {
        if (!this.classList.contains('slot-disabled')) {
          this.style.transform = 'scale(1.05)';
          this.style.zIndex = '10';
          this.style.boxShadow = '0 0 8px rgba(0,0,0,0.2)';
        }
      });

      cell.addEventListener('mouseleave', function () {
        if (!this.classList.contains('slot-disabled')) {
          this.style.transform = '';
          this.style.zIndex = '';
          this.style.boxShadow = '';
        }
      });

      // 點擊效果
      cell.addEventListener('mousedown', function () {
        if (!this.classList.contains('slot-disabled')) {
          this.style.transform = 'scale(0.95)';
        }
      });

      cell.addEventListener('mouseup', function () {
        if (!this.classList.contains('slot-disabled')) {
          this.style.transform = 'scale(1.05)';
        }
      });
    });

    // 已預約時段
    const bookedCells = document.querySelectorAll('div.time-slot-booked');
    bookedCells.forEach((cell) => {
      if (!cell.querySelector('.cell-content')) {
        const content = document.createElement('div');
        content.className = 'cell-content';
        content.textContent = '已預約';
        content.style.color = '#f44336';
        cell.appendChild(content);
      }
    });

    // 已過時間
    const pastCells = document.querySelectorAll('div.time-slot-past');
    pastCells.forEach((cell) => {
      if (!cell.querySelector('.cell-content')) {
        const content = document.createElement('div');
        content.className = 'cell-content';
        content.textContent = '已過期';
        content.style.color = '#757575';
        cell.appendChild(content);
      }
    });
  }

  // 標記已過時間與已預約格子
  function markDisabledCells() {
    // 檢查是否為今天
    const timetable =
      document.getElementById('booking-timetable') ||
      document.getElementById('time-grid');
    if (!timetable) return;

    const bookingDate = timetable.dataset.bookingDate || '';
    const isToday = isBookingDateToday(bookingDate);
    const currentHour = new Date().getHours();

    // 處理已預約格子
    const bookedCells = document.querySelectorAll('div.time-slot-booked');
    bookedCells.forEach((cell) => {
      cell.classList.add('slot-disabled');
      cell.style.cursor = 'not-allowed';
    });

    // 處理已過時間格子
    if (isToday) {
      const availableCells = document.querySelectorAll(
        'div.time-slot-available'
      );
      availableCells.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour || '0', 10);
        if (!isNaN(hour) && hour <= currentHour) {
          cell.classList.remove('time-slot-available');
          cell.classList.add('time-slot-past', 'slot-disabled');
          cell.style.pointerEvents = 'none';
          cell.style.backgroundColor = '#e0e0e0';
          cell.setAttribute('title', '此時段已過期');
        }
      });
    }
  }

  // 初始化提示工具
  function initTooltips() {
    const bookedCells = document.querySelectorAll(
      'div.time-slot-booked, div.time-cell.booked'
    );

    if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
      // 使用Bootstrap工具提示
      bookedCells.forEach((cell) => {
        const html = `
          <div class="booking-tooltip">
            <div class="booking-tooltip-header">預約資訊</div>
            <div class="booking-tooltip-content">
              <div class="booking-tooltip-row"><strong>租借人</strong>${
                cell.getAttribute('data-user') || ''
              }</div>
              <div class="booking-tooltip-row"><strong>聯絡</strong>${
                cell.getAttribute('data-email') || ''
              }</div>
              <div class="booking-tooltip-row"><strong>用途</strong>${
                cell.getAttribute('data-purpose') || ''
              }</div>
            </div>
          </div>`;
        cell.setAttribute('data-bs-toggle', 'tooltip');
        cell.setAttribute('data-bs-html', 'true');
        cell.setAttribute('title', html);
        try {
          new bootstrap.Tooltip(cell, {
            customClass: 'booking-custom-tooltip',
            placement: 'bottom',
            // 行動版使用點擊觸發，桌面版使用懸停觸發
            trigger: isMobile ? 'click' : 'hover focus',
          });
        } catch (e) {
          // Bootstrap tooltip初始化失敗
        }
      });
      return;
    }

    // 後備原生提示工具
    let tip;

    function showTip(cell) {
      if (tip) tip.remove();

      tip = document.createElement('div');
      tip.className =
        'tooltip-container visible' + (isMobile ? ' mobile-tooltip' : '');
      tip.innerHTML = `
        <div class="tooltip-header">預約資訊</div>
        <div class="tooltip-content">
          <div class="tooltip-row"><strong>租借人:</strong> ${
            cell.getAttribute('data-user') || ''
          }</div>
          <div class="tooltip-row"><strong>聯絡方式:</strong> ${
            cell.getAttribute('data-email') || ''
          }</div>
          <div class="tooltip-row"><strong>用途:</strong> ${
            cell.getAttribute('data-purpose') || ''
          }</div>
        </div>`;

      document.body.appendChild(tip);
      const rect = cell.getBoundingClientRect();
      tip.style.left = Math.max(10, rect.left) + 'px';
      tip.style.top = rect.bottom + 10 + 'px';

      // 行動版自動隱藏
      if (isMobile) {
        setTimeout(() => {
          if (tip) {
            tip.remove();
            tip = null;
          }
        }, 3000);
      }
    }

    function hideTip() {
      if (tip) {
        tip.remove();
        tip = null;
      }
    }

    bookedCells.forEach((cell) => {
      if (isMobile) {
        // 行動版只使用點擊
        cell.addEventListener('click', (e) => {
          showTip(cell);
          e.stopPropagation();
        });
      } else {
        // 桌面版使用懸停和點擊
        cell.addEventListener('mouseenter', () => showTip(cell));
        cell.addEventListener('mouseleave', hideTip);
        cell.addEventListener('click', (e) => {
          if (tip) hideTip();
          else showTip(cell);
          e.stopPropagation();
        });
      }
    });

    // 點擊其他地方關閉提示
    document.addEventListener('click', hideTip);
  }

  // 綁定點擊事件
  function attachClickEvents() {
    // 點擊選取
    document.addEventListener('click', function (e) {
      const cell = e.target.closest(
        '.time-slot-available:not(.slot-disabled), .time-cell:not(.booked):not(.slot-disabled)'
      );
      if (!cell) return;

      // 切換選取狀態
      if (cell.classList.contains('time-slot-selected')) {
        cell.classList.remove('time-slot-selected');
      } else {
        cell.classList.add('time-slot-selected');
      }

      // 行動版點擊特效
      if (isMobile && !cell.classList.contains('slot-disabled')) {
        const ripple = document.createElement('div');
        ripple.className = 'ripple';
        cell.appendChild(ripple);

        ripple.style.left = e.offsetX + 'px';
        ripple.style.top = e.offsetY + 'px';

        setTimeout(() => {
          ripple.remove();
        }, 500);
      }

      // 更新表單顯示
      updateFormVisibility();
    });

    // 行動版點擊其他區域隱藏表單
    if (isMobile) {
      document.addEventListener('click', function (e) {
        const formArea = e.target.closest(
          '#booking-form-container, #booking-form, .time-slot-available, .time-cell:not(.booked)'
        );
        const formBox =
          document.getElementById('booking-form-container') ||
          document.getElementById('booking-form');

        if (!formArea && formBox && formBox.classList.contains('visible')) {
          formBox.classList.remove('visible');
          formBox.style.display = 'none';
        }
      });

      // 取消按鈕事件
      const cancelBtn = document.getElementById('cancel-booking-btn');
      if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
          // 清除所有選取
          const selectedCells = document.querySelectorAll(
            '.time-slot-selected'
          );
          selectedCells.forEach((cell) =>
            cell.classList.remove('time-slot-selected')
          );

          // 隱藏表單
          updateFormVisibility();
        });
      }
    }
  }

  // 更新表單顯示
  function updateFormVisibility() {
    const selectedSlots = document.querySelectorAll('div.time-slot-selected');
    const formContainer =
      document.getElementById('booking-form-container') ||
      document.getElementById('booking-form');
    const purposeInput = document.getElementById('booking-purpose-input');
    const selectedTimeRange = document.getElementById('selected-time-range');
    const timetable = document.getElementById('booking-timetable');
    const selectedDate = timetable ? timetable.dataset.bookingDate : '';

    if (!formContainer) return;

    // 有選取的格子時顯示表單
    if (selectedSlots.length > 0) {
      formContainer.style.display = 'block';
      formContainer.classList.add('visible');

      // 啟用目的輸入框
      if (purposeInput) {
        purposeInput.disabled = false;
        purposeInput.placeholder = '請輸入預約目的';
      }

      // 更新隱藏輸入
      const selectedInput = document.getElementById('selected_slots');
      if (selectedInput) {
        const selectedData = Array.from(selectedSlots).map((slot) => ({
          classroomId: parseInt(slot.dataset.classroomId, 10),
          hour: parseInt(slot.dataset.hour, 10),
          classroomName: slot.dataset.classroomName || '',
          classroomLocation: slot.dataset.classroomLocation || '',
        }));
        selectedInput.value = JSON.stringify(selectedData);

        // 更新顯示選定時段
        if (selectedTimeRange) {
          // 依據教室和時間排序
          selectedData.sort((a, b) => {
            if (a.classroomId === b.classroomId) {
              return a.hour - b.hour;
            }
            return a.classroomId - b.classroomId;
          });

          // 整理顯示格式
          let displayText = selectedDate + ' ';
          let currentClassroom = null;
          let timeRanges = [];

          // 整理每個教室的時段
          selectedData.forEach((slot) => {
            if (currentClassroom !== slot.classroomId) {
              if (currentClassroom !== null) {
                displayText += `${slot.classroomName}(${formatTimeRanges(
                  timeRanges
                )})`;
                timeRanges = [];
              }
              currentClassroom = slot.classroomId;
              displayText += `${slot.classroomName || slot.classroomLocation}(`;
            }
            timeRanges.push(slot.hour);
          });

          // 添加最後一個教室的時段
          if (timeRanges.length > 0) {
            displayText += `${formatTimeRanges(timeRanges)})`;
          }

          selectedTimeRange.textContent = displayText;
        }
      }
    } else {
      // 沒有選取的格子時隱藏表單
      formContainer.style.display = 'none';
      formContainer.classList.remove('visible');

      // 禁用目的輸入框
      if (purposeInput) {
        purposeInput.disabled = true;
        purposeInput.placeholder = '請選擇時段後輸入目的';
      }

      // 重置選定時段顯示
      if (selectedTimeRange) {
        selectedTimeRange.textContent = selectedDate + ' (尚未選擇時段)';
      }
    }
  }

  // 同步預約目的輸入
  function initPurposeSync() {
    const purposeInput = document.getElementById('booking-purpose-input');
    const bookingPurpose = document.getElementById('booking-purpose');

    if (purposeInput && bookingPurpose) {
      purposeInput.addEventListener('input', function () {
        bookingPurpose.value = this.value;
      });

      bookingPurpose.addEventListener('input', function () {
        purposeInput.value = this.value;
      });
    }
  }

  // 格式化時間範圍字串
  function formatTimeRanges(hours) {
    if (!hours || hours.length === 0) return '';

    // 將小時排序
    hours.sort((a, b) => a - b);

    // 合併連續的時間段
    let ranges = [];
    let start = hours[0];
    let end = hours[0];

    for (let i = 1; i < hours.length; i++) {
      if (hours[i] === end + 1) {
        // 連續時間
        end = hours[i];
      } else {
        // 不連續，建立新的時間範圍
        ranges.push(formatTimeRange(start, end));
        start = hours[i];
        end = hours[i];
      }
    }

    // 添加最後一個範圍
    ranges.push(formatTimeRange(start, end));

    return ranges.join('、');
  }

  // 格式化單個時間範圍
  function formatTimeRange(start, end) {
    // 處理特殊時間顯示格式
    function formatHour(hour) {
      if (hour == 12) {
        return '12:00';
      } else if (hour >= 13) {
        return `${hour}:30`;
      } else {
        return `${hour}:00`;
      }
    }

    function formatEndHour(hour) {
      if (hour == 12) {
        return '13:30';
      } else if (hour >= 13) {
        return `${hour + 1}:30`;
      } else {
        return `${hour + 1}:00`;
      }
    }

    if (start === end) {
      return `${formatHour(start)}-${formatEndHour(start)}`;
    } else {
      return `${formatHour(start)}-${formatEndHour(end)}`;
    }
  }

  // 初始化函數 - 適配 booking-loader.js
  function initialize() {
    // 重置狀態，防止多次初始化
    const selectedCells = document.querySelectorAll('.time-slot-selected');
    selectedCells.forEach((cell) =>
      cell.classList.remove('time-slot-selected')
    );

    // 初始化系統
    initBookingSystem();
  }

  // 初始化全局對象
  window.Booking = window.Booking || {};
  window.Booking.initialize = initialize;

  // 調試用API (僅開發環境)
  window.Booking._debugGetSelected = function () {
    const selectedCells = document.querySelectorAll('.time-slot-selected');
    return Array.from(selectedCells).map((cell) => ({
      classroomId: parseInt(cell.dataset.classroomId || '0', 10),
      hour: parseInt(cell.dataset.hour || '0', 10),
      classroomName: cell.dataset.classroomName || '',
      classroomLocation: cell.dataset.classroomLocation || '',
    }));
  };
})();
