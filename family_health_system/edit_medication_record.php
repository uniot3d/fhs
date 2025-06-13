<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$id = (int)$_GET['id'];

// 获取用药记录信息
$sql = "SELECT mr.*, fm.name as member_name, fm.relationship 
        FROM medication_records mr 
        JOIN family_members fm ON mr.member_id = fm.id 
        WHERE mr.id = ? AND fm.user_id = ?";

try {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('准备SQL语句失败: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('执行SQL语句失败: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception('获取结果集失败: ' . mysqli_error($conn));
    }

    $record = mysqli_fetch_assoc($result);
    if (!$record) {
        throw new Exception('找不到用药记录或无权访问');
    }

    // 获取该成员的所有病例记录
    $sql = "SELECT id, visit_date, diagnosis FROM medical_records WHERE member_id = ? ORDER BY visit_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('准备病例记录SQL语句失败: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $record['member_id']);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('执行病例记录SQL语句失败: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception('获取病例记录结果集失败: ' . mysqli_error($conn));
    }

    $member_medical_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // 从现有用量中提取数值和单位
    $dosage_parts = explode(' ', $record['dosage']);
    $dosage_value = $dosage_parts[0] ?? '';
    $dosage_unit_value = $dosage_parts[1] ?? 'mg';

} catch (Exception $e) {
    // 显示错误信息
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading">错误信息</h4>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">SQL查询: ' . htmlspecialchars($sql) . '</p>';
    echo '<p>记录ID: ' . $id . '</p>';
    echo '<p>用户ID: ' . $user_id . '</p>';
    echo '</div>';
    
    // 添加返回按钮
    echo '<div class="text-center mt-3">';
    echo '<a href="medication_records.php" class="btn btn-primary">返回用药记录列表</a>';
    echo '</div>';
    
    include 'includes/footer.php';
    exit;
}

// 处理编辑用药记录请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medication_type = sanitizeInput($_POST['medication_type']);
    $medication_name = sanitizeInput($_POST['medication_name']);
    $dosage = sanitizeInput($_POST['dosage']);
    $dosage_unit = sanitizeInput($_POST['dosage_unit']);
    $frequency = sanitizeInput($_POST['frequency']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? sanitizeInput($_POST['end_date']) : null;
    $notes = sanitizeInput($_POST['notes']);
    $medical_record_id = isset($_POST['medical_record_id']) ? (int)$_POST['medical_record_id'] : null;
    $error = '';
    
    // 验证输入
    if (empty($medication_type)) {
        $error = '请选择用药类型';
    } elseif (empty($medication_name)) {
        $error = '请输入药物名称';
    } elseif (empty($dosage)) {
        $error = '请输入用量';
    } elseif (empty($frequency)) {
        $error = '请输入用药频率';
    } elseif (empty($start_date)) {
        $error = '请选择开始日期';
    } else {
        // 组合用量和单位
        $full_dosage = $dosage . ' ' . $dosage_unit;
        
        // 更新用药记录
        $sql = "UPDATE medication_records SET 
                medication_type = ?,
                medication_name = ?,
                dosage = ?,
                frequency = ?,
                start_date = ?,
                end_date = ?,
                notes = ?,
                medical_record_id = ?
                WHERE id = ? AND member_id IN (SELECT id FROM family_members WHERE user_id = ?)";
        
        try {
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new Exception('准备SQL语句失败: ' . mysqli_error($conn));
            }

            // 绑定参数
            if (!mysqli_stmt_bind_param($stmt, "sssssssiii", 
                $medication_type,
                $medication_name,
                $full_dosage,
                $frequency,
                $start_date,
                $end_date,
                $notes,
                $medical_record_id,
                $id,
                $user_id
            )) {
                throw new Exception('绑定参数失败: ' . mysqli_stmt_error($stmt));
            }

            // 执行更新
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('执行更新失败: ' . mysqli_stmt_error($stmt));
            }

            // 检查是否真的更新了记录
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                displayMessage('用药记录更新成功', 'success');
                redirect('medication_records.php');
            } else {
                throw new Exception('没有记录被更新，可能是记录不存在或无权访问');
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            // 添加调试信息
            error_log("更新用药记录失败 - SQL: " . $sql);
            error_log("参数: " . print_r([
                'medication_type' => $medication_type,
                'medication_name' => $medication_name,
                'dosage' => $full_dosage,
                'frequency' => $frequency,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'notes' => $notes,
                'medical_record_id' => $medical_record_id,
                'id' => $id,
                'user_id' => $user_id
            ], true));
        } finally {
            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 定义用药类型
$medication_types = [
    'prescription' => '处方药',
    'otc' => '非处方药',
    'chinese' => '中药',
    'supplement' => '营养补充剂',
    'vaccine' => '疫苗',
    'topical' => '外用药',
    'inhaler' => '吸入剂',
    'injection' => '注射剂',
    'other' => '其他'
];

// 定义用量单位
$dosage_units = [
    'mg' => '毫克(mg)',
    'ug' => '微克(μg)',
    'iu' => '国际单位(IU)',
    'ml' => '毫升(mL)'
];

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">编辑用药记录</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="edit_medication_record.php?id=<?php echo $id; ?>" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="member_id" class="form-label">家庭成员</label>
                                <select class="form-select" id="member_id" name="member_id" disabled>
                                    <option value="<?php echo $record['member_id']; ?>">
                                        <?php echo $record['member_name']; ?> (<?php echo $record['relationship']; ?>)
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="medication_type" class="form-label">用药类型</label>
                                <select class="form-select" id="medication_type" name="medication_type" required>
                                    <option value="">请选择用药类型</option>
                                    <?php foreach ($medication_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($record['medication_type'] == $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">请选择用药类型</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="medication_name" class="form-label">药物名称</label>
                                <input type="text" class="form-control" id="medication_name" name="medication_name" 
                                       value="<?php echo htmlspecialchars($record['medication_name']); ?>" required>
                                <div class="invalid-feedback">请输入药物名称</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="dosage" class="form-label">用量</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="dosage" name="dosage" 
                                           value="<?php echo htmlspecialchars($dosage_value); ?>" placeholder="例如: 5" required>
                                    <select class="form-select" id="dosage_unit" name="dosage_unit" style="max-width: 150px;">
                                        <?php foreach ($dosage_units as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo ($dosage_unit_value == $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text">请输入具体数值，例如：5</div>
                                <div class="invalid-feedback">请输入用量</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="frequency" class="form-label">用药频率</label>
                                <input type="text" class="form-control" id="frequency" name="frequency" 
                                       value="<?php echo htmlspecialchars($record['frequency']); ?>" 
                                       placeholder="例如: 每日三次，饭后服用" required>
                                <div class="invalid-feedback">请输入用药频率</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">开始日期</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $record['start_date']; ?>" required>
                                <div class="invalid-feedback">请选择开始日期</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">结束日期 <small class="text-muted">(留空表示长期用药)</small></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $record['end_date']; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="medical_record_id" class="form-label">关联病例（可选）</label>
                                <select class="form-select" id="medical_record_id" name="medical_record_id">
                                    <option value="">不关联病例</option>
                                    <?php foreach ($member_medical_records as $mr): ?>
                                    <option value="<?php echo $mr['id']; ?>" <?php echo ($record['medical_record_id'] == $mr['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mr['visit_date'] . ' | ' . mb_substr($mr['diagnosis'],0,20)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">可选，选择后该用药将与该病例关联。</div>
                            </div>
                            
                            <div class="col-12">
                                <label for="notes" class="form-label">备注</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="请输入用药注意事项或其他备注信息"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> 保存修改
                                </button>
                                <a href="medication_records.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> 取消
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
