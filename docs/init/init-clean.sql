-- ============================================
-- 乾淨的初始化 SQL 文件
-- 用途：建立所有系統表結構，不含任何測試資料
-- 適用於：需要完全乾淨環境的專案
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================
-- 1. 系統架構表
-- ============================================

CREATE TABLE IF NOT EXISTS `sys_structure` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '父層級 ID（NULL 表示第一層）',
  `label` varchar(100) NOT NULL COMMENT '層級名稱',
  `module_id` int(11) DEFAULT NULL COMMENT '模組 id',
  `url` varchar(255) DEFAULT NULL COMMENT '自訂 URL（可選，如果為空則使用模組的 name）',
  `is_show_frontend` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示前台：1=顯示,0=不顯示',
  `is_show_backend` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示後台：1=顯示,0=不顯示',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序（數字越小越前面）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_structure_parent` FOREIGN KEY (`parent_id`) REFERENCES `sys_structure` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系統架構層級表';

-- ============================================
-- 2. 模組表
-- ============================================

CREATE TABLE IF NOT EXISTS `sys_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL COMMENT '模組名稱',
  `name` varchar(100) NOT NULL COMMENT '模組代碼',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 3. 管理員表
-- ============================================

CREATE TABLE IF NOT EXISTS `sys_admin` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `permission_name` varchar(50) DEFAULT NULL COMMENT '權限名稱',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `username` varchar(100) NOT NULL COMMENT '帳號（唯一）',
  `password_hash` varchar(255) NOT NULL COMMENT '密碼雜湊值',
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `phone` varchar(50) DEFAULT NULL COMMENT '電話',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 4. RBAC 系統表
-- ============================================

-- 角色表
CREATE TABLE IF NOT EXISTS `sys_roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `name` varchar(100) NOT NULL COMMENT '角色名稱（唯一）',
  `label` varchar(255) NOT NULL COMMENT '角色顯示名稱',
  `description` text DEFAULT NULL COMMENT '角色描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='角色表';

-- 權限表
CREATE TABLE IF NOT EXISTS `sys_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `name` varchar(255) NOT NULL COMMENT '權限名稱（唯一，格式：module.action 或 module.category.action）',
  `label` varchar(255) NOT NULL COMMENT '權限顯示名稱',
  `description` text DEFAULT NULL COMMENT '權限描述',
  `module_id` int(11) DEFAULT NULL COMMENT '關聯的模組 ID（可選）',
  `category` varchar(50) DEFAULT NULL COMMENT '分類（如：tw, sg, mm）',
  `action` varchar(50) DEFAULT NULL COMMENT '動作（如：view, create, edit, delete, manage）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序（數字越小越前面）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `idx_module_id` (`module_id`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `sys_module` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='權限表';

-- 角色權限關聯表
CREATE TABLE IF NOT EXISTS `sys_role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色 ID',
  `permission_id` bigint(20) UNSIGNED NOT NULL COMMENT '權限 ID',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission_id` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='角色權限關聯表';

-- 使用者角色關聯表
CREATE TABLE IF NOT EXISTS `sys_user_roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '使用者 ID（sys_admin.id）',
  `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色 ID',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role_id` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `sys_admin` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='使用者角色關聯表';

-- 使用者權限關聯表（直接授予或撤銷）
CREATE TABLE IF NOT EXISTS `sys_user_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '使用者 ID（sys_admin.id）',
  `permission_id` bigint(20) UNSIGNED NOT NULL COMMENT '權限 ID',
  `is_granted` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否授予：1=授予,0=撤銷',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission` (`user_id`,`permission_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_permission_id` (`permission_id`),
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `sys_admin` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='使用者權限關聯表（直接授予或撤銷）';

-- ============================================
-- 預設資料：建立超級管理員角色
-- ============================================

INSERT INTO `sys_roles` (`name`, `label`, `description`, `status`, `created_at`, `updated_at`) VALUES
('super_admin', '超級管理員', '擁有所有權限的超級管理員角色', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name`=`name`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

