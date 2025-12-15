-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-10
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
-- 資料表結構 `company_base`
--

CREATE TABLE `company_base` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `name` varchar(255) DEFAULT NULL COMMENT '公司名稱',
  `copyright` varchar(255) DEFAULT NULL COMMENT '版權資訊',
  `phone` varchar(50) DEFAULT NULL COMMENT '電話',
  `fax` varchar(50) DEFAULT NULL COMMENT '傳真',
  `email` varchar(255) DEFAULT NULL COMMENT '電子郵件',
  `case_email` varchar(255) DEFAULT NULL COMMENT '案件電子郵件',
  `zipcode` varchar(10) DEFAULT NULL COMMENT '郵遞區號',
  `city` varchar(100) DEFAULT NULL COMMENT '城市',
  `district` varchar(100) DEFAULT NULL COMMENT '行政區',
  `address` varchar(500) DEFAULT NULL COMMENT '地址',
  `fb_url` varchar(500) DEFAULT NULL COMMENT 'Facebook URL',
  `yt_url` varchar(500) DEFAULT NULL COMMENT 'YouTube URL',
  `line_url` varchar(500) DEFAULT NULL COMMENT 'LINE URL',
  `keywords` text DEFAULT NULL COMMENT '關鍵字',
  `description` text DEFAULT NULL COMMENT '描述',
  `head_code` text DEFAULT NULL COMMENT '<head>代碼',
  `body_code` text DEFAULT NULL COMMENT '<body>代碼',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='公司基本資訊表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `company_base`
--
ALTER TABLE `company_base`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `company_base`
--
ALTER TABLE `company_base`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵', AUTO_INCREMENT=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
