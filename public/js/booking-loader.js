// booking-loader.js - 根據裝置類型載入適合的教室預約系統腳本
document.addEventListener('DOMContentLoaded', function () {
  console.log('初始化教室預約系統載入器');

  // 檢測是否為移動裝置
  const isMobile =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );

  // 動態載入適合的腳本文件
  function loadScript(src) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  // 根據裝置類型載入不同的腳本
  if (isMobile) {
    console.log('偵測到移動裝置，載入移動版腳本');
    loadScript('/public/js/booking-mobile.js')
      .then(() => console.log('移動版腳本載入完成'))
      .catch((err) => console.error('移動版腳本載入失敗', err));
  } else {
    console.log('偵測到桌面裝置，載入桌面版腳本');
    loadScript('/public/js/booking.js')
      .then(() => console.log('桌面版腳本載入完成'))
      .catch((err) => console.error('桌面版腳本載入失敗', err));
  }
});
