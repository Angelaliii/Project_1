<div class="profile-container">
    <!-- 個人資料卡片 -->
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['user_name'] ?? ''); ?></h2>
                <span class="profile-role">
                    <?php 
                    $roleText = '用戶';
                    if (isset($user['role'])) {
                        switch($user['role']) {
                            case 'admin': $roleText = '管理員'; break;
                            case 'teacher': $roleText = '教師'; break;
                            case 'student': $roleText = '學生'; break;
                        }
                    }
                    echo $roleText;
                    ?>
                </span>
            </div>
        </div>
        
        <div class="profile-body">
            <div class="profile-section">
                <h3>個人資料</h3>
                <div class="profile-field">
                    <span class="field-label">電子郵件</span>
                    <span class="field-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">學號/工號</span>
                    <span class="field-value"><?php echo htmlspecialchars($user['student_id'] ?? ''); ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">科系</span>
                    <span class="field-value"><?php echo htmlspecialchars($user['department'] ?? ''); ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">註冊日期</span>
                    <span class="field-value">
                        <?php echo isset($user['created_at']) ? date('Y/m/d', strtotime($user['created_at'])) : ''; ?>
                    </span>
                </div>
            </div>
            
            <div class="profile-actions">
                <a href="<?php echo url('user/edit'); ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> 編輯資料
                </a>
                <a href="<?php echo url('user/changePassword'); ?>" class="btn btn-secondary">
                    <i class="fas fa-key"></i> 修改密碼
                </a>
            </div>
        </div>
    </div>
    
    <!-- 統計數據 -->
    <div class="profile-stats">
        <div class="stats-card">
            <div class="stats-header">
                <h3>預約統計</h3>
            </div>
            <div class="stats-body">
                <div class="stats-item">
                    <div class="stats-value"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stats-label">總預約數</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $stats['upcoming'] ?? 0; ?></div>
                    <div class="stats-label">即將到來</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $stats['month'] ?? 0; ?></div>
                    <div class="stats-label">本月預約</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card">
            <div class="stats-header">
                <h3>活動記錄</h3>
            </div>
            <div class="stats-body">
                <?php if (empty($activities)): ?>
                    <div class="empty-state">
                        <p>沒有最近活動記錄</p>
                    </div>
                <?php else: ?>
                    <ul class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <li class="activity-item">
                                <span class="activity-icon">
                                    <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                </span>
                                <div class="activity-content">
                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <span class="activity-time"><?php echo date('m/d H:i', strtotime($activity['timestamp'])); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($activities)): ?>
                    <div class="activity-actions">
                        <a href="<?php echo url('user/activities'); ?>" class="btn btn-text">查看所有活動</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
