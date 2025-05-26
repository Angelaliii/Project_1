<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>錯誤 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('css/components.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/FJU_logo.png'); ?>">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>頁面不存在</h1>
            <p>抱歉，您請求的頁面不存在或已被移除。</p>
            <a href="<?php echo url(''); ?>" class="btn btn-primary">返回首頁</a>
        </div>
    </div>
</body>
</html>
