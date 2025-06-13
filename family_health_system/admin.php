<?php
require_once 'includes/functions.php';

// 仅管理员可访问
if (!isLoggedIn() || !isAdmin()) {
    displayMessage('无权限访问该页面', 'error');
    redirect('dashboard.php');
}

// 获取所有用户
$sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

// 处理重置密码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_user_id'])) {
    $reset_user_id = (int)$_POST['reset_user_id'];
    $new_password = '123456';
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $new_password_hash, $reset_user_id);
    if (mysqli_stmt_execute($stmt)) {
        displayMessage('用户ID ' . $reset_user_id . ' 密码已重置为 123456', 'success');
        redirect('admin.php');
    } else {
        displayMessage('重置密码失败: ' . mysqli_error($conn), 'error');
    }
}

// 处理删除用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_user_id = (int)$_POST['delete_user_id'];
    if ($delete_user_id == $_SESSION['user_id']) {
        displayMessage('不能删除当前登录账户', 'error');
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $delete_user_id);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('用户ID ' . $delete_user_id . ' 已被删除', 'success');
            redirect('admin.php');
        } else {
            displayMessage('删除用户失败: ' . mysqli_error($conn), 'error');
        }
    }
}

// 处理新增用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'family_member';
    $password = $_POST['password'];
    $error = '';
    if (empty($username) || empty($email) || empty($password)) {
        $error = '用户名、邮箱和密码不能为空';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $error = '用户名或邮箱已存在';
        }
    }
    if (empty($error)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $password_hash, $role);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('用户添加成功', 'success');
            redirect('admin.php');
        } else {
            displayMessage('添加用户失败: ' . mysqli_error($conn), 'error');
        }
    } else {
        displayMessage($error, 'error');
    }
}

// 处理编辑用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $edit_user_id = (int)$_POST['edit_user_id'];
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $role = $_POST['edit_role'] === 'admin' ? 'admin' : 'family_member';
    $error = '';
    if (empty($username) || empty($email)) {
        $error = '用户名和邮箱不能为空';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        $sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $edit_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $error = '用户名或邮箱已存在';
        }
    }
    if (empty($error)) {
        $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $role, $edit_user_id);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('用户信息已更新', 'success');
            redirect('admin.php');
        } else {
            displayMessage('更新用户失败: ' . mysqli_error($conn), 'error');
        }
    } else {
        displayMessage($error, 'error');
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-gear"></i> 管理员面板</h2>
    <a href="dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回仪表盘
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">用户管理</h5>
    </div>
    <div class="card-body">
        <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['role'] == 'admin' ? '<span class="badge bg-danger">管理员</span>' : '<span class="badge bg-secondary">家庭成员</span>'; ?></td>
                        <td><?php echo formatDate($user['created_at'], 'Y-m-d H:i'); ?></td>
                        <td>
                            <form method="POST" action="admin.php" class="d-inline">
                                <input type="hidden" name="reset_user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('确定重置该用户密码为123456？')">
                                    <i class="bi bi-key"></i> 重置密码
                                </button>
                            </form>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="admin.php" class="d-inline ms-2">
                                <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除该用户？此操作不可恢复！')">
                                    <i class="bi bi-trash"></i> 删除
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                <i class="bi bi-pencil"></i> 编辑
                            </button>
                            <!-- 编辑用户模态框 -->
                            <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <form method="POST" action="admin.php">
                                    <div class="modal-header">
                                      <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">编辑用户</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                      <input type="hidden" name="edit_user_id" value="<?php echo $user['id']; ?>">
                                      <div class="mb-3">
                                        <label class="form-label">用户名</label>
                                        <input type="text" class="form-control" name="edit_username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">邮箱</label>
                                        <input type="email" class="form-control" name="edit_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">角色</label>
                                        <select class="form-select" name="edit_role">
                                          <option value="family_member" <?php echo $user['role'] == 'family_member' ? 'selected' : ''; ?>>家庭成员</option>
                                          <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>管理员</option>
                                        </select>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                      <button type="submit" class="btn btn-primary">保存更改</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">暂无用户</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">添加新用户</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="admin.php" class="row g-3">
            <input type="hidden" name="add_user" value="1">
            <div class="col-md-3">
                <input type="text" class="form-control" name="username" placeholder="用户名" required>
            </div>
            <div class="col-md-3">
                <input type="email" class="form-control" name="email" placeholder="邮箱" required>
            </div>
            <div class="col-md-3">
                <input type="password" class="form-control" name="password" placeholder="密码" required>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="role">
                    <option value="family_member">家庭成员</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-success w-100">添加</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 