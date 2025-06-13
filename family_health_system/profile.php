<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserInfo($user_id);

// 处理资料更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $error = '';
    
    if (empty($username)) {
        $error = '用户名不能为空';
    } elseif (empty($email)) {
        $error = '邮箱不能为空';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        // 检查用户名是否被其他用户占用
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = '用户名已被占用';
        } else {
            // 检查邮箱是否被其他用户占用
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = '邮箱已被占用';
            } else {
                // 更新资料
                $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['username'] = $username;
                    displayMessage('资料更新成功', 'success');
                    redirect('profile.php');
                } else {
                    $error = '资料更新失败: ' . mysqli_error($conn);
                }
            }
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 处理密码修改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $error = '';
    
    // 获取当前密码哈希
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $password_hash = $row['password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = '请填写所有密码字段';
    } elseif (!password_verify($current_password, $password_hash)) {
        $error = '当前密码不正确';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度至少为6位';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } else {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_password_hash, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('密码修改成功', 'success');
            redirect('profile.php');
        } else {
            $error = '密码修改失败: ' . mysqli_error($conn);
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

$user = getUserInfo($user_id); // 重新获取最新信息
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-person"></i> 个人资料</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" class="needs-validation" novalidate>
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        <div class="invalid-feedback">请输入用户名</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">邮箱</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <div class="invalid-feedback">请输入有效的邮箱地址</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">保存资料</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-key"></i> 修改密码</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" class="needs-validation" novalidate>
                    <input type="hidden" name="change_password" value="1">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">当前密码</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="invalid-feedback">请输入当前密码</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">新密码</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <div class="invalid-feedback">新密码长度至少为6位</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">确认新密码</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">请再次输入新密码</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning">修改密码</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 