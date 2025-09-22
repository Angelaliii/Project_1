// booking.js — 桌面版：滑鼠拖曳（不做還原已選；只由 loader 初始化）
(function () {
  const LONG_HOURS_START = 8; // 營運 08:00
  const LONG_HOURS_END = 21; // 到 21:00（可選起始小時 8..20）

  // 輔助：補零
  const pad2 = (n) => String(n).padStart(2, '0');

  // 是否為今天
  function isBookingDateToday(dateStr) {
    if (!dateStr) return false;
    const [y, m, d] = dateStr.split('-').map((x) => parseInt(x, 10));
    if (!y || !m || !d) return false;
    const today = new Date();
    const dt = new Date(y, m - 1, d);
    const t0 = new Date(
      today.getFullYear(),
      today.getMonth(),
      today.getDate()
    ).getTime();
    const d0 = new Date(
      dt.getFullYear(),
      dt.getMonth(),
      dt.getDate()
    ).getTime();
    return t0 === d0;
  }

  // 取得容器、常用節點
  function getDOM() {
    const timetable =
      document.getElementById('booking-timetable') ||
      document.getElementById('time-grid');
    const formBox =
      document.getElementById('booking-form-container') ||
      document.getElementById('booking-form');
    const selectedInput = document.getElementById('selected_slots');
    const bookingDate = timetable ? timetable.dataset.bookingDate : '';
    return { timetable, formBox, selectedInput, bookingDate };
  }

  // 禁用已過時段與已預約
  function markDisabledCells(bookingDate) {
    const isToday = isBookingDateToday(bookingDate);
    const nowHour = new Date().getHours();
    // 可被互動的（舊 .time-cell 也支援）
    const allAvailable = document.querySelectorAll(
      '.time-slot-available, .time-cell:not(.booked)'
    );
    // 已預約
    const allBooked = document.querySelectorAll(
      '.time-slot-booked, .time-cell.booked'
    );

    // 已預約 → 不可點
    allBooked.forEach((cell) => {
      cell.classList.add('slot-disabled');
    });

    // 今日過去時段 → 灰化 + 不可點
    if (isToday) {
      allAvailable.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour, 10);
        if (!isNaN(hour) && hour <= nowHour) {
          cell.classList.add('time-slot-past', 'slot-disabled');
          cell.style.pointerEvents = 'none';
        }
      });
    }
  }

  // 建立（或使用 Bootstrap）tooltip
  function initTooltips() {
    const bookedCells = document.querySelectorAll(
      '.time-slot-booked, .time-cell.booked'
    );

    // 優先使用 Bootstrap 5
    if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
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
          const inst =
            bootstrap.Tooltip.getInstance(cell) ||
            new bootstrap.Tooltip(cell, {
              customClass: 'booking-custom-tooltip', // 走你的 tooltip CSS
              placement: 'bottom',
              trigger: 'hover focus',
            });
        } catch (e) {
          // 忽略
        }
      });
      return;
    }

    // 後備：原生 tooltip（簡化）
    let tip;
    function show(cell) {
      if (tip) tip.remove();
      tip = document.createElement('div');
      tip.className = 'tooltip-container visible';
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
      tip.style.left = rect.left + 'px';
      tip.style.top = rect.bottom + 10 + 'px';
    }
    function hide() {
      if (tip) {
        tip.remove();
        tip = null;
      }
    }

    bookedCells.forEach((cell) => {
      cell.addEventListener('mouseenter', () => show(cell));
      cell.addEventListener('mouseleave', hide);
      cell.addEventListener('click', (e) => {
        // 行動點一下切換
        if (tip) hide();
        else show(cell);
        e.stopPropagation();
      });
    });
    document.addEventListener('click', hide);
  }

  // 更新隱藏欄位 & 表單顯示
  function updateForm(selectedSlots, dom) {
    const { formBox, selectedInput } = dom;
    if (selectedInput) selectedInput.value = JSON.stringify(selectedSlots);
    if (formBox) {
      if (selectedSlots.length > 0) {
        formBox.classList.add('visible');
        formBox.style.display = 'block';
      } else {
        formBox.classList.remove('visible');
        formBox.style.display = 'none';
      }
    }
  }

  // 拖曳規則（四情境）
  function applyDragDecision(
    rangeCells,
    startSelected,
    hasSelectedAfter,
    selectedSlots,
    classroomId
  ) {
    // 工具：判斷是否已選
    const isPicked = (cid, h) =>
      selectedSlots.some((s) => s.classroomId === cid && s.hour === h);
    const add = (cell) => {
      const hour = parseInt(cell.dataset.hour, 10);
      if (isNaN(hour)) return;
      if (!isPicked(classroomId, hour)) {
        selectedSlots.push({
          classroomId,
          hour,
          classroomName: cell.dataset.classroomName || '',
          classroomLocation: cell.dataset.classroomLocation || '',
        });
        cell.classList.add('time-slot-selected');
      }
    };
    const del = (cell) => {
      const hour = parseInt(cell.dataset.hour, 10);
      if (isNaN(hour)) return;
      const idx = selectedSlots.findIndex(
        (s) => s.classroomId === classroomId && s.hour === hour
      );
      if (idx !== -1) selectedSlots.splice(idx, 1);
      cell.classList.remove('time-slot-selected');
    };

    if (!startSelected && !hasSelectedAfter) {
      // 情境 1：全選
      rangeCells.forEach(add);
    } else if (startSelected && !hasSelectedAfter) {
      // 情境 2：只取消 S
      del(rangeCells[0]);
    } else if (!startSelected && hasSelectedAfter) {
      // 情境 3：選 S 與未選的新格；既選不變
      rangeCells.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour, 10);
        if (
          !selectedSlots.some(
            (s) => s.classroomId === classroomId && s.hour === hour
          )
        ) {
          add(cell);
        }
      });
    } else {
      // 情境 4：取消範圍內所有已選
      rangeCells.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour, 10);
        if (
          selectedSlots.some(
            (s) => s.classroomId === classroomId && s.hour === hour
          )
        ) {
          del(cell);
        }
      });
    }
  }

  // 取得 S..E 的連續格（同教室）
  function getRangeCells(startCell, endCell) {
    const cid = parseInt(startCell.dataset.classroomId || '1', 10);
    if (parseInt(endCell.dataset.classroomId || '1', 10) !== cid) return [];
    const h1 = parseInt(startCell.dataset.hour, 10);
    const h2 = parseInt(endCell.dataset.hour, 10);
    if (isNaN(h1) || isNaN(h2)) return [];
    const lo = Math.min(h1, h2),
      hi = Math.max(h1, h2);
    const cells = [];
    for (let h = lo; h <= hi; h++) {
      const q = document.querySelector(
        `.time-slot-available[data-classroom-id="${cid}"][data-hour="${h}"], .time-cell[data-hour="${h}"]:not(.booked)`
      );
      if (q) cells.push(q);
    }
    return cells;
  }

  function initDesktop() {
    const dom = getDOM();
    if (!dom.timetable) return;

    // 移除還原已選功能（按你的要求）

    // 禁用狀態與 tooltip
    markDisabledCells(dom.bookingDate);
    initTooltips();

    // 狀態
    let selectedSlots = []; // [{classroomId, hour, name, location}]
    let dragging = false;
    let startCell = null;

    // 點擊（當作 S=E 的拖曳）
    document.addEventListener('click', (e) => {
      const cell = e.target.closest(
        '.time-slot-available, .time-cell:not(.booked)'
      );
      if (!cell || cell.classList.contains('slot-disabled')) return;

      const range = [cell];
      const classroomId = parseInt(cell.dataset.classroomId || '1', 10);
      const startSelected = cell.classList.contains('time-slot-selected');
      const hasSelectedAfter = false;
      applyDragDecision(
        range,
        startSelected,
        hasSelectedAfter,
        selectedSlots,
        classroomId
      );
      updateForm(selectedSlots, dom);
    });

    // 滑鼠拖曳
    document.addEventListener('mousedown', (e) => {
      const cell = e.target.closest(
        '.time-slot-available, .time-cell:not(.booked)'
      );
      if (!cell || cell.classList.contains('slot-disabled')) return;
      dragging = true;
      startCell = cell;
      e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
      if (!dragging || !startCell) return;
      // 即時預覽簡化：不做，避免複雜度；結束時一次套用
    });

    document.addEventListener('mouseup', (e) => {
      if (!dragging || !startCell) return;
      const endCell =
        e.target.closest('.time-slot-available, .time-cell:not(.booked)') ||
        startCell;
      const rangeCells = getRangeCells(startCell, endCell);
      if (rangeCells.length > 0) {
        const classroomId = parseInt(startCell.dataset.classroomId || '1', 10);
        const startSelected =
          startCell.classList.contains('time-slot-selected');
        const hasSelectedAfter = rangeCells
          .slice(1)
          .some((c) => c.classList.contains('time-slot-selected'));
        applyDragDecision(
          rangeCells,
          startSelected,
          hasSelectedAfter,
          selectedSlots,
          classroomId
        );
        updateForm(selectedSlots, dom);
      }
      dragging = false;
      startCell = null;
    });

    // 公開 API
    window.Booking._debugGetSelected = () => selectedSlots.slice();
  }

  window.Booking = window.Booking || {};
  window.Booking.initialize = initDesktop;
})();
