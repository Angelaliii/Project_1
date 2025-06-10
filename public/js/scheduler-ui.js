// scheduler-ui.js - 排程系統的UI元素和視覺呈現

// 導入公用變數和函數
let SchedulerUI = (function () {
  // 渲染空間列表
  function renderSpaceList(spaces) {
    const spaceList = document.getElementById('space-list');
    if (!spaceList) return;

    spaceList.innerHTML = '';
    spaces.forEach((space) => {
      const item = document.createElement('li');
      item.className = 'space-item';
      item.dataset.id = space.classroom_ID;
      item.innerHTML = `
          <div class="space-name">${space.classroom_name}</div>
          <div class="space-details">${space.building} ${space.room || ''}</div>
      `;
      item.addEventListener('click', function () {
        window.SchedulerCore.selectSpace(space.classroom_ID);
      });
      spaceList.appendChild(item);
    });
  }

  // 渲染排程表格
  function renderScheduleGrid(slots, classroom) {
    const gridContainer = document.getElementById('scheduler-grid');
    if (!gridContainer) return;

    console.log('渲染排程表格，slots:', slots, 'classroom:', classroom);

    // 生成時間段（8:00-20:30，使用特定時間間隔）
    const timeSlots = [];
    // 上午時段：8:00-12:00，每小時一個時段
    for (let hour = 8; hour <= 11; hour++) {
      timeSlots.push(`${hour}:00`);
    }
    // 中午時段：12:00-13:30
    timeSlots.push('12:00');
    // 下午時段：13:30-20:30，每小時一個時段
    for (let hour = 13; hour <= 20; hour++) {
      timeSlots.push(`${hour}:30`);
    }

    // 創建表格
    let tableHTML =
      '<table><tr><th class="time-cell">時間</th><th class="date-cell">' +
      window.SchedulerUtils.formatDateHeader(window.SchedulerCore.startDate) +
      '</th></tr>';

    timeSlots.forEach((time, index) => {
      // 忽略最後一個時間點，因為它只是結束時間
      if (index >= timeSlots.length - 1) return;

      const hour = parseInt(time.split(':')[0]);
      const minute = time.split(':')[1];
      const timeKey = hour.toString().padStart(2, '0') + (minute || '00');

      // 計算結束時間
      let nextTime;
      if (index < timeSlots.length - 1) {
        nextTime = timeSlots[index + 1];
      } else {
        nextTime = '21:30'; // 最後一個時段的結束時間
      }

      // 顯示時間範圍
      const displayTime = `${time}-${nextTime}`;

      // 檢查這個時段是否已被預訂
      const slot = slots.find((s) => s.hour === hour);
      const isBooked = slot ? !slot.available : false;

      const cellClass = isBooked ? 'grid-cell-booked' : 'grid-cell-available';

      tableHTML += `
          <tr>
              <td class="time-cell">${displayTime}</td>
              <td class="grid-cell ${cellClass}" data-time="${timeKey}" data-index="${index}"></td>
          </tr>
      `;
    });

    tableHTML += '</table>';
    gridContainer.innerHTML = tableHTML;

    // 更新教室信息
    const classroomTitle = document.getElementById('classroom-title');
    if (classroomTitle && classroom) {
      classroomTitle.textContent = classroom.classroom_name;
    }

    // 綁定拖曳選擇事件
    window.SchedulerEvents.setupGridEvents();
  }

  // 根據建築物過濾空間
  function filterSpaces(building) {
    if (building === 'all') {
      document.querySelectorAll('.space-item').forEach((item) => {
        item.style.display = '';
      });
      return;
    }

    document.querySelectorAll('.space-item').forEach((item) => {
      const details = item.querySelector('.space-details').textContent;
      if (details.includes(building)) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
  }

  // 顯示預約對話框
  function showBookingModal() {
    if (window.SchedulerCore.selectedCells.length === 0) return;

    const start = Math.min(...window.SchedulerCore.selectedCells);
    const end = Math.max(...window.SchedulerCore.selectedCells);

    const startCell = document.querySelector(
      `.grid-cell[data-index="${start}"]`
    );
    const endCell = document.querySelector(`.grid-cell[data-index="${end}"]`);

    if (!startCell || !endCell) return;

    const timeSlots = [];
    for (let hour = 8; hour <= 22; hour++) {
      timeSlots.push(`${hour}:00`);
    }

    // 計算開始和結束時間
    const startTime = timeSlots[start];
    let endTime;
    if (end < timeSlots.length - 1) {
      endTime = timeSlots[end + 1];
    } else {
      endTime = '21:30'; // 最後一個時段結束時間
    }

    const classroom = document.getElementById('classroom-title').textContent;
    const bookingDate = window.SchedulerUtils.formatDateDisplay(
      window.SchedulerCore.startDate
    );

    // 創建和顯示Modal
    const modal = document.createElement('div');
    modal.className = 'booking-modal';
    modal.id = 'booking-modal';
    modal.innerHTML = `
        <div class="booking-modal-content">
            <div class="booking-modal-header">
                <h3 class="booking-modal-title">確認預約</h3>
                <button class="booking-modal-close">&times;</button>
            </div>
            <div class="booking-modal-body">
                <div class="booking-details">
                    <div class="booking-detail-label">空間：</div>
                    <div class="booking-detail-value">${classroom}</div>
                    
                    <div class="booking-detail-label">日期：</div>
                    <div class="booking-detail-value">${bookingDate}</div>
                    
                    <div class="booking-detail-label">時間：</div>
                    <div class="booking-detail-value">${startTime} - ${endTime}</div>
                    
                    <div class="booking-detail-label">預約目的：</div>
                    <div class="booking-detail-value">
                      <div class="form-group">
                        <select id="booking-purpose-select" class="form-control">
                          <option value="">請選擇使用目的</option>
                          <option value="課堂活動">課堂活動</option>
                          <option value="研習會">研習會</option>
                          <option value="討論會議">討論會議</option>
                          <option value="活動排練">活動排練</option>
                          <option value="其他">其他</option>
                        </select>
                      </div>
                      <div id="other-purpose-group" class="form-group" style="display: none; margin-top: 10px;">
                        <input type="text" id="other-purpose" class="form-control" placeholder="請輸入其他使用目的">
                      </div>
                    </div>
                </div>
            </div>
            <div class="booking-modal-footer">
                <button class="scheduler-btn" id="cancel-booking">取消</button>
                <button class="scheduler-btn scheduler-btn-primary" id="confirm-booking">確認預約</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // 添加動畫效果
    setTimeout(() => modal.classList.add('active'), 10);

    // 綁定事件
    const closeButton = modal.querySelector('.booking-modal-close');
    const cancelButton = document.getElementById('cancel-booking');
    const confirmButton = document.getElementById('confirm-booking');

    function closeModal() {
      modal.classList.remove('active');
      setTimeout(() => modal.remove(), 300);
    }

    closeButton.addEventListener('click', closeModal);
    cancelButton.addEventListener('click', closeModal);

    confirmButton.addEventListener('click', function () {
      // 獲取預約目的
      const purposeSelect = document.getElementById('booking-purpose-select');
      const otherPurpose = document.getElementById('other-purpose');
      let bookingPurpose = purposeSelect.value;

      // 檢查是否選擇了目的
      if (!bookingPurpose) {
        window.SchedulerUtils.showAlert('error', '請選擇預約目的');
        return;
      }

      // 如果選擇了"其他"，則檢查是否填寫了其他目的
      if (bookingPurpose === '其他') {
        if (!otherPurpose.value.trim()) {
          window.SchedulerUtils.showAlert('error', '請填寫其他預約目的');
          return;
        }
        bookingPurpose = otherPurpose.value.trim();
      }

      // 顯示確認對話框
      if (confirm(`確定要預約嗎？預約目的：${bookingPurpose}`)) {
        // 提交預約
        window.SchedulerBooking.submitBooking(
          window.SchedulerCore.currentSpaceId,
          window.SchedulerCore.startDate,
          start,
          end,
          bookingPurpose
        );
        closeModal();
      }
    });

    // 添加目的選單變更事件
    const purposeSelect = document.getElementById('booking-purpose-select');
    const otherPurposeGroup = document.getElementById('other-purpose-group');

    purposeSelect.addEventListener('change', function () {
      if (this.value === '其他') {
        otherPurposeGroup.style.display = 'block';
      } else {
        otherPurposeGroup.style.display = 'none';
      }
    });
  }

  // 清除選擇
  function clearSelection() {
    document.querySelectorAll('.grid-cell-selected').forEach((cell) => {
      cell.classList.remove('grid-cell-selected');
    });
    window.SchedulerCore.selectedCells = [];
  }

  // 更新選擇的單元格
  function updateSelection() {
    clearSelection();

    const start = Math.min(
      window.SchedulerCore.dragStart,
      window.SchedulerCore.dragEnd
    );
    const end = Math.max(
      window.SchedulerCore.dragStart,
      window.SchedulerCore.dragEnd
    );

    // 檢查選擇範圍中是否有已預訂的單元格
    let hasBookedCell = false;
    for (let i = start; i <= end; i++) {
      const cell = document.querySelector(`.grid-cell[data-index="${i}"]`);
      if (cell && cell.classList.contains('grid-cell-booked')) {
        hasBookedCell = true;
        break;
      }
    }

    if (hasBookedCell) {
      window.SchedulerUtils.showAlert('warning', '選擇範圍中包含已預訂的時段');
      return;
    }

    // 更新選擇
    for (let i = start; i <= end; i++) {
      const cell = document.querySelector(`.grid-cell[data-index="${i}"]`);
      if (cell && !cell.classList.contains('grid-cell-booked')) {
        cell.classList.add('grid-cell-selected');
        window.SchedulerCore.selectedCells.push(i);
      }
    }
  }

  // 返回公開的方法
  return {
    renderSpaceList,
    renderScheduleGrid,
    filterSpaces,
    showBookingModal,
    clearSelection,
    updateSelection,
  };
})();

// 將UI模組導出到全局
window.SchedulerUI = SchedulerUI;
