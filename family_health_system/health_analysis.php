<?php
require_once 'includes/functions.php';
if (!isLoggedIn()) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];

// 获取所有家庭成员
$family_members = getAllFamilyMembers($user_id);
$selected_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : ($family_members[0]['id'] ?? 0);

// 获取健康记录数据
$labels = [];
$height_data = [];
$weight_data = [];
$blood_sugar_data = [];
$bp_systolic = [];
$bp_diastolic = [];

if ($selected_member_id) {
    $sql = "SELECT record_date, height, weight, blood_pressure, blood_sugar FROM health_records WHERE member_id = ? ORDER BY record_date ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $selected_member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = formatDate($row['record_date'], 'Y-m-d');
        $height_data[] = $row['height'] ? (float)$row['height'] : null;
        $weight_data[] = $row['weight'] ? (float)$row['weight'] : null;
        $blood_sugar_data[] = $row['blood_sugar'] ? (float)$row['blood_sugar'] : null;
        // 血压分离
        if (!empty($row['blood_pressure']) && strpos($row['blood_pressure'], '/') !== false) {
            list($sys, $dia) = explode('/', $row['blood_pressure']);
            $bp_systolic[] = (float)$sys;
            $bp_diastolic[] = (float)$dia;
        } else {
            $bp_systolic[] = null;
            $bp_diastolic[] = null;
        }
    }
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> 健康分析</h2>
    <a href="dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回仪表盘
    </a>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="member_id" class="form-label">选择家庭成员</label>
                <select class="form-select" id="member_id" name="member_id" onchange="this.form.submit()">
                    <?php foreach ($family_members as $member): ?>
                    <option value="<?php echo $member['id']; ?>" <?php echo ($selected_member_id == $member['id']) ? 'selected' : ''; ?>>
                        <?php echo $member['name']; ?> (<?php echo $member['relationship']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-activity"></i> 血压趋势</div>
            <div class="card-body chart-container">
                <canvas id="bpChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-droplet-half"></i> 血糖趋势</div>
            <div class="card-body chart-container">
                <canvas id="sugarChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart-line"></i> 身高趋势</div>
            <div class="card-body chart-container">
                <canvas id="heightChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart"></i> 体重趋势</div>
            <div class="card-body chart-container">
                <canvas id="weightChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartLabels = <?php echo json_encode($labels); ?>;
const heightData = <?php echo json_encode($height_data); ?>;
const weightData = <?php echo json_encode($weight_data); ?>;
const sugarData = <?php echo json_encode($blood_sugar_data); ?>;
const bpSystolic = <?php echo json_encode($bp_systolic); ?>;
const bpDiastolic = <?php echo json_encode($bp_diastolic); ?>;

// 血压
new Chart(document.getElementById('bpChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: '收缩压',
                data: bpSystolic,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.1)',
                borderWidth: 2,
                tension: 0.3,
                spanGaps: true
            },
            {
                label: '舒张压',
                data: bpDiastolic,
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23,162,184,0.1)',
                borderWidth: 2,
                tension: 0.3,
                spanGaps: true
            }
        ]
    },
    options: {responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:false}}}
});
// 血糖
new Chart(document.getElementById('sugarChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: '血糖 (mmol/L)',
            data: sugarData,
            borderColor: '#ff9800',
            backgroundColor: 'rgba(255,152,0,0.1)',
            borderWidth: 2,
            tension: 0.3,
            spanGaps: true
        }]
    },
    options: {responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:false}}}
});
// 身高
new Chart(document.getElementById('heightChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: '身高 (cm)',
            data: heightData,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.1)',
            borderWidth: 2,
            tension: 0.3,
            spanGaps: true
        }]
    },
    options: {responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:false}}}
});
// 体重
new Chart(document.getElementById('weightChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: '体重 (kg)',
            data: weightData,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            borderWidth: 2,
            tension: 0.3,
            spanGaps: true
        }]
    },
    options: {responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:false}}}
});
</script>
<?php include 'includes/footer.php'; ?> 