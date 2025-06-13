<?php
require_once 'includes/functions.php';

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

// 处理添加用药记录请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = (int)$_POST['member_id'];
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
    if (empty($member_id)) {
        $error = '请选择家庭成员';
    } elseif (empty($medication_type)) {
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
        // 验证成员是否属于当前用户
        $member = getFamilyMember($member_id, $user_id);
        if (!$member) {
            $error = '所选家庭成员无效';
        } else {
            // 组合用量和单位
            $full_dosage = $dosage . ' ' . $dosage_unit;
            
            // 插入用药记录
            $sql = "INSERT INTO medication_records (member_id, medication_type, medication_name, dosage, frequency, start_date, end_date, notes, medical_record_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssssssi", $member_id, $medication_type, $medication_name, $full_dosage, $frequency, $start_date, $end_date, $notes, $medical_record_id);
            
            if (mysqli_stmt_execute($stmt)) {
                displayMessage('用药记录添加成功', 'success');
                redirect('medication_records.php');
            } else {
                $error = '添加用药记录失败: ' . mysqli_error($conn);
            }
        }
    }
    
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 获取所有家庭成员
$family_members = getAllFamilyMembers($user_id);

// 获取所选成员的病例
$member_medical_records = [];
if ($selected_member_id) {
    $sql = "SELECT id, visit_date, diagnosis FROM medical_records WHERE member_id = ? ORDER BY visit_date DESC, id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $selected_member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $member_medical_records[] = $row;
    }
}

// 如果没有家庭成员，提示先添加家庭成员
if (count($family_members) == 0) {
    displayMessage('请先添加家庭成员', 'info');
    redirect('family_members.php');
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
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle"></i> 添加用药记录</h2>
    <a href="medication_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回用药记录列表
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="add_medication_record.php" class="needs-validation" novalidate>
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
                    <label for="medication_type" class="form-label">用药类型</label>
                    <select class="form-select" id="medication_type" name="medication_type" required>
                        <option value="">请选择用药类型</option>
                        <?php foreach ($medication_types as $value => $label): ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">请选择用药类型</div>
                </div>
                
                <div class="col-md-6">
                    <label for="medication_name" class="form-label">药物名称</label>
                    <input type="text" class="form-control" id="medication_name" name="medication_name" required>
                    <div class="invalid-feedback">请输入药物名称</div>
                </div>
                
                <div class="col-md-6">
                    <label for="dosage" class="form-label">用量</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="dosage" name="dosage" placeholder="例如: 5" required>
                        <select class="form-select" id="dosage_unit" name="dosage_unit" style="max-width: 150px;">
                            <?php foreach ($dosage_units as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-text">请输入具体数值，例如：5</div>
                    <div class="invalid-feedback">请输入用量</div>
                </div>
                
                <div class="col-md-6">
                    <label for="frequency" class="form-label">用药频率</label>
                    <input type="text" class="form-control" id="frequency" name="frequency" placeholder="例如: 每日三次，饭后服用" required>
                    <div class="invalid-feedback">请输入用药频率</div>
                </div>
                
                <div class="col-md-6">
                    <label for="start_date" class="form-label">开始日期</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    <div class="invalid-feedback">请选择开始日期</div>
                </div>
                
                <div class="col-md-6">
                    <label for="end_date" class="form-label">结束日期 <small class="text-muted">(留空表示长期用药)</small></label>
                    <input type="date" class="form-control" id="end_date" name="end_date">
                </div>
                
                <div class="col-md-6">
                    <label for="medical_record_id" class="form-label">关联病例（可选）</label>
                    <select class="form-select" id="medical_record_id" name="medical_record_id">
                        <option value="">不关联病例</option>
                        <?php foreach ($member_medical_records as $mr): ?>
                        <option value="<?php echo $mr['id']; ?>">
                            <?php echo htmlspecialchars($mr['visit_date'] . ' | ' . mb_substr($mr['diagnosis'],0,20)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">可选，选择后该用药将与该病例关联。</div>
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">备注</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="请输入用药注意事项或其他备注信息"></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> 保存用药记录
                    </button>
                    <a href="medication_records.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> 取消
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var memberSelect = document.getElementById('member_id');
    var medicalRecordSelect = document.getElementById('medical_record_id');
    if (memberSelect && medicalRecordSelect) {
        memberSelect.addEventListener('change', function() {
            var memberId = this.value;
            // 清空病例下拉框
            medicalRecordSelect.innerHTML = '<option value="">加载中...</option>';
            if (!memberId) {
                medicalRecordSelect.innerHTML = '<option value="">不关联病例</option>';
                return;
            }
            fetch('get_medical_records.php?member_id=' + memberId)
                .then(resp => resp.json())
                .then(data => {
                    let html = '<option value="">不关联病例</option>';
                    if (data.length > 0) {
                        data.forEach(function(mr) {
                            html += `<option value="${mr.id}">${mr.visit_date} | ${mr.diagnosis.substring(0,20)}</option>`;
                        });
                    }
                    medicalRecordSelect.innerHTML = html;
                })
                .catch(() => {
                    medicalRecordSelect.innerHTML = '<option value="">加载失败</option>';
                });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?> 