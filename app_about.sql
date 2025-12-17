-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-17 06:40:54
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
-- 資料表結構 `app_about`
--

CREATE TABLE `app_about` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL COMMENT '頁面標題，可選',
  `sections_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'cutSections 序列化 JSON' CHECK (json_valid(`sections_json`)),
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=啟用,0=停用',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `app_about`
--

INSERT INTO `app_about` (`id`, `title`, `sections_json`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, '[{\"id\":\"section-20251215173905\",\"index\":1,\"fields\":[{\"id\":\"field-1765791548340-x5fjc8\",\"type\":\"desktop_image\",\"label\":\"電腦版圖片\",\"value\":\"http:\\/\\/localhost:8080\\/uploads\\/1765792658_ff6995ebdd15225206df.jpg\"}]},{\"id\":\"section-20251215162804\",\"index\":2,\"fields\":[{\"id\":\"field-1765787335479-7znlot\",\"type\":\"title\",\"label\":\"title\",\"value\":\"石的穩健，云的視野11\"},{\"id\":\"field-1765787356322-hm9p4f\",\"type\":\"content\",\"label\":\"內文\",\"value\":\"多年等待與好地重逢，百年遠見與未來相遇。\\n精華如何定義，由我們決定。\\n為台中精選好地，留給懂得住的人。\\n\\n不只蓋房，更打造一處生活提案的原點。\\n從產地到餐桌，從書頁到對話，從城市到自然。\\n一個懂得建築，也懂得生活的品牌，就此登場。\"}]},{\"id\":\"section-1765788174314-u9h1ftg8b\",\"index\":3,\"fields\":[{\"id\":\"field-1765788704115-rev0kl\",\"type\":\"title\",\"label\":\"標題\",\"value\":\"石云建設\\n實踐宜居的提案者\"},{\"id\":\"field-1765788722355-5ged6a\",\"type\":\"content\",\"label\":\"內文\",\"value\":\"我們以「永續宅」築起安心根基，用專業團隊與穩健施工為未來把關；\\n以「綠家園」回應自然，重視環境、棟距與每一處陽光與綠意的流動；\\n以「優生活」延伸日常，融入禮遇、鮮蔬、樂活，\\n讓家的想像不止於建築，而是完整生活的實現。\"},{\"id\":\"field-1765790451147-dtknh3\",\"type\":\"title\",\"label\":\"Feature-1-title\",\"value\":\"永續宅\"},{\"id\":\"field-1765790565828-drn209\",\"type\":\"content\",\"label\":\"內文\",\"value\":\"洞察世界建築趨勢，\\nSDGs×ESG，實踐健康\\n環保的永續目標。\"},{\"id\":\"field-1765790590820-unf5oa\",\"type\":\"title\",\"label\":\"featrue-2-title\",\"value\":\"綠家園\"},{\"id\":\"field-1765790603363-8xl9dp\",\"type\":\"content\",\"label\":\"內文\",\"value\":\"垂直綠化、環境共好、\\n社區同心，讓出空間給綠意，\\n虛懷若谷，廣納自然。\"},{\"id\":\"field-1765790845452-u1g5sf\",\"type\":\"title\",\"label\":\"featrue-3-title\",\"value\":\"優生活\"},{\"id\":\"field-1765790990860-f55qyd\",\"type\":\"content\",\"label\":\"內文\",\"value\":\"全方位服務體系，跨國\\n集團多元事業體，為建築\\n導入軟性的人文關懷。\"}]},{\"id\":\"section-20251217134010\",\"index\":4,\"fields\":[{\"id\":\"field-1765950018275-cpycmr\",\"type\":\"content\",\"label\":\"內文\",\"value\":\"test\"}]}]', 1, '2025-12-14 23:26:32', '2025-12-16 21:40:20');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `app_about`
--
ALTER TABLE `app_about`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `app_about`
--
ALTER TABLE `app_about`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
