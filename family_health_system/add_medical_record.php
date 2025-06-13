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

// 处理添加病历记录请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = (int)$_POST['member_id'];
    $visit_date = sanitizeInput($_POST['visit_date']);
    $hospital = sanitizeInput($_POST['hospital']);
    $doctor = sanitizeInput($_POST['doctor']);
    $diagnosis = sanitizeInput($_POST['diagnosis']);
    $prescription = sanitizeInput($_POST['prescription']);
    $notes = sanitizeInput($_POST['notes']);
    $ct_image = null;
    $attachments = [];
    $error = '';
    
    // 处理CT片上传
    if (isset($_FILES['ct_image']) && $_FILES['ct_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/ct/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['ct_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('ct_', true) . '.' . $ext;
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['ct_image']['tmp_name'], $target_path)) {
            $ct_image = $target_path;
        } else {
            $error = 'CT片上传失败';
        }
    }
    
    // 处理5个附件上传
    for ($i = 1; $i <= 5; $i++) {
        $att = 'attachment'.$i;
        if (isset($_FILES[$att]) && $_FILES[$att]['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES[$att]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','pdf'])) {
                $error = '附件'.$i.'格式不支持';
                break;
            }
            $filename = uniqid('att'.$i.'_', true) . '.' . $ext;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$att]['tmp_name'], $target_path)) {
                $attachments[$i] = $target_path;
            } else {
                $error = '附件'.$i.'上传失败';
                break;
            }
        } else {
            $attachments[$i] = null;
        }
    }
    
    // 验证输入
    if (empty($member_id)) {
        $error = '请选择家庭成员';
    } elseif (empty($visit_date)) {
        $error = '请选择就诊日期';
    } elseif (empty($hospital)) {
        $error = '请输入医院名称';
    } elseif (empty($doctor)) {
        $error = '请输入医生姓名';
    } elseif (empty($diagnosis)) {
        $error = '请输入诊断结果';
    } else {
        // 验证成员是否属于当前用户
        $member = getFamilyMember($member_id, $user_id);
        if (!$member) {
            $error = '所选家庭成员无效';
        } else {
            // 插入病历记录
            $sql = "INSERT INTO medical_records (member_id, visit_date, hospital, doctor, diagnosis, prescription, notes, ct_image, attachment1, attachment2, attachment3, attachment4, attachment5) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issssssssssss", $member_id, $visit_date, $hospital, $doctor, $diagnosis, $prescription, $notes, $ct_image, $attachments[1], $attachments[2], $attachments[3], $attachments[4], $attachments[5]);
            
            if (mysqli_stmt_execute($stmt)) {
                displayMessage('病历记录添加成功', 'success');
                redirect('medical_records.php');
            } else {
                $error = '添加病历记录失败: ' . mysqli_error($conn) . '<br>SQL: ' . $sql;
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
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle"></i> 添加病历记录</h2>
    <a href="medical_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回病历列表
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="add_medical_record.php" class="needs-validation" novalidate enctype="multipart/form-data">
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
                    <label for="visit_date" class="form-label">就诊日期</label>
                    <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                    <div class="invalid-feedback">请选择就诊日期</div>
                </div>
                
                <div class="col-md-6">
                    <label for="hospital" class="form-label">医院</label>
                    <input type="text" class="form-control" id="hospital" name="hospital" required>
                    <div class="invalid-feedback">请输入医院名称</div>
                </div>
                
                <div class="col-md-6">
                    <label for="doctor" class="form-label">医生</label>
                    <input type="text" class="form-control" id="doctor" name="doctor" required>
                    <div class="invalid-feedback">请输入医生姓名</div>
                </div>
                
                <div class="col-12">
                    <label for="diagnosis" class="form-label">诊断结果</label>
                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                    <div class="invalid-feedback">请输入诊断结果</div>
                </div>
                
                <div class="col-12">
                    <label for="prescription" class="form-label">处方</label>
                    <textarea class="form-control" id="prescription" name="prescription" rows="3"></textarea>
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">备注</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="col-12">
                    <label for="ct_image" class="form-label">CT片（可选，支持图片上传）</label>
                    <input type="file" class="form-control" id="ct_image" name="ct_image" accept="image/*">
                </div>
                
                <div class="col-12">
                    <label class="form-label">拍照上传（最多5个，支持图片/PDF）</label>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="file" class="form-control mb-2" name="attachment<?php echo $i; ?>" accept="image/*,.pdf">
                    <?php endfor; ?>
                    <div class="form-text">每个附件最大10MB，支持图片和PDF</div>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">保存病历记录</button>
                    <a href="medical_records.php" class="btn btn-secondary">取消</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 