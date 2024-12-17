-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2024 at 09:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carasfoodhaven`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_ID` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `ID` int(10) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contactNumber` varchar(11) NOT NULL,
  `address` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`ID`, `firstName`, `lastName`, `email`, `password`, `contactNumber`, `address`) VALUES
(1, 'Riki', 'Ryans', 'cameguing@gmail.com', '$2y$10$.ufbwMYMiay84SgWo4yqLuDUiW6CHpRIRTtiL1A6zrwL9IFIa52qO', '09478824913', '123 East Street'),
(2, 'Sean', 'Paolo', 'seancameguing@gmail.com', '$2y$10$8BOBG5RaCzM3bTLLYgFF9uDIiru0phA4XfMt9IehA8BHmTJEjQXUO', 'N/A', 'N/A'),
(3, 'sean', 'paolo', 'seanpaolo1627@gmail.com', '$2y$10$FmcECxOEy2wW/ff8fFf91O2DX9t/MTgLislOJ3neM8tXeUvhk8K3u', 'N/A', 'N/A'),
(4, 'Juan', 'One', 'juanone3353@gmail.com', '$2y$10$bMQno8Qb.AjYi.prRZOpneIDE7l/zqP8.Wmmgz6g.b6BD3WzKaUAi', 'N/A', 'N/A'),
(5, 'Kein', 'Saligan', 'keinsaligan@gmail.com', '$2y$10$l9sQfleDTijSuGtt89McE.Scy3rc84mBNpM5i6qlUEwhXkEcHDHOO', '12312311', '123 Street'),
(6, 'Kein', 'Daryle', 'eclarinal@gmail.com', '$2y$10$5JHU4Z21eZQUnSpbvmQuOusDAGOamZ6vzQmApeiGGmbysaDZbhYKO', '09478824914', '123 East Street');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `ID` int(25) NOT NULL,
  `firstName` varchar(25) NOT NULL,
  `lastName` varchar(25) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contactNumber` varchar(25) NOT NULL,
  `address` varchar(25) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`ID`, `firstName`, `lastName`, `email`, `password`, `contactNumber`, `address`, `role`) VALUES
(1, 'Kein', 'Saligan', 'admin3@gmail.com', '$2y$10$JUhh.2zCWpMcEVGNC3BO9OZSKJ6N5U4yqPfmJIGj025tGV1WEDJSS', '09478824913', '123 Street', 'Employee'),
(2, 'Sean', 'Cameguing', 'seancameguing@gmail.com', '$2y$10$jFvmYQtpr19tP9geMcpR/OeX7z.ahuoRJBRebjak/Vxz18st2eouy', '123123123123', '123 East Street', 'Employee');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient`
--

CREATE TABLE `ingredient` (
  `ID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ing_category` int(11) DEFAULT NULL,
  `ing_unit` int(11) DEFAULT NULL,
  `low_stock_th` double DEFAULT NULL,
  `medium_stock_th` double DEFAULT NULL,
  `reorder_point` double DEFAULT NULL,
  `total_qty` double DEFAULT NULL,
  `willAutoDeduct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient`
--

INSERT INTO `ingredient` (`ID`, `name`, `ing_category`, `ing_unit`, `low_stock_th`, `medium_stock_th`, `reorder_point`, `total_qty`, `willAutoDeduct`) VALUES
(13, 'Ice Cream', 13, 2, 15, 25, 16, -2, 0),
(14, 'Chicken Breast', 15, 8, 5, 12, 6, -1, 0),
(30, 'test', 15, 8, 5, 12, 6, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ing_category`
--

CREATE TABLE `ing_category` (
  `ID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ing_category`
--

INSERT INTO `ing_category` (`ID`, `name`) VALUES
(13, 'Frozen Product'),
(14, 'Pasta'),
(15, 'Chicken');

-- --------------------------------------------------------

--
-- Table structure for table `ing_qty_consumed`
--

CREATE TABLE `ing_qty_consumed` (
  `ID` int(11) NOT NULL,
  `ingredient` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ing_unit`
--

CREATE TABLE `ing_unit` (
  `ID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ing_unit`
--

INSERT INTO `ing_unit` (`ID`, `name`) VALUES
(2, 'Serving'),
(8, 'Pieces'),
(25, 'Gramss');

-- --------------------------------------------------------

--
-- Table structure for table `key_ingredient`
--

CREATE TABLE `key_ingredient` (
  `ID` varchar(255) NOT NULL,
  `menu_item_ID` int(11) DEFAULT NULL,
  `ingredient` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_category`
--

CREATE TABLE `menu_category` (
  `ID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_category`
--

INSERT INTO `menu_category` (`ID`, `name`) VALUES
(21, 'Desserts'),
(22, 'Platters'),
(30, 'Chicken'),
(31, 'Main Course');

-- --------------------------------------------------------

--
-- Table structure for table `menu_item`
--

CREATE TABLE `menu_item` (
  `ID` int(11) NOT NULL,
  `menu_item_status` enum('Available','Unavailable','Discontinued') NOT NULL,
  `image` blob DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `menu_category` int(11) DEFAULT NULL,
  `price` double NOT NULL,
  `description` text DEFAULT NULL,
  `ingredient` varchar(255) DEFAULT NULL,
  `main_ingredient` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_item`
--

INSERT INTO `menu_item` (`ID`, `menu_item_status`, `image`, `name`, `menu_category`, `price`, `description`, `ingredient`, `main_ingredient`) VALUES
(16, 'Available', 0x75706c6f6164732f7b35334541363534322d384344342d344135362d383945332d4239373131433734353435337d2e706e67, 'Chicken', 22, 123, 'test', NULL, NULL),
(30, '', '', 'Pork', 22, 123, 'undefined', NULL, NULL),
(37, 'Available', NULL, 'Halo halo', 21, 125, 'test', NULL, NULL),
(38, 'Available', NULL, 'Creme de Leche', 21, 235, 'Ice cold', NULL, NULL),
(39, 'Available', NULL, 'Milk Shake', 21, 50, 'Tasty and Creamy!', NULL, NULL),
(40, 'Available', NULL, 'Strawberry Shake', 21, 79, 'Strawberry Flavor!', NULL, NULL),
(41, 'Available', NULL, 'Sisig', 22, 125, 'Tasty', NULL, NULL),
(42, 'Available', NULL, 'Chocolate Shake', 21, 90, 'Chocolate Flavor!', NULL, NULL),
(43, 'Available', NULL, 'Palabok', 31, 500, 'Tasty', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_status`
--

CREATE TABLE `menu_item_status` (
  `ID` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_item_status`
--

INSERT INTO `menu_item_status` (`ID`, `status_name`) VALUES
(1, 'PENDING'),
(2, 'PREPARING'),
(3, 'READY FOR PICKUP');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `ID` int(11) NOT NULL,
  `order_type` enum('DINE_IN','TAKEOUT','DELIVERY') DEFAULT NULL,
  `order_status` enum('PENDING','PREPARING','READY_FOR_PICKUP','COMPLETE','CANCELED') DEFAULT 'PENDING',
  `menu_item_ID` int(11) DEFAULT NULL,
  `date_ordered` datetime DEFAULT current_timestamp(),
  `total_price` double DEFAULT NULL,
  `discount_code` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `customer_ID` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`ID`, `order_type`, `order_status`, `menu_item_ID`, `date_ordered`, `total_price`, `discount_code`, `payment_id`, `customer_ID`) VALUES
(5, 'DELIVERY', 'PREPARING', NULL, '2024-11-24 17:35:59', 371, NULL, '0', 3),
(8, 'TAKEOUT', 'CANCELED', NULL, '2024-11-24 18:37:10', 494, NULL, '0', 3),
(58, 'TAKEOUT', 'PENDING', NULL, '2024-11-25 14:25:58', 125, NULL, 'PAYID-M5CBRCQ1NW2184562748880S', 5),
(59, 'DINE_IN', 'PENDING', NULL, '2024-11-25 14:35:00', 125, NULL, 'PAYID-M5CBVKA5M3641257B184402S', 5),
(60, 'DELIVERY', 'COMPLETE', NULL, '2024-11-25 20:16:12', 371, NULL, 'PAYID-M5CGVHI6K121181D0660345M', 5),
(61, 'TAKEOUT', 'PREPARING', NULL, '2024-11-26 15:00:25', 125, NULL, 'PAYID-M5CXEHA55N53164HB681452P', 5),
(62, 'DINE_IN', 'READY_FOR_PICKUP', NULL, '2024-11-28 14:20:19', 125, NULL, 'PAYID-M5EAXNA4V730982VT8572047', 5),
(68, 'DINE_IN', 'PENDING', NULL, '2024-12-16 17:42:31', 100, NULL, 'pi_X1dPPTR6QmF5xJqCk43jcB8Q', 5),
(71, 'DELIVERY', 'PENDING', NULL, '2024-12-16 22:08:00', 100, NULL, 'PAYID-M5QDISQ3G246333F63447613', 5),
(72, 'DELIVERY', 'PENDING', NULL, '2024-12-16 22:08:50', 100, NULL, 'pi_s1kQ6UwBu2HsXzHt8LutdyjZ', 5);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_ID` int(11) NOT NULL,
  `menu_item_ID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` double NOT NULL,
  `status_id` enum('PENDING','PREPARING','READY_FOR_PICKUP','COMPLETE','CANCELED') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_ID`, `menu_item_ID`, `quantity`, `price_at_time`, `status_id`) VALUES
(5, 16, 1, 123, 'PREPARING'),
(5, 30, 1, 123, 'PREPARING'),
(5, 37, 1, 125, 'PREPARING'),
(8, 16, 1, 123, 'CANCELED'),
(8, 30, 2, 123, 'CANCELED'),
(8, 37, 1, 125, 'CANCELED'),
(58, 37, 1, 125, 'PREPARING'),
(59, 37, 1, 125, 'CANCELED'),
(60, 16, 1, 123, 'PREPARING'),
(60, 30, 1, 123, 'PREPARING'),
(60, 37, 1, 125, 'PREPARING'),
(61, 37, 1, 125, 'PREPARING'),
(62, 37, 1, 125, 'PREPARING'),
(68, 39, 2, 50, NULL),
(71, 39, 2, 50, NULL),
(72, 39, 2, 50, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_status`
--

CREATE TABLE `order_status` (
  `status_id` int(11) NOT NULL,
  `value` enum('PENDING','PREPARING','READY FOR PICKUP','COMPLETE','CANCELED') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `amount` double NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transaction`
--

CREATE TABLE `payment_transaction` (
  `transaction_id` int(11) NOT NULL,
  `transaction_status` varchar(50) NOT NULL,
  `payment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_ID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `ingredient`
--
ALTER TABLE `ingredient`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ing_category` (`ing_category`),
  ADD KEY `ing_unit` (`ing_unit`);

--
-- Indexes for table `ing_category`
--
ALTER TABLE `ing_category`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `ing_qty_consumed`
--
ALTER TABLE `ing_qty_consumed`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ingredient` (`ingredient`);

--
-- Indexes for table `ing_unit`
--
ALTER TABLE `ing_unit`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `key_ingredient`
--
ALTER TABLE `key_ingredient`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ingredient` (`ingredient`),
  ADD KEY `fk_menu_item` (`menu_item_ID`);

--
-- Indexes for table `menu_category`
--
ALTER TABLE `menu_category`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `menu_item`
--
ALTER TABLE `menu_item`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `menu_category` (`menu_category`),
  ADD KEY `ingredient` (`ingredient`),
  ADD KEY `idx_main_ingredient` (`main_ingredient`);

--
-- Indexes for table `menu_item_status`
--
ALTER TABLE `menu_item_status`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_customer_order` (`customer_ID`),
  ADD KEY `order_status` (`order_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_ID`,`menu_item_ID`),
  ADD KEY `menu_item_ID` (`menu_item_ID`),
  ADD KEY `order_items_ibfk_3` (`status_id`);

--
-- Indexes for table `order_status`
--
ALTER TABLE `order_status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `payment_transaction`
--
ALTER TABLE `payment_transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `account_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `ID` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ingredient`
--
ALTER TABLE `ingredient`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `ing_category`
--
ALTER TABLE `ing_category`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `ing_qty_consumed`
--
ALTER TABLE `ing_qty_consumed`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ing_unit`
--
ALTER TABLE `ing_unit`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `menu_category`
--
ALTER TABLE `menu_category`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `menu_item`
--
ALTER TABLE `menu_item`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `menu_item_status`
--
ALTER TABLE `menu_item_status`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `order_status`
--
ALTER TABLE `order_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transaction`
--
ALTER TABLE `payment_transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ingredient`
--
ALTER TABLE `ingredient`
  ADD CONSTRAINT `ingredient_ibfk_1` FOREIGN KEY (`ing_category`) REFERENCES `ing_category` (`ID`),
  ADD CONSTRAINT `ingredient_ibfk_2` FOREIGN KEY (`ing_unit`) REFERENCES `ing_unit` (`ID`);

--
-- Constraints for table `ing_qty_consumed`
--
ALTER TABLE `ing_qty_consumed`
  ADD CONSTRAINT `ing_qty_consumed_ibfk_1` FOREIGN KEY (`ingredient`) REFERENCES `ingredient` (`ID`);

--
-- Constraints for table `key_ingredient`
--
ALTER TABLE `key_ingredient`
  ADD CONSTRAINT `fk_menu_item` FOREIGN KEY (`menu_item_ID`) REFERENCES `menu_item` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `key_ingredient_ibfk_1` FOREIGN KEY (`ingredient`) REFERENCES `ingredient` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu_item`
--
ALTER TABLE `menu_item`
  ADD CONSTRAINT `menu_item_ibfk_1` FOREIGN KEY (`menu_category`) REFERENCES `menu_category` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `fk_customer_order` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`ID`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_ID`) REFERENCES `order` (`ID`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_ID`) REFERENCES `menu_item` (`ID`);

--
-- Constraints for table `payment_transaction`
--
ALTER TABLE `payment_transaction`
  ADD CONSTRAINT `payment_transaction_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
