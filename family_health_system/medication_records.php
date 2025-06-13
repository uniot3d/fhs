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
        redirect('medication_records.php');
    }
    $member_filter = " AND fm.id = $member_id";
}

// 检查是否只显示当前用药
$active_only = isset($_GET['active_only']) && $_GET['active_only'] == '1';
$active_filter = $active_only ? " AND (mr.end_date IS NULL OR mr.end_date >= CURDATE())" : "";

// 分页
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($current_page - 1) * $records_per_page;

// 获取记录总数
$sql = "SELECT COUNT(*) as total FROM medication_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ?$member_filter$active_filter";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// 获取用药记录
$sql = "SELECT mr.*, fm.name as member_name FROM medication_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE fm.user_id = ?$member_filter$active_filter
        ORDER BY mr.start_date DESC
        LIMIT $offset, $records_per_page";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$medication_records = mysqli_stmt_get_result($stmt);

// 获取所有家庭成员用于筛选
$family_members = getAllFamilyMembers($user_id);
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-capsule"></i> 用药记录</h2>
    <a href="add_medication_record.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> 添加用药记录
    </a>
</div>

<!-- 筛选器 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="medication_records.php" class="row g-3" id="filterForm">
            <div class="col-md-4">
                <label for="member_id" class="form-label">选择家庭成员</label>
                <select class="form-select" id="member_id" name="member_id" onchange="this.form.submit()">
                    <option value="">所有成员</option>
                    <?php foreach ($family_members as $member): ?>
                    <option value="<?php echo $member['id']; ?>" <?php echo ($member_id == $member['id']) ? 'selected' : ''; ?>>
                        <?php echo $member['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="active_only" class="form-label">用药状态</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="active_only" name="active_only" value="1" <?php echo $active_only ? 'checked' : ''; ?> onchange="this.form.submit()">
                    <label class="form-check-label" for="active_only">只显示当前用药</label>
                </div>
            </div>
            <?php if ($member_id || $active_only): ?>
            <div class="col-md-4 d-flex align-items-end">
                <a href="medication_records.php" class="btn btn-secondary">清除筛选</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- 用药记录列表 -->
<div class="card">
    <div class="card-body">
        <?php if (mysqli_num_rows($medication_records) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>家庭成员</th>
                        <th>药物名称</th>
                        <th>用量</th>
                        <th>频率</th>
                        <th>开始日期</th>
                        <th>结束日期</th>
                        <th>关联病例</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($record = mysqli_fetch_assoc($medication_records)): ?>
                    <tr>
                        <td><?php echo $record['member_name']; ?></td>
                        <td><?php echo $record['medication_name']; ?></td>
                        <td><?php echo $record['dosage']; ?></td>
                        <td><?php echo $record['frequency']; ?></td>
                        <td><?php echo formatDate($record['start_date']); ?></td>
                        <td>
                            <?php 
                            if ($record['end_date']) {
                                echo formatDate($record['end_date']);
                            } else {
                                echo '<span class="badge bg-info">长期</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($record['medical_record_id'])) {
                                // 查询病例
                                $sql_case = "SELECT visit_date, diagnosis FROM medical_records WHERE id=?";
                                $stmt_case = mysqli_prepare($conn, $sql_case);
                                mysqli_stmt_bind_param($stmt_case, "i", $record['medical_record_id']);
                                mysqli_stmt_execute($stmt_case);
                                $result_case = mysqli_stmt_get_result($stmt_case);
                                if ($row_case = mysqli_fetch_assoc($result_case)) {
                                    echo htmlspecialchars($row_case['visit_date'] . ' | ' . mb_substr($row_case['diagnosis'],0,20));
                                } else {
                                    echo '<span class="text-muted">已删除</span>';
                                }
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (!$record['end_date'] || $record['end_date'] >= date('Y-m-d')) {
                                echo '<span class="badge bg-success">当前用药</span>';
                            } else {
                                echo '<span class="badge bg-secondary">已停用</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="view_medication_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit_medication_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_medication_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger delete-btn">
                                    <i class="bi bi-trash"></i>
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
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo $member_id ? '&member_id=' . $member_id : ''; ?><?php echo $active_only ? '&active_only=1' : ''; ?>">上一页</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $member_id ? '&member_id=' . $member_id : ''; ?><?php echo $active_only ? '&active_only=1' : ''; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo $member_id ? '&member_id=' . $member_id : ''; ?><?php echo $active_only ? '&active_only=1' : ''; ?>">下一页</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-capsule text-muted" style="font-size: 4rem;"></i>
            <h5 class="mt-3">暂无用药记录</h5>
            <p class="text-muted">点击"添加用药记录"按钮开始记录用药信息</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 