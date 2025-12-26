-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-22
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
-- 資料表結構 `app_progress`
--

CREATE TABLE `app_progress` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `case_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '所屬建案（對應 app_case.id）',
  `title` varchar(255) DEFAULT NULL COMMENT '標題',
  `progress_date` date DEFAULT NULL COMMENT '日期',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態（1=上線,0=下線）',
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '工程進度圖（JSON格式，多圖上傳）' CHECK (json_valid(`images`)),
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工程進度表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `app_progress`
--

ALTER TABLE `app_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_progress_date` (`progress_date`),
  ADD KEY `idx_sort` (`sort`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `app_progress`
--

ALTER TABLE `app_progress`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

