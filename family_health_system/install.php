<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// 安装锁检测
if (file_exists(__DIR__ . '/install.lock')) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host']);
    $db_user = trim($_POST['db_user']);
    $db_pass = isset($_POST['db_pass']) ? trim($_POST['db_pass']) : '';
    $db_name = trim($_POST['db_name']);

    if ($db_pass === '' || ctype_space($db_pass)) {
        $db_pass = '';
    }

    // 1. 连接数据库
    $conn = @new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        var_dump('连接数据库失败', $conn->connect_error); exit;
    }

    // 2. 创建数据库
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        var_dump('创建数据库失败', $conn->error); exit;
    }

    // 3. 选择数据库
    if (!$conn->select_db($db_name)) {
        var_dump('选择数据库失败', $conn->error); exit;
    }

    // 4. 导入表结构
    $sql = file_get_contents(__DIR__ . '/db_init.sql');
    if (!$conn->multi_query($sql)) {
        var_dump('数据库初始化失败', $conn->error); exit;
    }

    // 5. 写入配置文件
    $config = "<?php\nreturn [\n    'db_host' => '" . addslashes($db_host) . "',\n    'db_user' => '" . addslashes($db_user) . "',\n    'db_pass' => '" . addslashes($db_pass) . "',\n    'db_name' => '" . addslashes($db_name) . "',\n];\n";
    if (!file_put_contents(__DIR__ . '/config/config.php', $config)) {
        var_dump('写入config.php失败'); exit;
    }
    if (!file_put_contents(__DIR__ . '/install.lock', 'installed')) {
        var_dump('写入install.lock失败'); exit;
    }

    var_dump('全部成功'); exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>家庭健康管理系统 - 安装向导</title>
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">家庭健康管理系统 - 安装向导</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">数据库主机</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库用户名</label>
                            <input type="text" name="db_user" class="form-control" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库密码 <span class="text-muted">(可留空)</span></label>
                            <input type="password" name="db_pass" class="form-control" autocomplete="off" placeholder="可留空">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库名称</label>
                            <input type="text" name="db_name" class="form-control" value="family_health_db" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">安装系统</button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3 text-muted">&copy; <?php echo date('Y'); ?> 家庭健康管理系统</div>
        </div>
    </div>
</div>
<div class="button-row">
  <button class="main-btn health-btn">健康分析</button>
  <button class="main-btn med-btn">用药分析</button>
</div>
<div class="button-row">
  <button class="main-btn add-btn">添加家庭成员</button>
</div>
<style>
.button-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px; /* 按钮间距 */
  margin: 18px 0;
}

.main-btn {
  width: 90vw;
  max-width: 350px;
  height: 48px;
  border: none;
  border-radius: 24px;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 500;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: background 0.2s;
}

.health-btn {
  background: #4caf50;
  color: #fff;
}

.med-btn {
  background: #03a9f4;
  color: #fff;
}

.add-btn {
  background: #1976d2;
  color: #fff;
}

.main-btn:active {
  opacity: 0.85;
}

.button-row {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 10px;
}

.main-btn {
  flex: 1 1 40vw;
  min-width: 120px;
  max-width: 180px;
  height: 44px;
  border-radius: 22px;
  font-size: 16px;
}
</style>
</body>
</html>
