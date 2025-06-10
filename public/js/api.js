// api.js - 處理與後端API的交互

/**
 * 獲取用戶預約列表
 * @returns {Promise<Object>} 返回一個Promise，解析為包含預約列表的對象
 */
async function getUserBookings() {
  try {
    const response = await fetch('../api/bookings/user.php');

    if (!response.ok) {
      throw new Error(`HTTP 錯誤 ${response.status}: ${response.statusText}`);
    }

    const text = await response.text();
    if (!text) {
      throw new Error('API返回空響應');
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      console.log('JSON解析錯誤，原始響應:', text);
      throw new Error('無效的JSON響應: ' + e.message);
    }
  } catch (error) {
    console.error('獲取預約列表時出錯:', error);
    return {
      status: 'error',
      message: '獲取預約列表時發生錯誤，請稍後再試',
    };
  }
}

/**
 * 取消預約
 * @param {number} bookingId 預約ID
 * @returns {Promise<Object>} 返回一個Promise，解析為包含取消結果的對象
 */
async function cancelBooking(bookingId) {
  try {
    const response = await fetch('../api/bookings/cancel.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `booking_id=${bookingId}`,
    });

    if (!response.ok) {
      throw new Error(`HTTP 錯誤 ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('取消預約時出錯:', error);
    return {
      status: 'error',
      message: '取消預約時發生錯誤，請稍後再試',
    };
  }
}

/**
 * 獲取教室列表
 * @returns {Promise<Object>} 返回一個Promise，解析為包含教室列表的對象
 */
async function getClassrooms() {
  try {
    const response = await fetch('../api/classrooms/list.php');

    if (!response.ok) {
      throw new Error(`HTTP 錯誤 ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('獲取教室列表時出錯:', error);
    return {
      status: 'error',
      message: '獲取教室列表時發生錯誤，請稍後再試',
    };
  }
}

/**
 * 創建教室預約
 * @param {Object} bookingData 預約數據
 * @returns {Promise<Object>} 返回一個Promise，解析為包含創建結果的對象
 */
async function createBooking(bookingData) {
  try {
    const response = await fetch('../api/bookings/create.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(bookingData),
    });

    return response;
  } catch (error) {
    console.error('創建預約時出錯:', error);
    throw error;
  }
}

/**
 * 檢查教室可用時段
 * @param {number} classroomId 教室ID
 * @param {string} date 日期字符串 (YYYY-MM-DD)
 * @returns {Promise<Object>} 返回一個Promise，解析為包含時段可用性的對象
 */
async function getAvailability(classroomId, date) {
  try {
    const response = await fetch(
      `../api/bookings/availability.php?classroom_id=${classroomId}&date=${date}`
    );

    if (!response.ok) {
      throw new Error(`HTTP 錯誤 ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('獲取可用性時出錯:', error);
    return {
      status: 'error',
      message: '獲取可用時段時發生錯誤，請稍後再試',
    };
  }
}

/**
 * 用戶登入
 * @param {Object} credentials 登入憑證 {username, password}
 * @returns {Promise<Object>} 返回一個Promise，解析為包含登入結果的對象
 */
async function login(credentials) {
  try {
    const response = await fetch('../api/auth/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(credentials),
    });

    if (!response.ok) {
      throw new Error(`HTTP 錯誤 ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('登入時出錯:', error);
    return {
      status: 'error',
      message: '登入時發生錯誤，請稍後再試',
    };
  }
}

/**
 * 用戶登出
 * @returns {Promise<Object>} 返回一個Promise，解析為包含登出結果的對象
 */
async function logout() {
  try {
    const response = await fetch('../api/auth/logout.php');

    if (!response.ok) {
      throw new Error(`HTTP 錯誤 ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('登出時出錯:', error);
    return {
      status: 'error',
      message: '登出時發生錯誤，請稍後再試',
    };
  }
}
