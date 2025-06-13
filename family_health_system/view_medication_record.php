<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 获取用药记录ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('用药记录ID不能为空', 'error');
    redirect('medication_records.php');
}
$record_id = (int)$_GET['id'];

// 获取用药记录
$sql = "SELECT mr.*, fm.name as member_name, fm.user_id FROM medication_records mr JOIN family_members fm ON mr.member_id = fm.id WHERE mr.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) != 1) {
    displayMessage('找不到该用药记录', 'error');
    redirect('medication_records.php');
}
$record = mysqli_fetch_assoc($result);
if ($record['user_id'] != $user_id) {
    displayMessage('无权限查看该用药记录', 'error');
    redirect('medication_records.php');
}
?>
<?php include 'includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-capsule"></i> 用药记录详情</h2>
    <a href="medication_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回用药记录列表
    </a>
</div>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">成员：<?php echo htmlspecialchars($record['member_name']); ?></h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>药物名称：</strong> <?php echo htmlspecialchars($record['medication_name']); ?>
            </div>
            <div class="col-md-6">
                <strong>用量：</strong> <?php echo htmlspecialchars($record['dosage']); ?>
            </div>
            <div class="col-md-6">
                <strong>频率：</strong> <?php echo htmlspecialchars($record['frequency']); ?>
            </div>
            <div class="col-md-6">
                <strong>开始日期：</strong> <?php echo htmlspecialchars($record['start_date']); ?>
            </div>
            <div class="col-md-6">
                <strong>结束日期：</strong> <?php echo $record['end_date'] ? htmlspecialchars($record['end_date']) : '<span class="badge bg-info">长期</span>'; ?>
            </div>
            <div class="col-md-6">
                <strong>状态：</strong> <?php echo (!$record['end_date'] || $record['end_date'] >= date('Y-m-d')) ? '<span class="badge bg-success">当前用药</span>' : '<span class="badge bg-secondary">已停用</span>'; ?>
            </div>
        </div>
        <?php if (!empty($record['notes'])): ?>
        <div class="mb-3">
            <strong>备注：</strong>
            <div class="border rounded p-2 bg-light mt-1"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer text-muted">
        <small>创建时间：<?php echo formatDate($record['created_at'], 'Y-m-d H:i:s'); ?></small>
    </div>
</div>
<?php include 'includes/footer.php'; ?> 