// scheduler-events.js - 排程系統的事件處理

// 事件模組
let SchedulerEvents = (function () {
  // 初始化日期選擇器
  function initDatePicker() {
    const datePicker = document.getElementById('date-picker');
    if (datePicker) {
      // 設置最小日期為今天，防止選擇過去的日期
      const today = new Date();
      const minDate = window.SchedulerUtils.formatDate(today);
      datePicker.setAttribute('min', minDate);

      // 默認選擇今天
      datePicker.valueAsDate = window.SchedulerCore.startDate;

      datePicker.addEventListener('change', function () {
        const selectedDate = new Date(this.value);
        const currentDate = new Date();

        // 檢查所選日期是否為過去日期
        if (selectedDate < new Date(currentDate.setHours(0, 0, 0, 0))) {
          window.SchedulerUtils.showAlert('error', '不能選擇過去的日期');
          datePicker.valueAsDate = window.SchedulerCore.startDate; // 重置為上一個有效日期
          return;
        }

        window.SchedulerCore.startDate = selectedDate;
        if (window.SchedulerCore.currentSpaceId) {
          window.SchedulerCore.loadSchedule(
            window.SchedulerCore.currentSpaceId,
            window.SchedulerCore.startDate
          );
        }
      });
    }

    // 前一天按鈕
    const prevDayBtn = document.getElementById('prev-day');
    if (prevDayBtn) {
      prevDayBtn.addEventListener('click', function () {
        window.SchedulerCore.startDate.setDate(
          window.SchedulerCore.startDate.getDate() - 1
        );
        if (datePicker) datePicker.valueAsDate = window.SchedulerCore.startDate;
        if (window.SchedulerCore.currentSpaceId) {
          window.SchedulerCore.loadSchedule(
            window.SchedulerCore.currentSpaceId,
            window.SchedulerCore.startDate
          );
        }
      });
    }

    // 後一天按鈕
    const nextDayBtn = document.getElementById('next-day');
    if (nextDayBtn) {
      nextDayBtn.addEventListener('click', function () {
        window.SchedulerCore.startDate.setDate(
          window.SchedulerCore.startDate.getDate() + 1
        );
        if (datePicker) datePicker.valueAsDate = window.SchedulerCore.startDate;
        if (window.SchedulerCore.currentSpaceId) {
          window.SchedulerCore.loadSchedule(
            window.SchedulerCore.currentSpaceId,
            window.SchedulerCore.startDate
          );
        }
      });
    }

    // 今天按鈕
    const todayBtn = document.getElementById('today-btn');
    if (todayBtn) {
      todayBtn.addEventListener('click', function () {
        window.SchedulerCore.startDate = new Date();
        if (datePicker) datePicker.valueAsDate = window.SchedulerCore.startDate;
        if (window.SchedulerCore.currentSpaceId) {
          window.SchedulerCore.loadSchedule(
            window.SchedulerCore.currentSpaceId,
            window.SchedulerCore.startDate
          );
        }
      });
    }
  }

  // 設置全局事件監聽
  function setupEventListeners() {
    // 當用戶離開拖曳區域時取消選擇
    document.addEventListener('mouseleave', function () {
      if (window.SchedulerCore.isDragging) {
        window.SchedulerCore.isDragging = false;
        // 不清除選擇，讓用戶有機會重新進入並繼續選擇
      }
    });

    // 過濾下拉選單事件
    const buildingSelect = document.getElementById('building-filter');
    if (buildingSelect) {
      buildingSelect.addEventListener('change', function () {
        const building = this.value;
        window.SchedulerUI.filterSpaces(building);
      });
    }
  }

  // 設置表格的事件
  function setupGridEvents() {
    const cells = document.querySelectorAll('.grid-cell');

    cells.forEach((cell) => {
      // 忽略已經預訂的單元格
      if (cell.classList.contains('grid-cell-booked')) return;

      // 滑鼠按下開始選擇
      cell.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return; // 只處理左鍵點擊

        window.SchedulerCore.isDragging = true;
        window.SchedulerUI.clearSelection();
        window.SchedulerCore.dragStart = parseInt(this.dataset.index);
        window.SchedulerCore.dragEnd = window.SchedulerCore.dragStart;

        // 立即將當前格子添加到選擇中
        const cellIndex = parseInt(this.dataset.index);
        this.classList.add('grid-cell-selected');
        window.SchedulerCore.selectedCells.push(cellIndex);

        // 防止文本選擇
        e.preventDefault();
      });

      // 滑鼠移動時更新選擇
      cell.addEventListener('mouseover', function () {
        if (!window.SchedulerCore.isDragging) return;

        window.SchedulerCore.dragEnd = parseInt(this.dataset.index);
        window.SchedulerUI.updateSelection();
      });
    });

    // 滑鼠釋放結束選擇
    document.addEventListener('mouseup', function () {
      if (!window.SchedulerCore.isDragging) return;

      window.SchedulerCore.isDragging = false;

      // 如果有選擇，顯示預約對話框
      if (window.SchedulerCore.selectedCells.length > 0) {
        window.SchedulerUI.showBookingModal();
      }
    });
  }

  // 返回公開的方法
  return {
    initDatePicker,
    setupEventListeners,
    setupGridEvents,
  };
})();

// 將事件模組導出到全局
window.SchedulerEvents = SchedulerEvents;
