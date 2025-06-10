// scheduler-core.js - 排程系統核心功能

// 核心模組
let SchedulerCore = (function () {
  // 全局變數
  let currentSpaceId = null;
  let startDate = new Date();
  let selectedCells = [];
  let isDragging = false;
  let dragStart = null;
  let dragEnd = null;

  // 初始化排程系統
  function init() {
    window.SchedulerEvents.initDatePicker();
    loadSpaces();
    window.SchedulerEvents.setupEventListeners();
  }

  // 載入所有可用空間
  function loadSpaces() {
    window.SchedulerUtils.showLoading(true);
    // 使用 api.js 中的 getClassrooms 函數獲取教室列表
    getClassrooms()
      .then((data) => {
        console.log('API 回應數據:', data);
        if (data.status === 'success') {
          window.SchedulerUI.renderSpaceList(data.classrooms);
          if (data.classrooms && data.classrooms.length > 0) {
            selectSpace(data.classrooms[0].classroom_ID);
          } else {
            console.log('沒有可用的教室');
            window.SchedulerUtils.showAlert('info', '目前沒有可用的教室');
          }
        } else {
          window.SchedulerUtils.showAlert(
            'error',
            '無法載入空間列表：' + (data.message || '未知錯誤')
          );
        }
        window.SchedulerUtils.showLoading(false);
      })
      .catch((error) => {
        console.error('獲取空間列表時出錯:', error);
        window.SchedulerUtils.showAlert(
          'error',
          '獲取空間列表時出錯：' + error.message
        );
        window.SchedulerUtils.showLoading(false);
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
  }

  // 載入排程數據
  function loadSchedule(spaceId, date) {
    window.SchedulerUtils.showLoading(true);
    window.SchedulerUI.clearSelection();

    const formattedDate = window.SchedulerUtils.formatDate(date);
    console.log(`正在載入教室 ${spaceId} 在 ${formattedDate} 的排程`);

    // 使用 api.js 中的 getAvailableSlots 函數獲取可用時段
    getAvailableSlots(spaceId, formattedDate)
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
          window.SchedulerUI.renderScheduleGrid(data.slots, data.classroom);
        } else {
          window.SchedulerUtils.showAlert(
            'error',
            '無法載入排程：' + data.message
          );
        }
        window.SchedulerUtils.showLoading(false);
      })
      .catch((error) => {
        console.error('獲取排程數據時出錯:', error);
        window.SchedulerUtils.showAlert(
          'error',
          '獲取排程數據時出錯：' + error.message
        );
        window.SchedulerUtils.showLoading(false);
      });
  }

  // 初始化後返回公開的屬性和方法
  return {
    // 屬性
    get currentSpaceId() {
      return currentSpaceId;
    },
    set currentSpaceId(value) {
      currentSpaceId = value;
    },
    get startDate() {
      return startDate;
    },
    set startDate(value) {
      startDate = value;
    },
    get selectedCells() {
      return selectedCells;
    },
    set selectedCells(value) {
      selectedCells = value;
    },
    get isDragging() {
      return isDragging;
    },
    set isDragging(value) {
      isDragging = value;
    },
    get dragStart() {
      return dragStart;
    },
    set dragStart(value) {
      dragStart = value;
    },
    get dragEnd() {
      return dragEnd;
    },
    set dragEnd(value) {
      dragEnd = value;
    },

    // 方法
    init,
    loadSpaces,
    selectSpace,
    loadSchedule,
  };
})();

// 監聽 DOMContentLoaded 事件，確保DOM完全載入後再初始化排程系統
document.addEventListener('DOMContentLoaded', function () {
  // 初始化排程系統
  window.SchedulerCore.init();
});

// 將核心模組導出到全局
window.SchedulerCore = SchedulerCore;
