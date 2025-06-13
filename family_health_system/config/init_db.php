<?php
// 引入数据库配置
require_once 'database.php';

// 创建用户表
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'family_member') NOT NULL DEFAULT 'family_member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// 创建家庭成员表
$sql_family_members = "CREATE TABLE IF NOT EXISTS family_members (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender ENUM('男', '女', '其他') NOT NULL,
    birthday DATE NOT NULL,
    relationship VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// 创建健康记录表
$sql_health_records = "CREATE TABLE IF NOT EXISTS health_records (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id INT(11) NOT NULL,
    record_date DATE NOT NULL,
    height DECIMAL(5,2) DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    blood_pressure VARCHAR(20) DEFAULT NULL,
    blood_sugar DECIMAL(5,2) DEFAULT NULL,
    heart_rate INT(3) DEFAULT NULL,
    temperature DECIMAL(3,1) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
)";

// 创建病历表
$sql_medical_records = "CREATE TABLE IF NOT EXISTS medical_records (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id INT(11) NOT NULL,
    visit_date DATE NOT NULL,
    hospital VARCHAR(100) NOT NULL,
    doctor VARCHAR(100) NOT NULL,
    diagnosis TEXT NOT NULL,
    prescription TEXT,
    notes TEXT,
    ai_result TEXT,   // 新增AI分析结果字段
    ct_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
)";

// 创建用药记录表
$sql_medication_records = "CREATE TABLE IF NOT EXISTS medication_records (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id INT(11) NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
)";

// 创建体检记录表
$sql_checkup_records = "CREATE TABLE IF NOT EXISTS checkup_records (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id INT(11) NOT NULL,
    checkup_date DATE NOT NULL,
    hospital VARCHAR(100) NOT NULL,
    result TEXT NOT NULL,
    ai_result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
)";

// 执行SQL语句
$tables = [
    'users' => $sql_users,
    'family_members' => $sql_family_members,
    'health_records' => $sql_health_records,
    'medical_records' => $sql_medical_records,
    'medication_records' => $sql_medication_records,
    'checkup_records' => $sql_checkup_records
];

$success = true;
foreach ($tables as $table => $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "创建表 $table 失败: " . mysqli_error($conn) . "<br>";
        $success = false;
    }
}

// 创建默认管理员账户
$default_username = "admin";
$default_password = password_hash("admin123", PASSWORD_DEFAULT);
$default_email = "admin@example.com";

$check_admin = "SELECT id FROM users WHERE username = '$default_username'";
$result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($result) == 0) {
    $sql_admin = "INSERT INTO users (username, password, email, role) 
                  VALUES ('$default_username', '$default_password', '$default_email', 'admin')";
    
    if (mysqli_query($conn, $sql_admin)) {
        echo "默认管理员账户创建成功！<br>";
    } else {
        echo "创建默认管理员账户失败: " . mysqli_error($conn) . "<br>";
        $success = false;
    }
}

if ($success) {
    echo "数据库初始化成功！<br>";
} else {
    echo "数据库初始化过程中出现错误！<br>";
}

mysqli_close($conn);
?>