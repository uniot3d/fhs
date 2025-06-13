<?php
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

require_once 'includes/functions.php';

// 如果用户已登录，则重定向到仪表盘
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitizeInput($_POST['email']);
    $error = '';
    
    // 验证输入
    if (empty($username)) {
        $error = '请输入用户名';
    } elseif (empty($password)) {
        $error = '请输入密码';
    } elseif ($password != $confirm_password) {
        $error = '两次输入的密码不一致';
    } elseif (empty($email)) {
        $error = '请输入邮箱';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        // 检查用户名是否已存在
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = '用户名已存在，请选择其他用户名';
        } else {
            // 检查邮箱是否已存在
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = '邮箱已存在，请使用其他邮箱';
            } else {
                // 加密密码
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // 插入新用户
                $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'family_member')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $email);
                
                if (mysqli_stmt_execute($stmt)) {
                    // 注册成功
                    displayMessage('注册成功，请登录', 'success');
                    redirect('login.php');
                } else {
                    $error = '注册失败，请重试: ' . mysqli_error($conn);
                }
            }
        }
    }
    
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="auth-form">
    <h2 class="form-title">注册账户</h2>
    
    <form method="POST" action="register.php" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label">用户名</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback">请输入用户名</div>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">邮箱</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="invalid-feedback">请输入有效的邮箱地址</div>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label">密码</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password')" data-target="password">
                    <i class="bi bi-eye"></i>
                </button>
                <div class="invalid-feedback">密码长度至少为6个字符</div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="confirm_password" class="form-label">确认密码</label>
            <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password')" data-target="confirm_password">
                    <i class="bi bi-eye"></i>
                </button>
                <div class="invalid-feedback">请确认密码</div>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">注册</button>
        </div>
    </form>
    
    <hr class="my-4">
    
    <p class="text-center mb-0">
        已有账户？ <a href="login.php">立即登录</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?> 