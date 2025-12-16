-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-16 03:11:25
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
-- 資料表結構 `sys_admin`
--

CREATE TABLE `sys_admin` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '主鍵',
  `permission_name` varchar(50) DEFAULT NULL COMMENT '權限名稱',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `username` varchar(100) NOT NULL COMMENT '帳號（唯一）',
  `password_hash` varchar(255) NOT NULL COMMENT '密碼雜湊值',
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `phone` varchar(50) DEFAULT NULL COMMENT '電話',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `sys_admin`
--

INSERT INTO `sys_admin` (`id`, `permission_name`, `status`, `username`, `password_hash`, `name`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'admin', 1, 'test', '$2y$10$rGgUGiFL6L/apmYDVQBIHOq.0xz1e9LBnT/h4tTRScH8xYb3a4xzG', 'test', '', '', '2025-12-09 01:27:23', '2025-12-09 01:27:23'),
(2, 'admin', 1, 'test1', '$2y$10$2C7yQxlBEGAwhalr4cComOkVtzAJEWNoIqz1rW.hmvn5C8IjH/Cby', 'test1', '', '', '2025-12-09 01:31:54', '2025-12-09 01:31:54'),
(6, 'admin', 1, 'admin', '$2y$10$CjlAGYn0gfVJJP54D3SsGe.bSosQi.A3F9vmN66uc3vcPca9RNGse', 'admin', '', '', '2025-12-09 19:15:48', '2025-12-09 19:15:48');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `sys_admin`
--
ALTER TABLE `sys_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sys_admin`
--
ALTER TABLE `sys_admin`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵', AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
