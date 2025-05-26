<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('css/components.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/FJU_logo.png'); ?>">
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?php echo url('css/' . $style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-image: url('<?php echo url('assets/images/fju_fx_3.svg'); ?>'); background-repeat: no-repeat; background-size: 100%; background-attachment: fixed;">
    <div class="container">
        <?php include_once APPROOT . '/views/components/header.php'; ?>
        
        <main class="main-content">
            <?php if (isset($flash)): ?>
                <div class="flash-message <?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <?php echo $content; ?>
        </main>
        
        <?php include_once APPROOT . '/views/components/footer.php'; ?>
    </div>
    
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo url('js/' . $script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
