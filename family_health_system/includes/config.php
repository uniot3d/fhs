<?php
// 数据库连接配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'DRsXT5ZJ6Oi55LPQ');
define('DB_NAME', 'family_health_db');

// 创建数据库连接
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 检查连接
if (!$conn) {
    die("数据库连接失败: " . mysqli_connect_error());
}

// 设置字符集
mysqli_set_charset($conn, "utf8mb4");

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 定义网站根目录
define('ROOT_PATH', dirname(__DIR__));

// 定义上传文件目录
define('UPLOAD_PATH', ROOT_PATH . '/attachments');

// 定义允许的文件类型
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// 定义最大文件大小（5MB）
define('MAX_FILE_SIZE', 5 * 1024 * 1024); 