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

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $error = '';
    
    // 验证输入
    if (empty($username)) {
        $error = '请输入用户名';
    } elseif (empty($password)) {
        $error = '请输入密码';
    } else {
        // 查询用户
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // 验证密码
            if (password_verify($password, $user['password'])) {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 重定向到仪表盘
                redirect('dashboard.php');
            } else {
                $error = '密码不正确';
            }
        } else {
            $error = '用户名不存在';
        }
    }
    
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="auth-form">
    <h2 class="form-title">登录</h2>
    
    <form method="POST" action="login.php" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label">用户名</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback">请输入用户名</div>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label">密码</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password')" data-target="password">
                    <i class="bi bi-eye"></i>
                </button>
                <div class="invalid-feedback">请输入密码</div>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">登录</button>
        </div>
    </form>
    
    <hr class="my-4">
    
    <p class="text-center mb-0">
        还没有账户？ <a href="register.php">立即注册</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?> 