<?php
if (!file_exists(dirname(__DIR__) . '/install.lock')) {
    header('Location: ../install.php');
    exit;
}
session_start();

// 引入数据库配置
require_once dirname(__FILE__) . '/../config/database.php';

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 检查是否为管理员
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// 重定向函数
function redirect($url) {
    header("Location: $url");
    exit;
}

// 获取当前页面文件名
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

// 安全过滤输入数据
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

// 显示提示消息
function displayMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// 获取提示消息
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        
        // 清除消息
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// 格式化日期
function formatDate($date, $format = 'Y-m-d') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

// 计算年龄
function calculateAge($birthdate) {
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthdate));
    return $diff->y;
}

// 获取所有家庭成员
function getAllFamilyMembers($user_id) {
    global $conn;
    $sql = "SELECT * FROM family_members WHERE user_id = ? ORDER BY name";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $members = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
    
    return $members;
}

// 获取单个家庭成员信息
function getFamilyMember($member_id, $user_id) {
    global $conn;
    $sql = "SELECT * FROM family_members WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $member_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// 获取用户信息
function getUserInfo($user_id) {
    global $conn;
    $sql = "SELECT id, username, email, role FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}
?> 