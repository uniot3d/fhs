<?php
// 自动加载数据库配置
$config = require __DIR__ . '/config.php';

// 尝试连接数据库
$conn = mysqli_connect($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// 检查连接
if (!$conn) {
    die("连接失败: " . mysqli_connect_error());
}

// 设置字符集
mysqli_set_charset($conn, "utf8mb4");

// 创建数据库（如果不存在）
$sql = "CREATE DATABASE IF NOT EXISTS " . $config['db_name'];
if (mysqli_query($conn, $sql)) {
    // 选择数据库
    mysqli_select_db($conn, $config['db_name']);
} else {
    die("创建数据库失败: " . mysqli_error($conn));
}
?> 