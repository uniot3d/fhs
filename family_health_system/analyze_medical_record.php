<?php
require_once 'includes/functions.php';
require_once 'config/api.php'; // 引入API配置
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isLoggedIn()) {
    redirect('login.php');
}

$deepseek_api_key = defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '';
$deepseek_api_url = defined('DEEPSEEK_API_URL') ? DEEPSEEK_API_URL : '';
$model = defined('DEEPSEEK_MODEL') ? DEEPSEEK_MODEL : '';

$error = '';
$record = null;
$json_data = '';
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取病例详情
if (!$record_id) {
    $error = '病例ID不能为空';
} else {
    $sql = "SELECT mr.*, fm.name as member_name, fm.gender, fm.birthday, fm.relationship FROM medical_records mr JOIN family_members fm ON mr.member_id = fm.id WHERE mr.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $record_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) != 1) {
            $error = '找不到该病例';
        } else {
            $record = mysqli_fetch_assoc($result);
            $json_arr = [
                'member_name' => $record['member_name'],
                'relationship' => $record['relationship'],
                'gender' => $record['gender'],
                'birthday' => $record['birthday'],
                'visit_date' => $record['visit_date'],
                'hospital' => $record['hospital'],
                'doctor' => $record['doctor'],
                'diagnosis' => $record['diagnosis'],
                'prescription' => $record['prescription'],
                'notes' => $record['notes'],
            ];
            $json_data = json_encode($json_arr, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        }
    } else {
        $error = 'SQL错误: ' . mysqli_error($conn);
    }
}

// 处理AJAX分析请求（不再写入数据库）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze']) && $record) {
    header('Content-Type: application/json');
    if (empty($deepseek_api_key)) {
        echo json_encode(['error' => '请先在 config/api.php 文件中填写DeepSeek API Key']);
        exit;
    }
    $user_prompt = "请对以下病例内容进行医学分析，给出诊断要点、风险提示和后续建议，内容尽量简明易懂：\n" . $json_data;
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => '你是专业的医学AI助手，善于分析病例并给出医学建议。'],
            ['role' => 'user', 'content' => $user_prompt]
        ],
        'stream' => false
    ];
    $ch = curl_init($deepseek_api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $deepseek_api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    // 关闭SSL证书校验（仅开发环境用）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_CAINFO, 'D:/UPUPW_AP7.0_64/cacert.pem');
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($curl_error) {
        echo json_encode(['error' => '请求DeepSeek API失败: ' . $curl_error]);
        exit;
    } elseif ($http_code !== 200) {
        echo json_encode(['error' => 'DeepSeek API返回错误，HTTP状态码: ' . $http_code . '\n返回内容: ' . $response]);
        exit;
    } else {
        if (empty($response)) {
            echo json_encode(['error' => 'DeepSeek API无响应或超时']);
            exit;
        }
        $resp_arr = json_decode($response, true);
        if (isset($resp_arr['choices'][0]['message']['content'])) {
            $ai_result = $resp_arr['choices'][0]['message']['content'];
            echo json_encode(['result' => $ai_result]);
            exit;
        } else {
            echo json_encode(['error' => 'DeepSeek API返回内容无法解析: ' . $response]);
            exit;
        }
    }
}

// 新增：处理保存AI分析结果的请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_result']) && $record) {
    header('Content-Type: application/json');
    $ai_result = isset($_POST['ai_result']) ? trim($_POST['ai_result']) : '';
    if ($ai_result === '') {
        echo json_encode(['error' => 'AI分析结果不能为空']);
        exit;
    }
    $sql = "UPDATE medical_records SET ai_result=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode([
            'error' => 'SQL错误: ' . mysqli_error($conn),
            'sql' => $sql
        ]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "si", $ai_result, $record_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'error' => '保存失败: ' . mysqli_error($conn),
            'sql' => $sql
        ]);
    }
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-robot"></i> 病例AI分析</h2>
    <a href="medical_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回病例列表
    </a>
</div>
<?php if ($error): ?>
<div class="alert alert-danger">错误：<?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($record && !$error): ?>
<div class="card mb-4">
    <div class="card-header">病例信息</div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>成员：</strong><?php echo htmlspecialchars($record['member_name']); ?>（<?php echo htmlspecialchars($record['relationship']); ?>）</li>
            <li class="list-group-item"><strong>性别：</strong><?php echo htmlspecialchars($record['gender']); ?></li>
            <li class="list-group-item"><strong>出生日期：</strong><?php echo htmlspecialchars($record['birthday']); ?></li>
            <li class="list-group-item"><strong>就诊日期：</strong><?php echo htmlspecialchars($record['visit_date']); ?></li>
            <li class="list-group-item"><strong>医院：</strong><?php echo htmlspecialchars($record['hospital']); ?></li>
            <li class="list-group-item"><strong>医生：</strong><?php echo htmlspecialchars($record['doctor']); ?></li>
            <li class="list-group-item"><strong>诊断：</strong><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></li>
            <li class="list-group-item"><strong>处方：</strong><?php echo nl2br(htmlspecialchars($record['prescription'])); ?></li>
            <li class="list-group-item"><strong>备注：</strong><?php echo nl2br(htmlspecialchars($record['notes'])); ?></li>
        </ul>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">病例JSON数据</div>
    <div class="card-body">
        <pre style="white-space: pre-wrap; word-break: break-all;" id="jsonDataBox"><?php echo htmlspecialchars($json_data); ?></pre>
    </div>
</div>
<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    本分析结果基于 DeepSeek AI，仅供参考，不能替代专业医疗建议。如需专业诊断和治疗，请联系您的医生。
</div>
<div class="card" id="aiCard">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>AI分析结果</span>
        <?php if (!empty($record['ai_result'])): ?>
            <button class="btn btn-sm btn-outline-primary" id="analyzeBtn"><i class="bi bi-arrow-repeat"></i> AI重新分析</button>
        <?php else: ?>
            <button class="btn btn-sm btn-success" id="analyzeBtn"><i class="bi bi-robot"></i> AI分析</button>
        <?php endif; ?>
    </div>
    <div class="card-body" id="aiResultBox">
        <div id="aiLoading" class="text-center py-4" style="display:none;">
            <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
            <div>AI正在分析中，请耐心等待...</div>
        </div>
        <pre id="aiResultText" style="white-space: pre-wrap; word-break: break-all;<?php echo !empty($record['ai_result']) ? '' : ' display:none;'; ?>"><?php echo htmlspecialchars($record['ai_result'] ?? ''); ?></pre>
        <button class="btn btn-primary mt-3" id="saveAiResultBtn" style="display:none;">保存分析结果</button>
        <div id="saveResultMsg" class="mt-2"></div>
    </div>
</div>
<script>
function doAnalyze() {
    document.getElementById('aiLoading').style.display = 'block';
    document.getElementById('aiResultText').style.display = 'none';
    document.getElementById('analyzeBtn') && (document.getElementById('analyzeBtn').disabled = true);
    document.getElementById('saveAiResultBtn').style.display = 'none';
    document.getElementById('saveResultMsg').textContent = '';
    fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'analyze=1'
    })
    .then(resp => resp.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            document.getElementById('aiLoading').style.display = 'none';
            document.getElementById('aiResultText').style.display = 'block';
            document.getElementById('aiResultText').textContent = 'AI分析失败：返回内容不是合法JSON：' + text;
            document.getElementById('analyzeBtn') && (document.getElementById('analyzeBtn').disabled = false);
            return;
        }
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('analyzeBtn') && (document.getElementById('analyzeBtn').disabled = false);
        if (data.result) {
            document.getElementById('aiResultText').style.display = 'block';
            document.getElementById('aiResultText').textContent = data.result;
            document.getElementById('saveAiResultBtn').style.display = 'inline-block';
            // 切换按钮为"AI重新分析"
            var analyzeBtn = document.getElementById('analyzeBtn');
            if (analyzeBtn) {
                analyzeBtn.className = 'btn btn-sm btn-outline-primary';
                analyzeBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> AI重新分析';
            }
        } else {
            document.getElementById('aiResultText').style.display = 'block';
            document.getElementById('aiResultText').textContent = 'AI分析失败：' + (data.error || '未知错误');
            document.getElementById('saveAiResultBtn').style.display = 'none';
        }
    })
    .catch(err => {
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('aiResultText').style.display = 'block';
        document.getElementById('aiResultText').textContent = 'AI分析失败：' + err.message;
        document.getElementById('analyzeBtn') && (document.getElementById('analyzeBtn').disabled = false);
        document.getElementById('saveAiResultBtn').style.display = 'none';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    var analyzeBtn = document.getElementById('analyzeBtn');
    if (analyzeBtn) {
        analyzeBtn.addEventListener('click', function() {
            doAnalyze();
        });
    }
    var saveBtn = document.getElementById('saveAiResultBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            var aiResult = document.getElementById('aiResultText').textContent;
            saveBtn.disabled = true;
            document.getElementById('saveResultMsg').textContent = '';
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'save_ai_result=1&ai_result=' + encodeURIComponent(aiResult)
            })
            .then(resp => resp.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch(e) {
                    document.getElementById('saveResultMsg').textContent = '保存失败：返回内容不是合法JSON：' + text;
                    saveBtn.disabled = false;
                    return;
                }
                if (data.success) {
                    document.getElementById('saveResultMsg').textContent = '分析结果已成功保存到病例！';
                    saveBtn.disabled = true;
                } else {
                    document.getElementById('saveResultMsg').textContent = '保存失败：' + (data.error || '未知错误');
                    saveBtn.disabled = false;
                }
            })
            .catch(err => {
                document.getElementById('saveResultMsg').textContent = '保存失败：' + err.message;
                saveBtn.disabled = false;
            });
        });
    }
});
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
