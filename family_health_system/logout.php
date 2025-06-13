<?php
require_once 'includes/functions.php';

// 清除所有会话变量
$_SESSION = array();

// 销毁会话
session_destroy();

// 重定向到登录页面
redirect('login.php');
?> 