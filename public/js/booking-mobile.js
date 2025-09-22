// booking-mobile.js - 教室預約系統互動邏輯 (移動裝置專用版)
document.addEventListener('DOMContentLoaded', function () {
  console.log('初始化教室預約系統（移動裝置版）');

  // ===== 狀態變量 =====
  let selectedSlots = []; // 已選時段 [{classroomId, hour, classroomName, classroomLocation}]

  // 行動裝置長按拖曳專用變數
  let touchLongPressTimer = null;
  let touchDragging = false;
  let touchDragStartedOnCell = null;
  let dragMode = 'select'; // 'select' | 'deselect'
  let dragClassroomId = null; // 限制同教室拖曳
  const LONG_PRESS_MS = 300; // 長按觸發時間（毫秒）

  // 從 localStorage 加載之前選取的時段
  try {
    const savedSlots = localStorage.getItem('selectedBookingSlots');
    if (savedSlots) {
      selectedSlots = JSON.parse(savedSlots);
      console.log('已從本地儲存加載時段:', selectedSlots);
    }
  } catch (err) {
    console.error('讀取儲存的時段失敗:', err);
  }

  // ===== DOM元素引用 =====
  const timetable = document.getElementById('booking-timetable'); // 新版容器
  const timeGrid = document.getElementById('time-grid'); // 舊版容器（兼容）
  const bookingFormBox =
    document.getElementById('booking-form-container') ||
    document.getElementById('booking-form'); // 兼容舊版
  const selectedSlotsList = document.getElementById('selected-slots-list');
  const selectedSlotsInput = document.getElementById('selected_slots');

  // 獲取預約日期（用於禁用今天已過去的時段）
  const bookingDate =
    (timetable && timetable.dataset.bookingDate) ||
    (timeGrid && timeGrid.dataset.bookingDate) ||
    '';

  // ===== 工具函數 =====
  const pad2 = (n) => String(n).padStart(2, '0');
  const now = new Date();

  // 在選中時段數組中查找特定時段的索引
  function findSlotIndex(classroomId, hour) {
    return selectedSlots.findIndex(
      (s) => s.classroomId === classroomId && s.hour === hour
    );
  }

  // 將單元格轉換為時段對象
  function cellToSlot(cell) {
    if (!cell || !cell.dataset) return null;

    const hour = parseInt(cell.dataset.hour, 10);
    if (isNaN(hour)) return null;

    if (cell.dataset.classroomId) {
      const classroomId = parseInt(cell.dataset.classroomId, 10);
      if (isNaN(classroomId)) return null;

      return {
        classroomId,
        hour,
        classroomName: cell.dataset.classroomName || '',
        classroomLocation: cell.dataset.classroomLocation || '',
      };
    }

    // 舊版（單教室）使用教室ID=1
    return {
      classroomId: 1,
      hour,
      classroomName: cell.dataset.classroomName || '',
      classroomLocation: cell.dataset.classroomLocation || '',
    };
  }

  // 切換時段選擇狀態
  function toggleSlot(
    cell,
    classroomId,
    hour,
    classroomName,
    classroomLocation
  ) {
    const idx = findSlotIndex(classroomId, hour);

    if (idx !== -1) {
      // 移除已選時段
      selectedSlots.splice(idx, 1);
      cell.classList.remove('time-slot-selected', 'selected');
    } else {
      // 添加新時段
      selectedSlots.push({
        classroomId,
        hour,
        classroomName,
        classroomLocation,
      });
      cell.classList.add('time-slot-selected', 'selected');
    }
  }

  // 判斷單元格是否為禁用狀態
  function isCellDisabled(cell) {
    return (
      cell.classList.contains('slot-disabled') ||
      cell.classList.contains('time-slot-past') ||
      cell.classList.contains('time-slot-booked') ||
      cell.classList.contains('booked')
    );
  }

  // 標記禁用的單元格（已過時間或已預約）
  function markDisabledCells() {
    const allCells = document.querySelectorAll(
      '.time-slot-available, .time-slot-booked, .time-cell, .time-slot'
    );

    // 將預約日期轉換為Date對象
    const bookingDateObj = (() => {
      if (!bookingDate) return null;
      const [y, m, d] = bookingDate.split('-').map((x) => parseInt(x, 10));
      return new Date(y, (m || 1) - 1, d || 1);
    })();

    // 今天的日期（僅年月日）
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    // 檢查預約日期是否為過去日期
    const isPastDate = bookingDateObj && bookingDateObj < today;

    // 檢查是否為今天
    const isToday =
      bookingDateObj && bookingDateObj.getTime() === today.getTime();

    const currentHour = now.getHours();

    console.log('預約日期:', bookingDate);
    console.log('是否為今天:', isToday);
    console.log('是否為過去日期:', isPastDate);
    console.log('當前小時:', currentHour);

    allCells.forEach((cell) => {
      // 跳過無效單元格
      if (!cell.dataset || !cell.dataset.hour) return;

      const hour = parseInt(cell.dataset.hour, 10);
      if (isNaN(hour)) return;

      // 已預約時段標記為禁用
      if (
        cell.classList.contains('time-slot-booked') ||
        cell.classList.contains('booked')
      ) {
        cell.classList.add('slot-disabled');
        return;
      }

      // 過去日期，所有時段標記為禁用
      if (isPastDate) {
        cell.classList.add('time-slot-past', 'slot-disabled');
        return;
      }

      // 今天，只禁用已過去的時段 (小於或等於當前小時)
      if (isToday && hour <= currentHour) {
        cell.classList.add('time-slot-past', 'slot-disabled');
      }
    });
  }

  // 在頁面載入後還原已選取的時段
  function restoreSelectedSlots() {
    selectedSlots.forEach((slot) => {
      // 嘗試選取對應的單元格（同時兼容新舊版格式）
      const cell =
        document.querySelector(
          `.time-slot[data-classroom-id="${slot.classroomId}"][data-hour="${slot.hour}"]`
        ) ||
        document.querySelector(
          `.time-cell[data-hour="${slot.hour}"]:not(.booked)`
        );

      if (cell && !isCellDisabled(cell)) {
        // 確保新畫面中的教室存在且可選
        cell.classList.add('time-slot-selected', 'selected');
      } else {
        console.log('找不到或無法選取的時段:', slot);
      }
    });
  }

  // 更新預約表單顯示
  function updateFormDisplay() {
    // 去重並排序選中的時段
    const seen = new Set();
    selectedSlots = selectedSlots.filter((s) => {
      const key = `${s.classroomId}-${s.hour}`;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });

    selectedSlots.sort((a, b) =>
      a.classroomId === b.classroomId
        ? a.hour - b.hour
        : a.classroomId - b.classroomId
    );

    // 更新隱藏輸入框的值
    if (selectedSlotsInput) {
      selectedSlotsInput.value = JSON.stringify(selectedSlots);
    }

    // 根據是否有選中時段顯示/隱藏表單
    if (bookingFormBox) {
      if (selectedSlots.length > 0) {
        bookingFormBox.classList.add('visible');
        bookingFormBox.style.display = 'block';
      } else {
        bookingFormBox.classList.remove('visible');
        bookingFormBox.style.display = 'none';
      }
    }

    // 更新已選時段列表
    if (selectedSlotsList) {
      // 清空列表
      selectedSlotsList.innerHTML = '';

      // 按教室分組時段
      const groups = {};
      selectedSlots.forEach((s) => {
        if (!groups[s.classroomId]) {
          groups[s.classroomId] = {
            name: s.classroomName || `教室#${s.classroomId}`,
            location: s.classroomLocation || '',
            hours: [],
          };
        }
        groups[s.classroomId].hours.push(s.hour);
      });

      // 生成UI
      Object.entries(groups).forEach(([classroomId, info]) => {
        info.hours.sort((a, b) => a - b);

        // 將連續時段合併顯示
        const ranges = [];
        let start = info.hours[0];
        let end = info.hours[0];

        for (let i = 1; i < info.hours.length; i++) {
          if (info.hours[i] === end + 1) {
            end = info.hours[i];
          } else {
            ranges.push({ start, end: end + 1 });
            start = info.hours[i];
            end = info.hours[i];
          }
        }
        ranges.push({ start, end: end + 1 });

        // 創建教室項
        const li = document.createElement('li');
        li.className = 'classroom-group mb-2';
        li.innerHTML = `<div class="fw-bold">${info.name}${
          info.location ? ` (${info.location})` : ''
        }</div>`;

        // 創建時段列表
        const ul = document.createElement('ul');
        ul.className = 'time-ranges ps-3';

        // 添加每個時段範圍
        ranges.forEach((r) => {
          const timeLi = document.createElement('li');
          timeLi.className =
            'd-flex justify-content-between align-items-center';
          timeLi.innerHTML = `
            <span>${pad2(r.start)}:00 - ${pad2(r.end)}:00</span>
            <button type="button" class="slot-remove-btn" data-classroom-id="${classroomId}" data-start="${
            r.start
          }" data-end="${r.end}">
              <i class="fas fa-times"></i>
            </button>
          `;
          ul.appendChild(timeLi);
        });

        li.appendChild(ul);
        selectedSlotsList.appendChild(li);
      });

      // 為刪除按鈕添加事件處理
      selectedSlotsList.querySelectorAll('.slot-remove-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
          const classroomId = parseInt(this.dataset.classroomId, 10);
          const start = parseInt(this.dataset.start, 10);
          const end = parseInt(this.dataset.end, 10);

          // 移除連續時段
          for (let h = start; h < end; h++) {
            removeTimeSlot(classroomId, h);
          }

          updateFormDisplay();
        });
      });
    }

    // 儲存選擇的時段到 localStorage，以便跨頁面保存
    try {
      localStorage.setItem(
        'selectedBookingSlots',
        JSON.stringify(selectedSlots)
      );
    } catch (err) {
      console.error('無法保存時段至本地儲存:', err);
    }
  }

  // 從狀態和界面中移除時段
  function removeTimeSlot(classroomId, hour) {
    const idx = findSlotIndex(classroomId, hour);
    if (idx !== -1) {
      selectedSlots.splice(idx, 1);
    }

    // 找到對應的單元格並移除選中樣式（兼容新舊版）
    const cell =
      document.querySelector(
        `.time-slot[data-classroom-id="${classroomId}"][data-hour="${hour}"]`
      ) ||
      document.querySelector(`.time-cell[data-hour="${hour}"]:not(.booked)`);

    if (cell) {
      cell.classList.remove('time-slot-selected', 'selected');
    }
  }

  // 為已預約時段初始化提示框（行動版本）
  function initializeTooltips() {
    const bookedCells = document.querySelectorAll(
      '.time-slot-booked, .time-cell.booked'
    );
    if (!bookedCells.length) return;

    // 創建提示框元素
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.innerHTML = `
      <div class="tooltip-header">預約資訊</div>
      <div class="tooltip-content">
        <div class="tooltip-row"><strong>租借人:</strong> <span data-field="user"></span></div>
        <div class="tooltip-row"><strong>聯絡方式:</strong> <span data-field="email"></span></div>
        <div class="tooltip-row"><strong>用途:</strong> <span data-field="purpose"></span></div>
      </div>
    `;
    document.body.appendChild(tooltip);

    // 顯示提示框函數
    function showTooltip(cell) {
      tooltip.querySelector('[data-field="user"]').textContent =
        cell.getAttribute('data-user') || '';
      tooltip.querySelector('[data-field="email"]').textContent =
        cell.getAttribute('data-email') || '';
      tooltip.querySelector('[data-field="purpose"]').textContent =
        cell.getAttribute('data-purpose') || '';

      const rect = cell.getBoundingClientRect();
      tooltip.style.left = Math.round(rect.left) + 'px';
      tooltip.style.top = Math.round(rect.bottom + 10) + 'px';
      tooltip.classList.add('visible');
    }

    // 隱藏提示框函數
    function hideTooltip() {
      tooltip.classList.remove('visible');
    }

    // 為每個已預約單元格添加點擊事件（手機版）
    bookedCells.forEach((cell) => {
      // 移動設備：點擊顯示/隱藏提示框
      cell.addEventListener('click', (e) => {
        if (!tooltip.classList.contains('visible')) {
          showTooltip(cell);
        } else {
          hideTooltip();
        }
        e.stopPropagation();
      });
    });

    // 點擊其他地方隱藏提示框
    document.addEventListener('click', hideTooltip);

    // 滾動時隱藏提示框
    window.addEventListener('scroll', hideTooltip, { passive: true });
  }

  // 初始化行動裝置拖曳功能
  function initializeMobileDragSelect() {
    if (!timetable && !timeGrid) return;
    const container = timetable || timeGrid;
    const allCells = container.querySelectorAll(
      '.time-slot, .time-cell:not(.booked)'
    );

    // 處理每個時間格子
    allCells.forEach((cell) => {
      if (isCellDisabled(cell)) return; // 跳過已禁用的格子

      // 單次點擊事件處理
      cell.addEventListener('click', function (e) {
        // 如果是長按後的觸發，則忽略
        if (touchDragging) return;

        const slot = cellToSlot(this);
        if (!slot) return;

        toggleSlot(
          this,
          slot.classroomId,
          slot.hour,
          slot.classroomName,
          slot.classroomLocation
        );

        updateFormDisplay();
        e.stopPropagation();
      });

      // 長按開始
      cell.addEventListener(
        'touchstart',
        function (e) {
          if (isCellDisabled(this)) return;

          const touch = e.touches[0];
          touchDragStartedOnCell = this;

          // 長按才進入拖曳
          touchLongPressTimer = setTimeout(() => {
            const slot = cellToSlot(touchDragStartedOnCell);
            if (!slot) return;

            touchDragging = true;
            dragClassroomId = slot.classroomId;

            const already = findSlotIndex(slot.classroomId, slot.hour) !== -1;
            dragMode = already ? 'deselect' : 'select';

            // 視覺反饋
            touchDragStartedOnCell.classList.add('long-press-active');

            toggleSlot(
              touchDragStartedOnCell,
              slot.classroomId,
              slot.hour,
              slot.classroomName,
              slot.classroomLocation
            );
            updateFormDisplay();
          }, LONG_PRESS_MS);
        },
        { passive: true }
      );

      // 拖曳移動
      cell.addEventListener(
        'touchmove',
        function (e) {
          if (!touchDragging) {
            // 若還在等待長按，但已經移動了，則取消長按計時器
            if (touchLongPressTimer) {
              clearTimeout(touchLongPressTimer);
              touchLongPressTimer = null;
            }
            return;
          }

          const t = e.touches[0];
          const el = document.elementFromPoint(t.clientX, t.clientY);
          if (!el) return;

          // 找到最接近的可用 cell
          const target =
            el.closest('.time-slot') || el.closest('.time-cell:not(.booked)');
          if (
            !target ||
            isCellDisabled(target) ||
            target === touchDragStartedOnCell
          )
            return;

          const slot = cellToSlot(target);
          if (!slot || slot.classroomId !== dragClassroomId) return;

          const already = findSlotIndex(slot.classroomId, slot.hour) !== -1;
          if (dragMode === 'select' && !already) {
            toggleSlot(
              target,
              slot.classroomId,
              slot.hour,
              slot.classroomName,
              slot.classroomLocation
            );
            updateFormDisplay();
          } else if (dragMode === 'deselect' && already) {
            toggleSlot(
              target,
              slot.classroomId,
              slot.hour,
              slot.classroomName,
              slot.classroomLocation
            );
            updateFormDisplay();
          }
          e.preventDefault();
        },
        { passive: false }
      );

      // 觸摸結束
      cell.addEventListener('touchend', function () {
        clearTimeout(touchLongPressTimer);
        touchLongPressTimer = null;

        if (touchDragStartedOnCell) {
          touchDragStartedOnCell.classList.remove('long-press-active');
        }

        touchDragStartedOnCell = null;
        touchDragging = false;
      });

      // 觸摸取消
      cell.addEventListener('touchcancel', function () {
        clearTimeout(touchLongPressTimer);
        touchLongPressTimer = null;

        if (touchDragStartedOnCell) {
          touchDragStartedOnCell.classList.remove('long-press-active');
        }

        touchDragStartedOnCell = null;
        touchDragging = false;
      });
    });
  }

  // 自動提交篩選器功能
  function initializeAutoFilters() {
    document.querySelectorAll('.auto-submit').forEach((element) => {
      element.addEventListener('change', function () {
        // 將選取的時段儲存到 localStorage
        localStorage.setItem(
          'selectedBookingSlots',
          JSON.stringify(selectedSlots)
        );
        console.log('已儲存時段到本地儲存:', selectedSlots.length, '個時段');

        // 觸發表單提交
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
          filterForm.submit();
        }
      });
    });
  }

  // 主要功能初始化
  function initialize() {
    console.log('初始化行動版預約系統');

    // 檢測是否為移動設備
    const isMobile =
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent
      );
    if (!isMobile) {
      console.log('非移動裝置，跳過初始化');
      return;
    }

    // 標記禁用單元格
    markDisabledCells();

    // 初始化提示框（點擊版本）
    initializeTooltips();

    // 還原已選取的時段
    restoreSelectedSlots();

    // 初始化移動裝置拖曳功能
    initializeMobileDragSelect();

    // 初始化自動篩選器
    initializeAutoFilters();

    // 初始更新表單顯示
    updateFormDisplay();
  }

  // 全局清除選擇函數，供「清除選擇」按鈕調用
  if (typeof window.clearSelection !== 'function') {
    window.clearSelection = function () {
      // 清除選中狀態
      document
        .querySelectorAll('.time-slot-selected, .selected')
        .forEach((cell) => {
          cell.classList.remove('time-slot-selected', 'selected');
        });

      // 重置狀態
      selectedSlots = [];

      // 更新隱藏輸入和表單顯示
      if (selectedSlotsInput) {
        selectedSlotsInput.value = '';
      }

      updateFormDisplay();
    };
  }

  // 初始化系統
  initialize();
});
