<?php
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 检查是否筛选特定成员
$member_filter = '';
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
if ($member_id > 0) {
    // 验证成员是否属于当前用户
    $member = getFamilyMember($member_id, $user_id);
    if (!$member) {
        displayMessage('找不到该家庭成员', 'error');
        redirect('medical_records.php');
    }
    $member_filter = " AND fm.id = $member_id";
}

// 分页
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($current_page - 1) * $records_per_page;

// 获取记录总数
$sql = "SELECT COUNT(*) as total FROM medical_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ?$member_filter";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// 获取病历记录
$sql = "SELECT mr.*, fm.name as member_name FROM medical_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ?$member_filter
        ORDER BY mr.visit_date DESC
        LIMIT $offset, $records_per_page";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$medical_records = mysqli_stmt_get_result($stmt);

// 获取所有家庭成员用于筛选
$family_members = getAllFamilyMembers($user_id);
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-medical"></i> 病历管理</h2>
    <a href="add_medical_record.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> 添加病历记录
    </a>
</div>

<!-- 筛选器 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="medical_records.php" class="row g-3">
            <div class="col-md-6">
                <label for="member_id" class="form-label">选择家庭成员</label>
                <select class="form-select" id="member_id" name="member_id">
                    <option value="">所有成员</option>
                    <?php foreach ($family_members as $member): ?>
                    <option value="<?php echo $member['id']; ?>" <?php echo ($member_id == $member['id']) ? 'selected' : ''; ?>>
                        <?php echo $member['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">筛选</button>
                <?php if ($member_id): ?>
                <a href="medical_records.php" class="btn btn-secondary">清除筛选</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 病历记录列表 -->
<div class="card">
    <div class="card-body">
        <?php if (mysqli_num_rows($medical_records) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>家庭成员</th>
                        <th>就诊日期</th>
                        <th>医院</th>
                        <th>医生</th>
                        <th>诊断</th>
                        <th>CT片</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($record = mysqli_fetch_assoc($medical_records)): ?>
                    <tr>
                        <td><?php echo $record['member_name']; ?></td>
                        <td><?php echo formatDate($record['visit_date']); ?></td>
                        <td><?php echo $record['hospital']; ?></td>
                        <td><?php echo $record['doctor']; ?></td>
                        <td><?php echo mb_substr($record['diagnosis'], 0, 30) . (mb_strlen($record['diagnosis']) > 30 ? '...' : ''); ?></td>
                        <td>
                            <?php if (!empty($record['ct_image'])): ?>
                                <a href="<?php echo $record['ct_image']; ?>" target="_blank">
                                    <img src="<?php echo $record['ct_image']; ?>" alt="CT片" style="max-width:60px;max-height:60px;object-fit:cover;">
                                </a>
                            <?php else: ?>
                                <span class="text-muted">无</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit_medical_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_medical_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger delete-btn">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <a href="analyze_medical_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-success ms-1">
                                    <i class="bi bi-robot"></i> AI分析
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo $member_id ? '&member_id=' . $member_id : ''; ?>">上一页</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $member_id ? '&member_id=' . $member_id : ''; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo $member_id ? '&member_id=' . $member_id : ''; ?>">下一页</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-file-medical text-muted" style="font-size: 4rem;"></i>
            <h5 class="mt-3">暂无病历记录</h5>
            <p class="text-muted">点击"添加病历记录"按钮开始记录病历信息</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 