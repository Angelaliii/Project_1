// booking.js - 整合版教室預約系統互動邏輯，專為桌面設備優化
document.addEventListener('DOMContentLoaded', function () {
  console.log('初始化教室預約系統（桌面版）');

  // ===== 狀態變量 =====
  let selectedSlots = []; // 已選時段 [{classroomId, hour, classroomName, classroomLocation}]
  let isDragging = false; // 是否處於拖曳狀態
  let dragMode = 'select'; // 'select' 選取 | 'deselect' 取消選取
  let dragClassroomId = null; // 限制只能在同一教室內拖曳

  // ===== 拖曳選取特定變量 =====
  let dragStartCell = null; // 拖曳開始的單元格
  let dragStartSelected = false; // 開始拖曳時，起點格是否已選中
  let hasSelectedAfterStart = false; // 起點之後的範圍內是否有已選中的格子
  let dragRangeCells = []; // 當前拖曳範圍內的所有單元格
  let dragEndCell = null; // 當前拖曳結束的單元格
  let lastClickedCell = null; // 防止 mouseup 後 click 事件重複觸發

  // ===== DOM元素引用 =====
  const timetable = document.getElementById('booking-timetable'); // 新版容器
  const timeGrid = document.getElementById('time-grid'); // 舊版容器（兼容）
  const bookingFormBox =
    document.getElementById('booking-form-container') ||
    document.getElementById('booking-form'); // 兼容舊版
  const selectedSlotsList = document.getElementById('selected-slots-list');
  const selectedSlotsInput = document.getElementById('selected_slots');

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

      // 已預約時段標記為禁用（但要顯示提示框）
      if (
        cell.classList.contains('time-slot-booked') ||
        cell.classList.contains('booked')
      ) {
        cell.classList.add('slot-disabled');
        // 設置幫助游標，表示有更多信息可查看
        cell.style.cursor = 'help';
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

  // 獲取拖曳範圍內的所有單元格
  function getDragRangeCells(startCell, endCell) {
    if (!startCell || !endCell) return [];

    const startSlot = cellToSlot(startCell);
    const endSlot = cellToSlot(endCell);

    if (
      !startSlot ||
      !endSlot ||
      startSlot.classroomId !== endSlot.classroomId
    ) {
      return []; // 不同教室，返回空
    }

    // 確定範圍的起止小時（不在乎方向，總是從小到大）
    const startHour = Math.min(startSlot.hour, endSlot.hour);
    const endHour = Math.max(startSlot.hour, endSlot.hour);
    const classroomId = startSlot.classroomId;

    // 找出該範圍內的所有可用單元格
    const cells = [];
    for (let h = startHour; h <= endHour; h++) {
      // 同時兼容新舊版格式
      const cell =
        document.querySelector(
          `.time-slot[data-classroom-id="${classroomId}"][data-hour="${h}"]`
        ) ||
        (classroomId === 1 &&
          document.querySelector(`.time-cell[data-hour="${h}"]:not(.booked)`));

      if (cell && !isCellDisabled(cell)) {
        cells.push(cell);
      }
    }

    return cells;
  }

  // 檢查拖曳範圍內是否有已選中的格子（不包括起點）
  function checkRangeHasSelected(rangeCells, startCell) {
    if (rangeCells.length <= 1) return false; // 只有起點格或空範圍

    return rangeCells.some((cell) => {
      if (cell === startCell) return false; // 排除起點

      const slot = cellToSlot(cell);
      if (!slot) return false;

      return findSlotIndex(slot.classroomId, slot.hour) !== -1; // 是否已選中
    });
  }

  // 初始化滑鼠拖曳選取功能
  function initializeDragSelect() {
    if (!timetable && !timeGrid) return; // 沒有表格容器
    const container = timetable || timeGrid;

    // 滑鼠拖曳開始
    container.addEventListener('mousedown', function (e) {
      // 找到點擊的時段單元格
      const target =
        e.target.closest('.time-slot') ||
        e.target.closest('.time-cell:not(.booked)');
      if (!target || isCellDisabled(target)) return;

      // 獲取時段信息
      const slot = cellToSlot(target);
      if (!slot) return;

      // 記錄拖曳起點信息
      dragStartCell = target;
      dragStartSelected = findSlotIndex(slot.classroomId, slot.hour) !== -1;
      dragClassroomId = slot.classroomId;
      isDragging = true;
      hasSelectedAfterStart = false;
      dragRangeCells = [target]; // 初始範圍只有起點格

      console.log('開始拖曳:', {
        classroomId: slot.classroomId,
        hour: slot.hour,
        startSelected: dragStartSelected,
      });

      // 防止點擊事件的重複觸發
      e.preventDefault();
    });

    // 滑鼠移動處理
    container.addEventListener('mouseover', function (e) {
      if (!isDragging || !dragStartCell) return;

      // 找到當前滑過的單元格
      const target =
        e.target.closest('.time-slot') ||
        e.target.closest('.time-cell:not(.booked)');
      if (!target || isCellDisabled(target)) return;

      const slot = cellToSlot(target);
      if (!slot || slot.classroomId !== dragClassroomId) return;

      // 更新結束點和拖曳範圍
      dragEndCell = target;
      dragRangeCells = getDragRangeCells(dragStartCell, target);

      // 檢查範圍內是否有已選中的格子（除起點外）
      hasSelectedAfterStart = checkRangeHasSelected(
        dragRangeCells,
        dragStartCell
      );

      // 清除所有臨時樣式並重新應用
      document.querySelectorAll('.drag-preview').forEach((cell) => {
        cell.classList.remove('drag-preview', 'drag-select', 'drag-deselect');
      });

      // 根據四種情境應用臨時樣式
      dragRangeCells.forEach((cell) => {
        if (cell === dragStartCell) return; // 起點格不應用臨時樣式

        const cellSlot = cellToSlot(cell);
        const isSelected =
          findSlotIndex(cellSlot.classroomId, cellSlot.hour) !== -1;

        cell.classList.add('drag-preview');

        // 情境1: !startSelected && !hasSelectedAfter - 全範圍選取
        if (!dragStartSelected && !hasSelectedAfterStart) {
          if (!isSelected) cell.classList.add('drag-select');
        }

        // 情境2: startSelected && !hasSelectedAfter - 僅取消起點，不動其他
        // 不對範圍內其他格做任何操作

        // 情境3: !startSelected && hasSelectedAfter - 選取未選，保留已選
        else if (!dragStartSelected && hasSelectedAfterStart) {
          if (!isSelected) cell.classList.add('drag-select');
        }

        // 情境4: startSelected && hasSelectedAfter - 全範圍取消
        else if (dragStartSelected && hasSelectedAfterStart) {
          if (isSelected) cell.classList.add('drag-deselect');
        }
      });
    });
  }

  // 初始化點擊事件處理
  function initializeClickHandler() {
    if (!timetable && !timeGrid) return;
    const container = timetable || timeGrid;

    // 使用事件委託處理點擊
    container.addEventListener('click', function (e) {
      // 如果是從拖曳結束後的事件，忽略處理以避免重複觸發
      if (
        isDragging ||
        lastClickedCell === e.target.closest('.time-slot') ||
        lastClickedCell === e.target.closest('.time-cell:not(.booked)')
      ) {
        // 重置最後點擊的單元格，以便後續點擊可以被處理
        lastClickedCell = null;
        return;
      }

      // 找到點擊的時段單元格
      const target =
        e.target.closest('.time-slot') ||
        e.target.closest('.time-cell:not(.booked)');
      if (!target) return;

      console.log('點擊了單元格:', target);

      // 忽略已禁用單元格
      if (isCellDisabled(target)) {
        console.log('單元格已禁用');
        return;
      }

      // 獲取時段信息
      const slot = cellToSlot(target);
      if (!slot) {
        console.log('無法解析時段信息');
        return;
      }

      console.log('時段信息:', slot);

      // 切換選中狀態
      toggleSlot(
        target,
        slot.classroomId,
        slot.hour,
        slot.classroomName,
        slot.classroomLocation
      );
      updateFormDisplay();
    });
  }

  // 為已預約時段初始化 Bootstrap 提示框
  function initializeTooltips() {
    console.log('初始化 Bootstrap 提示框...');
    const bookedCells = document.querySelectorAll(
      '.time-slot-booked, .time-cell.booked'
    );
    console.log('找到已預約單元格數量:', bookedCells.length);
    if (!bookedCells.length) return;

    // 檢查是否已加載 Bootstrap tooltip 功能
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
      console.error(
        'Bootstrap tooltip 功能未載入！請確認已正確引入 Bootstrap JS'
      );
      return;
    }

    // 為每個已預約單元格設置 Bootstrap tooltip
    bookedCells.forEach((cell) => {
      // 移除可能存在的禁止樣式，設置為幫助游標
      cell.style.cursor = 'help';

      // 檢查是否有預約數據
      const userName = cell.getAttribute('data-user');
      const userEmail = cell.getAttribute('data-email');
      const purpose = cell.getAttribute('data-purpose');

      if (!userName && !userEmail) {
        console.log('單元格缺少預約數據:', cell);
        return;
      }

      // 創建 tooltip 內容，使用 HTML 格式化
      const tooltipContent = `
        <div class="booking-tooltip">
          <div class="booking-tooltip-header">預約資訊</div>
          <div class="booking-tooltip-content">
            <div class="booking-tooltip-row"><strong>租借人:</strong> ${
              userName || '未指定'
            }</div>
            <div class="booking-tooltip-row"><strong>聯絡方式:</strong> ${
              userEmail || '未指定'
            }</div>
            <div class="booking-tooltip-row"><strong>用途:</strong> ${
              purpose || '未指定'
            }</div>
          </div>
        </div>
      `;

      // 檢查是否已經具有 data-bs-toggle 屬性
      if (!cell.hasAttribute('data-bs-toggle')) {
        cell.setAttribute('data-bs-toggle', 'tooltip');
        cell.setAttribute('data-bs-html', 'true');
        cell.setAttribute('data-bs-title', tooltipContent);
        cell.setAttribute('data-bs-placement', 'auto');
        cell.setAttribute('data-bs-container', 'body');
      }

      // 只有當還沒有初始化過 tooltip 時才初始化
      if (!bootstrap.Tooltip.getInstance(cell)) {
        // 初始化 Bootstrap tooltip
        new bootstrap.Tooltip(cell, {
          trigger: 'hover focus',
          html: true,
          delay: { show: 200, hide: 100 },
          placement: 'auto',
          container: 'body',
          template:
            '<div class="tooltip booking-custom-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
        });
      }
    });

    console.log('Bootstrap 提示框初始化完成');
  }

  // 拖曳結束處理（在 document 上監聽，以確保即使滑鼠移出表格也能觸發）
  document.addEventListener('mouseup', function (e) {
    if (!isDragging || !dragStartCell || dragRangeCells.length === 0) {
      isDragging = false;
      dragStartCell = null;
      dragEndCell = null;
      dragRangeCells = [];
      return;
    }

    // 保存最後點擊的單元格，避免點擊事件重複觸發
    lastClickedCell =
      e.target.closest('.time-slot') ||
      e.target.closest('.time-cell:not(.booked)');

    // 根據四種情境執行操作
    console.log('拖曳結束，執行情境:', {
      startSelected: dragStartSelected,
      hasSelectedAfter: hasSelectedAfterStart,
      rangeSize: dragRangeCells.length,
    });

    // 情境1: !startSelected && !hasSelectedAfter - 全範圍選取
    if (!dragStartSelected && !hasSelectedAfterStart) {
      dragRangeCells.forEach((cell) => {
        const slot = cellToSlot(cell);
        if (!slot) return;

        const isSelected = findSlotIndex(slot.classroomId, slot.hour) !== -1;
        if (!isSelected) {
          toggleSlot(
            cell,
            slot.classroomId,
            slot.hour,
            slot.classroomName,
            slot.classroomLocation
          );
        }
      });
    }

    // 情境2: startSelected && !hasSelectedAfter - 僅取消起點
    else if (dragStartSelected && !hasSelectedAfterStart) {
      const startSlot = cellToSlot(dragStartCell);
      toggleSlot(
        dragStartCell,
        startSlot.classroomId,
        startSlot.hour,
        startSlot.classroomName,
        startSlot.classroomLocation
      );
    }

    // 情境3: !startSelected && hasSelectedAfter - 選取起點和所有未選，保留已選
    else if (!dragStartSelected && hasSelectedAfterStart) {
      dragRangeCells.forEach((cell) => {
        const slot = cellToSlot(cell);
        if (!slot) return;

        const isSelected = findSlotIndex(slot.classroomId, slot.hour) !== -1;
        if (!isSelected) {
          toggleSlot(
            cell,
            slot.classroomId,
            slot.hour,
            slot.classroomName,
            slot.classroomLocation
          );
        }
      });
    }

    // 情境4: startSelected && hasSelectedAfter - 取消全範圍已選
    else if (dragStartSelected && hasSelectedAfterStart) {
      dragRangeCells.forEach((cell) => {
        const slot = cellToSlot(cell);
        if (!slot) return;

        const isSelected = findSlotIndex(slot.classroomId, slot.hour) !== -1;
        if (isSelected) {
          toggleSlot(
            cell,
            slot.classroomId,
            slot.hour,
            slot.classroomName,
            slot.classroomLocation
          );
        }
      });
    }

    // 清除所有臨時樣式
    document.querySelectorAll('.drag-preview').forEach((cell) => {
      cell.classList.remove('drag-preview', 'drag-select', 'drag-deselect');
    });

    // 更新表單顯示
    updateFormDisplay();

    // 重置拖曳狀態
    isDragging = false;
    dragStartCell = null;
    dragEndCell = null;
    dragRangeCells = [];
    dragClassroomId = null;
  });

  // 如果滑鼠離開頁面，確保也能結束拖曳
  document.addEventListener('mouseleave', function () {
    if (isDragging) {
      document.querySelectorAll('.drag-preview').forEach((cell) => {
        cell.classList.remove('drag-preview', 'drag-select', 'drag-deselect');
      });
      isDragging = false;
      dragStartCell = null;
      dragEndCell = null;
      dragRangeCells = [];
      dragClassroomId = null;
    }
  });

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
    // 檢測是否為移動設備
    const isMobile =
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent
      );
    if (isMobile) {
      console.log('移動裝置偵測，桌面版初始化停止');
      // 移動裝置的初始化會在 booking-mobile.js 中處理
      return;
    }

    console.log('=== 初始化桌面版預約系統 ===');

    // 檢查 Bootstrap 是否可用
    if (typeof bootstrap === 'undefined') {
      console.warn('警告: Bootstrap JS 未載入，某些功能可能無法正常運作');
    } else {
      console.log(
        'Bootstrap 已載入，版本:',
        bootstrap.Tooltip.VERSION || '未知'
      );
    }

    // 標記禁用單元格
    markDisabledCells();

    // 確保所有已預約單元格的游標樣式是幫助形式
    document
      .querySelectorAll('.time-slot-booked, .time-cell.booked')
      .forEach((cell) => {
        cell.style.cursor = 'help';
      });

    // 初始化 Bootstrap 提示框
    initializeTooltips();

    // 還原已選取的時段
    restoreSelectedSlots();

    // 初始化拖曳選取功能
    initializeDragSelect();

    // 初始化點擊事件處理
    initializeClickHandler();

    // 初始化自動篩選器
    initializeAutoFilters();

    // 初始更新表單顯示
    updateFormDisplay();

    // 延遲檢查並確保 tooltip 正確初始化
    setTimeout(() => {
      // 檢查 Bootstrap 是否可用
      if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        console.warn('警告: Bootstrap Tooltip 功能不可用，嘗試使用舊版提示框');
        return;
      }

      // 檢查是否有未初始化的已預約單元格
      document
        .querySelectorAll('.time-slot-booked, .time-cell.booked')
        .forEach((cell) => {
          if (!bootstrap.Tooltip.getInstance(cell)) {
            console.log('發現未初始化的單元格，重新應用 tooltip');

            // 重新設置數據屬性
            const userName = cell.getAttribute('data-user') || '未指定';
            const userEmail = cell.getAttribute('data-email') || '未指定';
            const purpose = cell.getAttribute('data-purpose') || '未指定';

            const tooltipContent = `
              <div class="booking-tooltip">
                <div class="booking-tooltip-header">預約資訊</div>
                <div class="booking-tooltip-content">
                  <div class="booking-tooltip-row"><strong>租借人:</strong> ${userName}</div>
                  <div class="booking-tooltip-row"><strong>聯絡方式:</strong> ${userEmail}</div>
                  <div class="booking-tooltip-row"><strong>用途:</strong> ${purpose}</div>
                </div>
              </div>
            `;

            // 設置 Bootstrap tooltip 屬性
            cell.setAttribute('data-bs-toggle', 'tooltip');
            cell.setAttribute('data-bs-html', 'true');
            cell.setAttribute('data-bs-title', tooltipContent);
            cell.setAttribute('data-bs-placement', 'auto');

            // 初始化 Bootstrap tooltip
            new bootstrap.Tooltip(cell, {
              trigger: 'hover focus',
              html: true,
              delay: { show: 200, hide: 100 },
              placement: 'auto',
              container: 'body',
              template:
                '<div class="tooltip booking-custom-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
            });
          }

          // 確保游標樣式為 help
          if (cell.style.cursor !== 'help') {
            cell.style.cursor = 'help';
          }
        });

      console.log('Bootstrap Tooltips 檢查完成');
    }, 1000);
  }

  // 全局清除選擇函數，供「清除選擇」按鈕調用
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

  // 初始化系統
  initialize();
});
