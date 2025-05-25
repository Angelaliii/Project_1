<?php
// admin/settings.php - 管理員系統設定頁面
// 包含初始化檔案
require_once '../init.php';

// 頁面標題
$page_title = '系統設定';

// 包含頁頭
include_once '../components/header.php';
?>

<div class="admin-container">
    <?php include_once '../components/admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-cogs"></i> 系統設定</h1>
        </div>
        
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2>一般設定</h2>
                </div>
                <div class="card-body">
                    <form id="system-settings-form" method="post" action="../api/admin/update_settings.php">
                        <div class="form-group">
                            <label for="site-title">網站標題</label>
                            <input type="text" id="site-title" name="site_title" class="form-control" value="教室租借系統" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-start-hour">預約開始時間</label>
                            <select id="booking-start-hour" name="booking_start_hour" class="form-control">
                                <?php for($i = 6; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>:00</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-end-hour">預約結束時間</label>
                            <select id="booking-end-hour" name="booking_end_hour" class="form-control">
                                <?php for($i = 17; $i <= 22; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>:00</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-interval">預約時間間隔（分鐘）</label>
                            <select id="booking-interval" name="booking_interval" class="form-control">
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="60">60</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="max-booking-days-ahead">最多可提前預約天數</label>
                            <input type="number" id="max-booking-days-ahead" name="max_booking_days_ahead" class="form-control" value="30" min="1" max="90">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">儲存設定</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h2>系統維護</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>資料庫備份</label>
                        <button id="backup-database" class="btn btn-secondary">建立備份</button>
                    </div>
                    
                    <div class="form-group">
                        <label>清除過期預約</label>
                        <button id="clear-expired-bookings" class="btn btn-warning">清除資料</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 載入現有設定
    fetchSystemSettings();
    
    // 系統設定表單提交
    document.getElementById('system-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        updateSystemSettings(this);
    });
    
    // 資料庫備份
    document.getElementById('backup-database').addEventListener('click', function() {
        backupDatabase();
    });
    
    // 清除過期預約
    document.getElementById('clear-expired-bookings').addEventListener('click', function() {
        clearExpiredBookings();
    });
});

// 載入系統設定
function fetchSystemSettings() {
    fetch('../api/admin/get_settings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 填入表單數據
                document.getElementById('site-title').value = data.settings.site_title;
                document.getElementById('booking-start-hour').value = data.settings.booking_start_hour;
                document.getElementById('booking-end-hour').value = data.settings.booking_end_hour;
                document.getElementById('booking-interval').value = data.settings.booking_interval;
                document.getElementById('max-booking-days-ahead').value = data.settings.max_booking_days_ahead;
            } else {
                showAlert('error', '無法載入系統設定');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', '發生錯誤');
        });
}

// 更新系統設定
function updateSystemSettings(form) {
    const formData = new FormData(form);
    
    fetch('../api/admin/update_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', '系統設定已更新');
        } else {
            showAlert('error', data.message || '無法更新系統設定');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', '發生錯誤');
    });
}

// 資料庫備份
function backupDatabase() {
    fetch('../api/admin/backup_database.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', '資料庫備份已完成');
            } else {
                showAlert('error', data.message || '無法備份資料庫');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', '發生錯誤');
        });
}

// 清除過期預約
function clearExpiredBookings() {
    if (confirm('確定要清除所有過期預約嗎？此操作無法復原。')) {
        fetch('../api/admin/clear_expired_bookings.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', `已清除 ${data.count} 筆過期預約`);
                } else {
                    showAlert('error', data.message || '無法清除過期預約');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', '發生錯誤');
            });
    }
}

// 顯示通知
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = message;
    
    document.querySelector('.main-content').insertBefore(alertDiv, document.querySelector('.page-header'));
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php include_once '../components/admin_footer.php'; ?>
