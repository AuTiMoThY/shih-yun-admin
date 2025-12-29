-- 為 sys_permissions 表添加 sort_order 欄位
-- 執行時間：2025-12-29

ALTER TABLE `sys_permissions` 
ADD COLUMN `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序（數字越小越前面）' AFTER `status`;

-- 為現有資料設定初始排序值（使用 id 作為初始排序）
UPDATE `sys_permissions` SET `sort_order` = `id` WHERE `sort_order` = 0;

-- 添加索引以優化排序查詢
ALTER TABLE `sys_permissions` 
ADD KEY `idx_sort_order` (`sort_order`);

