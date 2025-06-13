<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/functions.php';
if (!isLoggedIn()) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];

// 处理添加体检报告
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_checkup'])) {
    $title = sanitizeInput($_POST['title']);
    $checkup_date = sanitizeInput($_POST['checkup_date']);
    $hospital = sanitizeInput($_POST['hospital']);
    $result = sanitizeInput($_POST['result']);
    $notes = sanitizeInput($_POST['notes']);
    $error = '';
    if (empty($title)) {
        $error = '请输入报告标题';
    } elseif (empty($checkup_date)) {
        $error = '请选择体检日期';
    } elseif (empty($hospital)) {
        $error = '请输入体检医院';
    } elseif (empty($result)) {
        $error = '请输入体检结果';
    } else {
        $sql = "INSERT INTO checkup_records (user_id, title, checkup_date, hospital, result, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $title, $checkup_date, $hospital, $result, $notes);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('体检报告添加成功', 'success');
            redirect('checkup_records.php');
        } else {
            $error = '添加体检报告失败: ' . mysqli_error($conn);
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 处理编辑体检报告
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_checkup_id'])) {
    $edit_id = (int)$_POST['edit_checkup_id'];
    $title = sanitizeInput($_POST['edit_title']);
    $checkup_date = sanitizeInput($_POST['edit_checkup_date']);
    $hospital = sanitizeInput($_POST['edit_hospital']);
    $result = sanitizeInput($_POST['edit_result']);
    $notes = sanitizeInput($_POST['edit_notes']);
    $error = '';
    if (empty($title)) {
        $error = '请输入报告标题';
    } elseif (empty($checkup_date)) {
        $error = '请选择体检日期';
    } elseif (empty($hospital)) {
        $error = '请输入体检医院';
    } elseif (empty($result)) {
        $error = '请输入体检结果';
    } else {
        $sql = "UPDATE checkup_records SET title=?, checkup_date=?, hospital=?, result=?, notes=? WHERE id=? AND user_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $title, $checkup_date, $hospital, $result, $notes, $edit_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            displayMessage('体检报告已更新', 'success');
            redirect('checkup_records.php');
        } else {
            $error = '更新体检报告失败: ' . mysqli_error($conn);
        }
    }
    if (!empty($error)) {
        displayMessage($error, 'error');
    }
}

// 处理删除体检报告
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_checkup_id'])) {
    $delete_id = (int)$_POST['delete_checkup_id'];
    $sql = "DELETE FROM checkup_records WHERE id=? AND user_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        displayMessage('体检报告已删除', 'success');
        redirect('checkup_records.php');
    } else {
        displayMessage('删除体检报告失败: ' . mysqli_error($conn), 'error');
    }
}

// 获取体检报告列表
$sql = "SELECT * FROM checkup_records WHERE user_id = ? ORDER BY checkup_date DESC, id DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    $fatal_error = 'SQL错误: ' . mysqli_error($conn);
} else {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $checkup_records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $checkup_records[] = $row;
    }
}

include 'includes/header.php';
?>
<?php if (!empty($fatal_error)): ?>
<div class="alert alert-danger mt-3">致命错误：<?php echo htmlspecialchars($fatal_error); ?></div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-check"></i> 体检管理</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCheckupModal">
        <i class="bi bi-plus-circle"></i> 添加体检报告
    </button>
</div>
<div class="card">
    <div class="card-body">
        <?php if (count($checkup_records) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px; text-align:center;">标题</th>
                        <th style="width: 110px; text-align:center;">日期</th>
                        <th style="width: 120px; text-align:center;">医院</th>
                        <th style="min-width: 300px;">体检结果</th>
                        <th style="width: 80px; text-align:center;">详情</th>
                        <th style="width: 80px; text-align:center;">编辑</th>
                        <th style="width: 80px; text-align:center;">删除</th>
                        <th style="width: 100px; text-align:center;">AI分析</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checkup_records as $rec): ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($rec['title']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($rec['checkup_date']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($rec['hospital']); ?></td>
                        <td style="white-space: pre-line;">
                            <?php 
                            $short_result = mb_substr($rec['result'], 0, 25, 'UTF-8');
                            echo htmlspecialchars($short_result) . (mb_strlen($rec['result'], 'UTF-8') > 25 ? '...' : '');
                            ?>
                        </td>
                        <td class="text-center">
                            <a href="checkup_detail.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> 详情</a>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCheckupModal<?php echo $rec['id']; ?>">
                                <i class="bi bi-pencil"></i> 编辑
                            </button>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="checkup_records.php" class="d-inline" onsubmit="return confirm('确定要删除该体检报告吗？');" style="margin:0;">
                                <input type="hidden" name="delete_checkup_id" value="<?php echo $rec['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> 删除</button>
                            </form>
                        </td>
                        <td class="text-center">
                            <a href="checkup_ai.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-robot"></i> AI分析</a>
                        </td>
                    </tr>
                    <!-- 编辑体检报告模态框 -->
                    <div class="modal fade" id="editCheckupModal<?php echo $rec['id']; ?>" tabindex="-1" aria-labelledby="editCheckupModalLabel<?php echo $rec['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editCheckupModalLabel<?php echo $rec['id']; ?>">编辑体检报告</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="checkup_records.php" class="needs-validation" novalidate>
                                        <input type="hidden" name="edit_checkup_id" value="<?php echo $rec['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">报告标题</label>
                                            <input type="text" class="form-control" name="edit_title" value="<?php echo htmlspecialchars($rec['title']); ?>" required>
                                            <div class="invalid-feedback">请输入报告标题</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">体检日期</label>
                                            <input type="date" class="form-control" name="edit_checkup_date" value="<?php echo htmlspecialchars($rec['checkup_date']); ?>" required>
                                            <div class="invalid-feedback">请选择体检日期</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">体检医院</label>
                                            <input type="text" class="form-control" name="edit_hospital" value="<?php echo htmlspecialchars($rec['hospital']); ?>" required>
                                            <div class="invalid-feedback">请输入体检医院</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">体检结果</label>
                                            <textarea class="form-control" name="edit_result" rows="3" required><?php echo htmlspecialchars($rec['result']); ?></textarea>
                                            <div class="invalid-feedback">请输入体检结果</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">备注</label>
                                            <textarea class="form-control" name="edit_notes" rows="2"><?php echo htmlspecialchars($rec['notes']); ?></textarea>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">保存修改</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-clipboard-check text-muted" style="font-size: 4rem;"></i>
            <h5 class="mt-3">暂无体检报告</h5>
            <p class="text-muted">点击"添加体检报告"按钮开始记录</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- 添加体检报告模态框 -->
<div class="modal fade" id="addCheckupModal" tabindex="-1" aria-labelledby="addCheckupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCheckupModalLabel">添加体检报告</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="checkup_records.php" class="needs-validation" novalidate>
                    <input type="hidden" name="add_checkup" value="1">
                    <div class="mb-3">
                        <label for="title" class="form-label">报告标题</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <div class="invalid-feedback">请输入报告标题</div>
                    </div>
                    <div class="mb-3">
                        <label for="checkup_date" class="form-label">体检日期</label>
                        <input type="date" class="form-control" id="checkup_date" name="checkup_date" required>
                        <div class="invalid-feedback">请选择体检日期</div>
                    </div>
                    <div class="mb-3">
                        <label for="hospital" class="form-label">体检医院</label>
                        <input type="text" class="form-control" id="hospital" name="hospital" required>
                        <div class="invalid-feedback">请输入体检医院</div>
                    </div>
                    <div class="mb-3">
                        <label for="result" class="form-label">体检结果</label>
                        <textarea class="form-control" id="result" name="result" rows="3" required></textarea>
                        <div class="invalid-feedback">请输入体检结果</div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">备注</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?> 