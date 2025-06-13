<?php
require_once 'includes/functions.php';

if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取所有家庭成员
$sql = "SELECT id, name FROM family_members WHERE user_id = ? ORDER BY name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$family_members = mysqli_stmt_get_result($stmt);

// 获取病历记录（根据选择的成员ID筛选）
$medical_records = null;
$selected_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
if ($selected_member_id > 0) {
    $sql = "SELECT mr.id, mr.visit_date, mr.diagnosis, mr.member_id, fm.name as member_name 
            FROM medical_records mr
            JOIN family_members fm ON mr.member_id = fm.id
            WHERE fm.user_id = ? AND mr.member_id = ?
            ORDER BY mr.visit_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $selected_member_id);
    mysqli_stmt_execute($stmt);
    $medical_records = mysqli_stmt_get_result($stmt);
}

// 获取用药数据
$medication_data = [];
$medication_types = [];
if ($selected_member_id > 0) {
    // 获取该成员的所有用药记录
    $sql = "SELECT 
                mr.medication_name,
                COUNT(*) as usage_count,
                DATE_FORMAT(mr.start_date, '%Y-%m') as month
            FROM medication_records mr
            WHERE mr.member_id = ?
            GROUP BY mr.medication_name, DATE_FORMAT(mr.start_date, '%Y-%m')
            ORDER BY month ASC, usage_count DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $selected_member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (!in_array($row['medication_name'], $medication_types)) {
            $medication_types[] = $row['medication_name'];
        }
        $medication_data[$row['medication_name']][] = [
            'month' => $row['month'],
            'count' => $row['usage_count']
        ];
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> 用药分析</h2>
    <a href="medication_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回用药记录
    </a>
</div>

<!-- 筛选器 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="medication_analysis.php" class="row g-3" id="filterForm">
            <div class="col-md-4">
                <label for="member_id" class="form-label">选择家庭成员</label>
                <select class="form-select" id="member_id" name="member_id" onchange="this.form.submit()">
                    <option value="">请选择家庭成员</option>
                    <?php while ($member = mysqli_fetch_assoc($family_members)): ?>
                    <option value="<?php echo $member['id']; ?>" <?php echo ($selected_member_id == $member['id']) ? 'selected' : ''; ?>>
                        <?php echo $member['name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_member_id > 0): ?>
    <!-- 病历记录列表 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">病历记录</h5>
        </div>
        <div class="card-body">
            <?php if ($medical_records && mysqli_num_rows($medical_records) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>就诊日期</th>
                            <th>诊断</th>
                            <th>关联用药</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = mysqli_fetch_assoc($medical_records)): ?>
                        <tr>
                            <td><?php echo formatDate($record['visit_date']); ?></td>
                            <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                            <td>
                                <?php
                                // 获取该病历关联的用药记录
                                $sql_med = "SELECT medication_name FROM medication_records WHERE medical_record_id = ?";
                                $stmt_med = mysqli_prepare($conn, $sql_med);
                                mysqli_stmt_bind_param($stmt_med, "i", $record['id']);
                                mysqli_stmt_execute($stmt_med);
                                $medications = mysqli_stmt_get_result($stmt_med);
                                $med_names = [];
                                while ($med = mysqli_fetch_assoc($medications)) {
                                    $med_names[] = $med['medication_name'];
                                }
                                echo implode(', ', $med_names) ?: '<span class="text-muted">无</span>';
                                ?>
                            </td>
                            <td>
                                <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> 查看详情
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">暂无病历记录</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- 用药分析图表 -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">用药趋势分析</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" id="toggleChartType">
                    <i class="bi bi-bar-chart"></i> 切换图表类型
                </button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="medicationChart" height="300"></canvas>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        请选择家庭成员以查看用药分析
    </div>
<?php endif; ?>

<!-- 添加Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($selected_member_id > 0 && !empty($medication_types)): ?>
    const ctx = document.getElementById('medicationChart').getContext('2d');
    let chartType = 'bar';
    
    // 准备图表数据
    const datasets = <?php echo json_encode($medication_types); ?>.map((medication, index) => {
        const data = <?php echo json_encode($medication_data); ?>[medication] || [];
        const color = `hsl(${index * 360 / <?php echo count($medication_types); ?>}, 70%, 50%)`;
        
        return {
            label: medication,
            data: data.map(item => item.count),
            backgroundColor: color + '80',
            borderColor: color,
            borderWidth: 1
        };
    });

    const months = [...new Set(Object.values(<?php echo json_encode($medication_data); ?>)
        .flat()
        .map(item => item.month)
    )].sort();

    const chart = new Chart(ctx, {
        type: chartType,
        data: {
            labels: months,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: '用药趋势分析'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '用药次数'
                    },
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return value;
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '月份'
                    }
                }
            },
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    right: 10,
                    bottom: 10,
                    left: 10
                }
            }
        }
    });

    // 切换图表类型
    document.getElementById('toggleChartType').addEventListener('click', function() {
        chartType = chartType === 'bar' ? 'line' : 'bar';
        chart.config.type = chartType;
        chart.update();
    });
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?> 