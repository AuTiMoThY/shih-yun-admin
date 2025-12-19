-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-19 09:19:56
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
-- 資料表結構 `app_contact`
--

CREATE TABLE `app_contact` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `structure_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '系統架構 ID',
  `name` varchar(255) NOT NULL COMMENT '姓名',
  `phone` varchar(50) NOT NULL COMMENT '電話',
  `email` varchar(255) NOT NULL COMMENT '信箱',
  `message` text DEFAULT NULL COMMENT '留言（選填）',
  `reply` longtext DEFAULT NULL COMMENT '管理員回信內容',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '處理狀態（0=待處理, 1=處理中, 2=已完成）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `app_contact`
--
ALTER TABLE `app_contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_structure_id` (`structure_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `app_contact`
--
ALTER TABLE `app_contact`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
