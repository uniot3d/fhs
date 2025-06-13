<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 处理添加家庭成员请求
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
        // 插入家庭成员
        $sql = "INSERT INTO family_members (user_id, name, gender, birthday, relationship) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $name, $gender, $birthday, $relationship);
        
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('家庭成员添加成功', 'success');
            redirect('family_members.php');
        } else {
            $error = '添加家庭成员失败: ' . mysqli_error($conn);
        }
    }
    
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 获取所有家庭成员
$sql = "SELECT * FROM family_members WHERE user_id = ? ORDER BY name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$family_members = [];
while ($row = mysqli_fetch_assoc($result)) {
    $family_members[] = $row;
}

?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> 家庭成员管理</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="bi bi-plus-circle"></i> 添加家庭成员
    </button>
</div>

<!-- 家庭成员列表 -->
<div class="card">
    <div class="card-body">
        <?php if (count($family_members) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>姓名</th>
                        <th>性别</th>
                        <th>年龄</th>
                        <th>关系</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($family_members as $member): ?>
                    <tr>
                        <td><?php echo $member['name']; ?></td>
                        <td><?php echo $member['gender']; ?></td>
                        <td><?php echo calculateAge($member['birthday']); ?> 岁</td>
                        <td><?php echo $member['relationship']; ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="view_member.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> 查看详情
                                </a>
                                <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> 编辑
                                </a>
                                <a href="delete_member.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-danger delete-btn">
                                    <i class="bi bi-trash"></i> 删除
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-people-fill text-muted" style="font-size: 4rem;"></i>
            <h5 class="mt-3">暂无家庭成员</h5>
            <p class="text-muted">点击"添加家庭成员"按钮开始添加</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加家庭成员模态框 -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">添加家庭成员</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="family_members.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">姓名</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">请输入姓名</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="gender" class="form-label">性别</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">请选择性别</option>
                            <option value="男">男</option>
                            <option value="女">女</option>
                            <option value="其他">其他</option>
                        </select>
                        <div class="invalid-feedback">请选择性别</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="birthday" class="form-label">出生日期</label>
                        <input type="date" class="form-control" id="birthday" name="birthday" required>
                        <div class="invalid-feedback">请选择出生日期</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="relationship" class="form-label">与您的关系</label>
                        <select class="form-select" id="relationship" name="relationship" required>
                            <option value="">请选择关系</option>
                            <option value="配偶">配偶</option>
                            <option value="父亲">父亲</option>
                            <option value="母亲">母亲</option>
                            <option value="儿子">儿子</option>
                            <option value="女儿">女儿</option>
                            <option value="兄弟">兄弟</option>
                            <option value="姐妹">姐妹</option>
                            <option value="祖父">祖父</option>
                            <option value="祖母">祖母</option>
                            <option value="外祖父">外祖父</option>
                            <option value="外祖母">外祖母</option>
                            <option value="其他">其他</option>
                        </select>
                        <div class="invalid-feedback">请选择关系</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 