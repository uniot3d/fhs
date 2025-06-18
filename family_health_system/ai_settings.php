<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('你没有权限登录该页面。'); window.location.href='login.php';</script>";
    exit;
}

require_once 'includes/functions.php';

// 读取当前配置
$api_config_file = __DIR__ . '/config/api.php';
$api_key = '';
$api_url = 'https://api.deepseek.com/chat/completions';
$model = 'deepseek-chat';
if (file_exists($api_config_file)) {
    $lines = file($api_config_file);
    foreach ($lines as $line) {
        if (preg_match("/define\('DEEPSEEK_API_KEY',\s*'(.+?)'\)/", $line, $m)) {
            $api_key = $m[1];
        }
        if (preg_match("/define\('DEEPSEEK_API_URL',\s*'(.+?)'\)/", $line, $m)) {
            $api_url = $m[1];
        }
        if (preg_match("/define\('DEEPSEEK_MODEL',\s*'(.+?)'\)/", $line, $m)) {
            $model = $m[1];
        }
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = trim($_POST['api_key'] ?? '');
    $api_url = trim($_POST['api_url'] ?? '');
    $model = trim($_POST['model'] ?? '');
    // 保存到config/api.php
    $content = "<?php\n";
    $content .= "// AI接口配置\n";
    $content .= "define('DEEPSEEK_API_KEY', '" . addslashes($api_key) . "');\n";
    $content .= "define('DEEPSEEK_API_URL', '" . addslashes($api_url) . "');\n";
    $content .= "define('DEEPSEEK_MODEL', '" . addslashes($model) . "');\n";
    $content .= "?>\n";
    file_put_contents($api_config_file, $content);
    displayMessage('AI接口设置已保存', 'success');
    header('Location: ai_settings.php');
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
        <h2 class="mb-0"><i class="bi bi-gear"></i> AI接口设置</h2>
        <a href="https://platform.deepseek.com/sign_in" target="_blank" class="btn btn-warning ms-3"><i class="bi bi-currency-dollar"></i> DeepSeek充值</a>
    </div>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> 返回首页</a>
</div>
<?php if ($msg = getMessage()): ?>
    <div class="alert alert-<?php echo $msg['type'] === 'success' ? 'success' : 'danger'; ?>"> <?php echo htmlspecialchars($msg['message']); ?> </div>
<?php endif; ?>
<div class="card">
    <div class="card-body">
        <form method="POST" action="ai_settings.php">
            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>
                <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($api_key); ?>" required>
            </div>
            <div class="mb-3">
                <label for="api_url" class="form-label">API URL</label>
                <input type="text" class="form-control" id="api_url" name="api_url" value="<?php echo htmlspecialchars($api_url); ?>" required>
            </div>
            <div class="mb-3">
                <label for="model" class="form-label">模型名称</label>
                <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($model); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">保存设置</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?> 