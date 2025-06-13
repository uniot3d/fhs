<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/functions.php';

if (!file_exists('install.lock')) {
    header('Location: install.php');
    exit;
}

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 检查是否预选了家庭成员
$selected_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
if ($selected_member_id > 0) {
    // 验证成员是否属于当前用户
    $member = getFamilyMember($selected_member_id, $user_id);
    if (!$member) {
        $selected_member_id = 0;
    }
}

// 处理添加健康记录请求
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
            // 插入健康记录
            $sql = "INSERT INTO health_records (member_id, record_date, height, weight, blood_pressure, blood_sugar, heart_rate, temperature, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                displayMessage('SQL错误: ' . mysqli_error($conn), 'error');
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    "isddsdisd",
                    $member_id,
                    $record_date,
                    $height,
                    $weight,
                    $blood_pressure,
                    $blood_sugar,
                    $heart_rate,
                    $temperature,
                    $notes
                );
                if (mysqli_stmt_execute($stmt)) {
                    displayMessage('健康记录添加成功', 'success');
                    redirect('health_records.php');
                } else {
                    displayMessage('添加健康记录失败: ' . mysqli_error($conn), 'error');
                }
            }
        }
    }
    
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 获取所有家庭成员
$family_members = getAllFamilyMembers($user_id);

// 如果没有家庭成员，提示先添加家庭成员
if (count($family_members) == 0) {
    displayMessage('请先添加家庭成员', 'info');
    redirect('family_members.php');
}

$message = getMessage();
if ($message):
?>
<div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $message['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle"></i> 添加健康记录</h2>
    <a href="health_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回健康记录列表
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="add_health_record.php" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="member_id" class="form-label">家庭成员</label>
                    <select class="form-select" id="member_id" name="member_id" required>
                        <option value="">请选择家庭成员</option>
                        <?php foreach ($family_members as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo ($selected_member_id == $member['id']) ? 'selected' : ''; ?>>
                            <?php echo $member['name']; ?> (<?php echo $member['relationship']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">请选择家庭成员</div>
                </div>
                
                <div class="col-md-6">
                    <label for="record_date" class="form-label">记录日期</label>
                    <input type="date" class="form-control" id="record_date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>
                    <div class="invalid-feedback">请选择记录日期</div>
                </div>
                
                <div class="col-md-6">
                    <label for="height" class="form-label">身高 (cm)</label>
                    <input type="number" class="form-control" id="height" name="height" step="0.1" min="0" max="300">
                </div>
                
                <div class="col-md-6">
                    <label for="weight" class="form-label">体重 (kg)</label>
                    <input type="number" class="form-control" id="weight" name="weight" step="0.1" min="0" max="500">
                </div>
                
                <div class="col-md-6">
                    <label for="blood_pressure" class="form-label">血压 (mmHg)</label>
                    <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="例如: 120/80">
                </div>
                
                <div class="col-md-6">
                    <label for="blood_sugar" class="form-label">血糖 (mmol/L)</label>
                    <input type="number" class="form-control" id="blood_sugar" name="blood_sugar" step="0.1" min="0" max="50">
                </div>
                
                <div class="col-md-6">
                    <label for="heart_rate" class="form-label">心率 (次/分)</label>
                    <input type="number" class="form-control" id="heart_rate" name="heart_rate" min="0" max="300">
                </div>
                
                <div class="col-md-6">
                    <label for="temperature" class="form-label">体温 (°C)</label>
                    <input type="number" class="form-control" id="temperature" name="temperature" step="0.1" min="30" max="45">
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">备注</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">保存健康记录</button>
                    <a href="health_records.php" class="btn btn-secondary">取消</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 