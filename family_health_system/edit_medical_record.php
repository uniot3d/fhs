<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
$sql = "SELECT mr.*, fm.user_id, fm.name as member_name FROM medical_records mr JOIN family_members fm ON mr.member_id = fm.id WHERE mr.id = ?";
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
    displayMessage('无权限操作该病历', 'error');
    redirect('medical_records.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_date = sanitizeInput($_POST['visit_date']);
    $hospital = sanitizeInput($_POST['hospital']);
    $doctor = sanitizeInput($_POST['doctor']);
    $diagnosis = sanitizeInput($_POST['diagnosis']);
    $prescription = sanitizeInput($_POST['prescription']);
    $notes = sanitizeInput($_POST['notes']);
    $ct_image = isset($record['ct_image']) ? $record['ct_image'] : null;
    $error = '';
    // 处理CT片上传
    if (isset($_FILES['ct_image']) && $_FILES['ct_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/images/ct/';
        $web_dir = 'images/ct/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['ct_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('ct_', true) . '.' . $ext;
        $target_path = $upload_dir . $filename;
        $web_path = $web_dir . $filename;
        if (move_uploaded_file($_FILES['ct_image']['tmp_name'], $target_path)) {
            $ct_image = $web_path;
        } else {
            $error = 'CT片上传失败';
        }
    }
    for ($i = 1; $i <= 5; $i++) {
        $att = 'attachment'.$i;
        if (isset($_FILES[$att]) && $_FILES[$att]['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/attachments/';
            $web_dir = 'attachments/';
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
            $web_path = $web_dir . $filename;
            if (move_uploaded_file($_FILES[$att]['tmp_name'], $target_path)) {
                $$att = $web_path;
            } else {
                $error = '附件'.$i.'上传失败';
                break;
            }
        } else {
            $$att = isset($record[$att]) ? $record[$att] : null;
        }
    }
    if (empty($visit_date) || empty($hospital) || empty($doctor) || empty($diagnosis)) {
        $error = '请填写所有必填项';
    }
    if (empty($error)) {
        $sql = "UPDATE medical_records SET visit_date=?, hospital=?, doctor=?, diagnosis=?, prescription=?, notes=?, ct_image=?, attachment1=?, attachment2=?, attachment3=?, attachment4=?, attachment5=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            die('SQL错误: ' . mysqli_error($conn) . '<br>SQL: ' . $sql);
        }
        mysqli_stmt_bind_param($stmt, "ssssssssssssi", $visit_date, $hospital, $doctor, $diagnosis, $prescription, $notes, $ct_image, $attachment1, $attachment2, $attachment3, $attachment4, $attachment5, $record_id);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('病历记录更新成功', 'success');
            redirect('medical_records.php');
        } else {
            $error = '更新失败: ' . mysqli_error($conn) . '<br>SQL: ' . $sql;
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
    // 重新获取最新数据
    $record['visit_date'] = $visit_date;
    $record['hospital'] = $hospital;
    $record['doctor'] = $doctor;
    $record['diagnosis'] = $diagnosis;
    $record['prescription'] = $prescription;
    $record['notes'] = $notes;
    $record['ct_image'] = $ct_image;
    $record['attachment1'] = $attachment1;
    $record['attachment2'] = $attachment2;
    $record['attachment3'] = $attachment3;
    $record['attachment4'] = $attachment4;
    $record['attachment5'] = $attachment5;
}

// 处理CT片删除请求
if (isset($_GET['delete_ct']) && $_GET['delete_ct'] == 1 && !empty($record['ct_image'])) {
    $file_path = __DIR__ . '/' . $record['ct_image'];
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
    $sql = "UPDATE medical_records SET ct_image=NULL WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $record_id);
    mysqli_stmt_execute($stmt);
    displayMessage('CT片已删除', 'success');
    redirect('edit_medical_record.php?id=' . $record_id);
}

// 处理附件删除请求
if (isset($_GET['delete_attachment']) && in_array((int)$_GET['delete_attachment'], [1,2,3,4,5])) {
    $idx = (int)$_GET['delete_attachment'];
    $att = 'attachment'.$idx;
    if (!empty($record[$att])) {
        $file_path = __DIR__ . '/' . $record[$att];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        $sql = "UPDATE medical_records SET $att=NULL WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $record_id);
        mysqli_stmt_execute($stmt);
        displayMessage('附件已删除', 'success');
        redirect('edit_medical_record.php?id=' . $record_id);
    }
}

?>
<?php include 'includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> 编辑病历记录</h2>
    <a href="medical_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回病历列表
    </a>
</div>
<div class="card">
    <div class="card-header">
        <strong>成员：</strong><?php echo htmlspecialchars($record['member_name']); ?>
    </div>
    <div class="card-body">
        <form method="POST" action="edit_medical_record.php?id=<?php echo $record_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="visit_date" class="form-label">就诊日期</label>
                    <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?php echo htmlspecialchars($record['visit_date']); ?>" required>
                    <div class="invalid-feedback">请选择就诊日期</div>
                </div>
                <div class="col-md-6">
                    <label for="hospital" class="form-label">医院</label>
                    <input type="text" class="form-control" id="hospital" name="hospital" value="<?php echo htmlspecialchars($record['hospital']); ?>" required>
                    <div class="invalid-feedback">请输入医院名称</div>
                </div>
                <div class="col-md-6">
                    <label for="doctor" class="form-label">医生</label>
                    <input type="text" class="form-control" id="doctor" name="doctor" value="<?php echo htmlspecialchars($record['doctor']); ?>" required>
                    <div class="invalid-feedback">请输入医生姓名</div>
                </div>
                <div class="col-12">
                    <label for="diagnosis" class="form-label">诊断结果</label>
                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                    <div class="invalid-feedback">请输入诊断结果</div>
                </div>
                <div class="col-12">
                    <label for="prescription" class="form-label">处方</label>
                    <textarea class="form-control" id="prescription" name="prescription" rows="3"><?php echo htmlspecialchars($record['prescription']); ?></textarea>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">备注</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                </div>
                <div class="col-12">
                    <label for="ct_image" class="form-label">CT片（可选，支持图片上传）</label>
                    <?php if (!empty($record['ct_image'])): ?>
                        <div class="mb-2">
                            <a href="<?php echo $record['ct_image']; ?>" target="_blank">
                                <img src="<?php echo $record['ct_image']; ?>" alt="CT片" style="max-width:80px;max-height:80px;object-fit:cover;">
                            </a>
                            <span class="text-muted ms-2">当前已上传CT片</span>
                            <a href="edit_medical_record.php?id=<?php echo $record_id; ?>&delete_ct=1" class="btn btn-sm btn-danger ms-2" onclick="return confirm('确定要删除该CT片吗？');">删除CT片</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="ct_image" name="ct_image" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label">拍照上传（最多5个，支持图片/PDF）</label>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php $att = 'attachment'.$i; ?>
                        <?php if (!empty($record[$att])): ?>
                            <div class="mb-2">
                                <a href="<?php echo $record[$att]; ?>" target="_blank">
                                    <?php if (preg_match('/\\.(jpg|jpeg|png|gif)$/i', $record[$att])): ?>
                                        <img src="<?php echo $record[$att]; ?>" alt="附件<?php echo $i; ?>" style="max-width:80px;max-height:80px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="badge bg-secondary">PDF</span> 查看
                                    <?php endif; ?>
                                </a>
                                <a href="edit_medical_record.php?id=<?php echo $record_id; ?>&delete_attachment=<?php echo $i; ?>" class="btn btn-sm btn-danger ms-2" onclick="return confirm('确定要删除该附件吗？');">删除</a>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control mb-2" name="attachment<?php echo $i; ?>" accept="image/*,.pdf">
                    <?php endfor; ?>
                    <div class="form-text">每个附件最大10MB，支持图片和PDF</div>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">保存修改</button>
                    <a href="medical_records.php" class="btn btn-secondary">取消</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
