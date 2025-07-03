document.addEventListener('DOMContentLoaded', function () {
  let isDragging = false;
  let dragMode = 'select'; // 'select' 或 'deselect'
  let selectedHours = [];
  const timeGrid = document.getElementById('time-grid');
  const bookingForm = document.getElementById('booking-form');
  const selectedHoursInput = document.getElementById('selected_hours');
  const selectedTimesDisplay = document.getElementById(
    'selected-times-display'
  );

  // 初始化 Tooltips
  initTooltips();

  if (timeGrid) {
    const cells = document.querySelectorAll('.time-cell:not(.booked)');

    cells.forEach((cell) => {
      // 點擊單格切換選取
      cell.addEventListener('click', function (e) {
        if (!isDragging) {
          const hour = parseInt(this.dataset.hour);
          if (isNaN(hour)) return;

          // 修復單格取消功能
          if (selectedHours.includes(hour)) {
            // 取消選取
            this.classList.remove('selected');
            selectedHours = selectedHours.filter((h) => h !== hour);
            console.log('取消選取時間:', hour, '剩餘選取:', selectedHours);
          } else {
            // 添加選取
            this.classList.add('selected');
            selectedHours.push(hour);
          }

          // 更新 input 值和顯示
          selectedHours.sort((a, b) => a - b);
          selectedHoursInput.value = JSON.stringify(selectedHours);
          updateSelectedTimes();

          // 如果沒有選取的小時，隱藏表單
          bookingForm.style.display =
            selectedHours.length > 0 ? 'block' : 'none';
        }

        // 防止事件冒泡
        e.preventDefault();
      });

      // 拖曳開始
      cell.addEventListener('mousedown', function (e) {
        isDragging = true;
        const hour = parseInt(this.dataset.hour);
        if (isNaN(hour)) return;

        dragMode = selectedHours.includes(hour) ? 'deselect' : 'select';
        applyDragAction(this);
        updateSelectedTimes();
        e.preventDefault();
      });

      // 拖曳過程
      cell.addEventListener('mouseover', function () {
        if (isDragging) {
          applyDragAction(this);
          updateSelectedTimes();
          console.log('拖曳中: 選取的時間', selectedHours);
        }
      });
    });

    // 拖曳結束
    document.addEventListener('mouseup', function () {
      if (isDragging) {
        isDragging = false;
        // 確保更新表單顯示狀態
        bookingForm.style.display = selectedHours.length > 0 ? 'block' : 'none';
        console.log('拖曳結束: 最終選取的時間', selectedHours);
      }
    });
  }

  function applyDragAction(cell) {
    const hour = parseInt(cell.dataset.hour);
    if (isNaN(hour)) return;

    const isSelected = selectedHours.includes(hour);

    if (dragMode === 'select' && !isSelected) {
      cell.classList.add('selected');
      selectedHours.push(hour);
      console.log('拖曳選取時間:', hour);
    } else if (dragMode === 'deselect' && isSelected) {
      cell.classList.remove('selected');
      selectedHours = selectedHours.filter((h) => h !== hour);
      console.log('拖曳取消選取時間:', hour);
    }

    selectedHours.sort((a, b) => a - b);
    selectedHoursInput.value = JSON.stringify(selectedHours);
  }

  function updateSelectedTimes() {
    console.log('更新時間顯示，當前選取:', selectedHours);

    if (selectedHours.length === 0) {
      selectedTimesDisplay.innerText = '尚未選擇時間，請在上方時間表格拖曳選擇';
      return;
    }

    // 對選擇的時間排序
    selectedHours.sort((a, b) => a - b);
    console.log('排序後的時間:', selectedHours);

    // 找出連續的時間段分組
    let timeRanges = [];
    let currentGroup = [selectedHours[0]];

    for (let i = 1; i < selectedHours.length; i++) {
      // 如果當前時間和前一個時間連續，加入同一組
      if (selectedHours[i] === selectedHours[i - 1] + 1) {
        currentGroup.push(selectedHours[i]);
      } else {
        // 不連續，結束當前組並開始新的一組
        const startHour = currentGroup[0];
        const endHour = currentGroup[currentGroup.length - 1];
        timeRanges.push(`${startHour}:00 - ${endHour + 1}:00`);
        currentGroup = [selectedHours[i]];
      }
    }

    // 處理最後一組
    if (currentGroup.length > 0) {
      const startHour = currentGroup[0];
      const endHour = currentGroup[currentGroup.length - 1];
      timeRanges.push(`${startHour}:00 - ${endHour + 1}:00`);
    }

    console.log('產生的時間範圍:', timeRanges);

    // 顯示所有時間段
    const displayHTML =
      '<strong>已選擇時間段:</strong><br>' +
      timeRanges
        .map((range, index) => `時段 ${index + 1}: ${range}`)
        .join('<br>');

    selectedTimesDisplay.innerHTML = displayHTML;
    console.log('設置HTML:', displayHTML);
  }

  // 全部清除
  window.clearSelection = function () {
    console.log('清除所有選取');

    // 移除所有已選取格子的選取樣式
    document.querySelectorAll('.time-cell.selected').forEach((cell) => {
      cell.classList.remove('selected');
    });

    // 重置所有狀態
    selectedHours = [];
    selectedHoursInput.value = '';
    selectedTimesDisplay.innerText = '尚未選擇時間，請在上方時間表格拖曳選擇';
    bookingForm.style.display = 'none';

    console.log('清除完成');
  };

  // 初始化 Tooltips
  function initTooltips() {
    const bookedCells = document.querySelectorAll('.time-cell.booked');

    bookedCells.forEach((cell) => {
      // 為已預約的時段添加懸停事件
      if (cell.hasAttribute('data-user')) {
        const userName = cell.getAttribute('data-user');
        const userEmail = cell.getAttribute('data-email');

        // 建立自訂 Tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.innerHTML = `
          <div class="tooltip-header">預約資訊</div>
          <div class="tooltip-content">
            <div class="tooltip-row"><strong>租借人:</strong> ${userName}</div>
            <div class="tooltip-row"><strong>聯絡方式:</strong> ${userEmail}</div>
          </div>
        `;

        // 添加至 body
        document.body.appendChild(tooltip);

        // 滑鼠懸停顯示 Tooltip
        cell.addEventListener('mouseenter', (e) => {
          const rect = cell.getBoundingClientRect();
          tooltip.style.left = rect.left + 'px';
          tooltip.style.top = rect.bottom + 10 + 'px';
          tooltip.classList.add('visible');
        });

        // 滑鼠離開隱藏 Tooltip
        cell.addEventListener('mouseleave', () => {
          tooltip.classList.remove('visible');
        });
      }
    });
  }
});

// 添加自訂 Tooltip 樣式
const style = document.createElement('style');
style.textContent = `
  .custom-tooltip {
    position: absolute;
    z-index: 1000;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    padding: 0;
    width: 250px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s;
  }
  
  .custom-tooltip.visible {
    opacity: 1;
    visibility: visible;
  }
  
  .tooltip-header {
    background-color: #4285f4;
    color: white;
    padding: 8px 12px;
    border-radius: 4px 4px 0 0;
    font-weight: bold;
  }
  
  .tooltip-content {
    padding: 10px;
  }
  
  .tooltip-row {
    margin-bottom: 5px;
  }
  
  .time-cell.booked {
    cursor: help;
  }
`;
document.head.appendChild(style);
