<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家庭健康管理系统</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="index.php">家庭健康管理系统</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <?php if (isLoggedIn()): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> 仪表盘
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'family_members.php') ? 'active' : ''; ?>" href="family_members.php">
                                <i class="bi bi-people"></i> 家庭成员
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'checkup_records.php') ? 'active' : ''; ?>" href="checkup_records.php">
                                <i class="bi bi-clipboard-check"></i> 体检管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'health_records.php') ? 'active' : ''; ?>" href="health_records.php">
                                <i class="bi bi-heart-pulse"></i> 健康记录
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'medical_records.php') ? 'active' : ''; ?>" href="medical_records.php">
                                <i class="bi bi-file-medical"></i> 病历管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'medication_records.php') ? 'active' : ''; ?>" href="medication_records.php">
                                <i class="bi bi-capsule"></i> 用药记录
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'ai_settings.php') ? 'active' : ''; ?>" href="ai_settings.php">
                                <i class="bi bi-gear"></i> 设置
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> 个人资料</a></li>
                                <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear"></i> 管理员面板</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> 退出登录</a></li>
                            </ul>
                        </li>
                    </ul>
                    <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'login.php') ? 'active' : ''; ?>" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> 登录
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (getCurrentPage() == 'register.php') ? 'active' : ''; ?>" href="register.php">
                                <i class="bi bi-person-plus"></i> 注册
                            </a>
                        </li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php
        $message = getMessage();
        if ($message): 
        ?>
        <div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $message['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?> 