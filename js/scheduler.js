// scheduler.js - 排程系統的前端交互邏輯

document.addEventListener('DOMContentLoaded', function () {
  // 全局變數
  let currentSpaceId = null;
  let startDate = new Date();
  let selectedCells = [];
  let isDragging = false;
  let dragStart = null;
  let dragEnd = null;

  // 初始化
  initDatePicker();
  loadSpaces();
  setupEventListeners();

  // 日期選擇器初始化
  function initDatePicker() {
    const datePicker = document.getElementById('date-picker');
    if (datePicker) {
      datePicker.valueAsDate = startDate;
      datePicker.addEventListener('change', function () {
        startDate = new Date(this.value);
        if (currentSpaceId) {
          loadSchedule(currentSpaceId, startDate);
        }
      });
    }

    // 前一天按鈕
    const prevDayBtn = document.getElementById('prev-day');
    if (prevDayBtn) {
      prevDayBtn.addEventListener('click', function () {
        startDate.setDate(startDate.getDate() - 1);
        if (datePicker) datePicker.valueAsDate = startDate;
        if (currentSpaceId) loadSchedule(currentSpaceId, startDate);
      });
    }

    // 後一天按鈕
    const nextDayBtn = document.getElementById('next-day');
    if (nextDayBtn) {
      nextDayBtn.addEventListener('click', function () {
        startDate.setDate(startDate.getDate() + 1);
        if (datePicker) datePicker.valueAsDate = startDate;
        if (currentSpaceId) loadSchedule(currentSpaceId, startDate);
      });
    }

    // 今天按鈕
    const todayBtn = document.getElementById('today-btn');
    if (todayBtn) {
      todayBtn.addEventListener('click', function () {
        startDate = new Date();
        if (datePicker) datePicker.valueAsDate = startDate;
        if (currentSpaceId) loadSchedule(currentSpaceId, startDate);
      });
    }
  }

  // 載入所有可用空間
  function loadSpaces() {
    showLoading(true);
    // 添加時間戳參數避免快取問題
    fetch('../api/classrooms/index.php?t=' + new Date().getTime())
      .then((response) => {
        if (!response.ok) {
          console.log('API 回應非 OK:', response.status, response.statusText);
          return response.text().then((text) => {
            console.log('API 錯誤詳情:', text);
            throw new Error(
              `HTTP 錯誤 ${response.status}: ${response.statusText}`
            );
          });
        }
        // 先檢查響應內容是否為空
        return response.text().then((text) => {
          if (!text) {
            throw new Error('API 返回空響應');
          }
          try {
            // 嘗試解析為JSON
            return JSON.parse(text);
          } catch (e) {
            console.log('JSON 解析錯誤，原始回應:', text);
            throw new Error('無效的 JSON 回應: ' + e.message);
          }
        });
      })
      .then((data) => {
        console.log('API 回應數據:', data);
        if (data.status === 'success') {
          renderSpaceList(data.classrooms);
          if (data.classrooms && data.classrooms.length > 0) {
            selectSpace(data.classrooms[0].classroom_ID);
          } else {
            console.log('沒有可用的教室');
            showAlert('info', '目前沒有可用的教室');
          }
        } else {
          showAlert(
            'error',
            '無法載入空間列表：' + (data.message || '未知錯誤')
          );
        }
        showLoading(false);
      })
      .catch((error) => {
        console.error('獲取空間列表時出錯:', error);
        showAlert('error', '獲取空間列表時出錯：' + error.message);
        showLoading(false);
      });
  }

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
                <div class="space-details">${space.building} ${
        space.room || ''
      }</div>
            `;
      item.addEventListener('click', function () {
        selectSpace(space.classroom_ID);
      });
      spaceList.appendChild(item);
    });
  }

  // 選擇空間
  function selectSpace(spaceId) {
    currentSpaceId = spaceId;

    // 更新選擇狀態的UI
    document.querySelectorAll('.space-item').forEach((item) => {
      item.classList.toggle('selected', item.dataset.id == spaceId);
    });

    // 載入該空間的排程
    loadSchedule(spaceId, startDate);
  } // 載入排程數據
  function loadSchedule(spaceId, date) {
    showLoading(true);
    clearSelection();

    const formattedDate = formatDate(date);
    console.log(`正在載入教室 ${spaceId} 在 ${formattedDate} 的排程`);

    // 確保使用絕對路徑，並加上時間戳防止快取
    const timestamp = new Date().getTime();
    const apiUrl = `/dashboard/Project_1/api/bookings/slots_fixed.php?classroom_id=${spaceId}&date=${formattedDate}&t=${timestamp}`;
    console.log('API URL:', apiUrl);

    fetch(apiUrl)
      .then((response) => {
        if (!response.ok) {
          return response.text().then((text) => {
            console.log('API 錯誤詳情:', text);
            throw new Error(
              `HTTP 錯誤 ${response.status}: ${response.statusText}`
            );
          });
        }
        return response.text().then((text) => {
          if (!text) {
            throw new Error('API 返回空響應');
          }
          try {
            return JSON.parse(text);
          } catch (e) {
            console.log('JSON 解析錯誤，原始回應:', text);
            throw new Error('無效的 JSON 回應: ' + e.message);
          }
        });
      })
      .then((data) => {
        if (data.status === 'success') {
          renderScheduleGrid(data.slots, data.classroom);
        } else {
          showAlert('error', '無法載入排程：' + data.message);
        }
        showLoading(false);
      })
      .catch((error) => {
        console.error('獲取排程數據時出錯:', error);
        showAlert('error', '獲取排程數據時出錯：' + error.message);
        showLoading(false);
      });
  }

  // 渲染排程表格
  function renderScheduleGrid(slots, classroom) {
    const gridContainer = document.getElementById('scheduler-grid');
    if (!gridContainer) return;

    console.log('渲染排程表格，slots:', slots, 'classroom:', classroom);

    // 生成時間段（8:00-22:00，每整點一個時段）
    const timeSlots = [];
    for (let hour = 8; hour <= 22; hour++) {
      timeSlots.push(`${hour}:00`);
    }

    // 創建表格
    let tableHTML =
      '<table><tr><th class="time-cell">時間</th><th class="date-cell">' +
      formatDateHeader(startDate) +
      '</th></tr>';

    timeSlots.forEach((time, index) => {
      if (index >= timeSlots.length - 1) return; // 忽略最後一個時間點，因為它只是結束時間

      const hour = parseInt(time.split(':')[0]);
      const timeKey = hour.toString().padStart(2, '0') + '00';

      // 檢查這個時段是否已被預訂
      const slot = slots.find((s) => s.hour === hour);
      const isBooked = slot ? !slot.available : false;

      const cellClass = isBooked ? 'grid-cell-booked' : 'grid-cell-available';

      tableHTML += `
                <tr>
                    <td class="time-cell">${time}</td>
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
    setupGridEvents();
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

        isDragging = true;
        clearSelection();
        dragStart = parseInt(this.dataset.index);
        dragEnd = dragStart;
        updateSelection();

        // 防止文本選擇
        e.preventDefault();
      });

      // 滑鼠移動時更新選擇
      cell.addEventListener('mouseover', function () {
        if (!isDragging) return;

        dragEnd = parseInt(this.dataset.index);
        updateSelection();
      });
    });

    // 滑鼠釋放結束選擇
    document.addEventListener('mouseup', function () {
      if (!isDragging) return;

      isDragging = false;

      // 如果有選擇，顯示預約對話框
      if (selectedCells.length > 0) {
        showBookingModal();
      }
    });
  }

  // 更新選擇的單元格
  function updateSelection() {
    clearSelection();

    const start = Math.min(dragStart, dragEnd);
    const end = Math.max(dragStart, dragEnd);

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
      showAlert('warning', '選擇範圍中包含已預訂的時段');
      return;
    }

    // 更新選擇
    for (let i = start; i <= end; i++) {
      const cell = document.querySelector(`.grid-cell[data-index="${i}"]`);
      if (cell && !cell.classList.contains('grid-cell-booked')) {
        cell.classList.add('grid-cell-selected');
        selectedCells.push(i);
      }
    }
  }

  // 清除選擇
  function clearSelection() {
    document.querySelectorAll('.grid-cell-selected').forEach((cell) => {
      cell.classList.remove('grid-cell-selected');
    });
    selectedCells = [];
  }

  // 顯示預約對話框
  function showBookingModal() {
    if (selectedCells.length === 0) return;

    const start = Math.min(...selectedCells);
    const end = Math.max(...selectedCells);

    const startCell = document.querySelector(
      `.grid-cell[data-index="${start}"]`
    );
    const endCell = document.querySelector(`.grid-cell[data-index="${end}"]`);

    if (!startCell || !endCell) return;

    const timeSlots = [];
    for (let hour = 8; hour <= 22; hour++) {
      timeSlots.push(`${hour}:00`);
    }

    const startTime = timeSlots[start];
    const endTime = end < timeSlots.length - 1 ? timeSlots[end + 1] : '23:00';

    const classroom = document.getElementById('classroom-title').textContent;
    const bookingDate = formatDateDisplay(startDate);

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
      // 提交預約
      submitBooking(currentSpaceId, startDate, start, end);
      closeModal();
    });
  }

  // 提交預約
  function submitBooking(spaceId, date, startIndex, endIndex) {
    showLoading(true);

    // 獲取用戶輸入的預約目的
    const purpose = prompt('請輸入預約目的', '課堂活動');
    if (!purpose) {
      showAlert('error', '請提供預約目的');
      showLoading(false);
      return;
    }

    // 整點時間槽
    const timeSlots = [];
    for (let hour = 8; hour <= 22; hour++) {
      timeSlots.push({ hour });
    }

    // 選擇的時間範圍
    const selectedSlots = [];
    for (let i = startIndex; i <= endIndex; i++) {
      selectedSlots.push(timeSlots[i].hour);
    }

    const formattedDate = formatDate(date);

    const bookingData = {
      classroom_ID: parseInt(spaceId),
      date: formattedDate,
      slots: selectedSlots,
      purpose: purpose,
    };

    console.log('提交預約數據:', bookingData);

    fetch('../api/bookings/create_booking.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(bookingData),
    })
      .then((response) => {
        if (!response.ok) {
          return response.text().then((text) => {
            console.log('API 錯誤詳情:', text);
            throw new Error(
              `HTTP 錯誤 ${response.status}: ${response.statusText}`
            );
          });
        }
        return response.text().then((text) => {
          if (!text) {
            throw new Error('API 返回空響應');
          }
          try {
            return JSON.parse(text);
          } catch (e) {
            console.log('JSON 解析錯誤，原始回應:', text);
            throw new Error('無效的 JSON 回應: ' + e.message);
          }
        });
      })
      .then((data) => {
        if (data.status === 'success') {
          showAlert('success', '預約成功！');
          // 重新載入排程數據
          loadSchedule(spaceId, date);
        } else {
          showAlert('error', '預約失敗：' + (data.message || '未知錯誤'));
          showLoading(false);
        }
      })
      .catch((error) => {
        console.error('預約提交時出錯:', error);
        showAlert('error', '預約提交時出錯：' + error.message);
        showLoading(false);
      });
  }

  // 顯示/隱藏加載動畫
  function showLoading(show) {
    let loader = document.querySelector('.scheduler-loading');

    if (show) {
      if (!loader) {
        loader = document.createElement('div');
        loader.className = 'scheduler-loading';
        loader.innerHTML = '<div class="spinner"></div>';
        document.querySelector('.scheduler-grid-container').appendChild(loader);
      }
      loader.style.display = 'flex';
    } else if (loader) {
      loader.style.display = 'none';
    }
  }

  // 設置全局事件監聽
  function setupEventListeners() {
    // 當用戶離開拖曳區域時取消選擇
    document.addEventListener('mouseleave', function () {
      if (isDragging) {
        isDragging = false;
        // 不清除選擇，讓用戶有機會重新進入並繼續選擇
      }
    });

    // 過濾下拉選單事件
    const buildingSelect = document.getElementById('building-filter');
    if (buildingSelect) {
      buildingSelect.addEventListener('change', function () {
        const building = this.value;
        filterSpaces(building);
      });
    }
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

  // 工具函數：格式化日期為 YYYY-MM-DD
  function formatDate(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  // 工具函數：格式化日期為顯示格式
  function formatDateDisplay(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const weekdays = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
    const weekday = weekdays[d.getDay()];
    return `${year}/${month}/${day} (${weekday})`;
  }

  // 工具函數：格式化日期為表頭顯示
  function formatDateHeader(date) {
    const d = new Date(date);
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const weekdays = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
    const weekday = weekdays[d.getDay()];
    return `${month}/${day} (${weekday})`;
  }

  // 工具函數：格式化日期時間為API
  function formatDateTimeForAPI(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  }

  // 顯示提示訊息
  function showAlert(type, message, duration = 3000) {
    // 確保頁面已經載入
    if (!document.body) {
      console.error('頁面DOM尚未完全載入，無法顯示警告');
      console.error(message);
      return;
    }

    let alertOverlay = document.querySelector('.alert-overlay');
    if (!alertOverlay) {
      alertOverlay = document.createElement('div');
      alertOverlay.className = 'alert-overlay';
      document.body.appendChild(alertOverlay);
    }

    const alertBox = document.createElement('div');
    alertBox.className = `alert-box alert-${type}`;
    alertBox.innerHTML = `
            ${message}
            <button class="alert-close">&times;</button>
        `;

    alertOverlay.appendChild(alertBox);

    // 綁定關閉按鈕
    const closeBtn = alertBox.querySelector('.alert-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        alertBox.remove();
      });
    }

    // 自動消失
    setTimeout(() => {
      alertBox.style.opacity = '0';
      setTimeout(() => alertBox.remove(), 300);
    }, duration);
  }
});
