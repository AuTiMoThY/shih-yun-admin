-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-29 03:52:22
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `shih-yun`
--

-- --------------------------------------------------------

--
-- 資料表結構 `sys_permissions`
--

CREATE TABLE `sys_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `name` varchar(255) NOT NULL COMMENT '權限名稱（唯一，格式：module.action 或 module.category.action）',
  `label` varchar(255) NOT NULL COMMENT '權限顯示名稱',
  `description` text DEFAULT NULL COMMENT '權限描述',
  `module_id` int(11) DEFAULT NULL COMMENT '關聯的模組 ID（可選）',
  `category` varchar(50) DEFAULT NULL COMMENT '分類（如：tw, sg, mm）',
  `action` varchar(50) DEFAULT NULL COMMENT '動作（如：view, create, edit, delete, manage）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='權限表';

--
-- 傾印資料表的資料 `sys_permissions`
--

INSERT INTO `sys_permissions` (`id`, `name`, `label`, `description`, `module_id`, `category`, `action`, `status`, `created_at`, `updated_at`) VALUES
(1, 'about.section.create', '關於我們-新增區塊', '新增區塊(卡)', NULL, '', 'create', 1, '2025-12-15 18:53:57', '2025-12-16 19:39:58'),
(2, 'about.section.delete', '關於我們-刪除區塊', '刪除區塊(卡)', NULL, NULL, 'delete', 1, '2025-12-15 18:53:57', '2025-12-15 18:53:57'),
(3, 'about.section.sort', '關於我們-區塊排序', '區塊上移/下移', NULL, NULL, 'sort', 1, '2025-12-15 18:53:57', '2025-12-15 18:53:57'),
(4, 'about.field.create', '關於我們-新增欄位', '新增欄位', NULL, NULL, 'create', 1, '2025-12-15 18:53:57', '2025-12-15 18:53:57'),
(5, 'about.field.delete', '關於我們-刪除欄位', '刪除欄位', NULL, NULL, 'delete', 1, '2025-12-15 18:53:57', '2025-12-15 18:53:57'),
(6, 'about.field.sort', '關於我們-欄位排序', '欄位上移/下移', NULL, NULL, 'sort', 1, '2025-12-15 18:53:57', '2025-12-15 18:53:57'),
(11, 'about.view', '關於我們-單元查看', '', 1, '', '', 1, '2025-12-28 18:48:21', '2025-12-28 18:48:21');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `sys_permissions`
--
ALTER TABLE `sys_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`),
  ADD KEY `idx_module_id` (`module_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sys_permissions`
--
ALTER TABLE `sys_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵', AUTO_INCREMENT=12;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `sys_permissions`
--
ALTER TABLE `sys_permissions`
  ADD CONSTRAINT `fk_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `sys_module` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
