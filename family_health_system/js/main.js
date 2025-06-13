// 等待文档加载完成
document.addEventListener('DOMContentLoaded', function() {
    // 启用所有工具提示
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 日期选择器初始化
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    });
    
    // 表单验证
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // 自动关闭警告消息
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // 确认删除对话框
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('确定要删除此记录吗？此操作无法撤销。')) {
                e.preventDefault();
            }
        });
    });
    
    // 图表初始化（如果有图表容器）
    initCharts();
});

// 初始化图表
function initCharts() {
    // 健康数据趋势图
    const weightChartCanvas = document.getElementById('weightChart');
    if (weightChartCanvas) {
        const ctx = weightChartCanvas.getContext('2d');
        const weightChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weightChartData.labels,
                datasets: [{
                    label: '体重 (kg)',
                    data: weightChartData.data,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            precision: 1
                        }
                    }
                }
            }
        });
    }
    
    // 身高图表
    const heightChartCanvas = document.getElementById('heightChart');
    if (heightChartCanvas) {
        const ctx = heightChartCanvas.getContext('2d');
        const heightChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: heightChartData.labels,
                datasets: [{
                    label: '身高 (cm)',
                    data: heightChartData.data,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            precision: 1
                        }
                    }
                }
            }
        });
    }
    
    // 血压图表
    const bpChartCanvas = document.getElementById('bloodPressureChart');
    if (bpChartCanvas) {
        const ctx = bpChartCanvas.getContext('2d');
        const bpChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: bpChartData.labels,
                datasets: [
                    {
                        label: '收缩压',
                        data: bpChartData.systolic,
                        borderColor: '#dc3545',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: '舒张压',
                        data: bpChartData.diastolic,
                        borderColor: '#17a2b8',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
}

// 切换密码可见性
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.querySelector(`[data-target="${inputId}"] i`);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
} 