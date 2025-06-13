-- 添加用药类型列
ALTER TABLE `medication_records` 
ADD COLUMN `medication_type` varchar(50) NOT NULL COMMENT '用药类型' AFTER `member_id`;

-- 为现有记录设置默认值
UPDATE `medication_records` SET `medication_type` = 'prescription' WHERE `medication_type` = ''; 