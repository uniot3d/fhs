<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取成员ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('成员ID不能为空', 'error');
    redirect('family_members.php');
}

$member_id = (int)$_GET['id'];

// 获取成员信息
$member = getFamilyMember($member_id, $user_id);

if (!$member) {
    displayMessage('找不到该家庭成员', 'error');
    redirect('family_members.php');
}

// 处理编辑请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $gender = sanitizeInput($_POST['gender']);
    $birthday = sanitizeInput($_POST['birthday']);
    $relationship = sanitizeInput($_POST['relationship']);
    $error = '';
    // 验证输入
    if (empty($name)) {
        $error = '请输入姓名';
    } elseif (empty($gender)) {
        $error = '请选择性别';
    } elseif (empty($birthday)) {
        $error = '请选择出生日期';
    } elseif (empty($relationship)) {
        $error = '请输入与您的关系';
    } else {
        // 更新家庭成员
        $sql = "UPDATE family_members SET name = ?, gender = ?, birthday = ?, relationship = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssii", $name, $gender, $birthday, $relationship, $member_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('家庭成员信息更新成功', 'success');
            redirect('family_members.php');
        } else {
            $error = '更新家庭成员信息失败: ' . mysqli_error($conn);
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> 编辑家庭成员</h2>
    <a href="family_members.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回家庭成员列表
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="edit_member.php?id=<?php echo $member_id; ?>" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">姓名</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $member['name']; ?>" required>
                <div class="invalid-feedback">请输入姓名</div>
            </div>
            
            <div class="mb-3">
                <label for="gender" class="form-label">性别</label>
                <select class="form-select" id="gender" name="gender" required>
                    <option value="">请选择性别</option>
                    <option value="男" <?php echo ($member['gender'] == '男') ? 'selected' : ''; ?>>男</option>
                    <option value="女" <?php echo ($member['gender'] == '女') ? 'selected' : ''; ?>>女</option>
                    <option value="其他" <?php echo ($member['gender'] == '其他') ? 'selected' : ''; ?>>其他</option>
                </select>
                <div class="invalid-feedback">请选择性别</div>
            </div>
            
            <div class="mb-3">
                <label for="birthday" class="form-label">出生日期</label>
                <input type="date" class="form-control" id="birthday" name="birthday" value="<?php echo $member['birthday']; ?>" required>
                <div class="invalid-feedback">请选择出生日期</div>
            </div>
            
            <div class="mb-3">
                <label for="relationship" class="form-label">与您的关系</label>
                <select class="form-select" id="relationship" name="relationship" required>
                    <option value="">请选择关系</option>
                    <option value="配偶" <?php echo ($member['relationship'] == '配偶') ? 'selected' : ''; ?>>配偶</option>
                    <option value="父亲" <?php echo ($member['relationship'] == '父亲') ? 'selected' : ''; ?>>父亲</option>
                    <option value="母亲" <?php echo ($member['relationship'] == '母亲') ? 'selected' : ''; ?>>母亲</option>
                    <option value="儿子" <?php echo ($member['relationship'] == '儿子') ? 'selected' : ''; ?>>儿子</option>
                    <option value="女儿" <?php echo ($member['relationship'] == '女儿') ? 'selected' : ''; ?>>女儿</option>
                    <option value="兄弟" <?php echo ($member['relationship'] == '兄弟') ? 'selected' : ''; ?>>兄弟</option>
                    <option value="姐妹" <?php echo ($member['relationship'] == '姐妹') ? 'selected' : ''; ?>>姐妹</option>
                    <option value="祖父" <?php echo ($member['relationship'] == '祖父') ? 'selected' : ''; ?>>祖父</option>
                    <option value="祖母" <?php echo ($member['relationship'] == '祖母') ? 'selected' : ''; ?>>祖母</option>
                    <option value="外祖父" <?php echo ($member['relationship'] == '外祖父') ? 'selected' : ''; ?>>外祖父</option>
                    <option value="外祖母" <?php echo ($member['relationship'] == '外祖母') ? 'selected' : ''; ?>>外祖母</option>
                    <option value="其他" <?php echo ($member['relationship'] == '其他') ? 'selected' : ''; ?>>其他</option>
                </select>
                <div class="invalid-feedback">请选择关系</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">更新信息</button>
                <a href="family_members.php" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 