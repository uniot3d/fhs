-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'family_member') NOT NULL DEFAULT 'family_member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 家庭成员表
CREATE TABLE IF NOT EXISTS family_members (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender ENUM('男', '女', '其他') NOT NULL,
    birthday DATE NOT NULL,
    relationship VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 健康记录表
CREATE TABLE IF NOT EXISTS health_records (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 病历表
CREATE TABLE IF NOT EXISTS medical_records (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id INT(11) NOT NULL,
    visit_date DATE NOT NULL,
    hospital VARCHAR(100) NOT NULL,
    doctor VARCHAR(100) NOT NULL,
    diagnosis TEXT NOT NULL,
    prescription TEXT,
    notes TEXT,
    ct_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用药记录表
CREATE TABLE IF NOT EXISTS medication_records (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 检查记录表
CREATE TABLE `checkup_records` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `checkup_date` DATE NOT NULL,
    `hospital` VARCHAR(255) NOT NULL,
    `result` TEXT NOT NULL,
    `notes` TEXT
);

-- 默认管理员账户（如无则插入）
INSERT IGNORE INTO users (username, password, email, role) VALUES ('admin', '$2y$10$he8llI0FdroWjIwJ1UoA8uM4vm4LLQ31UpEj82YuPoMAlzJ.6ddSi', 'admin@example.com', 'admin'); 