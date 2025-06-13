<?php
require_once 'includes/functions.php';
if (!isLoggedIn()) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];
// 获取所有家庭成员
$family_members = getAllFamilyMembers($user_id);
// 获取所有病历，按时间排序
$sql = "SELECT mr.*, fm.name as member_name FROM medical_records mr JOIN family_members fm ON mr.member_id = fm.id WHERE fm.user_id = ? ORDER BY mr.visit_date ASC, mr.id ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$records = mysqli_stmt_get_result($stmt);
// 按疾病分组
$diseases = [];
while ($row = mysqli_fetch_assoc($records)) {
    $diagnosis = trim($row['diagnosis']);
    if ($diagnosis === '') $diagnosis = '未填写';
    $diseases[$diagnosis][] = $row;
}
?>
<?php include 'includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-virus2"></i> 疾病管理</h2>
    <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> 返回仪表盘</a>
</div>
<div class="alert alert-info">本页按疾病分组，展示每种疾病的确诊、复查、治疗、用药等过程。点击疾病名称可查看详细过程。</div>
<?php if (count($diseases) === 0): ?>
    <div class="alert alert-warning">暂无确诊疾病记录。</div>
<?php else: ?>
    <?php foreach ($diseases as $disease => $records): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><strong>疾病：</strong> <a href="disease_detail.php?name=<?php echo urlencode($disease); ?>"><?php echo htmlspecialchars($disease); ?></a></span>
                <span class="badge bg-primary">共<?php echo count($records); ?>次就诊</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>成员</th>
                                <th>就诊日期</th>
                                <th>医院</th>
                                <th>医生</th>
                                <th>处方/治疗</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rec['member_name']); ?></td>
                                <td><?php echo htmlspecialchars($rec['visit_date']); ?></td>
                                <td><?php echo htmlspecialchars($rec['hospital']); ?></td>
                                <td><?php echo htmlspecialchars($rec['doctor']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($rec['prescription'])); ?></td>
                                <td><a href="view_medical_record.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-info">详情</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?> 