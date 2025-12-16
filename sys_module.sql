-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-16 03:11:30
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
-- 資料表結構 `sys_module`
--

CREATE TABLE `sys_module` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL COMMENT '模組名稱',
  `name` varchar(100) NOT NULL COMMENT '模組代碼',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `sys_module`
--

INSERT INTO `sys_module` (`id`, `label`, `name`, `created_at`, `updated_at`) VALUES
(1, '關於我們', 'about', '2025-12-12 07:21:18', '2025-12-12 07:21:18'),
(2, '最新消息', 'news', '2025-12-12 07:21:30', '2025-12-12 07:21:30'),
(3, '聯絡我們', 'contact', '2025-12-12 08:41:59', '2025-12-12 08:41:59'),
(4, '建案', 'case', '2025-12-12 08:42:24', '2025-12-12 08:42:24');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `sys_module`
--
ALTER TABLE `sys_module`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sys_module`
--
ALTER TABLE `sys_module`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
