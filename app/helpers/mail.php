<?php
// app/helpers/mail.php
// 簡易郵件發送 helper：優先使用 PHPMailer（若已安裝），否則使用 PHP mail() 作為 fallback。

function send_initial_password_email($toEmail, $toName, $username, $password) {
    $siteName = defined('SITE_NAME') ? SITE_NAME : '教室預約系統';
    $subject = "[{$siteName}] 帳號已建立 - 初始密碼";

    $textBody = "您好 {$toName}\n\n帳號已建立，以下為登入資訊：\n帳號：{$username}\n初始密碼：{$password}\n\n請登入後立即變更密碼。\n\n-- {$siteName}";

    $loginUrl = defined('SITE_LOGIN_URL') ? SITE_LOGIN_URL : (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/app/pages/login.php' : '/app/pages/login.php');

    $htmlBody = "<html><body style=\"font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.5;\">" .
        "<h2 style=\"color:#0b5ed7;\">{$siteName} — 帳號已建立</h2>" .
        "<p>您好 <strong>" . htmlspecialchars($toName ?: $username) . "</strong>,</p>" .
        "<p>系統已為您建立帳號，以下為登入資訊：</p>" .
        "<ul><li><strong>帳號</strong>: " . htmlspecialchars($username) . "</li>" .
        "<li><strong>初始密碼</strong>: " . htmlspecialchars($password) . "</li></ul>" .
        "<p>請使用以下連結登入並立即變更密碼：<br><a href=\"{$loginUrl}\">登入頁面</a></p>" .
        "<p style=\"color:#666;font-size:0.9em;\">若非您本人申請，請忽略此郵件或聯絡管理員。</p>" .
        "<hr><p style=\"font-size:0.85em;color:#777;\">此為系統自動郵件，請勿直接回覆。</p>" .
        "</body></html>";

    // 若有 PHPMailer 可用，嘗試使用它
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') || class_exists('PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            if (defined('MAIL_HOST')) {
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = (!empty(MAIL_USERNAME) && !empty(MAIL_PASSWORD));
                if (!empty(MAIL_USERNAME)) $mail->Username = MAIL_USERNAME;
                if (!empty(MAIL_PASSWORD)) $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = MAIL_ENCRYPTION ?? 'tls';
                $mail->Port = MAIL_PORT ?? 587;
            }
            $fromAddress = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@example.com';
            $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : $siteName;
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail, $toName ?: $toEmail);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->CharSet = 'UTF-8';
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer send failed: ' . $e->getMessage());
            // fallback to mail()
        }
    }

    // fallback to PHP mail() with HTML content-type
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : $siteName;
    $fromAddr = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@example.com';
    $headers = "From: {$fromName} <{$fromAddr}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "Reply-To: {$fromAddr}\r\n";

    return mail($toEmail, $subject, $htmlBody, $headers);
}
