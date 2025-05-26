<?php
// 檢查 Apache 的 mod_rewrite 模組是否啟用
$modRewriteEnabled = in_array('mod_rewrite', apache_get_modules());

echo "<h1>重定向測試</h1>";
echo "<p>mod_rewrite 是否啟用: " . ($modRewriteEnabled ? '是' : '否') . "</p>";

// 嘗試重定向
header('Location: public/');
exit;
?>
