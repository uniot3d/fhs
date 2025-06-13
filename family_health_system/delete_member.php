<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取成员ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    displayMessage('成员ID不能为空', 'error');
    redirect('family_members.php');
}

$member_id = (int)$_GET['id'];

// 验证成员是否属于当前用户
$member = getFamilyMember($member_id, $user_id);

if (!$member) {
    displayMessage('找不到该家庭成员', 'error');
    redirect('family_members.php');
}

// 执行删除操作
$sql = "DELETE FROM family_members WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $member_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    displayMessage('家庭成员删除成功', 'success');
} else {
    displayMessage('删除家庭成员失败: ' . mysqli_error($conn), 'error');
}

redirect('family_members.php');
?> 