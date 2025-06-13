<?php
require_once 'includes/functions.php';

if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取家庭成员数量
$sql = "SELECT COUNT(*) as total_members FROM family_members WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_members = mysqli_fetch_assoc($result)['total_members'];

// 获取最近的健康记录数量
$sql = "SELECT COUNT(*) as total_records FROM health_records hr
        JOIN family_members fm ON hr.member_id = fm.id
        WHERE fm.user_id = ? AND hr.record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recent_health_records = mysqli_fetch_assoc($result)['total_records'];

// 获取病历记录数量
$sql = "SELECT COUNT(*) as total_records FROM medical_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_medical_records = mysqli_fetch_assoc($result)['total_records'];

// 获取用药记录数量
$sql = "SELECT COUNT(*) as total_records FROM medication_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ? AND (mr.end_date IS NULL OR mr.end_date >= CURDATE())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$active_medications = mysqli_fetch_assoc($result)['total_records'];

// 获取最近的5条健康记录
$sql = "SELECT hr.*, fm.name as member_name FROM health_records hr
        JOIN family_members fm ON hr.member_id = fm.id
        WHERE fm.user_id = ?
        ORDER BY hr.record_date DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_records = mysqli_stmt_get_result($stmt);

// 获取即将到期的用药提醒
$sql = "SELECT mr.*, fm.name as member_name FROM medication_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ? AND (mr.end_date IS NULL OR mr.end_date >= CURDATE())
        ORDER BY mr.end_date ASC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$medication_reminders = mysqli_stmt_get_result($stmt);

?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2"></i> 仪表盘</h2>
    <div>
        <a href="health_analysis.php" class="btn btn-success me-2">
            <i class="bi bi-graph-up"></i> 健康分析
        </a>
        <a href="medication_analysis.php" class="btn btn-info me-2">
            <i class="bi bi-capsule"></i> 用药分析
        </a>
        <a href="family_members.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> 添加成员
        </a>
    </div>
</div>

<!-- 数据概览 -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="data-card bg-gradient-primary">
            <i class="bi bi-people"></i>
            <div class="card-value"><?php echo $total_members; ?></div>
            <div class="card-title">家庭成员</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="data-card bg-gradient-success">
            <i class="bi bi-activity"></i>
            <div class="card-value"><?php echo $recent_health_records; ?></div>
            <div class="card-title">近30天健康记录</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="data-card bg-gradient-info">
            <i class="bi bi-journal-medical"></i>
            <div class="card-value"><?php echo $total_medical_records; ?></div>
            <div class="card-title">病历记录</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="data-card bg-gradient-warning">
            <i class="bi bi-capsule"></i>
            <div class="card-value"><?php echo $active_medications; ?></div>
            <div class="card-title">当前用药</div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- 最近健康记录 -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> 最近健康记录</h5>
                <a href="health_records.php" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recent_records) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>成员</th>
                                <th>日期</th>
                                <th>身高</th>
                                <th>体重</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = mysqli_fetch_assoc($recent_records)): ?>
                            <tr>
                                <td><?php echo $record['member_name']; ?></td>
                                <td><?php echo formatDate($record['record_date']); ?></td>
                                <td><?php echo $record['height'] ? $record['height'] . ' cm' : '-'; ?></td>
                                <td><?php echo $record['weight'] ? $record['weight'] . ' kg' : '-'; ?></td>
                                <td>
                                    <a href="view_health_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">暂无健康记录</p>
                <div class="text-center">
                    <a href="add_health_record.php" class="btn btn-primary">添加健康记录</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 用药提醒 -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-bell"></i> 用药提醒</h5>
                <div>
                    <a href="medication_analysis.php" class="btn btn-sm btn-outline-info me-2">
                        <i class="bi bi-graph-up"></i> 查看分析
                    </a>
                    <a href="medication_records.php" class="btn btn-sm btn-outline-primary">查看全部</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($medication_reminders) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>成员</th>
                                <th>药物名称</th>
                                <th>用量</th>
                                <th>频率</th>
                                <th>结束日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($med = mysqli_fetch_assoc($medication_reminders)): ?>
                            <tr>
                                <td><?php echo $med['member_name']; ?></td>
                                <td><?php echo $med['medication_name']; ?></td>
                                <td><?php echo $med['dosage']; ?></td>
                                <td><?php echo $med['frequency']; ?></td>
                                <td>
                                    <?php 
                                    if ($med['end_date']) {
                                        echo formatDate($med['end_date']);
                                    } else {
                                        echo '<span class="badge bg-info">长期</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">暂无用药提醒</p>
                <div class="text-center">
                    <a href="add_medication_record.php" class="btn btn-primary">添加用药记录</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 健康贴士 -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> 健康贴士</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3 text-primary">
                        <i class="bi bi-water fs-1"></i>
                    </div>
                    <div>
                        <h5>保持水分</h5>
                        <p class="mb-0">每天至少饮用8杯水，保持身体水分充足。</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3 text-success">
                        <i class="bi bi-tree fs-1"></i>
                    </div>
                    <div>
                        <h5>户外活动</h5>
                        <p class="mb-0">每天进行适量户外活动，呼吸新鲜空气。</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3 text-warning">
                        <i class="bi bi-brightness-high fs-1"></i>
                    </div>
                    <div>
                        <h5>规律作息</h5>
                        <p class="mb-0">保持规律的作息时间，每晚睡够7-8小时。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 