-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-08-12 09:56:43
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `sinci`
--

-- --------------------------------------------------------

--
-- 資料表結構 `consultations`
--

CREATE TABLE `consultations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `consultations`
--

INSERT INTO `consultations` (`id`, `name`, `phone`, `email`, `message`, `created_at`) VALUES
(1, 'keli', '0907549863', 'linyishan1313@gmail.com', '價格', '2025-08-11 16:51:02'),
(2, '林宜姍', '0988234512', NULL, '衛生紙的包數?', '2025-08-11 17:11:16'),
(3, 'Sunny Lin', '0988765432', 'varmeego@gmail.com', '火力', '2025-08-11 17:12:55');

-- --------------------------------------------------------

--
-- 資料表結構 `consultation_items`
--

CREATE TABLE `consultation_items` (
  `id` int(11) NOT NULL,
  `consultation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `consultation_items`
--

INSERT INTO `consultation_items` (`id`, `consultation_id`, `product_id`, `qty`) VALUES
(1, 1, 16, 1),
(2, 1, 15, 1),
(3, 2, 14, 1),
(4, 3, 15, 1);

-- --------------------------------------------------------

--
-- 資料表結構 `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `product`
--

INSERT INTO `product` (`id`, `name`, `category`, `description`, `price`, `image`) VALUES
(14, '五月花', '紙類用品 - 抽取式衛生紙', '五月花', 499, 'uploads/14.webp'),
(15, '瓦斯罐', '燃料類 - 瓦斯罐/爐', '瓦斯罐', 599, 'uploads/15.webp'),
(16, '芳香除臭劑', '芳香/除臭用品 - 芳香除臭劑', '芳香除臭劑', 399, 'uploads/16.webp');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `consultation_items`
--
ALTER TABLE `consultation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultation_id` (`consultation_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 資料表索引 `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `consultation_items`
--
ALTER TABLE `consultation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `consultation_items`
--
ALTER TABLE `consultation_items`
  ADD CONSTRAINT `consultation_items_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
