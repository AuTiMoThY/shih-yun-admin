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
-- 資料表結構 `app_case`
--

CREATE TABLE `app_case` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `structure_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '系統架構 ID',
  `year` int(4) DEFAULT NULL COMMENT '年份',
  `title` varchar(255) DEFAULT NULL COMMENT '標題',
  `sub_title` varchar(255) DEFAULT NULL COMMENT '副標',
  `s_text` varchar(500) DEFAULT NULL COMMENT '小字',
  `summary` text DEFAULT NULL COMMENT '摘要',
  `zipcode` varchar(10) DEFAULT NULL COMMENT '郵遞區號',
  `city` varchar(100) DEFAULT NULL COMMENT '城市',
  `district` varchar(100) DEFAULT NULL COMMENT '行政區',
  `cover` varchar(500) DEFAULT NULL COMMENT '封面圖',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '內容（JSON格式，可自由增減區塊）' CHECK (json_valid(`content`)),
  `slide` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '輪播圖（JSON格式）' CHECK (json_valid(`slide`)),
  `ca_type` varchar(255) DEFAULT NULL COMMENT '建案規劃',
  `ca_area` varchar(255) DEFAULT NULL COMMENT '座落地點',
  `ca_square` varchar(255) DEFAULT NULL COMMENT '坪數規劃',
  `ca_layout` varchar(255) DEFAULT NULL COMMENT '格局規劃',
  `ca_units` varchar(255) DEFAULT NULL COMMENT '戶數規劃',
  `ca_floors` varchar(255) DEFAULT NULL COMMENT '樓層規劃',
  `ca_parking` varchar(255) DEFAULT NULL COMMENT '車位規劃',
  `ca_phone` varchar(50) DEFAULT NULL COMMENT '諮詢專線',
  `ca_adds` varchar(500) DEFAULT NULL COMMENT '接待會館',
  `ca_map` longtext DEFAULT NULL COMMENT '建案嵌入地圖',
  `fb_url` varchar(500) DEFAULT NULL COMMENT 'FB粉專',
  `ig_url` varchar(500) DEFAULT NULL COMMENT 'IG粉專',
  `case_url` varchar(500) DEFAULT NULL COMMENT '建案連結',
  `progress_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '工程進度（對應 progress.id）',
  `ca_pop_type` int(11) NOT NULL DEFAULT 0 COMMENT '彈窗類型（0=不顯示,1=圖片,2=影片）',
  `ca_pop` varchar(500) DEFAULT NULL COMMENT '彈窗內容（圖片或影片連結）',
  `is_sale` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否完售（1=完售,0=銷售中）',
  `is_msg` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否預約賞屋（1=是,0=否）',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否上線（1=上線,0=下線）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='建案表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `app_case`
--

ALTER TABLE `app_case`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_structure_id` (`structure_id`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort` (`sort`),
  ADD KEY `idx_progress_id` (`progress_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `app_case`
--

ALTER TABLE `app_case`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

