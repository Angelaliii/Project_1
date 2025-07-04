/**
 * 通知系統
 */
class NotificationSystem {
  constructor() {
    this.container = null;
    this.timeout = 3000; // 預設顯示時間為3秒
    this.initContainer();
  }

  /**
   * 初始化通知容器
   */
  initContainer() {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'notification-container';
      this.container.style.position = 'fixed';
      this.container.style.top = '20px';
      this.container.style.right = '20px';
      this.container.style.zIndex = '1050';
      document.body.appendChild(this.container);
    }
  }

  /**
   * 顯示成功通知
   * @param {string} message - 通知內容
   * @param {string} title - 通知標題 (可選)
   */
  showSuccess(message, title = '成功') {
    this.show(message, title, 'success');
  }

  /**
   * 顯示錯誤通知
   * @param {string} message - 通知內容
   * @param {string} title - 通知標題 (可選)
   */
  showError(message, title = '錯誤') {
    this.show(message, title, 'error');
  }

  /**
   * 顯示警告通知
   * @param {string} message - 通知內容
   * @param {string} title - 通知標題 (可選)
   */
  showWarning(message, title = '警告') {
    this.show(message, title, 'warning');
  }

  /**
   * 顯示通知
   * @param {string} message - 通知內容
   * @param {string} title - 通知標題
   * @param {string} type - 通知類型 (success, error, warning)
   */
  show(message, title, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    // 設定通知圖標
    let icon = '';
    switch (type) {
      case 'success':
        icon = '<i class="fas fa-check-circle notification-icon"></i>';
        break;
      case 'error':
        icon = '<i class="fas fa-times-circle notification-icon"></i>';
        break;
      case 'warning':
        icon = '<i class="fas fa-exclamation-circle notification-icon"></i>';
        break;
    }

    // 建立通知內容
    notification.innerHTML = `
      ${icon}
      <div class="notification-content">
        <div class="notification-title">${title}</div>
        <div class="notification-message">${message}</div>
      </div>
      <div class="notification-close"><i class="fas fa-times"></i></div>
    `;

    // 添加到容器
    this.container.appendChild(notification);

    // 添加關閉按鈕事件
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
      this.close(notification);
    });

    // 顯示通知
    setTimeout(() => {
      notification.classList.add('show');
    }, 10);

    // 自動關閉
    setTimeout(() => {
      this.close(notification);
    }, this.timeout);
  }

  /**
   * 關閉通知
   * @param {HTMLElement} notification - 通知元素
   */
  close(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
      if (notification.parentNode === this.container) {
        this.container.removeChild(notification);
      }
    }, 300);
  }
}

// 創建全局通知實例
const notificationSystem = new NotificationSystem();
