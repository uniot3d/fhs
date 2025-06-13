<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取成员ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('成员ID不能为空', 'error');
    redirect('family_members.php');
}

$member_id = (int)$_GET['id'];

// 获取成员信息
$member = getFamilyMember($member_id, $user_id);

if (!$member) {
    displayMessage('找不到该家庭成员', 'error');
    redirect('family_members.php');
}

// 获取健康记录
$sql = "SELECT * FROM health_records WHERE member_id = ? ORDER BY record_date DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$health_records = mysqli_stmt_get_result($stmt);

// 获取病历记录
$sql = "SELECT * FROM medical_records WHERE member_id = ? ORDER BY visit_date DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$medical_records = mysqli_stmt_get_result($stmt);

// 获取用药记录
$sql = "SELECT * FROM medication_records WHERE member_id = ? ORDER BY start_date DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$medication_records = mysqli_stmt_get_result($stmt);

// 获取最新的健康记录用于图表
$sql = "SELECT record_date, height, weight FROM health_records 
        WHERE member_id = ? 
        ORDER BY record_date ASC 
        LIMIT 12";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$chart_data = mysqli_stmt_get_result($stmt);

$labels = [];
$heights = [];
$weights = [];

while ($row = mysqli_fetch_assoc($chart_data)) {
    $labels[] = formatDate($row['record_date'], 'Y-m-d');
    $heights[] = $row['height'];
    $weights[] = $row['weight'];
}

?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person"></i> <?php echo $member['name']; ?> 的详细资料</h2>
    <a href="family_members.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回家庭成员列表
    </a>
</div>

<div class="row">
    <!-- 成员基本信息 -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">基本信息</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                </div>
                <div class="mb-3">
                    <strong>姓名:</strong> <?php echo $member['name']; ?>
                </div>
                <div class="mb-3">
                    <strong>性别:</strong> <?php echo $member['gender']; ?>
                </div>
                <div class="mb-3">
                    <strong>出生日期:</strong> <?php echo formatDate($member['birthday']); ?>
                </div>
                <div class="mb-3">
                    <strong>年龄:</strong> <?php echo calculateAge($member['birthday']); ?> 岁
                </div>
                <div class="mb-3">
                    <strong>与您的关系:</strong> <?php echo $member['relationship']; ?>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> 编辑信息
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 快速操作 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">快速操作</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="add_health_record.php?member_id=<?php echo $member['id']; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> 添加健康记录
                    </a>
                    <a href="add_medical_record.php?member_id=<?php echo $member['id']; ?>" class="btn btn-outline-success">
                        <i class="bi bi-plus-circle"></i> 添加病历记录
                    </a>
                    <a href="add_medication_record.php?member_id=<?php echo $member['id']; ?>" class="btn btn-outline-warning">
                        <i class="bi bi-plus-circle"></i> 添加用药记录
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- 健康指标图表 -->
        <?php if (count($labels) > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">健康指标趋势</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="healthTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="weight-tab" data-bs-toggle="tab" data-bs-target="#weight" type="button" role="tab" aria-controls="weight" aria-selected="true">体重</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="height-tab" data-bs-toggle="tab" data-bs-target="#height" type="button" role="tab" aria-controls="height" aria-selected="false">身高</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="healthTabsContent">
                    <div class="tab-pane fade show active" id="weight" role="tabpanel" aria-labelledby="weight-tab">
                        <div class="chart-container">
                            <canvas id="weightChart"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="height" role="tabpanel" aria-labelledby="height-tab">
                        <div class="chart-container">
                            <canvas id="heightChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 健康记录 -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">最近健康记录</h5>
                <a href="health_records.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($health_records) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>日期</th>
                                <th>身高 (cm)</th>
                                <th>体重 (kg)</th>
                                <th>血压 (mmHg)</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = mysqli_fetch_assoc($health_records)): ?>
                            <tr>
                                <td><?php echo formatDate($record['record_date']); ?></td>
                                <td><?php echo $record['height'] ?: '-'; ?></td>
                                <td><?php echo $record['weight'] ?: '-'; ?></td>
                                <td><?php echo $record['blood_pressure'] ?: '-'; ?></td>
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
                    <a href="add_health_record.php?member_id=<?php echo $member['id']; ?>" class="btn btn-primary">添加健康记录</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 病历记录 -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">最近病历记录</h5>
                <a href="medical_records.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($medical_records) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>就诊日期</th>
                                <th>医院</th>
                                <th>医生</th>
                                <th>诊断</th>
                                <th>CT片</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = mysqli_fetch_assoc($medical_records)): ?>
                            <tr>
                                <td><?php echo formatDate($record['visit_date']); ?></td>
                                <td><?php echo $record['hospital']; ?></td>
                                <td><?php echo $record['doctor']; ?></td>
                                <td><?php echo mb_substr($record['diagnosis'], 0, 20) . (mb_strlen($record['diagnosis']) > 20 ? '...' : ''); ?></td>
                                <td>
                                    <?php if (!empty($record['ct_image'])): ?>
                                        <a href="<?php echo $record['ct_image']; ?>" target="_blank">
                                            <img src="<?php echo $record['ct_image']; ?>" alt="CT片" style="max-width:40px;max-height:40px;object-fit:cover;">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">无</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">暂无病历记录</p>
                <div class="text-center">
                    <a href="add_medical_record.php?member_id=<?php echo $member['id']; ?>" class="btn btn-primary">添加病历记录</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 用药记录 -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">最近用药记录</h5>
                <a href="medication_records.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($medication_records) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>药物名称</th>
                                <th>用量</th>
                                <th>频率</th>
                                <th>开始日期</th>
                                <th>结束日期</th>
                                <th>关联病例</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = mysqli_fetch_assoc($medication_records)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['medication_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['dosage']); ?></td>
                                <td><?php echo htmlspecialchars($record['frequency']); ?></td>
                                <td><?php echo htmlspecialchars($record['start_date']); ?></td>
                                <td><?php echo $record['end_date'] ? htmlspecialchars($record['end_date']) : '<span class="text-muted">长期</span>'; ?></td>
                                <td>
                                    <?php
                                    if (!empty($record['medical_record_id'])) {
                                        $sql_case = "SELECT visit_date, diagnosis FROM medical_records WHERE id=?";
                                        $stmt_case = mysqli_prepare($conn, $sql_case);
                                        mysqli_stmt_bind_param($stmt_case, "i", $record['medical_record_id']);
                                        mysqli_stmt_execute($stmt_case);
                                        $result_case = mysqli_stmt_get_result($stmt_case);
                                        if ($row_case = mysqli_fetch_assoc($result_case)) {
                                            echo htmlspecialchars($row_case['visit_date'] . ' | ' . mb_substr($row_case['diagnosis'],0,20));
                                        } else {
                                            echo '<span class="text-muted">已删除</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="view_medication_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_medication_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">暂无用药记录</p>
                <div class="text-center">
                    <a href="add_medication_record.php?member_id=<?php echo $member['id']; ?>" class="btn btn-primary">添加用药记录</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($labels) > 0): ?>
<script>
// 图表数据
var weightChartData = {
    labels: <?php echo json_encode($labels); ?>,
    data: <?php echo json_encode($weights); ?>
};

var heightChartData = {
    labels: <?php echo json_encode($labels); ?>,
    data: <?php echo json_encode($heights); ?>
};
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 