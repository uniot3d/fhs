<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 获取记录ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('记录ID不能为空', 'error');
    redirect('health_records.php');
}
$record_id = (int)$_GET['id'];

// 获取健康记录
$sql = "SELECT hr.*, fm.user_id FROM health_records hr JOIN family_members fm ON hr.member_id = fm.id WHERE hr.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) != 1) {
    displayMessage('找不到该健康记录', 'error');
    redirect('health_records.php');
}
$record = mysqli_fetch_assoc($result);
if ($record['user_id'] != $user_id) {
    displayMessage('无权限编辑该健康记录', 'error');
    redirect('health_records.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = (int)$_POST['member_id'];
    $record_date = sanitizeInput($_POST['record_date']);
    $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $blood_pressure = sanitizeInput($_POST['blood_pressure']);
    $blood_sugar = !empty($_POST['blood_sugar']) ? (float)$_POST['blood_sugar'] : null;
    $heart_rate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    $notes = sanitizeInput($_POST['notes']);
    $error = '';

    // 验证输入
    if (empty($member_id)) {
        $error = '请选择家庭成员';
    } elseif (empty($record_date)) {
        $error = '请选择记录日期';
    } else {
        // 验证成员是否属于当前用户
        $member = getFamilyMember($member_id, $user_id);
        if (!$member) {
            $error = '所选家庭成员无效';
        } else {
            // 更新健康记录
            $sql = "UPDATE health_records SET member_id=?, record_date=?, height=?, weight=?, blood_pressure=?, blood_sugar=?, heart_rate=?, temperature=?, notes=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                displayMessage('SQL错误: ' . mysqli_error($conn), 'error');
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    "isddsdisdi",
                    $member_id,
                    $record_date,
                    $height,
                    $weight,
                    $blood_pressure,  // 问题点：血压值应是字符串
                    $blood_sugar,
                    $heart_rate,
                    $temperature,
                    $notes,
                    $record_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    displayMessage('健康记录更新成功', 'success');
                    redirect('health_records.php');
                } else {
                    displayMessage('更新健康记录失败: ' . mysqli_error($conn), 'error');
                }
            }
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
        // 更新$record用于表单回显
        $record = array_merge($record, [
            'member_id' => $member_id,
            'record_date' => $record_date,
            'height' => $height,
            'weight' => $weight,
            'blood_pressure' => $blood_pressure,
            'blood_sugar' => $blood_sugar,
            'heart_rate' => $heart_rate,
            'temperature' => $temperature,
            'notes' => $notes
        ]);
    }
}

// 获取所有家庭成员
$family_members = getAllFamilyMembers($user_id);
if (count($family_members) == 0) {
    displayMessage('请先添加家庭成员', 'info');
    redirect('family_members.php');
}

$message = getMessage();
if ($message): ?>
<div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $message['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> 编辑健康记录</h2>
    <a href="health_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回健康记录列表
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="edit_health_record.php?id=<?php echo $record_id; ?>" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="member_id" class="form-label">家庭成员</label>
                    <select class="form-select" id="member_id" name="member_id" required>
                        <option value="">请选择家庭成员</option>
                        <?php foreach ($family_members as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo ($record['member_id'] == $member['id']) ? 'selected' : ''; ?>>
                            <?php echo $member['name']; ?> (<?php echo $member['relationship']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">请选择家庭成员</div>
                </div>
                <div class="col-md-6">
                    <label for="record_date" class="form-label">记录日期</label>
                    <input type="date" class="form-control" id="record_date" name="record_date" value="<?php echo htmlspecialchars($record['record_date']); ?>" required>
                    <div class="invalid-feedback">请选择记录日期</div>
                </div>
                <div class="col-md-6">
                    <label for="height" class="form-label">身高 (cm)</label>
                    <input type="number" class="form-control" id="height" name="height" step="0.1" min="0" max="300" value="<?php echo htmlspecialchars($record['height']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="weight" class="form-label">体重 (kg)</label>
                    <input type="number" class="form-control" id="weight" name="weight" step="0.1" min="0" max="500" value="<?php echo htmlspecialchars($record['weight']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="blood_pressure" class="form-label">血压 (mmHg)</label>
                    <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="例如: 120/80" value="<?php echo htmlspecialchars($record['blood_pressure']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="blood_sugar" class="form-label">血糖 (mmol/L)</label>
                    <input type="number" class="form-control" id="blood_sugar" name="blood_sugar" step="0.1" min="0" max="50" value="<?php echo htmlspecialchars($record['blood_sugar']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="heart_rate" class="form-label">心率 (次/分)</label>
                    <input type="number" class="form-control" id="heart_rate" name="heart_rate" min="0" max="300" value="<?php echo htmlspecialchars($record['heart_rate']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="temperature" class="form-label">体温 (°C)</label>
                    <input type="number" class="form-control" id="temperature" name="temperature" step="0.1" min="30" max="45" value="<?php echo htmlspecialchars($record['temperature']); ?>">
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">备注</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">保存更改</button>
                    <a href="health_records.php" class="btn btn-secondary">取消</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>