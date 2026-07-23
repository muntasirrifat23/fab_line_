-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2026 at 04:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `knittingdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `knitting_input`
--

CREATE TABLE `knitting_input` (
  `KPID` int(11) NOT NULL,
  `BUDAT` date DEFAULT current_timestamp(),
  `SUPPLIER` varchar(50) DEFAULT NULL,
  `BUYER` varchar(50) DEFAULT NULL,
  `YARN_TYPE` varchar(50) DEFAULT NULL,
  `YARN_COUNT` varchar(50) DEFAULT NULL,
  `MC_DIA` varchar(50) DEFAULT NULL,
  `FINISH_DIA` varchar(50) DEFAULT NULL,
  `FABRICS_TYPE` varchar(50) DEFAULT NULL,
  `BOOKING` varchar(50) DEFAULT NULL,
  `STYLE` varchar(50) DEFAULT NULL,
  `FINISH_GSM` varchar(50) DEFAULT NULL,
  `OPEN_TUBE` varchar(50) DEFAULT NULL,
  `SONO` varchar(50) DEFAULT NULL,
  `SO_ITEM` varchar(50) DEFAULT NULL,
  `KNIT_MATERIAL_CODE` varchar(100) DEFAULT NULL,
  `KNIT_M_DESCRIPTION` varchar(255) DEFAULT NULL,
  `ORDER_TYPE` varchar(50) DEFAULT NULL,
  `KNITTING_TARGET_QTY` varchar(50) DEFAULT NULL,
  `FIRST_SHIPMENT_DATE` varchar(50) DEFAULT NULL,
  `LAST_SHIPMENT_DATE` varchar(50) DEFAULT NULL,
  `KNIT_TNA_START` varchar(50) DEFAULT NULL,
  `KNIT_TNA_END` varchar(50) DEFAULT NULL,
  `LOT_NO` varchar(255) DEFAULT NULL,
  `SL_VDQ` varchar(50) DEFAULT NULL,
  `CBUDAT` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `knitting_input`
--

INSERT INTO `knitting_input` (`KPID`, `BUDAT`, `SUPPLIER`, `BUYER`, `YARN_TYPE`, `YARN_COUNT`, `MC_DIA`, `FINISH_DIA`, `FABRICS_TYPE`, `BOOKING`, `STYLE`, `FINISH_GSM`, `OPEN_TUBE`, `SONO`, `SO_ITEM`, `KNIT_MATERIAL_CODE`, `KNIT_M_DESCRIPTION`, `ORDER_TYPE`, `KNITTING_TARGET_QTY`, `FIRST_SHIPMENT_DATE`, `LAST_SHIPMENT_DATE`, `KNIT_TNA_START`, `KNIT_TNA_END`, `LOT_NO`, `SL_VDQ`, `CBUDAT`) VALUES
(1, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '68', 'SJ', '230043287', '236860', '150', 'O', '4160027259', '10', 'SJ|150|68|O|K26001', 'K|YD|C|100CMBCTN|STONE WASH+PEACOAT', 'ZF01', '2,693.22', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216404\nPEACOAT=26216502', '2.75', '2026-07-18 08:44:53'),
(2, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '68', 'SJ', '230043287', '236860', '150', 'O', '4160027259', '20', 'SJ|150|68|O|K26001', 'K|YD|C|100CMBCTN|STONE WASH+PEACOAT', 'ZF01', '538', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(3, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '72', 'SJ', '230043287', '236860', '150', 'O', '4160027259', '30', 'SJ|150|72|O|K26002', 'K|YD|C|100CMBCTN|STONE WASH+PEACOAT', 'ZF01', '2,400.15', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(4, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '72', 'SJ', '230043287', '236860', '150', 'O', '4160027259', '40', 'SJ|150|72|O|K26002', 'K|YD|C|100CMBCTN|STONE WASH+PEACOAT', 'ZF01', '480', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(5, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '24', 'RB', '230043287', '236860', '260', 'T', '4160027259', '50', 'RB|260|24|T|K26001', 'K|GR|C|95CMBCTN5ELS|1X1', 'ZF01', '178.563', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(6, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '24', 'RB', '230043287', '236860', '260', 'T', '4160027259', '60', 'RB|260|24|T|K26001', 'K|GR|C|95CMBCTN5ELS|1X1', 'ZF01', '35', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(7, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '30', 'SJ', '230043287', '236860', '150', 'T', '4160027259', '70', 'SJ|150|30|T|K26001', 'K|GR|C|100CMBCTN', 'ZF01', '44.641', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(8, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '30', 'SJ', '230043287', '236860', '150', 'T', '4160027259', '80', 'SJ|150|30|T|K26001', 'K|GR|C|100CMBCTN', 'ZF01', '8', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(9, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '72', 'SJ', '230043287', '236860', '180', 'O', '4160027259', '90', 'SJ|180|72|O|K24073', 'K|GR|C|100CMBCTN', 'ZF01', '6,603.72', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53'),
(10, '2026-07-18', 'KARIM', 'HEMA', 'CB CMPT YD', '30/1 CB CMPT YD', '34X24', '72', 'SJ', '230043287', '236860', '180', 'O', '4160027259', '100', 'SJ|180|72|O|K24073', 'K|GR|C|100CMBCTN', 'ZF01', '1,320.00', '2026-11-30', '2026-12-30', '2026-06-01', '2026-10-30', 'S.WASH=26216403\nPEACOAT=26216503', '2.75', '2026-07-18 08:44:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(10) NOT NULL,
  `email` varchar(50) NOT NULL DEFAULT 'sarwar.alam@purbanigroup.com',
  `password` varchar(100) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created`) VALUES
(3, 'mukul', 'mukul@abc.com', 'e10adc3949ba59abbe56e057f20f883e', '0000-00-00 00:00:00'),
(4, 'noman', 'abc@efg.bnm', '81dc9bdb52d04dc20036dbd8313ed055', '0000-00-00 00:00:00'),
(5, 'abuhena', 'abuhena@purbanigroip.com', '8a032c11a781fcb28673b94fc952411e', '2026-06-04 14:01:37'),
(6, 'abcd', 'abcd@purbanigroip.com', 'e10adc3949ba59abbe56e057f20f883e', '0000-00-00 00:00:00'),
(7, 'Hossain', 'hossain_ma12@outlook.com', '4e30bdd431a33f85e96cee64f786599e', '0000-00-00 00:00:00'),
(9, 'admin', 'admin@rifat.com', '7c657cd3d46d8ec8fddb174773d57bb4', '2026-06-24 04:42:13'),
(10, 'kzmmaruf', 'maruf.it@purbanigroup.com', 'a3830f762ec59f5d2c8a621243917324', '0000-00-00 00:00:00'),
(11, '28', 'l28@abc.com', '202cb962ac59075b964b07152d234b70', '2026-04-13 07:02:27'),
(12, 'test1', 'test1@purbanigroup.com', 'e10adc3949ba59abbe56e057f20f883e', '0000-00-00 00:00:00'),
(13, '27', '27@abc.com', '202cb962ac59075b964b07152d234b70', '2026-05-07 10:13:40'),
(14, '26', 'l26@abc.com', 'c8ffe9a587b126f152ed3d89a146b445', '2026-04-05 03:32:55'),
(15, '25', 'l25@abc.com', '250cf8b51c773f3f8dc8b4be867a9a02', '2026-04-05 03:32:22'),
(16, '24', 'l24@abc.com', '0a113ef6b61820daa5611c870ed8d5ee', '2026-04-05 03:30:41'),
(17, '22', 'l22@abc.com', '23b023b22d0bf47626029d5961328028', '2026-04-05 03:28:31'),
(18, '21', 'l21@abc.com', '3c59dc048e8850243be8079a5c74d079', '2026-07-02 09:04:51'),
(19, 'PPQ29', 'sofiqul.islam@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(21, '29', 'l29@abc.com', '15de21c670ae7c3f6f3f1f37029303c9', '2026-04-05 03:25:04'),
(22, '30', 'l30@abc.com', 'caf1a3dfb505ffed0d024130f58c5cfa', '2026-04-05 03:23:32'),
(23, '103', 'f3@abc.com', '202cb962ac59075b964b07152d234b70', '2026-04-05 03:50:56'),
(24, '31', 'l31@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(25, '32', 'l32@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(26, '33', 'l33@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(27, '34', 'l34@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(28, '35', 'l35@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(29, '36', 'l36@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(30, '37', 'l37@abc.com', '202cb962ac59075b964b07152d234b70', '2020-12-21 23:31:14'),
(31, '38', 'l38@abc.com', '202cb962ac59075b964b07152d234b70', '2020-12-21 23:31:28'),
(32, '39', 'l39@abc.com', '202cb962ac59075b964b07152d234b70', '2026-06-11 08:49:14'),
(33, '104', 'f3@abc.com', '202cb962ac59075b964b07152d234b70', '2020-11-01 21:52:52'),
(34, '40', 'l40@abc.com', '202cb962ac59075b964b07152d234b70', '2026-05-09 12:21:02'),
(35, '23', 'l23@abc.com', '310dcbbf4cce62f762a2aaa148d556bd', '2026-04-05 03:29:01'),
(36, '11', '11@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(37, '12', '12@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(38, '13', '13@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(39, '14', '14@abc.com', '7a674153c63cff1ad7f0e261c369ab2c', '2026-04-05 06:07:08'),
(40, '15', '15@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(41, '16', '16@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(42, '17', '17@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(43, '18', '18@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(44, '19', '19@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(45, '20', '20@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(46, '102', '102@abc.com', '202cb962ac59075b964b07152d234b70', '2026-06-07 14:38:16'),
(47, '01', '01@abc.com', 'dc5c7986daef50c1e02ab09b442ee34f', '2026-04-05 04:43:52'),
(48, '02', '02@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(49, '03', '03@abc.com', 'ec8956637a99787bd197eacd77acce5e', '2026-04-05 04:46:15'),
(50, '04', '04@abc.com', '550a141f12de6341fba65b0ad0433500', '2026-04-05 08:45:04'),
(52, '06', '06@abc.com', 'd0970714757783e6cf17b26fb8e2298f', '2026-04-05 04:52:00'),
(53, '07', '07@abc.com', '202cb962ac59075b964b07152d234b70', '2026-04-06 09:35:22'),
(54, '08', '08@abc.com', '46f6341ce05f71416cddc3e42a76102c', '2026-04-05 04:39:39'),
(55, '09', '09@abc.com', 'd81f9c1be2e08964bf9f24b15f0e4900', '2026-04-05 04:37:51'),
(56, '10', '10@abc.com', '202cb962ac59075b964b07152d234b70', '2026-07-01 12:09:36'),
(57, '101', '101@abc.com', 'caf1a3dfb505ffed0d024130f58c5cfa', '2026-04-08 11:22:25'),
(58, '1003', '1003@abc.com', '202cb962ac59075b964b07152d234b70', '2021-05-08 00:08:01'),
(61, 'ppmm', 'sarwar.alam@purbanigroup.com', '81dc9bdb52d04dc20036dbd8313ed055', '2021-06-10 11:14:35'),
(62, 'AKM', 'sarwar.alam@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2021-06-10 11:43:51'),
(63, '05', '05@abc.com', '15de21c670ae7c3f6f3f1f37029303c9', '2026-04-05 04:50:10'),
(64, 'xyz', 'sarwar.alam@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2021-10-02 22:19:49'),
(65, 'PPQ30', 'sarwar.alam@purbanigroup.com', 'ad4b9f20e452f1b2bac8ea193a22f582', '2021-10-03 23:01:25'),
(66, '46', 'sarwar.alam@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2022-01-21 22:14:51'),
(68, 'PPL04', 'yousuf.ppc@purbanigroup.com', '895afaf89099b74daf113ecedde3c17b', '2026-03-12 04:21:03'),
(70, 'PPQ34', 'anis.rahman@purbanigroup.com', '24d6e46a16a8fd89f3d44d92e917e9f2', '2026-04-13 01:54:14'),
(71, 'tv', 'tv@gmail.com', '202cb962ac59075b964b07152d234b70', '2024-08-25 01:17:56'),
(72, 'PPQ28', 'mehedi.pad@purbanigroup.com', '82d24206cbe647f19716a30a28c25765', '2025-04-14 21:36:16'),
(73, 'PPQ70', 'rafiqul.rep@purbanigroup.com', '31891c666eb2cceda062f6e07d388dad', '2026-05-10 09:13:42'),
(74, 'F1', 'F1@purbanigroup.com', '898dc2c947cee718e4afd7dfcb2f1a09', '2026-04-16 05:04:12'),
(75, 'F2', 'F2@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-05-02 22:21:33'),
(76, 'F3', 'F3@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-05-02 22:21:51'),
(77, 'F4', 'F4@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2026-04-07 07:06:30'),
(78, 'PPQ71', 'ppq71@purbanigroup.com', 'f899139df5e1059396431415e770c6dd', '2026-04-24 06:28:42'),
(79, 'PPQ57', 'ppq57@gmail.com', '6320831839ab799ee20bdf86d4f19377', '2026-05-05 09:14:32'),
(80, '41', 'ktl@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-10-12 20:52:33'),
(81, '42', 'ktl1@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-10-12 20:53:05'),
(82, '43', '43@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-10-24 20:51:07'),
(83, '47', '47@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-10-24 20:51:49'),
(84, '48', '48@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-10-24 20:52:10'),
(85, '49', '49@purbanigroup.com', '202cb962ac59075b964b07152d234b70', '2025-10-24 20:52:32'),
(86, '44', '44@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(87, '45', '45@abc.com', '202cb962ac59075b964b07152d234b70', '0000-00-00 00:00:00'),
(89, 'test', 'test@gmail.com', 'ee3dd1c2669f11eabe41d99571167c74', '2026-05-18 09:28:55'),
(90, 'qms01', 'qms01@gmail.com', '202cb962ac59075b964b07152d234b70', '2026-05-23 04:40:46'),
(91, '2026', '2026@purbanigroup.com', 'e10adc3949ba59abbe56e057f20f883e', '2026-07-22 14:20:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `knitting_input`
--
ALTER TABLE `knitting_input`
  ADD PRIMARY KEY (`KPID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `knitting_input`
--
ALTER TABLE `knitting_input`
  MODIFY `KPID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
