<?php
require_once 'config/database.php';

// 检查表是否存在
$table_name = 'checkup_records';
$result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");

if (mysqli_num_rows($result) == 0) {
    echo "表 $table_name 不存在！正在创建...<br>";
    
    // 创建表
    $sql = "CREATE TABLE IF NOT EXISTS checkup_records (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        member_id INT(11) NOT NULL,
        checkup_date DATE NOT NULL,
        hospital VARCHAR(100) NOT NULL,
        result TEXT NOT NULL,
        ai_result TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (mysqli_query($conn, $sql)) {
        echo "表 $table_name 创建成功！<br>";
    } else {
        echo "创建表失败: " . mysqli_error($conn) . "<br>";
        echo "SQL: " . $sql . "<br>";
        exit;
    }
}

// 显示表结构
echo "<br>表结构：<br>";
$result = mysqli_query($conn, "DESCRIBE $table_name");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>字段名</th><th>类型</th><th>空值</th><th>键</th><th>默认值</th><th>额外</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "获取表结构失败: " . mysqli_error($conn) . "<br>";
}

// 检查表中的记录
echo "<br>表中的记录：<br>";
$result = mysqli_query($conn, "SELECT * FROM $table_name");
if ($result) {
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        // 获取字段名
        $fields = mysqli_fetch_fields($result);
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // 重置结果指针
        mysqli_data_seek($result, 0);
        
        // 显示数据
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "表中没有记录<br>";
    }
} else {
    echo "查询记录失败: " . mysqli_error($conn) . "<br>";
}

// 检查外键约束
echo "<br>外键约束：<br>";
$result = mysqli_query($conn, "
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
        REFERENCED_TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = '$table_name'
        AND REFERENCED_TABLE_NAME IS NOT NULL
");

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>表名</th><th>字段名</th><th>约束名</th><th>引用表</th><th>引用字段</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['TABLE_NAME'] . "</td>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "没有找到外键约束<br>";
    }
} else {
    echo "查询外键约束失败: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?> 