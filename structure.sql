-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-10 09:07:57
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
-- 資料表結構 `structure`
--

CREATE TABLE `structure` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '父層級 ID（NULL 表示第一層）',
  `name` varchar(100) NOT NULL COMMENT '層級名稱',
  `is_show_frontend` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示前台：1=顯示,0=不顯示',
  `is_show_backend` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示後台：1=顯示,0=不顯示',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序（數字越小越前面）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系統架構層級表';

--
-- 傾印資料表的資料 `structure`
--

INSERT INTO `structure` (`id`, `parent_id`, `name`, `is_show_frontend`, `is_show_backend`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, NULL, '單元1', 1, 1, 1, 0, '2025-12-09 21:28:24', '2025-12-10 00:07:38'),
(2, 1, '1-1', 1, 1, 1, 0, '2025-12-09 22:41:27', '2025-12-09 23:47:38'),
(3, 2, '1-1-1', 1, 1, 1, 0, '2025-12-09 22:41:35', '2025-12-09 23:41:53'),
(4, 1, '1-2', 1, 1, 1, 1, '2025-12-09 22:47:04', '2025-12-09 23:47:38'),
(5, NULL, ' 單元2', 1, 1, 1, 2, '2025-12-09 22:53:52', '2025-12-10 00:07:38'),
(6, NULL, '單元3', 1, 1, 1, 3, '2025-12-09 22:55:55', '2025-12-10 00:07:38'),
(7, NULL, '單元4', 1, 1, 1, 1, '2025-12-09 22:57:02', '2025-12-10 00:07:38'),
(8, 2, '1-1-2', 1, 1, 1, 0, '2025-12-09 22:57:40', '2025-12-09 23:44:00'),
(9, 4, '1-2-1', 1, 1, 1, 0, '2025-12-09 23:03:21', '2025-12-09 23:03:21');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `structure`
--
ALTER TABLE `structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `structure`
--
ALTER TABLE `structure`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵', AUTO_INCREMENT=10;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `structure`
--
ALTER TABLE `structure`
  ADD CONSTRAINT `fk_structure_parent` FOREIGN KEY (`parent_id`) REFERENCES `structure` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
