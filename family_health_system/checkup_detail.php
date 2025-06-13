<?php
require_once 'includes/functions.php';
if (!isLoggedIn()) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$record = null;
if (!$record_id) {
    $error = '体检报告ID不能为空';
} else {
    $sql = "SELECT * FROM checkup_records WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $record_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) != 1) {
            $error = '找不到该体检报告';
        } else {
            $record = mysqli_fetch_assoc($result);
        }
    } else {
        $error = 'SQL错误: ' . mysqli_error($conn);
    }
}
include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-data"></i> 体检报告详情</h2>
    <a href="checkup_records.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> 返回体检管理</a>
</div>
<?php if ($error): ?>
<div class="alert alert-danger">错误：<?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($record && !$error): ?>
<div class="card mb-4">
    <div class="card-header">体检报告信息</div>
    <div class="card-body">
        <div class="mb-2"><strong>标题：</strong><?php echo htmlspecialchars($record['title']); ?></div>
        <div class="mb-2"><strong>体检日期：</strong><?php echo htmlspecialchars($record['checkup_date']); ?></div>
        <div class="mb-2"><strong>医院：</strong><?php echo htmlspecialchars($record['hospital']); ?></div>
        <div class="mb-2"><strong>体检结果：</strong></div>
        <pre style="white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($record['result']); ?></pre>
        <div class="mb-2"><strong>备注：</strong></div>
        <pre style="white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($record['notes']); ?></pre>
        <?php if (!empty($record['ai_result'])): ?>
        <div class="mt-4">
            <strong>AI分析结果：</strong>
            <pre style="white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($record['ai_result']); ?></pre>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?> 