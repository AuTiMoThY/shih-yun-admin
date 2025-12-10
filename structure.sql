-- phpMyAdmin SQL Dump
-- 系統架構層級資料表
-- 資料庫： `shih-yun`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料表結構 `structure`
--

CREATE TABLE `structure` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '父層級 ID（NULL 表示第一層）',
  `name` varchar(100) NOT NULL COMMENT '層級名稱',
  `is_show_frontend` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示前台：1=顯示,0=不顯示',
  `is_show_backend` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示後台：1=顯示,0=不顯示',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1=啟用,0=停用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序（數字越小越前面）',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_structure_parent` FOREIGN KEY (`parent_id`) REFERENCES `structure` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系統架構層級表';

--
-- 索引說明
-- parent_id: 用於快速查詢子層級
-- status: 用於快速篩選啟用/停用的層級
-- sort_order: 用於排序顯示順序
-- 外鍵約束: 確保父層級存在，並支援級聯刪除
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

