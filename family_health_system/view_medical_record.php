<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 获取病历ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('病历ID不能为空', 'error');
    redirect('medical_records.php');
}
$record_id = (int)$_GET['id'];

// 获取病历记录
$sql = "SELECT mr.*, fm.name as member_name, fm.user_id FROM medical_records mr JOIN family_members fm ON mr.member_id = fm.id WHERE mr.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) != 1) {
    displayMessage('找不到该病历记录', 'error');
    redirect('medical_records.php');
}
$record = mysqli_fetch_assoc($result);
if ($record['user_id'] != $user_id) {
    displayMessage('无权限查看该病历', 'error');
    redirect('medical_records.php');
}
// var_dump($record_id, $record, $sql);
?>
<?php include 'includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-medical"></i> 病历详情</h2>
    <a href="medical_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回病历列表
    </a>
</div>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">成员：<?php echo htmlspecialchars($record['member_name']); ?></h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>就诊日期：</strong> <?php echo htmlspecialchars($record['visit_date']); ?>
            </div>
            <div class="col-md-6">
                <strong>医院：</strong> <?php echo htmlspecialchars($record['hospital']); ?>
            </div>
            <div class="col-md-6">
                <strong>医生：</strong> <?php echo htmlspecialchars($record['doctor']); ?>
            </div>
        </div>
        <div class="mb-3">
            <strong>诊断结果：</strong>
            <div class="border rounded p-2 bg-light mt-1"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></div>
        </div>
        <?php if (!empty($record['prescription'])): ?>
        <div class="mb-3">
            <strong>处方：</strong>
            <div class="border rounded p-2 bg-light mt-1"><?php echo nl2br(htmlspecialchars($record['prescription'])); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($record['notes'])): ?>
        <div class="mb-3">
            <strong>备注：</strong>
            <div class="border rounded p-2 bg-light mt-1"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($record['ct_image'])): ?>
        <div class="mb-3">
            <strong>CT片：</strong><br>
            <a href="<?php echo $record['ct_image']; ?>" target="_blank">
                <img src="<?php echo $record['ct_image']; ?>" alt="CT片" style="max-width:300px;max-height:300px;object-fit:contain;border:1px solid #ccc;">
            </a>
        </div>
        <?php endif; ?>
        <?php
        // 展示最多5个附件
        $has_attachment = false;
        for ($i = 1; $i <= 5; $i++) {
            $att = 'attachment'.$i;
            if (!empty($record[$att])) {
                if (!$has_attachment) {
                    echo '<div class="mb-3"><strong>附件：</strong><br>';
                    $has_attachment = true;
                }
                $ext = strtolower(pathinfo($record[$att], PATHINFO_EXTENSION));
                echo '<div class="d-inline-block me-3 mb-2">';
                if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                    echo '<a href="'.$record[$att].'" target="_blank"><img src="'.$record[$att].'" alt="附件'.$i.'" style="max-width:80px;max-height:80px;object-fit:cover;border:1px solid #ccc;"></a>';
                } elseif ($ext === 'pdf') {
                    echo '<a href="'.$record[$att].'" target="_blank"><span class="badge bg-secondary">PDF</span> 查看附件'.$i.'</a>';
                } else {
                    echo '<a href="'.$record[$att].'" target="_blank">附件'.$i.'</a>';
                }
                echo '</div>';
            }
        }
        if ($has_attachment) echo '</div>';
        ?>
    </div>
    <div class="card-footer text-muted">
        <small>创建时间：<?php echo formatDate($record['created_at'], 'Y-m-d H:i:s'); ?></small>
    </div>
</div>
<?php
// 查询与本病例关联的用药记录
$sql = "SELECT * FROM medication_records WHERE medical_record_id = ? ORDER BY start_date ASC, id ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$medications = mysqli_stmt_get_result($stmt);
?>
<?php if (mysqli_num_rows($medications) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-warning-subtle"><i class="bi bi-capsule"></i> 关联用药记录</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>药物名称</th>
                        <th>用量</th>
                        <th>频率</th>
                        <th>开始日期</th>
                        <th>结束日期</th>
                        <th>备注</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($med = mysqli_fetch_assoc($medications)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($med['medication_name']); ?></td>
                        <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                        <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                        <td><?php echo htmlspecialchars($med['start_date']); ?></td>
                        <td><?php echo $med['end_date'] ? htmlspecialchars($med['end_date']) : '<span class="text-muted">长期</span>'; ?></td>
                        <td><?php echo nl2br(htmlspecialchars($med['notes'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?> 