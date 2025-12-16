-- ============================================
-- sysadmin 表遷移腳本（可選）
-- 將 permission_name 改為可選（允許 NULL）
-- ============================================
-- 
-- 注意：此腳本為可選，建議先閱讀 docs/sysadmin-migration-guide.md
-- 
-- 如果選擇保留 permission_name 作為備用欄位（推薦），則不需要執行此腳本
-- 如果選擇將 permission_name 改為可選，請執行此腳本
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 將 permission_name 改為可選（允許 NULL）
-- 並更新註解說明此欄位已棄用
ALTER TABLE `sysadmin` 
MODIFY COLUMN `permission_name` varchar(50) NULL COMMENT '權限名稱（已棄用，請使用 RBAC 系統的 user_roles 和 user_permissions）';

-- 可選：將現有空字串改為 NULL
UPDATE `sysadmin` 
SET `permission_name` = NULL 
WHERE `permission_name` = '' OR `permission_name` IS NULL;

COMMIT;

-- ============================================
-- 執行此腳本後，需要修改以下檔案：
-- 
-- 1. api/app/Controllers/AdminsController.php
--    - 將 'permission_name' => 'required' 改為 'permit_empty'
-- 
-- 2. admin/app/components/Admins/Addadmin.vue
--    - 將 permission_name 欄位改為可選（移除 required）
-- 
-- 3. admin/app/components/Admins/Editadmin.vue
--    - 將 permission_name 欄位改為可選（移除 required）
-- ============================================
