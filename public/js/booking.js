// booking.js - 教室預約系統互動邏輯
// 確保JS觸發完整運行
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM內容載入完畢，初始化預約系統');
  // ===== 狀態 =====
  let selectedSlots = []; // [{classroomId, hour, classroomName, classroomLocation}]
  let isDragging = false;
  let dragMode = 'select'; // 'select' | 'deselect'
  let dragClassroomId = null; // 限制同教室拖曳
  let suppressClickOnce = false; // 避免 mousedown 後 click 再切一次

  // 行動裝置長按拖曳
  let touchLongPressTimer = null;
  let touchDragging = false;
  let touchDragStartedOnCell = null;
  const LONG_PRESS_MS = 300;

  // ===== DOM 參考 =====
  const timetable = document.getElementById('booking-timetable'); // 新版容器
  const timeGrid = document.getElementById('time-grid'); // 舊版容器
  const bookingFormBox =
    document.getElementById('booking-form-container') ||
    document.getElementById('booking-form'); // 兼容舊版
  const selectedSlotsList = document.getElementById('selected-slots-list');
  const selectedSlotsInput = document.getElementById('selected_slots');

  // 調試信息
  console.log('DOM元素載入狀態:');
  console.log('- 時間表格:', timetable ? '找到' : '未找到');
  console.log('- 表單容器:', bookingFormBox ? '找到' : '未找到');
  console.log('- 選擇清單:', selectedSlotsList ? '找到' : '未找到');

  // 由容器取得預約日期（用於判斷「今天已過去時段」）
  const bookingDate =
    (timetable && timetable.dataset.bookingDate) ||
    (timeGrid && timeGrid.dataset.bookingDate) ||
    '';

  // ===== 工具 =====
  const pad2 = (n) => String(n).padStart(2, '0');
  const now = (() => {
    // 以瀏覽器本地時間判斷；後端還是最終裁決
    return new Date();
  })();

  function findSlotIndex(classroomId, hour) {
    return selectedSlots.findIndex(
      (s) => s.classroomId === classroomId && s.hour === hour
    );
  }

  function cellToSlot(cell) {
    // 檢查單元格和資料集是否存在
    console.log('處理單元格:', cell);
    if (!cell || !cell.dataset) {
      console.log('無效單元格或無資料集');
      return null;
    }

    // 直接檢查 hour 屬性
    console.log('單元格數據集:', cell.dataset);
    const hour = parseInt(cell.dataset.hour, 10);
    if (Number.isNaN(hour)) {
      console.log('無效小時數:', cell.dataset.hour);
      return null;
    }

    // 多教室（新版）
    if (cell.dataset.classroomId) {
      const classroomId = parseInt(cell.dataset.classroomId, 10);
      if (Number.isNaN(classroomId)) {
        console.log('無效教室ID:', cell.dataset.classroomId);
        return null;
      }
      return {
        classroomId,
        hour,
        classroomName: cell.dataset.classroomName || '',
        classroomLocation: cell.dataset.classroomLocation || '',
      };
    }

    // 單教室（舊版）無 classroomId → 以 1 代表
    console.log('使用默認教室ID: 1');
    return {
      classroomId: 1,
      hour,
      classroomName: cell.dataset.classroomName || '',
      classroomLocation: cell.dataset.classroomLocation || '',
    };
  }

  function toggleSlot(
    cell,
    classroomId,
    hour,
    classroomName,
    classroomLocation
  ) {
    const idx = findSlotIndex(classroomId, hour);
    if (idx !== -1) {
      selectedSlots.splice(idx, 1);
      cell.classList.remove('time-slot-selected', 'selected');
    } else {
      selectedSlots.push({
        classroomId,
        hour,
        classroomName,
        classroomLocation,
      });
      cell.classList.add('time-slot-selected', 'selected');
    }
  }

  function markDisabledCells() {
    // 標出「已經過去」與「已預約」的格子，並禁止點選
    const allCells = document.querySelectorAll(
      '.time-slot-available, .time-slot-booked, .time-cell'
    );

    const isToday = (() => {
      if (!bookingDate) return false;
      const [y, m, d] = bookingDate.split('-').map((x) => parseInt(x, 10));
      const dt = new Date(y, (m || 1) - 1, d || 1);
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      return dt.getTime() === today.getTime();
    })();

    const currentHour = now.getHours(); // 0-23

    allCells.forEach((cell) => {
      const hour = parseInt(cell.dataset.hour, 10);
      if (Number.isNaN(hour)) return;

      // 已租借：紅色 & 不可點
      if (
        cell.classList.contains('time-slot-booked') ||
        cell.classList.contains('booked')
      ) {
        cell.classList.add('slot-disabled'); // 統一不可點
        // Tooltip 會另行處理
        return;
      }

      // 過去的小時（僅同一天）
      if (isToday && hour <= currentHour) {
        cell.classList.add('time-slot-past', 'slot-disabled'); // 灰色 + 不可點
      }
    });
  }

  function isCellDisabled(cell) {
    return (
      cell.classList.contains('slot-disabled') ||
      cell.classList.contains('time-slot-past') ||
      cell.classList.contains('time-slot-booked') ||
      cell.classList.contains('booked')
    );
  }

  // 調試輔助函數
  function debugCell(cell, event) {
    console.log('單元格事件:', event);
    console.log('單元格內容:', cell.outerHTML);
    console.log('單元格類別:', cell.className);
    console.log('單元格資料:', cell.dataset);
    console.log('是否禁用:', isCellDisabled(cell));
  }

  // ===== 綁定所有格子的互動 =====
  // 獲取所有時間格子
  const allSlots = document.querySelectorAll('.time-slot');
  const availableCells = document.querySelectorAll('.time-slot-available');
  const bookedCells = document.querySelectorAll('.time-slot-booked');
  
  // 為整個表格添加點擊事件委託
  if (timetable) {
    timetable.addEventListener('click', function(e) {
      const target = e.target.closest('.time-slot');
      if (target && !target.classList.contains('time-slot-booked')) {
        console.log('表格點擊事件:', target);
        if (!isCellDisabled(target)) {
          const slot = cellToSlot(target);
          if (slot) {
            toggleSlot(
              target,
              slot.classroomId,
              slot.hour,
              slot.classroomName,
              slot.classroomLocation
            );
            updateFormDisplay();
            e.stopPropagation();
          }
        }
      }
    });
  }

  // 調試信息
  console.log('總時間格子數量:', allSlots.length);
  console.log('可用單元格數量:', availableCells.length);
  console.log('已預約單元格數量:', bookedCells.length);

  // 確保所有可用格子都綁定點擊事件
  allSlots.forEach(cell => {
    if (!cell.classList.contains('time-slot-booked')) {
      // 移除可能已存在的事件處理器
      cell.removeEventListener('click', handleCellClick);
      // 添加點擊處理
      cell.addEventListener('click', function(e) {
        console.log('點擊了格子:', this);
        if (isCellDisabled(this)) {
          console.log('格子被禁用');
          return;
        }
        
        const slot = cellToSlot(this);
        if (!slot) {
          console.log('無法獲取格子資訊');
          return;
        }
        
        console.log('切換格子狀態:', slot);
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
    }
  });

  // 先標示禁用格
  markDisabledCells();

  // 定義點擊反應函數
  function handleCellClick(e) {
    console.log('進入點擊事件');
    
    if (isCellDisabled(this)) {
      console.log('單元格被禁用，不處理點擊');
      e.preventDefault();
      return;
    }
    
    if (suppressClickOnce) {
      console.log('抑制單次點擊，不處理');
      suppressClickOnce = false;
      e.preventDefault();
      return;
    }
    
    const slot = cellToSlot(this);
    console.log('解析的時段:', slot);
    if (!slot) {
      console.log('無法解析時段，不處理');
      return;
    }
    
    toggleSlot(
      this,
      slot.classroomId,
      slot.hour,
      slot.classroomName,
      slot.classroomLocation
    );
    updateFormDisplay();
    e.stopPropagation();
  }    // 滑鼠拖曳（桌機）
    cell.addEventListener('mousedown', function (e) {
      if (isCellDisabled(this)) {
        e.preventDefault();
        return;
      }
      const slot = cellToSlot(this);
      if (!slot) return;

      isDragging = true;
      dragClassroomId = slot.classroomId;
      const already = findSlotIndex(slot.classroomId, slot.hour) !== -1;
      dragMode = already ? 'deselect' : 'select';

      toggleSlot(
        this,
        slot.classroomId,
        slot.hour,
        slot.classroomName,
        slot.classroomLocation
      );
      updateFormDisplay();

      suppressClickOnce = true;
      e.preventDefault();
    });

    cell.addEventListener('mouseenter', function () {
      if (!isDragging) return;
      if (isCellDisabled(this)) return;

      const slot = cellToSlot(this);
      if (!slot || slot.classroomId !== dragClassroomId) return;

      const already = findSlotIndex(slot.classroomId, slot.hour) !== -1;
      if (dragMode === 'select' && !already) {
        toggleSlot(
          this,
          slot.classroomId,
          slot.hour,
          slot.classroomName,
          slot.classroomLocation
        );
        updateFormDisplay();
      } else if (dragMode === 'deselect' && already) {
        toggleSlot(
          this,
          slot.classroomId,
          slot.hour,
          slot.classroomName,
          slot.classroomLocation
        );
        updateFormDisplay();
      }
    });

    // ===== 行動裝置：長按拖曳（when2meet 風格）=====
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

    cell.addEventListener(
      'touchmove',
      function (e) {
        if (!touchDragging) return;

        const t = e.touches[0];
        const el = document.elementFromPoint(t.clientX, t.clientY);
        if (!el) return;

        // 找到最接近的可用 cell
        const target = el.closest(
          '.time-slot-available, .time-cell:not(.booked)'
        );
        if (!target || isCellDisabled(target)) return;

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

    cell.addEventListener('touchend', function () {
      clearTimeout(touchLongPressTimer);
      touchLongPressTimer = null;
      touchDragStartedOnCell = null;
      // 若未達長按門檻 → 視為單點 toggle
      if (!touchDragging && !isCellDisabled(this)) {
        const slot = cellToSlot(this);
        if (slot) {
          toggleSlot(
            this,
            slot.classroomId,
            slot.hour,
            slot.classroomName,
            slot.classroomLocation
          );
          updateFormDisplay();
        }
      }
      endDrag();
    });

    cell.addEventListener('touchcancel', function () {
      clearTimeout(touchLongPressTimer);
      touchLongPressTimer = null;
      touchDragStartedOnCell = null;
      endDrag();
    });
  });

  // 停止拖曳（桌機）
  function endDrag() {
    isDragging = false;
    dragClassroomId = null;
    touchDragging = false;
  }
  document.addEventListener('mouseup', endDrag);
  window.addEventListener('blur', endDrag);
  document.addEventListener('mouseleave', endDrag);

  // ===== Tooltip（單一容器重用；桌機 hover / 手機點一下）=====
  initReusableTooltip(bookedCells);

  function initReusableTooltip(bookedCellsNodeList) {
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

    function showTooltipForCell(cell) {
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
    function hideTooltip() {
      tooltip.classList.remove('visible');
    }

    bookedCellsNodeList.forEach((cell) => {
      // 桌機：hover 顯示
      cell.addEventListener('mouseenter', () => showTooltipForCell(cell));
      cell.addEventListener('mouseleave', hideTooltip);

      // 手機：點一下顯示 / 點其他地方關掉
      cell.addEventListener('click', (e) => {
        if (!tooltip.classList.contains('visible')) {
          showTooltipForCell(cell);
        } else {
          hideTooltip();
        }
        e.stopPropagation();
      });
    });

    document.addEventListener('click', hideTooltip);
    window.addEventListener('scroll', hideTooltip, { passive: true });

    // 提示框樣式已經移動到 booking.css 文件中
  }

  // ===== 更新表單顯示 =====
  function updateFormDisplay() {
    // 去重與排序
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

    // 同步隱藏欄位
    if (selectedSlotsInput) {
      selectedSlotsInput.value = JSON.stringify(selectedSlots);
    }

    // 顯示/隱藏表單
    if (bookingFormBox) {
      if (selectedSlots.length > 0) {
        bookingFormBox.classList.add('visible');
        bookingFormBox.style.display = 'block';
      } else {
        bookingFormBox.classList.remove('visible');
        bookingFormBox.style.display = 'none';
      }
    }

    // 渲染選取清單
    if (selectedSlotsList) {
      selectedSlotsList.innerHTML = '';
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

      Object.entries(groups).forEach(([classroomId, info]) => {
        info.hours.sort((a, b) => a - b);

        // 連續段
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

        // UI
        const li = document.createElement('li');
        li.className = 'classroom-group mb-2';
        li.innerHTML = `<div class="fw-bold">${info.name}${
          info.location ? ` (${info.location})` : ''
        }</div>`;
        const ul = document.createElement('ul');
        ul.className = 'time-ranges ps-3';

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

      // 刪除一段
      selectedSlotsList.querySelectorAll('.slot-remove-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
          const classroomId = parseInt(this.dataset.classroomId, 10);
          const start = parseInt(this.dataset.start, 10);
          const end = parseInt(this.dataset.end, 10);
          for (let h = start; h < end; h++) {
            removeTimeSlot(classroomId, h);
          }
          updateFormDisplay();
        });
      });
    }
  }

  // 從狀態/畫面移除單格
  function removeTimeSlot(classroomId, hour) {
    const idx = findSlotIndex(classroomId, hour);
    if (idx !== -1) selectedSlots.splice(idx, 1);

    const cell =
      document.querySelector(
        `.time-slot-available[data-classroom-id="${classroomId}"][data-hour="${hour}"]`
      ) ||
      document.querySelector(`.time-cell[data-hour="${hour}"]:not(.booked)`);
    if (cell) cell.classList.remove('time-slot-selected', 'selected');
  }

  // ===== 初次渲染 =====
  updateFormDisplay();

  // 樣式已經移動到 booking.css 文件中

  // 全局清除選擇函數，供「清除選擇」按鈕調用
  window.clearSelection = function () {
    // 清除所有選擇的時段
    document
      .querySelectorAll('.time-slot-selected, .time-cell.selected')
      .forEach((cell) => {
        cell.classList.remove('time-slot-selected', 'selected');
      });

    // 重置狀態
    selectedSlots = [];

    // 同步隱藏輸入欄位
    if (selectedSlotsInput) {
      selectedSlotsInput.value = '';
    }

    // 更新表單顯示
    updateFormDisplay();
  };
});
