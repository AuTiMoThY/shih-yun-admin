-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-19 09:20:12
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
-- 資料表結構 `app_news`
--

CREATE TABLE `app_news` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `structure_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '系統架構 ID',
  `title` varchar(255) DEFAULT NULL COMMENT '標題名稱',
  `cover` varchar(500) DEFAULT NULL COMMENT '代表圖檔',
  `slide` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '輪播圖（JSON格式）' CHECK (json_valid(`slide`)),
  `content` longtext DEFAULT NULL COMMENT '內文',
  `show_date` date DEFAULT NULL COMMENT '日期',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否上線（1=上線,0=下線）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='最新消息表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `app_news`
--
ALTER TABLE `app_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_structure_id` (`structure_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `app_news`
--
ALTER TABLE `app_news`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
