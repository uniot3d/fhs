<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取记录ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('记录ID不能为空', 'error');
    redirect('health_records.php');
}

$record_id = (int)$_GET['id'];

// 获取健康记录
$sql = "SELECT hr.*, fm.name as member_name, fm.id as member_id 
        FROM health_records hr
        JOIN family_members fm ON hr.member_id = fm.id
        WHERE hr.id = ? AND fm.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $record_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    displayMessage('找不到该健康记录', 'error');
    redirect('health_records.php');
}

$record = mysqli_fetch_assoc($result);
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-pulse"></i> 健康记录详情</h2>
    <div>
        <a href="edit_health_record.php?id=<?php echo $record_id; ?>" class="btn btn-primary me-2">
            <i class="bi bi-pencil"></i> 编辑记录
        </a>
        <a href="health_records.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> 返回健康记录列表
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $record['member_name']; ?> 的健康记录</h5>
            <span class="badge bg-primary"><?php echo formatDate($record['record_date']); ?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold">基本信息</h6>
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">家庭成员</td>
                        <td>
                            <a href="view_member.php?id=<?php echo $record['member_id']; ?>">
                                <?php echo $record['member_name']; ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">记录日期</td>
                        <td><?php echo formatDate($record['record_date']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold">身体指标</h6>
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">身高</td>
                        <td><?php echo $record['height'] ? $record['height'] . ' cm' : '未记录'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">体重</td>
                        <td><?php echo $record['weight'] ? $record['weight'] . ' kg' : '未记录'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold">血压与血糖</h6>
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">血压</td>
                        <td><?php echo $record['blood_pressure'] ?: '未记录'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">血糖</td>
                        <td><?php echo $record['blood_sugar'] ? $record['blood_sugar'] . ' mmol/L' : '未记录'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold">其他指标</h6>
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">心率</td>
                        <td><?php echo $record['heart_rate'] ? $record['heart_rate'] . ' 次/分' : '未记录'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">体温</td>
                        <td><?php echo $record['temperature'] ? $record['temperature'] . ' °C' : '未记录'; ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if (!empty($record['notes'])): ?>
            <div class="col-12 mt-3">
                <h6 class="fw-bold">备注</h6>
                <div class="border rounded p-3 bg-light">
                    <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-footer text-muted">
        <small>记录时间: <?php echo formatDate($record['created_at'], 'Y-m-d H:i:s'); ?></small>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 