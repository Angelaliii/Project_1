// booking-combined.js - 整合後的教室預約邏輯
console.log('[Booking] booking-combined.js loaded');

(function () {
  const HOURS_START = 8;
  const HOURS_END = 21;

  // 檢測行動裝置
  const isMobile =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );

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

  // 輔助函式：判斷日期是否早於今天
  function isBookingDateBeforeToday(dateStr) {
    if (!dateStr) return false;
    const [y, m, d] = dateStr.split('-').map((x) => parseInt(x, 10));
    if (!y || !m || !d) return false;

    const today = new Date();
    today.setHours(0, 0, 0, 0); // 設置為今天0點

    // 注意: 月份從0開始，所以要減1
    const bookingDate = new Date(y, m - 1, d);
    bookingDate.setHours(0, 0, 0, 0); // 設置為預約日0點

    return bookingDate < today;
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
    // 檢查預約日期
    const timetable =
      document.getElementById('booking-timetable') ||
      document.getElementById('time-grid');
    if (!timetable) return;

    const bookingDate = timetable.dataset.bookingDate || '';
    const isBeforeToday = isBookingDateBeforeToday(bookingDate);
    const isToday = isBookingDateToday(bookingDate);

    // 獲取當前時間，確保精確到分鐘
    const now = new Date();
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();

    // 處理已預約格子
    const bookedCells = document.querySelectorAll('div.time-slot-booked');
    bookedCells.forEach((cell) => {
      cell.classList.add('slot-disabled');
      cell.style.cursor = 'not-allowed';
    });

    // 處理過去日期：所有時間格全部禁用
    if (isBeforeToday) {
      const allCells = document.querySelectorAll('div.time-slot-available');
      allCells.forEach((cell) => {
        cell.classList.remove('time-slot-available');
        cell.classList.add('time-slot-past', 'slot-disabled');
        cell.style.pointerEvents = 'none';
        cell.style.backgroundColor = '#e0e0e0';
        cell.setAttribute('title', '此日期已過期，無法預約');

        // 清除任何現有的文字內容
        const contentElements = cell.querySelectorAll('.cell-content');
        contentElements.forEach((el) => el.remove());
      });
      return;
    }

    // 處理今天已過時間格子
    if (isToday) {
      const availableCells = document.querySelectorAll(
        'div.time-slot-available'
      );
      availableCells.forEach((cell) => {
        const hour = parseInt(cell.dataset.hour || '0', 10);
        // 當前小時已過，或當前小時且已過30分鐘（針對下午的時段）
        const isPastHour =
          !isNaN(hour) &&
          (hour < currentHour ||
            (hour === currentHour && currentMinute >= 30 && hour >= 13) || // 13:30以後的時段，過了30分就不能預約
            (hour === currentHour && hour < 13)); // 上午時段，當前小時就不能預約

        if (isPastHour) {
          cell.classList.remove('time-slot-available');
          cell.classList.add('time-slot-past', 'slot-disabled');
          cell.style.pointerEvents = 'none';
          cell.style.backgroundColor = '#e0e0e0';
          cell.setAttribute('title', '此時段已過期');

          // 清除任何現有的文字內容
          const contentElements = cell.querySelectorAll('.cell-content');
          contentElements.forEach((el) => el.remove());
        }
      });
    }
  }

  // 初始化提示工具
  function initTooltips() {
    const bookedCells = document.querySelectorAll(
      'div.time-slot-booked, div.time-cell.booked'
    );

    // 始終使用自定義CSS tooltip樣式
    // 移除Bootstrap tooltip的實現，改為一律使用自定義的原生tooltip

    // 後備原生提示工具
    let tip;

    function showTip(cell) {
      if (tip) tip.remove();

      tip = document.createElement('div');
      tip.className = 'tooltip-container visible';

      // 如果是行動裝置，添加特定類別
      if (isMobile) {
        tip.classList.add('mobile-tooltip');
      }
      // 取得資料
      const userName = cell.getAttribute('data-user') || '';
      const userEmail = cell.getAttribute('data-email') || '';
      const purpose = cell.getAttribute('data-purpose') || '';

      let tooltipContentHTML = '';
      tooltipContentHTML += `
          <div class="tooltip-row">
              <strong>租借人：</strong><span class="content-text">${userName}</span>
          </div>
          <div class="tooltip-row">
              <strong>聯絡方式：</strong>
              <div class="content-text" style="display: block; margin-top: 5px;">${userEmail}</div>
          </div>
          <div class="tooltip-row">
              <strong>用途：</strong><span class="content-text">${purpose}</span>
          </div>
      `;

      tip.innerHTML = `
        <div class="tooltip-header">預約資訊</div>
        <div class="tooltip-content">
          ${tooltipContentHTML}
        </div>`;

      // 先將 tooltip 添加到文檔中，但設為不可見，以便測量尺寸
      tip.style.visibility = 'hidden';
      document.body.appendChild(tip);

      // 獲取點擊的格子位置和尺寸
      const rect = cell.getBoundingClientRect();

      // 獲取 tooltip 尺寸
      const tipWidth = tip.offsetWidth;
      const tipHeight = tip.offsetHeight;

      // 獲取視窗尺寸
      const windowWidth = window.innerWidth;
      const windowHeight = window.innerHeight;

      // 獲取滾動位置
      const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
      const scrollY = window.pageYOffset || document.documentElement.scrollTop;

      // 計算最佳位置
      if (isMobile) {
        // 行動版根據格子位置顯示 tooltip
        tip.style.visibility = '';
        tip.style.position = 'fixed'; // 使用 fixed 定位避免滑動影響
        tip.style.pointerEvents = 'auto'; // 確保可以點擊

        // 檢查是否有 footer 元素
        const footer = document.querySelector('.main-footer');
        const footerRect = footer ? footer.getBoundingClientRect() : null;
        const footerTop = footerRect ? footerRect.top : windowHeight;
        const safeBottomMargin = 10; // 確保與 footer 有足夠間距

        // 計算可視範圍（考慮 footer 位置）
        const visibleBottom = footerTop - safeBottomMargin;

        // 判斷格子在螢幕上的位置
        const cellCenterX = rect.left + rect.width / 2;
        const cellCenterY = rect.top + rect.height / 2;
        const isInUpperHalf = cellCenterY < visibleBottom / 2;
        let leftPos, topPos, arrowClass;

        // 計算水平位置，確保不超出屏幕
        leftPos = Math.max(
          10,
          Math.min(cellCenterX - tipWidth / 2, windowWidth - tipWidth - 10)
        );

        if (isInUpperHalf) {
          // 格子在上半部，優先顯示在下方
          if (rect.bottom + tipHeight + 10 < visibleBottom) {
            // 下方有足夠空間
            topPos = rect.bottom + 10;
            arrowClass = 'arrow-top';

            // 設定箭頭水平位置
            const arrowLeftOffset = Math.min(
              Math.max(20, cellCenterX - leftPos),
              tipWidth - 30
            );
            tip.style.setProperty(
              '--arrow-left-position',
              `${arrowLeftOffset}px`
            );
          } else {
            // 下方空間不足，顯示在上方
            topPos = Math.max(10, rect.top - tipHeight - 10);
            arrowClass = 'arrow-bottom';

            // 設定箭頭水平位置
            const arrowLeftOffset = Math.min(
              Math.max(20, cellCenterX - leftPos),
              tipWidth - 30
            );
            tip.style.setProperty(
              '--arrow-left-position',
              `${arrowLeftOffset}px`
            );
          }
        } else {
          // 格子在下半部，優先顯示在上方
          if (rect.top - tipHeight - 10 >= 10) {
            // 上方有足夠空間
            topPos = rect.top - tipHeight - 10;
            arrowClass = 'arrow-bottom';

            // 設定箭頭水平位置
            const arrowLeftOffset = Math.min(
              Math.max(20, cellCenterX - leftPos),
              tipWidth - 30
            );
            tip.style.setProperty(
              '--arrow-left-position',
              `${arrowLeftOffset}px`
            );
          } else if (rect.bottom + tipHeight + 10 < visibleBottom) {
            // 上方空間不足，但下方有足夠空間
            topPos = rect.bottom + 10;
            arrowClass = 'arrow-top';

            // 設定箭頭水平位置
            const arrowLeftOffset = Math.min(
              Math.max(20, cellCenterX - leftPos),
              tipWidth - 30
            );
            tip.style.setProperty(
              '--arrow-left-position',
              `${arrowLeftOffset}px`
            );
          } else {
            // 上下都沒有足夠空間，顯示在中間位置
            topPos = Math.max(
              10,
              Math.min(
                windowHeight / 2 - tipHeight / 2,
                visibleBottom - tipHeight - 10
              )
            );
            arrowClass = ''; // 不顯示箭頭
          }
        }

        // 確保 tooltip 不超出 footer
        if (topPos + tipHeight + safeBottomMargin > visibleBottom) {
          topPos = visibleBottom - tipHeight - safeBottomMargin;
        }

        tip.style.visibility = '';
        tip.style.left = leftPos + 'px';
        tip.style.top = topPos + 'px';
        if (arrowClass) tip.classList.add(arrowClass);
      } else {
        // 檢查是否有 footer 元素
        const footer = document.querySelector('.main-footer');
        const footerRect = footer ? footer.getBoundingClientRect() : null;
        const footerTop = footerRect
          ? footerRect.top + scrollY
          : windowHeight + scrollY;
        const safeBottomMargin = 10; // 確保與 footer 有足夠間距

        // 首先嘗試將 tooltip 放在右側
        let leftPos = rect.right + scrollX + 10;
        let topPos = rect.top + scrollY;

        // 檢查右側是否有足夠空間
        if (leftPos + tipWidth > scrollX + windowWidth - 10) {
          // 右側空間不足，嘗試左側
          leftPos = rect.left + scrollX - tipWidth - 10;

          // 檢查左側是否有足夠空間
          if (leftPos < scrollX + 10) {
            // 左側也沒有足夠空間，放在下方或上方
            leftPos = Math.max(
              scrollX + 10,
              Math.min(
                scrollX + rect.left,
                scrollX + windowWidth - tipWidth - 10
              )
            );

            // 檢查下方是否有足夠空間，同時考慮 footer 位置
            if (
              rect.bottom + scrollY + tipHeight + 10 <=
              footerTop - safeBottomMargin
            ) {
              // 下方有足夠空間
              topPos = rect.bottom + scrollY + 10;
            } else {
              // 上方放置
              topPos = Math.max(
                scrollY + 10,
                rect.top + scrollY - tipHeight - 10
              );

              // 確認上方也沒有足夠空間的情況
              if (topPos < scrollY + 10) {
                // 上下都沒有足夠空間，選擇最佳位置
                topPos = Math.max(
                  scrollY + 10,
                  Math.min(
                    scrollY + windowHeight / 2 - tipHeight / 2,
                    footerTop - tipHeight - safeBottomMargin
                  )
                );
              }
            }
          }
        }

        // 判斷箭頭位置
        let arrowClass = '';

        // 根據放置位置決定箭頭方向
        if (leftPos > rect.right + scrollX) {
          // tooltip 在格子右側
          arrowClass = 'arrow-right';
          // 調整箭頭垂直位置與格子中心對齊
          const arrowTopOffset = Math.min(
            Math.max(20, rect.top + rect.height / 2 - topPos),
            tipHeight - 30
          );
          tip.style.setProperty('--arrow-top-position', `${arrowTopOffset}px`);
        } else if (leftPos + tipWidth < rect.left + scrollX) {
          // tooltip 在格子左側
          arrowClass = 'arrow-left';
          const arrowTopOffset = Math.min(
            Math.max(20, rect.top + rect.height / 2 - topPos),
            tipHeight - 30
          );
          tip.style.setProperty('--arrow-top-position', `${arrowTopOffset}px`);
        } else if (topPos > rect.bottom + scrollY) {
          // tooltip 在格子下方
          arrowClass = 'arrow-top';
          const arrowLeftOffset = Math.min(
            Math.max(20, rect.left + rect.width / 2 - leftPos),
            tipWidth - 30
          );
          tip.style.setProperty(
            '--arrow-left-position',
            `${arrowLeftOffset}px`
          );
        } else {
          // tooltip 在格子上方
          arrowClass = 'arrow-bottom';
          const arrowLeftOffset = Math.min(
            Math.max(20, rect.left + rect.width / 2 - leftPos),
            tipWidth - 30
          );
          tip.style.setProperty(
            '--arrow-left-position',
            `${arrowLeftOffset}px`
          );
        }

        // 應用計算後的位置和箭頭樣式
        tip.style.visibility = '';
        tip.style.position = 'absolute';
        tip.style.left = leftPos + 'px';
        tip.style.top = topPos + 'px';
        tip.classList.add(arrowClass);
      }

      // 行動版自動隱藏
      if (isMobile) {
        setTimeout(() => {
          if (tip) {
            tip.remove();
            tip = null;
          }
        }, 5000);
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
          // 如果有開啟的提示先關閉它
          if (tip) {
            hideTip();
          } else {
            showTip(cell);
          }
          e.stopPropagation();
        });

        // 簡化行動版長按顯示提示的邏輯
        let touchTimeout;

        function handleTouchStart() {
          touchTimeout = setTimeout(() => showTip(cell), 500);
        }

        function handleTouchEnd() {
          clearTimeout(touchTimeout);
        }

        cell.addEventListener('touchstart', handleTouchStart);
        cell.addEventListener('touchend', handleTouchEnd);
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

    // 點擊其他地方或tooltip本身(行動版)關閉提示
    document.addEventListener('click', (e) => {
      if (
        tip &&
        (e.target.closest('.tooltip-container') ||
          !e.target.closest('.time-slot-booked, .time-cell.booked'))
      ) {
        hideTip();
      }
    });
  }

  // 綁定點擊事件
  function attachClickEvents() {
    // 點擊選取
    document.addEventListener('click', function (e) {
      const cell = e.target.closest(
        '.time-slot.time-slot-available:not(.slot-disabled)'
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

      // 立即更新表單顯示
      updateFormVisibility();

      // 確保表單可見性正確
      const formContainer = document.getElementById('booking-form-container');
      if (
        document.querySelectorAll('.time-slot-selected').length > 0 &&
        formContainer
      ) {
        formContainer.style.display = 'block';
        formContainer.classList.add('visible');
      }
    });

    // 行動版點擊其他區域隱藏表單
    if (isMobile) {
      document.addEventListener('click', function (e) {
        const formArea = e.target.closest(
          '#booking-form-container, #booking-form, .timetable, .time-slot.time-slot-available, .time-info'
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
    const formContainer = document.getElementById('booking-form-container');
    const purposeInput = document.getElementById('booking-purpose');
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
          let currentClassroomName = '';
          let timeRanges = [];

          selectedData.forEach((slot, idx) => {
            if (currentClassroom !== slot.classroomId) {
              // 先收尾上個教室
              if (currentClassroom !== null) {
                displayText += `${currentClassroomName}(${formatTimeRanges(
                  timeRanges
                )}) `;
                timeRanges = [];
              }
              currentClassroom = slot.classroomId;
              currentClassroomName =
                slot.classroomName || slot.classroomLocation || '';
            }
            timeRanges.push(slot.hour);

            // 最後一筆時收尾
            if (idx === selectedData.length - 1) {
              displayText += `${currentClassroomName}(${formatTimeRanges(
                timeRanges
              )})`;
            }
          });

          selectedTimeRange.textContent = displayText.trim();
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
