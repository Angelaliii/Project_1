// scheduler-booking.js - 處理預約相關功能

// 預約模組
let SchedulerBooking = (function () {
  // 提交預約
  function submitBooking(spaceId, date, startIndex, endIndex, purpose) {
    window.SchedulerUtils.showLoading(true);

    // 生成時間槽陣列（與渲染排程表格函數中的定義保持一致）
    const timeSlots = [];
    // 上午時段：8:00-12:00，每小時一個時段
    for (let hour = 8; hour <= 11; hour++) {
      timeSlots.push({ hour });
    }
    // 中午時段：12:00-13:30
    timeSlots.push({ hour: 12 });
    // 下午時段：13:30-20:30，每小時一個時段
    for (let hour = 13; hour <= 20; hour++) {
      timeSlots.push({ hour });
    }

    // 選擇的時間範圍
    const selectedSlots = [];
    for (let i = startIndex; i <= endIndex; i++) {
      selectedSlots.push(timeSlots[i].hour);
    }

    const formattedDate = window.SchedulerUtils.formatDate(date);

    const bookingData = {
      classroom_ID: parseInt(spaceId),
      date: formattedDate,
      slots: selectedSlots,
      purpose: purpose,
    };

    console.log('提交預約數據:', bookingData);

    // 使用 api.js 中的 createBooking 函數提交預約
    createBooking(bookingData)
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
          window.SchedulerUtils.showAlert('success', '預約成功！');
          // 重新載入排程數據
          window.SchedulerCore.loadSchedule(spaceId, date);
        } else {
          window.SchedulerUtils.showAlert(
            'error',
            '預約失敗：' + (data.message || '未知錯誤')
          );
          window.SchedulerUtils.showLoading(false);
        }
      })
      .catch((error) => {
        console.error('預約提交時出錯:', error);
        window.SchedulerUtils.showAlert(
          'error',
          '預約提交時出錯：' + error.message
        );
        window.SchedulerUtils.showLoading(false);
      });
  }

  // 返回公開的方法
  return {
    submitBooking,
  };
})();

// 將預約模組導出到全局
window.SchedulerBooking = SchedulerBooking;
