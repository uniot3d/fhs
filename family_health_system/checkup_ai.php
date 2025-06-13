<?php
require_once 'includes/functions.php';
require_once 'config/api.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 检查数据库连接
if (!isset($conn) || !$conn) {
    die("数据库连接失败: " . mysqli_connect_error());
}

if (!isLoggedIn()) {
    redirect('login.php');
}

$deepseek_api_key = defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '';
$deepseek_api_url = defined('DEEPSEEK_API_URL') ? DEEPSEEK_API_URL : '';
$model = defined('DEEPSEEK_MODEL') ? DEEPSEEK_MODEL : '';

$error = '';
$ai_result = '';
$record = null;
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取体检报告详情
if (!$record_id) {
    $error = '体检报告ID不能为空';
} else {
    $sql = "SELECT * FROM checkup_records WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $record_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) != 1) {
            $error = '找不到该体检报告';
        } else {
            $record = mysqli_fetch_assoc($result);
            $ai_result = isset($record['ai_result']) ? $record['ai_result'] : '';
        }
    } else {
        $error = 'SQL错误: ' . mysqli_error($conn);
    }
}

// 处理AJAX分析请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze']) && $record) {
    header('Content-Type: application/json');
    if (empty($deepseek_api_key)) {
        echo json_encode(['error' => '请先在 config/api.php 文件中填写DeepSeek API Key']);
        exit;
    }
    $user_prompt = "请对以下体检报告内容进行医学分析，给出健康风险提示和后续建议，内容简明易懂：\n" . $record['result'];
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => '你是专业的医学AI助手，善于分析体检报告并给出健康建议。'],
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

// 处理保存AI分析结果的请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_result']) && $record) {
    header('Content-Type: application/json');
    $ai_result = isset($_POST['ai_result']) ? trim($_POST['ai_result']) : '';
    if ($ai_result === '') {
        echo json_encode(['error' => 'AI分析结果不能为空']);
        exit;
    }
    
    // 检查记录是否存在
    $check_sql = "SELECT id FROM checkup_records WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    if (!$check_stmt) {
        echo json_encode([
            'error' => '检查记录失败: ' . mysqli_error($conn),
            'sql' => $check_sql
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $record_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        echo json_encode([
            'error' => '要更新的记录不存在',
            'record_id' => $record_id
        ]);
        exit;
    }
    
    // 执行更新
    $sql = "UPDATE checkup_records SET ai_result=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode([
            'error' => 'SQL错误: ' . mysqli_error($conn),
            'sql' => $sql,
            'details' => [
                'error_code' => mysqli_errno($conn),
                'error_message' => mysqli_error($conn),
                'sql_state' => mysqli_sqlstate($conn)
            ]
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "si", $ai_result, $record_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'error' => '保存失败: ' . mysqli_error($conn),
            'sql' => $sql,
            'details' => [
                'error_code' => mysqli_errno($conn),
                'error_message' => mysqli_error($conn),
                'sql_state' => mysqli_sqlstate($conn)
            ]
        ]);
    }
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-robot"></i> 体检AI分析</h2>
    <a href="checkup_records.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> 返回体检管理
    </a>
</div>
<?php if ($error): ?>
<div class="alert alert-danger">错误：<?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($record && !$error): ?>
<div class="card mb-4">
    <div class="card-header">体检报告内容</div>
    <div class="card-body">
        <pre style="white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($record['result']); ?></pre>
    </div>
</div>
<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    本分析结果基于 DeepSeek AI，仅供参考，不能替代专业医疗建议。如需专业诊断和治疗，请联系您的医生。
</div>
<div class="card" id="aiCard">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>AI分析结果</span>
        <?php if (!empty($ai_result)): ?>
            <button class="btn btn-sm btn-outline-primary" id="reanalyzeBtn"><i class="bi bi-arrow-repeat"></i> 重新分析</button>
        <?php else: ?>
            <button class="btn btn-sm btn-success" id="analyzeBtn"><i class="bi bi-robot"></i> AI分析</button>
        <?php endif; ?>
    </div>
    <div class="card-body" id="aiResultBox">
        <div id="aiLoading" class="text-center py-4" style="display:none;">
            <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
            <div>AI正在分析中，请耐心等待...</div>
        </div>
        <pre id="aiResultText" style="white-space: pre-wrap; word-break: break-all; <?php echo $ai_result ? '' : 'display:none;'; ?>"><?php echo htmlspecialchars($ai_result); ?></pre>
        <button class="btn btn-primary mt-3" id="saveAiResultBtn" style="display:none;">保存分析结果</button>
        <div id="saveResultMsg" class="mt-2"></div>
    </div>
</div>
<script>
function doAnalyze() {
    document.getElementById('aiLoading').style.display = 'block';
    document.getElementById('aiResultText').style.display = 'none';
    document.getElementById('analyzeBtn') && (document.getElementById('analyzeBtn').disabled = true);
    document.getElementById('reanalyzeBtn') && (document.getElementById('reanalyzeBtn').disabled = true);
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
            return;
        }
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('analyzeBtn') && (document.getElementById('analyzeBtn').disabled = false);
        document.getElementById('reanalyzeBtn') && (document.getElementById('reanalyzeBtn').disabled = false);
        if (data.result) {
            document.getElementById('aiResultText').style.display = 'block';
            document.getElementById('aiResultText').textContent = data.result;
            document.getElementById('saveAiResultBtn').style.display = 'inline-block';
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
        document.getElementById('reanalyzeBtn') && (document.getElementById('reanalyzeBtn').disabled = false);
        document.getElementById('saveAiResultBtn').style.display = 'none';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    var analyzeBtn = document.getElementById('analyzeBtn');
    var reanalyzeBtn = document.getElementById('reanalyzeBtn');
    if (analyzeBtn) {
        analyzeBtn.addEventListener('click', function() {
            doAnalyze();
        });
    }
    if (reanalyzeBtn) {
        reanalyzeBtn.addEventListener('click', function() {
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
                    document.getElementById('saveResultMsg').innerHTML = '保存失败：返回内容不是合法JSON：<br>' + text;
                    saveBtn.disabled = false;
                    return;
                }
                if (data.success) {
                    document.getElementById('saveResultMsg').textContent = '分析结果已成功保存！';
                    saveBtn.disabled = true;
                } else {
                    let errorHtml = '保存失败：' + (data.error || '未知错误');
                    if (data.details) {
                        errorHtml += '<br>详细信息：<br>';
                        errorHtml += '错误代码：' + data.details.error_code + '<br>';
                        errorHtml += '错误信息：' + data.details.error_message + '<br>';
                        errorHtml += 'SQL状态：' + data.details.sql_state + '<br>';
                        errorHtml += 'SQL语句：' + data.sql;
                    }
                    document.getElementById('saveResultMsg').innerHTML = errorHtml;
                    saveBtn.disabled = false;
                }
            })
            .catch(err => {
                document.getElementById('saveResultMsg').innerHTML = '保存失败：' + err.message;
                saveBtn.disabled = false;
            });
        });
    }
});
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?> 