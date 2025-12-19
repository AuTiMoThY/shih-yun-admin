-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-19 09:20:28
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
-- 資料表結構 `sys_structure`
--

CREATE TABLE `sys_structure` (
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系統架構層級表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `sys_structure`
--
ALTER TABLE `sys_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sys_structure`
--
ALTER TABLE `sys_structure`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵';

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `sys_structure`
--
ALTER TABLE `sys_structure`
  ADD CONSTRAINT `fk_structure_parent` FOREIGN KEY (`parent_id`) REFERENCES `sys_structure` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
