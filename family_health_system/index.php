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
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-house"></i> 家庭健康管理系统</h2>
    <a href="ai_settings.php" class="btn btn-outline-secondary"><i class="bi bi-gear"></i> 设置</a>
</div>

<div class="row align-items-center">
    <div class="col-lg-6">
        <h1 class="display-4 mb-4">家庭健康管理系统</h1>
        <p class="lead mb-4">管理您和家人的健康记录，追踪身高体重变化，记录病历，管理用药，随时随地通过手机访问。</p>
        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
            <a href="login.php" class="btn btn-primary btn-lg px-4 me-md-2">立即登录</a>
            <a href="register.php" class="btn btn-outline-secondary btn-lg px-4">注册账户</a>
        </div>
    </div>
    <div class="col-lg-6 mt-5 mt-lg-0">
        <div class="card border-0 shadow">
            <div class="card-body p-4">
                <h5 class="card-title">系统功能</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-people-fill text-primary me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-0">家庭成员管理</h6>
                            <small class="text-muted">添加和管理家庭成员信息</small>
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-heart-pulse-fill text-danger me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-0">健康数据记录</h6>
                            <small class="text-muted">记录身高、体重、血压等健康指标</small>
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-file-medical-fill text-success me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-0">病历管理</h6>
                            <small class="text-muted">管理就医记录和诊断结果</small>
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-capsule-pill text-warning me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-0">用药记录</h6>
                            <small class="text-muted">追踪用药情况和提醒按时服药</small>
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-graph-up text-info me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-0">健康趋势分析</h6>
                            <small class="text-muted">通过图表可视化健康数据变化趋势</small>
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-virus2 text-danger me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-0">疾病管理</h6>
                            <small class="text-muted">按时间追踪确诊疾病及治疗过程</small>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-shield-check text-primary fs-1 mb-3"></i>
                <h5 class="card-title">数据安全</h5>
                <p class="card-text">您的健康数据安全至上，所有信息采用安全加密存储。</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-phone text-primary fs-1 mb-3"></i>
                <h5 class="card-title">移动友好</h5>
                <p class="card-text">响应式设计，支持在手机、平板等各种设备上使用。</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-cloud-arrow-up text-primary fs-1 mb-3"></i>
                <h5 class="card-title">云端存储</h5>
                <p class="card-text">数据存储在云端，随时随地访问，不用担心数据丢失。</p>
            </div>
        </div>
    </div>
   
</div>

<?php include 'includes/footer.php'; ?> 