-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-08-22 11:13:43
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
-- 資料表結構 `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'keli', '$2y$10$LsgKfbb2B4nsIphb.c9LkOX03.xcpS3KrgIxtR80AAcLmlOdJf5je', '2025-08-21 17:01:04');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=未處理, 1=已處理'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `consultations`
--

INSERT INTO `consultations` (`id`, `name`, `phone`, `email`, `message`, `created_at`, `status`) VALUES
(14, '朴智旻', '0952052052', 'jimin@gmail.com', '可以配送到離島嗎', '2025-08-13 16:31:34', 0),
(15, '蕭老大', '0955520520', 'osborn@gmail.com', NULL, '2025-08-14 10:32:51', 0),
(16, '朴智旻', '0952052052', 'jimin@gmail.com', '可以配送到離島嗎', '2025-08-14 11:00:40', 0),
(17, '蕭老大', '0955520520', 'osborn@gmail.com', '可以配送到離島嗎', '2025-08-14 17:52:02', 0),
(18, 'sandy', '0988787676', 'sunny.lin@varmeego.com', '多少免運', '2025-08-15 18:25:00', 0),
(19, '班長大人', '0988787676', 'jimin@gmail.com', '希希?', '2025-08-21 16:41:26', 1);

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
(19, 14, 18, 4),
(20, 14, 19, 4),
(21, 14, 20, 1),
(22, 15, 18, 1),
(23, 16, 18, 1),
(24, 17, 23, 3),
(25, 17, 22, 1),
(26, 18, 22, 3),
(27, 18, 19, 1),
(28, 19, 22, 2),
(29, 19, 23, 5),
(30, 19, 20, 1),
(31, 19, 19, 1),
(32, 19, 18, 1),
(33, 19, 21, 1),
(34, 19, 17, 1);

-- --------------------------------------------------------

--
-- 資料表結構 `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` varchar(500) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `product`
--

INSERT INTO `product` (`id`, `name`, `category`, `description`, `image`) VALUES
(17, '地墊', '地墊 - 吸水墊', '灰色/紅色', 'uploads/17.webp'),
(18, '塑膠醬油碟', '紙杯/免洗餐具 - 免洗餐具/盒', '紅色、小', 'uploads/18.webp'),
(19, '防疫口罩', '防疫專區 - 口罩', '採用「魚型折疊」版型，貼合臉部曲線並釋放口鼻空間，說話不貼嘴、妝容不易沾染。四層結構包含{{親膚層／熱風棉／熔噴過濾層／表布防潑水層}}，在透氣與防護間取得良好平衡，長時間會議或通勤也能舒適呼吸。通過{{CNS/EN 標準}}檢測，{{BFE/PFE/VFE ≥ 99%（依實測填寫）}}，配合可塑鼻樑條與兩側立體剪裁，加寬耳帶分散壓力，配戴穩定不易滑落。單片{{獨立包裝}}，乾淨攜帶更有儀式感；多色系與{{花粉季限定色}}，兼顧專業與穿搭美感，滿足日常、商務與旅遊等多場景需求。', 'uploads/19.webp'),
(20, '五月花', '紙類用品 - 抽取式衛生紙', '【五月花｜抽取式衛生紙】選用細緻紙漿與均勻壓紋，觸感柔軟、吸水力佳，抽取順暢不連張、不易掉屑；紙張韌性提升，濕擦也不易破。居家餐飲、廚房擦拭、辦公室與外出隨身皆適用。採{{包裝型態：盒裝/軟抽}}，體積輕巧好收納。規格：{{每抽張數}}×{{層數}}層，{{每袋入數/每箱入數}}，尺寸{{單張尺寸}}；請置於陰涼乾燥處，紙製品請勿丟入馬桶造成阻塞。\r\n\r\n', 'uploads/20.webp'),
(21, '紅外線酒精消毒機', '機台/垃圾桶/傘架 - 手部消毒機', '免接觸自動感應設計，內建紅外線感測器，手部靠近時自動噴出適量酒精，降低交叉感染風險。機身容量大、出液均勻，適合辦公室、商場、餐廳、學校等公共場所使用。支援酒精或含酒精消毒液，簡單補充、方便維護，有效守護日常衛生與安全。\r\n\r\n特色：\r\n\r\n紅外線自動感應，免手接觸\r\n\r\n支援噴霧或滴液式出液模式（依機型）\r\n\r\n大容量設計，減少頻繁加液\r\n\r\n節能省電，部分機型支援電池或 USB 供電\r\n\r\n適用於各種公共及商用空間', 'uploads/21.webp'),
(22, '大捲垃圾袋', '垃圾袋 - 捲式垃圾袋', '耐用加厚材質製成，不易破裂滲漏，適合大量廢棄物收集。採用大捲式包裝，方便抽取與存放，適用於家庭、辦公室、餐飲業及各類公共場所。\r\n\r\n產品特色：\r\n\r\n大捲設計：連續抽取，使用方便\r\n\r\n耐用材質：加厚防破，承重力佳\r\n\r\n防漏設計：有效防止液體滲漏\r\n\r\n多容量選擇：適合各種垃圾桶尺寸\r\n\r\n環保材質：部分款式可選用再生塑料', 'uploads/22.webp'),
(23, '妙管家洗碗精', '清潔用品 - 妙管家系列', '高效去油配方，能迅速分解頑固油汙，泡沫細緻綿密，輕鬆洗淨碗盤與廚具，溫和不傷手。適用於家庭、餐飲業及各類廚房清潔需求。\r\n\r\n產品特色：\r\n\r\n強效去油：快速分解油汙，潔淨如新\r\n\r\n溫和配方：含護手成分，減少乾燥不適\r\n\r\n泡沫豐富：少量即可產生細緻泡沫，易沖洗不殘留\r\n\r\n多用途：適用於餐具、鍋具及廚房表面清潔\r\n\r\n多種香味：檸檬、蘋果等清新香氣可選\r\n\r\n用途：清潔碗盤、杯具、鍋具及廚房用具\r\n使用方式：將適量洗碗精擠於海綿或清潔布上，加水起泡後清洗，再用清水沖淨', 'uploads/23.webp'),
(24, '可彎吸管', '其他餐飲用品 - 藝術吸管', '', 'uploads/24.webp');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

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
-- 使用資料表自動遞增(AUTO_INCREMENT) `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `consultation_items`
--
ALTER TABLE `consultation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
