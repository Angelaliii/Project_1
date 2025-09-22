// booking-mobile.js — 行動裝置：長按 300ms 進入拖曳（不做還原已選；只由 loader 初始化）
(function () {
  const LONG_PRESS_MS = 300;

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

  function markDisabledCells(bookingDate) {
    const isToday = isBookingDateToday(bookingDate);
    const nowHour = new Date().getHours();
    const allAvailable = document.querySelectorAll(
      '.time-slot-available, .time-cell:not(.booked)'
    );
    const allBooked = document.querySelectorAll(
      '.time-slot-booked, .time-cell.booked'
    );
    allBooked.forEach((cell) => cell.classList.add('slot-disabled'));
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

  function initTooltips() {
    const bookedCells = document.querySelectorAll(
      '.time-slot-booked, .time-cell.booked'
    );
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
              customClass: 'booking-custom-tooltip',
              placement: 'bottom',
              trigger: 'click', // 行動裝置以點擊開關
            });
        } catch (e) {}
      });
      return;
    }
    // 後備：原生簡易 tooltip
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
      cell.addEventListener('click', (e) => {
        if (tip) hide();
        else show(cell);
        e.stopPropagation();
      });
    });
    document.addEventListener('click', hide);
  }

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

  function applyDragDecision(
    rangeCells,
    startSelected,
    hasSelectedAfter,
    selectedSlots,
    classroomId
  ) {
    const isPicked = (cid, h) =>
      selectedSlots.some((s) => s.classroomId === cid && s.hour === h);
    const add = (cell) => {
      const hour = parseInt(cell.dataset.hour, 10);
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
      const idx = selectedSlots.findIndex(
        (s) => s.classroomId === classroomId && s.hour === hour
      );
      if (idx !== -1) selectedSlots.splice(idx, 1);
      cell.classList.remove('time-slot-selected');
    };

    if (!startSelected && !hasSelectedAfter) {
      rangeCells.forEach(add);
    } else if (startSelected && !hasSelectedAfter) {
      del(rangeCells[0]);
    } else if (!startSelected && hasSelectedAfter) {
      rangeCells.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour, 10);
        if (
          !selectedSlots.some(
            (s) => s.classroomId === classroomId && s.hour === hour
          )
        )
          add(cell);
      });
    } else {
      rangeCells.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour, 10);
        if (
          selectedSlots.some(
            (s) => s.classroomId === classroomId && s.hour === hour
          )
        )
          del(cell);
      });
    }
  }

  function initMobile() {
    const dom = getDOM();
    if (!dom.timetable) return;

    markDisabledCells(dom.bookingDate);
    initTooltips();

    let selectedSlots = [];
    let longPressTimer = null;
    let dragging = false;
    let startCell = null;

    // 點一下（非長按）視為單格 toggle（當作 S=E 規則）
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

    // 長按啟動拖曳
    document.addEventListener(
      'touchstart',
      (e) => {
        const cell = e.target.closest(
          '.time-slot-available, .time-cell:not(.booked)'
        );
        if (!cell || cell.classList.contains('slot-disabled')) return;

        startCell = cell;
        longPressTimer = setTimeout(() => {
          dragging = true;
          // 進入拖曳時，先套用 S（其餘等移動時）
          const classroomId = parseInt(
            startCell.dataset.classroomId || '1',
            10
          );
          const startSelected =
            startCell.classList.contains('time-slot-selected');
          const hasSelectedAfter = false;
          applyDragDecision(
            [startCell],
            startSelected,
            hasSelectedAfter,
            selectedSlots,
            classroomId
          );
          updateForm(selectedSlots, dom);
        }, LONG_PRESS_MS);
      },
      { passive: true }
    );

    document.addEventListener(
      'touchmove',
      (e) => {
        if (!dragging) return;
        const t = e.touches[0];
        const el = document.elementFromPoint(t.clientX, t.clientY);
        const cell =
          el && el.closest('.time-slot-available, .time-cell:not(.booked)');
        if (!cell || cell.classList.contains('slot-disabled') || !startCell)
          return;

        const rangeCells = getRangeCells(startCell, cell);
        if (rangeCells.length > 0) {
          const classroomId = parseInt(
            startCell.dataset.classroomId || '1',
            10
          );
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
        e.preventDefault();
      },
      { passive: false }
    );

    function endTouch() {
      clearTimeout(longPressTimer);
      longPressTimer = null;
      dragging = false;
      startCell = null;
    }
    document.addEventListener('touchend', endTouch, { passive: true });
    document.addEventListener('touchcancel', endTouch, { passive: true });

    window.Booking._debugGetSelected = () => selectedSlots.slice();
  }

  window.Booking = window.Booking || {};
  window.Booking.initialize = initMobile;
})();
