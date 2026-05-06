-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 06, 2026 at 12:29 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u650542527_plamalDB`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_details`
--

CREATE TABLE `admin_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_details`
--

INSERT INTO `admin_details` (`id`, `user_id`, `full_name`, `position`, `phone`, `photo`, `created_at`) VALUES
(1, 35, 'Roseanne Park', 'Secretary', '09707563428', 'admin_1771074828_20250126214907_Rose-5.webp', '2026-02-14 13:13:48'),
(2, 36, 'Jermaine Buendia', 'President', '09707563428', 'admin_1771074978_joshua-garcia-pep-best-bets-main-1716817689.jpeg', '2026-02-14 13:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `buyer_addresses`
--

CREATE TABLE `buyer_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(120) DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer_addresses`
--

INSERT INTO `buyer_addresses` (`id`, `user_id`, `label`, `street`, `barangay`, `full_address`, `city`, `province`, `zip`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 3, 'Home', '#157', NULL, NULL, 'CALUMPIT', 'BULACAN', '3003', 0, '2026-01-27 16:38:25', '2026-02-03 04:35:51'),
(3, 3, 'Work', '#157 PUROK 4', NULL, NULL, 'CALUMPIT', 'BULACAN', '3003', 0, '2026-01-27 17:32:00', '2026-01-27 17:32:00'),
(4, 13, 'Home', '#157 PUROK 4', NULL, NULL, 'CALUMPIT', 'BULACAN', '3003', 0, '2026-02-03 06:06:54', '2026-02-03 06:06:54'),
(12, 37, 'HOME', '#157 PUROK 4', 'IBA O\' ESTE', '#157 PUROK 4, IBA O\' ESTE, CALUMPIT, BULACAN 3003', 'CALUMPIT', 'BULACAN', '3003', 0, '2026-02-16 12:26:28', '2026-02-16 12:26:36'),
(13, 3, 'WORK', '#154 PUROK 1', 'CENTRO NORTE (POBLACION)', '#154 PUROK 1, CENTRO NORTE (POBLACION), ALCALA, CAGAYAN 3507', 'ALCALA', 'CAGAYAN', '3507', 0, '2026-02-17 11:05:15', '2026-02-17 11:05:15'),
(14, 37, 'WORK', '156 PUROK 4', 'CENTRO NORTE', '156 PUROK 4, CENTRO NORTE, ALCALA, CAGAYAN 3507', 'ALCALA', 'CAGAYAN', '3507', 0, '2026-02-19 06:43:16', '2026-02-19 06:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `buyer_details`
--

CREATE TABLE `buyer_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `address_label` varchar(50) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `barangay` varchar(120) DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `citymun_code` varchar(10) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `province_code` varchar(10) DEFAULT NULL,
  `zip` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer_details`
--

INSERT INTO `buyer_details` (`id`, `user_id`, `full_name`, `phone`, `photo`, `address_label`, `street`, `barangay`, `full_address`, `city`, `citymun_code`, `province`, `province_code`, `zip`) VALUES
(1, 3, 'Jermaine Buendia', '09707563428', '1770093382_etool_1741582250023.png', 'WORK', '#154 PUROK 1', 'CENTRO NORTE (POBLACION)', '#154 PUROK 1, CENTRO NORTE (POBLACION), ALCALA, CAGAYAN 3507', 'ALCALA', NULL, 'CAGAYAN', NULL, '3507'),
(10, 37, 'Mica Salamanca', '09159419371', '6992f37068e2e.jpg', 'WORK', '156 PUROK 4', 'CENTRO NORTE', '156 PUROK 4, CENTRO NORTE, ALCALA, CAGAYAN 3507', 'ALCALA', '', 'CAGAYAN', '', '3507');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `buyer_id`, `created_at`) VALUES
(1, 3, '2026-01-25 14:51:13'),
(3, 13, '2026-02-03 06:05:52'),
(4, 37, '2026-02-16 13:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price_at_add` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `qty`, `price_at_add`, `created_at`) VALUES
(10, 3, 13, 10, 2500.00, '2026-02-03 06:08:19'),
(22, 1, 11, 19, 1800.00, '2026-02-06 18:53:49'),
(24, 1, 22, 1, 1500.00, '2026-02-16 13:35:18'),
(52, 4, 22, 10, 1500.00, '2026-02-20 06:26:21'),
(53, 4, 23, 1, 1200.00, '2026-03-20 02:28:51'),
(54, 4, 17, 12, 1200.00, '2026-04-12 11:47:03'),
(55, 4, 11, 1, 1800.00, '2026-05-06 12:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_fee_rules`
--

CREATE TABLE `delivery_fee_rules` (
  `id` int(11) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT '',
  `fee` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `farmers_list`
--

CREATE TABLE `farmers_list` (
  `id` int(11) NOT NULL,
  `rsbsa_no` varchar(30) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `gender` enum('M','F','m','f') DEFAULT NULL,
  `farm_area` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmers_list`
--

INSERT INTO `farmers_list` (`id`, `rsbsa_no`, `last_name`, `first_name`, `middle_name`, `gender`, `farm_area`) VALUES
(3, '03-14-10-010-000008', 'ADRIANO', 'CIRILO', 'MAGSAKAY', 'M', 0.32),
(4, '03-14-10-010-000015', 'AGULAN', 'ANACLET A', 'BUENDIA', 'F', 1.85),
(5, '03-14-10-010-000092', 'BENEDICTOS', 'CORNELIA', 'ARANZANSO', 'F', 1.29),
(6, '03-14-10-010-000085', 'BENEDICTOS', 'JASON', 'LARGO', 'M', 0.41),
(7, '03-14-10-010-000025', 'BENEDICTOS', 'TRINIDAD', 'DELA CRUZ', 'F', 1.38),
(8, '03-14-10-010-000116', 'BERNARDO', 'CORNELIO', 'GODIOS', 'M', 0.18),
(9, '03-14-10-010-001098', 'BERNARDO', 'MONICA', 'DIONISIO', 'F', 0.33),
(10, '03-14-10-052-000021', 'CABANTOG', 'CATALINO', 'VENTURA', 'M', 1.49),
(11, '03-14-10-010-000048', 'CABASA', 'ISABELITA', 'SURIO', 'F', 1.49),
(12, '03-14-10-010-000054', 'CALAYAG', 'ISIDRO', 'ANGELO', 'M', 0.56),
(13, '03-14-10-010-000039', 'CASTILLO', 'ERLINDA', 'BERNARDO', 'F', 0.65),
(14, '03-14-10-010-000033', 'CORTEZ', 'MARCOS', 'MACLANG', 'M', 2.92),
(15, '03-14-10-010-000021', 'CRUZ', 'CRISPIN', 'CRUZ', 'M', 0.77),
(16, '03-14-10-010-000020', 'CRUZ', 'RENATO', 'CRUZ', 'M', 2.02),
(17, '03-14-10-010-000115', 'CRUZ', 'YOLANDA', 'BERNARDO', 'F', 1.12),
(18, '03-14-10-010-000018', 'DACARA', 'LEONORA', 'ROQUE', 'F', 0.83),
(19, '03-14-10-010-000003', 'DAYAO', 'BENITO', 'MAGSAKAY', 'M', 0.69),
(20, '03-14-10-010-000130', 'DAYAO', 'BRIAN NICOLE', 'RIVERA', 'F', 1.51),
(21, '03-14-10-010-000140', 'DAYAO', 'EDILBERTA', 'BENEDICTOS', 'F', 1.48),
(22, '03-14-10-010-000002', 'DAYAO', 'FAUSTINO', 'MAGSAKAY', 'M', 0.62),
(23, '03-14-10-010-000016', 'DAYAO', 'FLORENTINO', 'SANTOS', 'M', 0.19),
(24, '03-14-10-010-000076', 'DAYAO', 'HERMINIO', 'SANTOS', 'M', 1.01),
(25, '03-14-10-010-000141', 'DAYAO', 'JONATHAN', 'SANTOS', 'M', 0.40),
(26, '03-14-10-010-000142', 'DAYAO', 'JOSINE FELICE', 'RIVERA', 'F', 3.14),
(27, '03-14-10-010-000131', 'DAYAO', 'KISSHIA ELAINE', 'STA CRUZ', 'F', 0.71),
(28, '03-14-10-010-000121', 'DAYAO', 'NICOLAS', 'LEONCIO', 'M', 1.19),
(29, '03-14-10-010-000017', 'DAYAO', 'TERESITA', 'BERNARDO', 'F', 1.92),
(30, '03-14-10-010-000127', 'DAYAO', 'VICTORIA', 'BENEDICTOS', 'F', 0.97),
(31, '03-14-10-010-000145', 'DE GUZMAN', 'DONATO', 'CLEMENTE', 'M', 2.46),
(32, '03-14-10-034-000024', 'DE GUZMAN', 'PRUDENCIO', 'CLEMENTE', 'M', 0.32),
(33, '03-14-10-010-000030', 'DE GUZMAN', 'RENIE', 'MONJE', 'M', 2.55),
(34, '03-14-10-010-000086', 'DE LEMOS', 'REYNALDO', 'TARLAC', 'M', 0.67),
(35, '03-14-10-010-000069', 'DE LEMOS', 'VIRGILIO', 'SAPITAN', 'M', 1.96),
(36, '03-14-10-010-000032', 'DELA CRUZ', 'FIDEL', 'ENRIQUEZ', 'M', 1.39),
(37, '03-14-10-010-002004', 'DELA CRUZ', 'FRANCISCO', 'CAMUA', 'M', 0.87),
(38, '03-14-10-010-000034', 'DELA CRUZ', 'LEONARDO', 'ENRIQUEZ', 'M', 1.35),
(39, '03-14-10-010-000144', 'DELA CRUZ', 'MATILDE', 'HERNANDEZ', 'F', 1.23),
(40, '03-14-10-010-000070', 'DELA CRUZ', 'NAPOLEON', 'MAGSAKAY', 'M', 1.02),
(41, '03-14-10-010-000052', 'DELA CRUZ', 'OSCAR', 'MANABAT', 'M', 0.13),
(42, '03-14-10-010-000102', 'DIAZ', 'JOSELITO', 'DELA CRUZ', 'M', 1.15),
(43, '03-14-10-010-000143', 'DIONISIO', 'AGRIPINO', 'PADRINAO', 'M', 0.76),
(44, '03-14-10-010-000011', 'DIONISIO', 'LEONARDO', 'PADRINAO', 'M', 1.93),
(45, '03-14-10-010-000062', 'DIONISIO', 'REYNALDA', 'MARCELLINO', 'F', 2.68),
(46, '03-14-10-010-000053', 'DOMINGO', 'JULIA', 'DELA CRUZ', 'F', 0.12),
(47, '03-14-10-010-000050', 'DUCUT', 'RUFINA', 'GARCIA', 'F', 1.15),
(48, '03-14-10-010-000059', 'ESTRELLA', 'MARY JANE', 'SENA', 'F', 1.52),
(49, '03-14-10-010-000049', 'GARCIA', 'MARIANO', 'HERNANDEZ', 'M', 1.01),
(50, '03-14-10-010-000122', 'GUEVARRA', 'JESICA', 'BERNARDO', 'F', 1.06),
(51, '03-14-10-010-000067', 'HERNANDEZ', 'ROBERTO', 'CAILIPAN', 'M', 1.21),
(52, '03-14-10-010-000027', 'LEONCIO', 'REGINO', 'MAGSAKAY', 'M', 0.75),
(53, '03-14-10-010-000107', 'LIM', 'HERMIN', 'MANAHAN', 'M', 0.23),
(54, '03-14-10-010-000106', 'LOMOTAN', 'BENJAMIN', 'ESTRELLA', 'M', 0.47),
(55, '03-14-10-010-000060', 'LOMOTAN', 'JULIA', 'ESTRELLA', 'F', 0.41),
(56, '03-14-10-010-002008', 'LOMOTAN', 'ROMMEL', 'RODRIGUEZ', 'M', 0.93),
(57, '03-14-10-010-000109', 'LUCAS', 'DOROTEA', 'BENEDICTOS', 'F', 0.39),
(58, '03-14-10-010-000089', 'LUCAS', 'MARCELINO', 'DELA CRUZ', 'M', 0.60),
(59, '03-14-10-010-000118', 'LUCAS', 'MERALUNA', 'OLOROSO', 'F', 0.17),
(60, '03-14-10-010-000088', 'MACLANG', 'ISIDRO', 'SANTOS', 'M', 1.91),
(61, '03-14-10-010-000113', 'MADRLEJOS', 'DANTE', 'BALLARAN', 'M', 1.12),
(62, '03-14-10-010-000004', 'MAGSAKAY', 'EDUARDO', 'BOSITO', 'M', 3.35),
(63, '03-14-10-010-000131', 'MAGSAKAY', 'JOEL', 'EUSEBIO', 'M', 0.52),
(64, '03-14-10-010-000009', 'MAGSAKAY', 'JUANITO', 'BOSITO', 'M', 0.50),
(65, '03-14-10-010-000138', 'MAGSAKAY', 'ROLAND', 'TAMARES', 'M', 0.49),
(66, '03-14-10-010-000006', 'MAGSAKAY', 'VENUS', 'LALAGUNA', 'F', 0.49),
(67, '03-14-17-019-000072', 'MAÑAGO', 'SUSANA', 'SANTOS', 'F', 0.39),
(68, '03-14-10-047-IBUZ5D', 'MANAHAN', 'TIRSO', 'MENDIOLA', 'M', 1.93),
(69, '03-14-10-010-000120', 'MANALAYSAY', 'ARNEL', 'SAPITAN', 'M', 0.88),
(70, '03-14-10-010-000068', 'MANZANERO', 'ROSEVEL', 'DELA CRUZ', 'F', 1.24),
(71, '03-14-10-010-000111', 'MARINAS', 'ANTONIO', 'LUCAS', 'M', 0.67),
(72, '03-14-10-010-000110', 'MARINAS', 'REGINO', 'DOMINGO', 'M', 0.44),
(73, '03-14-10-010-000024', 'MARINAS', 'LIEZL', 'HERNANDEZ', 'F', 1.24),
(74, '03-14-10-010-000095', 'MENDOZA', 'MARGARITA', 'DIONISIO', 'F', 2.68),
(75, '03-14-10-010-000099', 'OCTIA', 'DANIEL', 'BERNARDO', 'M', 0.18),
(76, '03-14-10-010-000040', 'OCTIA', 'JULIO', 'ALCANTARA', 'M', 0.35),
(77, '03-14-10-010-000139', 'PAGSANJAN', 'RAMIL', 'CORTEZ', 'M', 0.37);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_details`
--

CREATE TABLE `farmer_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farmer_name` varchar(150) DEFAULT NULL,
  `farm_area` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `registry_num` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `barangay` varchar(120) DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `citymun_code` varchar(10) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `province_code` varchar(10) DEFAULT NULL,
  `zip` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_details`
--

INSERT INTO `farmer_details` (`id`, `user_id`, `farmer_name`, `farm_area`, `phone`, `registry_num`, `photo`, `street`, `barangay`, `full_address`, `city`, `citymun_code`, `province`, `province_code`, `zip`) VALUES
(1, 2, 'Jermaine Buendia', '0.77 ha', '09159419371', '03-14-10-010-000015', 'profile_2_1771329919.jpeg', '#157 Purok 4 Iba O\' Este', NULL, '#157 PUROK 4, IBA O\' ESTE, CALUMPIT, BULACAN', 'Calumpit', NULL, 'Bulacan', NULL, '3003'),
(13, 34, 'Andrea Brillantes', '1.9', '09707563428', '03-14-010-000015', 'profile_34_1771568253.jpeg', '#157 PUROK 4, IBA O\' ESTE, CALUMPIT, BULACAN', 'Iba O\' Este', '#157 PUROK 4, IBA O\' ESTE, CALUMPIT, BULACAN', 'CALUMPIT', '031407', 'BULACAN', '0314', '3003'),
(19, 43, 'Anacleta Agulan Buendia', '2.9', '09159419371', '03-14-10-010-000015', '69e08668a9d46.jpg', '158 PUROK 4 IBA O ESTE CALUMPIT BULACAN', 'Iba O\' Este', '158 PUROK 4 IBA O ESTE CALUMPIT BULACAN', 'CALUMPIT', '031407', 'BULACAN', '0314', '3003');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_plans`
--

CREATE TABLE `farmer_plans` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `plan_year` smallint(6) NOT NULL,
  `quarter` tinyint(4) NOT NULL,
  `season` enum('wet','dry') DEFAULT NULL,
  `rice_variety` varchar(120) NOT NULL,
  `planting_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_plans`
--

INSERT INTO `farmer_plans` (`id`, `farmer_id`, `plan_year`, `quarter`, `season`, `rice_variety`, `planting_date`, `notes`, `image_path`, `created_at`, `updated_at`) VALUES
(3, 2, 2026, 1, NULL, 'RC 480', '2026-02-09', 'I\'ll plant this again for the 1st quarter of 2026 because this has been sold fast and profitable on the year 2025.', '../uploads/plans/plan_2_1769686840_5191.png', '2026-01-29 19:40:40', '2026-02-06 15:57:54'),
(22, 2, 2026, 1, 'dry', 'US 88 Long Grain', '2026-02-07', 'Recommended because this variety had the highest total SOLD quantity during March–April (2026). Based on your sales trend, this crop shows strong market demand and is ideal for reuse in the next season.', '../uploads/products/prod_6972f044d6d9f.png', '2026-02-07 02:46:13', '2026-02-07 02:46:13'),
(24, 2, 2026, 3, 'wet', '436 Variety', '2026-02-10', 'Recommended because this variety had the highest total SOLD quantity during November–December (2026). Based on your sales trend, this crop shows strong market demand and is ideal for reuse in the next season.', 'uploads/products/prod_6979fd87b4205.png', '2026-02-10 22:24:44', '2026-02-10 22:24:44'),
(26, 34, 2026, 1, 'dry', '436 Variety', '2026-04-12', 'Recommended because this variety had the highest total SOLD quantity during March–April (2026). Based on your sales trend, this crop shows strong market demand and is ideal for reuse in the next season.', 'uploads/products/product_34_1771123612.png', '2026-04-12 14:43:19', '2026-04-12 14:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_products`
--

CREATE TABLE `farmer_products` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fulfillment_options` varchar(50) NOT NULL DEFAULT 'pickup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_products`
--

INSERT INTO `farmer_products` (`id`, `farmer_id`, `product_name`, `price`, `unit`, `quantity`, `image`, `created_at`, `fulfillment_options`) VALUES
(7, 2, '436 Variety', 1000.00, '', 100, 'prod_6979fd87b4205.png', '2026-01-21 11:03:50', 'pickup,delivery'),
(9, 2, 'US 88 Long Grain', 1500.00, 'sack', 150, 'prod_6972f044d6d9f.png', '2026-01-23 03:51:32', 'pickup,delivery'),
(11, 2, 'Hybrid Rice', 1800.00, 'sack', 9, 'prod_69855a81bca8f.jpg', '2026-01-23 07:57:05', 'pickup,delivery'),
(32, 34, 'RC 436', 1400.00, 'sack', 50, 'products/1776138826_69ddba4a8e602.png', '2026-04-14 05:34:40', 'pickup'),
(34, 34, 'Hybrid Rice', 5200.00, 'sack', 70, 'products/1776141598_69ddc51e4210a.png', '2026-04-14 05:39:24', 'pickup');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_transactions`
--

CREATE TABLE `farmer_transactions` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `type` enum('Income','Expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_transactions`
--

INSERT INTO `farmer_transactions` (`id`, `farmer_id`, `type`, `amount`, `description`, `date`, `created_at`) VALUES
(19, 34, 'Income', 7500.00, 'Manual sale: US 88 Long Grain (5 sack)', '2026-02-20', '2026-02-20 08:37:49'),
(20, 34, 'Income', 6000.00, 'Manual sale: RC 534 (5 sack)', '2026-03-23', '2026-03-23 20:03:25'),
(21, 34, 'Income', 12000.00, 'Manual sale: 436 Variety (10 sack)', '2026-04-06', '2026-04-06 15:04:20'),
(22, 34, 'Income', 25000.00, 'Manual sale: Hybrid Rice (5 sack)', '2026-04-14', '2026-04-14 03:52:09');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_transactions`
--

CREATE TABLE `marketplace_transactions` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) NOT NULL DEFAULT 'Order Completed',
  `txn_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketplace_transactions`
--

INSERT INTO `marketplace_transactions` (`id`, `farmer_id`, `order_id`, `amount`, `description`, `txn_date`, `created_at`) VALUES
(26, 34, 94, 27000.00, 'Order Completed', '2026-02-20', '2026-02-20 02:18:17'),
(27, 34, 96, 12000.00, 'Order Completed', '2026-03-04', '2026-03-04 11:23:44'),
(28, 34, 95, 27000.00, 'Order Completed', '2026-03-04', '2026-03-04 11:24:37'),
(29, 34, 100, 75000.00, 'Order Completed', '2026-03-23', '2026-03-23 20:01:21'),
(30, 34, 104, 14400.00, 'Order Completed', '2026-04-12', '2026-04-12 14:58:37');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `fulfillment` varchar(20) NOT NULL DEFAULT 'pickup',
  `delivery_address` text DEFAULT NULL,
  `date_needed` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `decline_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `completed_by` varchar(50) DEFAULT NULL,
  `stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
  `outflow_logged` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_provider` varchar(50) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(30) NOT NULL DEFAULT 'cod',
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `payment_reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `farmer_id`, `fulfillment`, `delivery_address`, `date_needed`, `status`, `decline_reason`, `created_at`, `completed_at`, `completed_by`, `stock_deducted`, `outflow_logged`, `delivery_provider`, `delivery_fee`, `payment_method`, `payment_status`, `payment_reference`) VALUES
(94, 37, 34, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'completed', NULL, '2026-02-20 10:17:54', '2026-02-20 10:18:17', 'buyer', 1, 1, 'Lalamove', 80.00, 'paymongo', 'paid', NULL),
(95, 37, 34, 'pickup', NULL, NULL, 'completed', NULL, '2026-02-20 10:19:02', '2026-03-04 19:24:37', 'buyer', 1, 1, NULL, 0.00, 'cod', 'unpaid', NULL),
(96, 37, 34, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'completed', NULL, '2026-03-04 19:21:09', '2026-03-04 19:23:44', 'buyer', 1, 1, 'Lalamove', 80.00, 'paymongo', 'paid', NULL),
(97, 37, 34, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'awaiting', NULL, '2026-03-20 02:35:41', NULL, NULL, 0, 0, 'Lalamove', 80.00, 'paymongo', 'paid', NULL),
(98, 37, 34, 'deliver', 'WORK - 156 PUROK 4, ALCALA, CAGAYAN 3507', NULL, 'cancelled', NULL, '2026-03-20 02:39:03', NULL, NULL, 0, 0, 'Lalamove', 0.00, 'cod', 'unpaid', NULL),
(99, 37, 34, 'pickup', NULL, NULL, 'cancelled', NULL, '2026-03-20 05:31:10', NULL, NULL, 0, 0, NULL, 0.00, 'cod', 'unpaid', NULL),
(100, 37, 34, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'completed', NULL, '2026-03-23 19:57:15', '2026-03-23 20:01:21', 'buyer', 1, 1, 'Lalamove', 80.00, 'paymongo', 'paid', NULL),
(101, 37, 34, 'pickup', NULL, NULL, 'awaiting', NULL, '2026-03-23 20:18:20', NULL, NULL, 1, 0, NULL, 0.00, 'cod', 'unpaid', NULL),
(102, 37, 34, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'awaiting', NULL, '2026-03-24 05:58:31', NULL, NULL, 0, 0, 'Lalamove', 80.00, 'paymongo', 'paid', NULL),
(103, 37, 2, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'cancelled', NULL, '2026-04-09 08:04:29', NULL, NULL, 0, 0, 'Lalamove', 80.00, 'paymongo', 'paid', NULL),
(104, 37, 34, 'pickup', '', '0000-00-00', 'completed', NULL, '2026-04-12 14:48:32', '2026-04-12 14:58:37', 'buyer', 1, 1, '', 0.00, 'paymongo', 'paid', NULL),
(105, 37, 34, 'pickup', NULL, NULL, 'awaiting', NULL, '2026-04-12 15:01:56', NULL, NULL, 1, 0, NULL, 0.00, 'cod', 'unpaid', NULL),
(106, 37, 34, 'deliver', 'HOME - #157 PUROK 4, CALUMPIT, BULACAN 3003', '0000-00-00', 'awaiting', NULL, '2026-04-12 15:02:24', NULL, NULL, 0, 0, 'Lalamove', 80.00, 'paymongo', 'paid', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `qty`, `price`, `unit`) VALUES
(100, 94, 22, 'US 88 Long Grain', 10, 1500.00, 'sack'),
(101, 94, 17, '436 Variety', 10, 1200.00, 'sack'),
(102, 95, 22, 'US 88 Long Grain', 10, 1500.00, 'sack'),
(103, 95, 17, '436 Variety', 10, 1200.00, 'sack'),
(104, 96, 23, 'RC 534', 10, 1200.00, 'sack'),
(105, 97, 23, 'RC 534', 10, 1200.00, 'sack'),
(106, 98, 17, '436 Variety', 10, 1200.00, 'sack'),
(107, 99, 23, 'RC 534', 10, 1200.00, 'sack'),
(108, 100, 22, 'US 88 Long Grain', 50, 1500.00, 'sack'),
(109, 101, 22, 'US 88 Long Grain', 1, 1500.00, 'sack'),
(110, 102, 22, 'US 88 Long Grain', 10, 1500.00, 'sack'),
(111, 103, 11, 'Hybrid Rice', 10, 1800.00, 'sack'),
(112, 104, 17, '436 Variety', 12, 1200.00, 'sack'),
(113, 105, 17, '436 Variety', 6, 1200.00, 'sack'),
(114, 106, 17, '436 Variety', 10, 1200.00, 'sack');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_intent_id` varchar(100) DEFAULT NULL,
  `payment_link_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_requests`
--

CREATE TABLE `product_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rice_variety` varchar(100) NOT NULL,
  `price_per_sack` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `status` enum('active','pending','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_requests`
--

INSERT INTO `product_requests` (`id`, `user_id`, `rice_variety`, `price_per_sack`, `product_image`, `status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(28, 34, 'RC 436', 1400.00, 'products/1776138826_69ddba4a8e602.png', 'active', NULL, '2026-04-14 05:32:38', '2026-04-14 05:32:38'),
(29, 34, 'Hybrid Rice', 5200.00, 'products/1776141598_69ddc51e4210a.png', 'active', NULL, '2026-04-14 05:39:13', '2026-04-14 05:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `rice_products`
--

CREATE TABLE `rice_products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rice_products`
--

INSERT INTO `rice_products` (`id`, `name`, `price`, `image`) VALUES
(8, 'RC 436', 1400.00, 'products/1776138826_69ddba4a8e602.png'),
(9, 'Hybrid Rice', 5250.00, 'products/1776141598_69ddc51e4210a.png'),
(10, 'RC 534', 1400.00, 'products/1776141621_69ddc535d86d2.png'),
(11, 'RC 480', 1400.00, 'products/1776141645_69ddc54db15c3.png');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `provider` varchar(30) DEFAULT NULL,
  `tracking_ref` varchar(100) DEFAULT NULL,
  `tracking_url` text DEFAULT NULL,
  `status` enum('preparing','out_for_delivery','delivered') NOT NULL DEFAULT 'preparing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `order_id`, `provider`, `tracking_ref`, `tracking_url`, `status`, `created_at`, `updated_at`) VALUES
(87, 94, NULL, NULL, NULL, 'delivered', '2026-02-20 10:18:04', '2026-02-20 10:18:07'),
(90, 96, 'Lalamove', 'PLM-000096', '', 'delivered', '2026-03-04 19:21:46', '2026-03-04 19:23:16'),
(94, 95, NULL, NULL, NULL, 'preparing', '2026-03-04 19:24:18', '2026-03-04 19:24:18'),
(95, 97, 'Lalamove', 'PLM-000097', '', 'delivered', '2026-03-20 02:45:16', '2026-03-20 02:46:42'),
(99, 100, 'Lalamove', 'PLM-000100', '', 'delivered', '2026-03-23 19:58:56', '2026-03-23 20:00:52'),
(103, 101, NULL, NULL, NULL, 'preparing', '2026-03-24 04:01:40', '2026-03-24 04:01:40'),
(104, 102, 'Lalamove', 'PLM-000102', '', 'delivered', '2026-03-24 05:59:14', '2026-04-12 15:02:49'),
(107, 104, NULL, NULL, NULL, 'preparing', '2026-04-12 14:58:14', '2026-04-12 14:58:14'),
(109, 106, 'Lalamove', 'PLM-000106', '', 'delivered', '2026-04-12 15:03:11', '2026-05-06 12:27:26'),
(113, 105, NULL, NULL, NULL, 'preparing', '2026-05-06 12:27:27', '2026-05-06 12:27:27');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_events`
--

CREATE TABLE `shipment_events` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `status` enum('preparing','out_for_delivery','delivered') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `event_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_events`
--

INSERT INTO `shipment_events` (`id`, `shipment_id`, `status`, `note`, `event_time`) VALUES
(87, 87, 'preparing', 'Order is being prepared', '2026-02-20 10:18:04'),
(88, 87, 'out_for_delivery', 'Out for delivery', '2026-02-20 10:18:07'),
(89, 87, 'delivered', 'Delivered to buyer', '2026-02-20 10:18:07'),
(90, 90, 'preparing', 'Order is being prepared', '2026-03-04 19:21:46'),
(91, 90, 'preparing', 'Tracking saved: Lalamove (PLM-000096)', '2026-03-04 19:22:48'),
(92, 90, 'out_for_delivery', 'Out for delivery', '2026-03-04 19:23:06'),
(93, 90, 'delivered', 'Delivered to buyer', '2026-03-04 19:23:16'),
(94, 94, 'preparing', 'Ready for pickup (awaiting buyer confirmation)', '2026-03-04 19:24:18'),
(95, 95, 'preparing', 'Order is being prepared', '2026-03-20 02:45:16'),
(96, 95, 'preparing', 'Tracking saved: Lalamove (PLM-000097)', '2026-03-20 02:45:54'),
(97, 95, 'out_for_delivery', 'Out for delivery', '2026-03-20 02:46:22'),
(98, 95, 'delivered', 'Delivered to buyer', '2026-03-20 02:46:42'),
(99, 99, 'preparing', 'Order is being prepared', '2026-03-23 19:58:56'),
(100, 99, 'preparing', 'Tracking saved: Lalamove (PLM-000100)', '2026-03-23 19:59:08'),
(101, 99, 'out_for_delivery', 'Out for delivery', '2026-03-23 20:00:12'),
(102, 99, 'delivered', 'Delivered to buyer', '2026-03-23 20:00:52'),
(103, 103, 'preparing', 'Ready for pickup (awaiting buyer confirmation)', '2026-03-24 04:01:40'),
(104, 104, 'preparing', 'Order is being prepared', '2026-03-24 05:59:14'),
(105, 104, 'preparing', 'Tracking saved: Lalamove (PLM-000102)', '2026-03-24 05:59:24'),
(106, 104, 'out_for_delivery', 'Out for delivery', '2026-03-24 05:59:33'),
(107, 107, 'preparing', 'Ready for pickup (awaiting buyer confirmation)', '2026-04-12 14:58:14'),
(108, 104, 'delivered', 'Delivered to buyer', '2026-04-12 15:02:49'),
(109, 109, 'preparing', 'Order is being prepared', '2026-04-12 15:03:11'),
(110, 109, 'preparing', 'Tracking saved: Lalamove (PLM-000106)', '2026-04-12 15:03:23'),
(111, 109, 'out_for_delivery', 'Out for delivery', '2026-04-12 15:03:41'),
(112, 109, 'delivered', 'Delivered to buyer', '2026-05-06 12:27:26'),
(113, 113, 'preparing', 'Ready for pickup (awaiting buyer confirmation)', '2026-05-06 12:27:27');

-- --------------------------------------------------------

--
-- Table structure for table `stock_outflows`
--

CREATE TABLE `stock_outflows` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('admin','buyer','farmer') NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','disabled','archived','deactivated') DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `deactivated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `username`, `email`, `password`, `created_at`, `status`, `email_verified`, `deactivated_at`) VALUES
(2, 'farmer', 'farmerJe', NULL, '$2y$10$ae/n/LRYw9IrYjDfRvbkA.As2HuU5gHH2tfAevwzQEZuhdMGWKCOO', '2025-11-19 13:12:39', 'active', 0, NULL),
(3, 'buyer', 'buyerJe', NULL, '$2y$10$S4d1kUZw6Rfy7n00Yjc5duUlGZ/4yCg32tqUkKacTr1IaRDhOzI/.', '2025-11-19 13:14:28', 'active', 0, NULL),
(34, 'farmer', 'Andrea_Brillantes', 'buendiajermaine@gmail.com', '$2y$10$AeiZpI2gc0TKCdnHoHFkU.M2Lm5d6KducROLjjTj7Cpby0A3S/5JS', '2026-02-11 11:07:47', 'active', 1, NULL),
(35, 'admin', 'Rosee', NULL, '$2y$10$7ExMqCAOMeJIzAr/1gQhxuZyVMU6vo3XsHSN9DKFiUG0XJ0ucdpca', '2026-02-14 13:13:48', 'active', 0, NULL),
(36, 'admin', 'notJe', NULL, '$2y$10$JGcWZNrSV2tWBeARobr.seUDUsdHWnjhFyaKPhWpSSSzJzkBIL2sa', '2026-02-14 13:16:18', 'active', 0, NULL),
(37, 'buyer', 'Mosh', 'capstonehosting3@gmail.com', '$2y$10$h356WxtPx.A2sqg41ukWwuVdFvnRHitHlV8DkSTp0vKM2FFUj4sxW', '2026-02-16 10:37:36', 'active', 1, NULL),
(43, 'farmer', 'eta', 'buendiavicky74@gmail.com', '$2y$10$w4a/bUpmsDlFOy4ATk38/er6owDY4jiZOoeFGwsLyprL89lZgHrYy', '2026-04-16 06:49:12', 'active', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method` enum('email') NOT NULL DEFAULT 'email',
  `destination` varchar(191) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_verifications`
--

INSERT INTO `user_verifications` (`id`, `user_id`, `method`, `destination`, `code_hash`, `expires_at`, `attempts`, `created_at`) VALUES
(25, 43, 'email', 'buendiavicky74@gmail.com', '$2y$10$3FhQv9JqaXb0tYXUNKlLtuxzxvlqswuGTeyVBPQzWCip.Nus/ymK6', '2026-04-16 06:59:12', 0, '2026-04-16 06:49:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_details`
--
ALTER TABLE `admin_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_admin_user` (`user_id`);

--
-- Indexes for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `buyer_details`
--
ALTER TABLE `buyer_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_buyer_details_user_id` (`user_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_buyer` (`buyer_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cart_product` (`cart_id`,`product_id`);

--
-- Indexes for table `delivery_fee_rules`
--
ALTER TABLE `delivery_fee_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `farmers_list`
--
ALTER TABLE `farmers_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `farmer_details`
--
ALTER TABLE `farmer_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `farmer_plans`
--
ALTER TABLE `farmer_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farmer_year_quarter` (`farmer_id`,`plan_year`,`quarter`),
  ADD KEY `idx_farmer_plans_farmer_year_season` (`farmer_id`,`plan_year`,`season`);

--
-- Indexes for table `farmer_products`
--
ALTER TABLE `farmer_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_farmer_product` (`farmer_id`,`product_name`,`unit`);

--
-- Indexes for table `farmer_transactions`
--
ALTER TABLE `farmer_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `marketplace_transactions`
--
ALTER TABLE `marketplace_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_farmer_order` (`farmer_id`,`order_id`),
  ADD KEY `idx_farmer_date` (`farmer_id`,`txn_date`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product_requests`
--
ALTER TABLE `product_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_farmer_variety` (`user_id`,`rice_variety`);

--
-- Indexes for table `rice_products`
--
ALTER TABLE `rice_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shipments_order` (`order_id`);

--
-- Indexes for table `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shipment_events_shipment_time` (`shipment_id`,`event_time`);

--
-- Indexes for table `stock_outflows`
--
ALTER TABLE `stock_outflows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `unique_users_email` (`email`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_latest` (`user_id`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_details`
--
ALTER TABLE `admin_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `buyer_details`
--
ALTER TABLE `buyer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `delivery_fee_rules`
--
ALTER TABLE `delivery_fee_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farmers_list`
--
ALTER TABLE `farmers_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `farmer_details`
--
ALTER TABLE `farmer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `farmer_plans`
--
ALTER TABLE `farmer_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `farmer_products`
--
ALTER TABLE `farmer_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `farmer_transactions`
--
ALTER TABLE `farmer_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `marketplace_transactions`
--
ALTER TABLE `marketplace_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_requests`
--
ALTER TABLE `product_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `rice_products`
--
ALTER TABLE `rice_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `shipment_events`
--
ALTER TABLE `shipment_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `stock_outflows`
--
ALTER TABLE `stock_outflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_details`
--
ALTER TABLE `admin_details`
  ADD CONSTRAINT `fk_admin_details_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_details`
--
ALTER TABLE `buyer_details`
  ADD CONSTRAINT `buyer_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_details`
--
ALTER TABLE `farmer_details`
  ADD CONSTRAINT `farmer_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_products`
--
ALTER TABLE `farmer_products`
  ADD CONSTRAINT `farmer_products_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_transactions`
--
ALTER TABLE `farmer_transactions`
  ADD CONSTRAINT `farmer_transactions_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_transactions`
--
ALTER TABLE `marketplace_transactions`
  ADD CONSTRAINT `fk_market_txn_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_market_txn_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_requests`
--
ALTER TABLE `product_requests`
  ADD CONSTRAINT `product_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD CONSTRAINT `fk_shipment_events_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_outflows`
--
ALTER TABLE `stock_outflows`
  ADD CONSTRAINT `stock_outflows_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `farmer_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_outflows_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
