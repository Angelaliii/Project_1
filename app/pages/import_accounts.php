<?php
session_start();
require_once '../config/database.php';
require_once '../models/UserModel.php';
require_once '../helpers/security.php';
require_once '../helpers/mail.php';

$message = '';
$results = null;

// 權限檢查：限 admin 或 department
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','department'])) {
    header('HTTP/1.1 403 Forbidden');
    echo '您沒有權限使用此功能';
    exit;
}

$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_accounts'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'CSRF 驗證失敗';
    } elseif (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $message = '請上傳有效的 CSV 檔案';
    } else {
        $tmp = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmp, 'r');
        if ($handle === false) {
            $message = '無法讀取上傳檔案';
        } else {
            $header = fgetcsv($handle);
            if ($header === false) {
                $message = 'CSV 檔案為空';
            } else {
                // 標準欄位
                $expected = ['account','name','email','unit','roles','initial_password'];
                $map = [];
                foreach ($header as $i => $col) {
                    $colNorm = strtolower(trim($col));
                    if (in_array($colNorm, $expected)) $map[$colNorm] = $i;
                }

                // 必要欄位檢查
                if (!isset($map['account']) || !isset($map['email'])) {
                    $message = 'CSV 必須包含 account 與 email 欄位（欄名不分大小寫）';
                } else {
                    $created = 0; $updated = 0; $errors = [];
                    $lineNo = 1;
                    while (($row = fgetcsv($handle)) !== false) {
                        $lineNo++;
                        $username = trim($row[$map['account']] ?? '');
                        $email = trim($row[$map['email']] ?? '');
                        $name = trim($row[$map['name']] ?? '');
                        $unit = trim($row[$map['unit']] ?? '');
                        $roles = trim($row[$map['roles']] ?? '');
                        $initial_password = trim($row[$map['initial_password']] ?? '');

                        if (empty($username) || empty($email)) {
                            $errors[] = "第{$lineNo}行：帳號或 Email 為空";
                            continue;
                        }
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "第{$lineNo}行：Email 格式不正確 ({$email})";
                            continue;
                        }

                        try {
                            $existing = $userModel->findByEmail($email) ?: $userModel->findByUsername($username);
                            $role = !empty($roles) ? $roles : 'student';
                            if ($existing) {
                                // update fields
                                $data = ['user_name' => $username, 'mail' => $email, 'role' => $role];
                                if (!empty($initial_password)) $data['password'] = $initial_password;
                                $userModel->update($existing['user_id'], $data);
                                $updated++;
                            } else {
                                $pw = $initial_password ?: bin2hex(random_bytes(4));
                                $userModel->create($username, $email, $pw, $role);
                                $created++;
                                // 若選擇寄送初始密碼，嘗試寄信
                                if (!empty($_POST['send_initial'])) {
                                    $sent = send_initial_password_email($email, $name ?: $username, $username, $pw);
                                    if (!$sent) {
                                        $errors[] = "第{$lineNo}行：無法寄送初始密碼到 {$email}";
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $errors[] = "第{$lineNo}行：處理時發生錯誤 - " . $e->getMessage();
                        }
                    }

                    $results = ['created'=>$created,'updated'=>$updated,'errors'=>$errors];
                }
            }
            fclose($handle);
        }
    }
}

// 頁面輸出
$pageTitle = '帳號批次匯入';
$pageStyles = ['auth.css'];
include_once '../components/header.php';
?>
<main class="content-container p-4">
    <div class="mx-auto" style="max-width:800px;">
        <div class="content-header">
            <h1>帳號匯入 (CSV)</h1>
            <p>CSV 欄位：account,name,email,unit,roles,initial_password（欄位不分大小寫）。</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">選擇 CSV 檔案</label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="sendInitial" name="send_initial">
                        <label class="form-check-label" for="sendInitial">匯入時寄送初始密碼給新建立的帳號</label>
                    </div>
                    <button class="btn btn-primary" name="import_accounts" type="submit">上傳並匯入</button>
                </form>

        <?php if (is_array($results)): ?>
            <hr>
            <h4>匯入結果</h4>
            <p>建立：<?= $results['created'] ?>，更新：<?= $results['updated'] ?></p>
            <?php if (!empty($results['errors'])): ?>
                <div class="alert alert-warning">
                    <ul>
                    <?php foreach ($results['errors'] as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../components/footer.php'; ?>
