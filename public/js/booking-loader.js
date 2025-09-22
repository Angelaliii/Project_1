// booking-loader.js
(function () {
  // 粗略偵測行動裝置
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  );

  // 動態載入 script
  function loadScript(src) {
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.onload = () => resolve();
      s.onerror = (e) => reject(e);
      document.head.appendChild(s);
    });
  }

  function getRootPath() {
    const currentScript =
      document.currentScript || document.querySelector('script[src*="booking-loader"]');
    const dataRoot = currentScript && currentScript.dataset && currentScript.dataset.root;
    if (dataRoot) return dataRoot.replace(/\/+$/, '') + '/';
    // 退而求其次：以當前 loader 所在路徑為基底
    const src = currentScript && currentScript.getAttribute('src');
    if (!src) return './';
    const parts = src.split('/');
    parts.pop(); // 去檔名
    return parts.join('/') + '/';
  }

  const root = getRootPath();

  // 依裝置載入對應檔案
  const entry = isMobile ? 'booking-mobile.js' : 'booking.js';

  loadScript(root + entry)
    .then(() => {
      // 防呆旗標，禁止重複初始化
      if (window.__bookingInitialized) return;
      window.__bookingInitialized = true;

      if (window.Booking && typeof window.Booking.initialize === 'function') {
        window.Booking.initialize();
      } else {
        console.error('[booking-loader] Booking.initialize() 不存在，請確認腳本載入順序。');
      }
    })
    .catch((err) => console.error('[booking-loader] 載入腳本失敗：', err));
})();
