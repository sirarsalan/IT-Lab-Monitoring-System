-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 08:34 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `network_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `pc_location`
--

CREATE TABLE `pc_location` (
  `id` int(11) NOT NULL,
  `pc_name` varchar(100) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `row_no` varchar(50) DEFAULT NULL,
  `table_no` varchar(50) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `motherboard_serial` varchar(100) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `floor_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pc_location`
--

INSERT INTO `pc_location` (`id`, `pc_name`, `room`, `row_no`, `table_no`, `last_updated`, `ip_address`, `motherboard_serial`, `room_no`, `room_name`, `floor_no`, `created_at`) VALUES
(1, 'Server Room', NULL, 'Row1', 'table6', NULL, '', '', 'No PC Enter', 'Office', 'Ground', '2026-05-07 03:12:24'),
(11, 'ADMISSION-02 (Baber)', 'N/A', 'Row1', 'Table4', '2026-05-09 12:06:15', '100.2.1.93', '8ED39D80-D349-11DE-A954-D0DDF33D88DA', 'Admission (Baber)', 'Office', 'Ground', '2026-05-09 07:06:15'),
(12, 'ADMISSION-M', 'Admission office', 'Row1', 'Table1', '2026-05-12 08:17:27', '100.2.1.90', '5277C584-94A6-11E6-9C43-BC0000500000', 'Admission (MAM)', 'Office', 'Ground', '2026-05-12 03:17:27'),
(13, 'ADMISSION2', 'Admission office', 'Row1', 'Table3', '2026-05-12 08:22:24', '100.2.1.92', '1E81CA80-09E8-11E0-B891-E16B3FE0861A', 'Admission (Misbah)', 'Office', 'Ground', '2026-05-12 03:22:24'),
(14, 'NOSHEEN-STAFF', 'Admission office', 'Row1', 'Table2', '2026-05-12 08:41:27', '100.2.1.91', '2C2C7FB1-BAAF-872C-AAA5-2CBB125A2CEF', 'Admission Bushra', 'Office', 'Ground', '2026-05-12 03:41:27'),
(15, 'RECEPTION', 'Reception office', 'Row1', 'Table1', '2026-05-12 08:47:45', '100.2.1.95', '60795900-ACF3-11DF-9172-E7CE529CA772', 'RECEPTION', 'Office', 'Ground', '2026-05-12 03:47:45'),
(16, 'JIBRAN', 'Account', 'Row1', 'Table1', '2026-05-12 08:51:16', '100.2.2.7', '8BFEC980-45DB-11E0-BEC5-D487DBCBCE47', 'Jibran', 'Office', 'Ground', '2026-05-12 03:51:16'),
(17, 'FINE-SYSTEM', 'office', 'Row1', 'Table2', '2026-05-12 08:54:27', '100.2.2.3', '506EE313-0942-FDC7-81C7-C476FA22EA84', 'Account (Fine-System)', 'Office', 'Ground', '2026-05-12 03:54:27'),
(18, 'RUKHSAR', 'Office', 'Row1', 'Table1', '2026-05-12 09:18:07', '100.2.2.2', '7CE4A417-DF15-D7E4-12D9-E50B2209E53F', 'Guard Room ', 'Office ', 'Ground', '2026-05-12 04:18:07'),
(19, 'ATTENDANCE-STD', 'Office', 'Row1', 'Table1', '2026-05-12 09:29:36', '100.2.2.62', '4134AEF4-E4C2-11E3-A40A-7C75AC571C00', 'Attendance', 'Office', 'Ground', '2026-05-12 04:29:36'),
(20, 'GMD2', 'Director Room', 'Row1', 'Table1', '2026-05-12 09:40:56', '169.254.201.237', 'D36968A0-05C0-11E3-84CF-383A50271800', 'Director Room', 'Office', 'First', '2026-05-12 04:40:56'),
(21, 'PRINCIPAL02', 'Office', 'Row1', 'Table1', '2026-05-12 10:03:59', '100.2.2.10', 'D89EB1D1-D7D0-339F-DA0F-9F67020B9F9B', 'Principal', 'Office', 'First', '2026-05-12 05:03:59'),
(22, 'SUPPORT1', 'Office', 'Row 1', 'Table1', '2026-05-12 10:09:34', '100.2.1.114', '4C4C4544-0047-5010-8030-C8C04F345631', 'Server Room', 'Office ', 'Ground', '2026-05-12 05:09:34'),
(23, 'SUPPORT02', 'Office', 'Row 1', 'Table 2', '2026-05-12 10:10:24', '100.2.1.113', '292F8000-42EF-11DF-A27F-96049C4568A1', 'Server Room', 'Office ', 'Ground', '2026-05-12 05:10:24'),
(24, 'RND', 'RND', 'Row 1', 'Table 1', '2026-05-12 11:55:23', '100.2.1.21', 'B6479252-AF9C-11DF-BBD8-DE61DF8D1CC1', 'RND', 'RND', 'Ground', '2026-05-12 06:55:23'),
(25, '', NULL, 'Row1', 'Table2', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 06:56:40'),
(26, 'RND-03', 'RND', 'Row 1', 'Table 3', '2026-05-12 11:57:44', '100.2.1.23', '4C4C4544-0037-5110-8054-C3C04F475331', 'RND', 'RND01', 'Ground', '2026-05-12 06:57:44'),
(27, 'RND04', 'RND', 'Row 2', 'Table 4', '2026-05-12 11:58:33', '100.2.1.24', 'D7642100-ACF6-11DF-8461-95C1889E0AE9', 'RND', 'RND', 'Ground', '2026-05-12 06:58:33'),
(28, '', NULL, 'Row1', 'Table5', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 06:59:15'),
(29, 'RND06', 'RND', 'Row 2', 'Table 6', '2026-05-12 12:02:40', '100.2.1.22', 'old system', 'RND', 'RND', 'Ground', '2026-05-12 07:02:40'),
(30, '', NULL, 'Row3', 'Table 7', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:04:58'),
(31, 'RND08', 'RND', 'Row 3', 'Table 8', '2026-05-12 12:06:04', '100.2.1.28', '4C4C4544-004D-5910-804D-CAC04F465131', 'RND', 'RND08', 'Ground', '2026-05-12 07:06:04'),
(32, '', NULL, 'Row3', 'Table 9', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:07:07'),
(33, 'RND10', 'RND', 'Row 1', 'RND10', '2026-05-12 12:12:04', '100.2.1.30', 'EE540880-59B9-11DE-A017-B0AB535D9499', 'N/A', 'RND', 'Ground', '2026-05-12 07:12:04'),
(34, '', NULL, 'Row1', 'Table 11', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:13:11'),
(35, 'RND12', 'RND', 'Row 1', 'TABLE 1', '2026-05-12 12:14:15', '100.2.1.32', 'AC0FDE6D-046D-0710-791A-103B0436107C', 'N/A', 'RND', 'Ground', '2026-05-12 07:14:15'),
(36, 'RND13', 'RND', 'Row 2', 'TABLE 13', '2026-05-12 12:15:23', '100.2.1.33', '09BD1F80-9644-11E5-A731-5065F3499286', 'N/A', 'RND', 'Ground', '2026-05-12 07:15:23'),
(37, 'RND14', 'RND', 'Row 2', 'table 14', '2026-05-12 12:22:19', '100.2.1.34', '7BE6B980-2B2B-11DE-8CA6-ACA07B5E31F5', 'N/A', 'RND', 'Ground', '2026-05-12 07:22:19'),
(38, 'RND15', 'RND', 'Row 2', 'TABLE 15', '2026-05-12 12:24:24', '100.2.1.35', '32C56C58-05BD-11E3-82C2-B5045C0E1400', 'RND', 'RND', 'Ground', '2026-05-12 07:24:24'),
(39, 'JHANZAIB', 'RND', 'Row 4', '19', '2026-05-12 12:27:18', '192.168.100.82', '4C4C4544-0032-5410-804E-C7C04F425A31', 'RND', 'RND', 'Ground', '2026-05-12 07:27:18'),
(40, '', NULL, 'Row3', 'Table 16', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:30:58'),
(41, '', NULL, 'Row3', 'Table 17', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:33:53'),
(42, '', NULL, 'Row3', 'Table 18', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:34:08'),
(43, '', NULL, 'Row4', 'Table 19', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:34:41'),
(44, '', NULL, 'Row4', 'Table 20', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:35:03'),
(45, '', NULL, 'Row4', 'Table 21', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:35:24'),
(46, '', NULL, 'Row4', 'Table 22', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:35:40'),
(47, '', NULL, 'Row4', 'Table 23', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:35:57'),
(48, '', NULL, 'Row4', 'Table 24', NULL, NULL, NULL, 'N/A', 'RND', 'Ground', '2026-05-12 07:36:14'),
(49, '', NULL, 'Row1', 'Table1', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 07:49:17'),
(50, 'VLAB01', 'VLAB', 'Row1', 'Table01', '2026-05-12 12:50:45', '100.2.1.40', '6FAC5196-7795-CAAC-9F7B-AD0B2509AD3F', 'VLAB', 'Vlab', 'Ground', '2026-05-12 07:50:45'),
(51, 'VLAB02', 'VLAB', 'Row1', 'Table02', '2026-05-12 12:51:54', '100.2.1.41', 'DE9BF4A0-1AA0-469C-A636-9C7A375D9CAE', 'VLAB', 'Vlab', 'Ground', '2026-05-12 07:51:54'),
(52, 'VLAB03', 'VLAB', 'Row 1', 'table 03', '2026-05-12 12:52:52', '100.2.1.42', '738D8880-D791-11E0-B232-B7297F3CC369', 'VLAB', 'VLAB', 'Ground', '2026-05-12 07:52:52'),
(53, 'VLAB04', 'VLAB', 'Row1', 'table 04', '2026-05-12 12:54:27', '100.2.1.43', '877D4E43-7442-E27D-4BA1-7E1634057E4A', 'VLAB', 'VLAB', 'Ground', '2026-05-12 07:54:27'),
(54, '', NULL, 'Row1', 'Table 5', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 07:55:53'),
(55, 'VLAB006', 'VLAB', 'Row 1', 'table 06', '2026-05-12 12:56:58', '100.2.1.45', 'E72F7FA0-BC9E-4F30-99DD-3083132F30B7', 'VLAB', 'VLAB', 'Ground', '2026-05-12 07:56:58'),
(56, 'VLAB07', 'VLAB', 'Row 1', 'table 07', '2026-05-12 12:57:39', '100.2.1.46', '1F39CC35-881A-BD3A-A00C-3AB80F2F1DB4', 'VLAB', 'VLAB', 'Ground', '2026-05-12 07:57:39'),
(57, 'VLAB08', 'VLAB', 'Row 1', 'table 08', '2026-05-12 12:58:51', '100.2.1.47', 'N/A', 'VLAB', 'Vlab', 'Ground', '2026-05-12 07:58:51'),
(58, 'VLAB09', 'VLAB', 'Row 1', 'table 09', '2026-05-12 12:59:55', '100.2.1.48', 'C205D630-912C-11E6-9C43-BC00009A0000', 'VLAB', 'VLAB', 'Ground', '2026-05-12 07:59:55'),
(59, 'VLAB10', 'VLAB', 'Row 1', 'table 10', '2026-05-12 13:00:55', '100.2.1.49', 'FA852200-EF2D-11DF-A0D5-9310A427E477', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:00:55'),
(60, 'VLAB11', 'VLAB', 'Row 2', 'table 11', '2026-05-12 13:01:51', '100.2.1.50', 'EE7502D1-3FCF-5676-CA4C-768A3B3C76BE', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:01:51'),
(61, 'VLAB12-PC', 'VLAB', 'Row 2', 'table 12', '2026-05-12 13:02:46', '100.2.1.51', '54FEC02E-3210-7C34-89D7-8A93FB556FE5', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:02:46'),
(62, 'VLAB13', 'VLAB', 'Row 2', 'table 13', '2026-05-12 13:03:39', '100.2.1.189', '4404CE80-175C-11DF-B983-B57B994041F7', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:03:39'),
(63, '', NULL, 'Row2', 'Table 14', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:04:41'),
(65, 'VLAB16', 'VLAB', 'Row 2', 'table 16', '2026-05-12 13:06:15', '100.2.1.55', '3B6EE8B0-0B27-11E3-B4AC-770CEFD61400', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:06:15'),
(66, 'VLAB17', 'VLAB', 'Row 2', 'table 17', '2026-05-12 13:07:10', '100.2.1.56', 'E8F01D80-7626-11E0-B3A4-D995FF7F6A6A', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:07:10'),
(67, 'VLAB18', 'VLAB', 'Row 2', 'table 18', '2026-05-12 13:08:08', '100.2.1.157', '650B6755-E7AA-11E5-BCF3-8030F502A0D3', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:08:08'),
(68, 'VLAB19', 'VLAB', 'Row 2', 'table 19', '2026-05-12 13:09:05', '100.2.1.58', '506E83CF-0401-F7B3-A886-730C3F618F2D', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:09:05'),
(69, '', NULL, 'Row2', 'Table 20', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:10:25'),
(70, 'VLAB21', 'VLAB', 'Row3', 'table 21', '2026-05-12 13:11:05', '192.168.1.25', '117FD8AC-E7B3-11E3-8819-5E540BFD1400', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:11:05'),
(71, '', NULL, 'Row3', 'Table 22', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:11:44'),
(72, 'VLAB23', 'VLAB', 'Row3', 'table 23', '2026-05-12 13:13:26', '100.2.1.62', '0B54C0C8-090C-11E0-BBD8-C0AFD6B778AC', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:13:26'),
(73, 'VLAB24', 'VLAB', 'Row3', 'table 24', '2026-05-12 13:14:15', '100.2.1.63', 'BBB34780-AD03-11DF-9832-EE33FF45A413', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:14:15'),
(74, 'VLAB-22', 'VLAB', 'Row3', 'table 22', '2026-05-12 13:15:10', '100.2.3.62', '27F631EB-D848-11E6-9C43-BC00007C0000', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:15:10'),
(75, 'VLAB25', 'VLAB', 'Row3', 'table 25', '2026-05-12 13:16:05', '100.2.1.64', '106DCBC8-C7F6-7A25-A590-69A9AE17DE8C', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:16:05'),
(77, 'VLAB27', 'VLAB', 'Row3', 'table 27', '2026-05-12 13:28:21', '100.2.1.66', 'ED94B654-FF11-11E2-9D71-D92CA7941700', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:28:21'),
(79, 'VLAB-29', 'VLAB', 'Row3', 'table 29', '2026-05-12 13:30:02', '100.2.1.68', '6DAAA300-8E42-11E0-97B7-E03E2B1BBC91', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:30:02'),
(80, 'VLAB30', 'VLAB', 'Row3', 'table 30', '2026-05-12 13:38:59', '100.2.1.69', '92B72C00-05B3-11E3-ACB8-611F6A361500', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:38:59'),
(81, 'VLAB31', 'VLAB', 'Row4', 'table 31', '2026-05-12 13:39:44', '100.2.1.70', 'D18DDE7A-3620-8C3D-AB50-0BB8949FAF50', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:39:44'),
(82, 'VLAB32', 'VLAB', 'Row4', 'table 32', '2026-05-12 13:43:02', '100.2.1.171', '34D6B80F-02EB-3E3C-8F30-7169B8831978', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:43:02'),
(83, 'VLAB33', 'VLAB', 'Row4', 'table 33', '2026-05-12 13:43:41', '100.2.1.72', 'C4230BFD-981C-11DE-BBD8-81EB2AD50024', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:43:41'),
(84, 'VLAB34', 'VLAB', 'Row4', 'table 34', '2026-05-12 13:44:38', '100.2.1.73', '03F8BA00-6ED0-11E0-9CB0-E2D62DEDFD07', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:44:38'),
(85, 'VLAB-35', 'VLAB', 'Row4', 'table 35', '2026-05-12 13:50:00', '100.2.1.71', '843976BA-CB68-E93E-B00B-0E4BF6C2DB16', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:50:00'),
(86, '', NULL, 'Row4', 'Table 36', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:51:10'),
(87, 'VLAB-37', 'VLAB', 'Row4', 'table 37', '2026-05-12 13:51:45', '100.2.6.4', '2B6B5480-0823-11E5-9B50-ECB1D7395BB8', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:51:45'),
(88, 'VLAB-38', 'VLAB', 'Row4', 'table 38', '2026-05-12 13:52:20', '192.168.1.220', 'BD2F8D80-0834-11E5-9294-ECB1D732A9CB', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:52:20'),
(89, '', NULL, 'Row4', 'Table 39', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:53:23'),
(90, '', NULL, 'Row 5', 'Table 41', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:55:03'),
(91, '', NULL, 'Row 5', 'Table 42', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:55:47'),
(92, '', NULL, 'Row 5', 'Table 43', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:56:05'),
(93, 'VLAB44', 'VLAB', 'Row 5', 'table 44', '2026-05-12 13:56:50', '100.2.1.44', 'F2F6D69A-099A-5AF7-A71E-F78E1C5CF7C2', 'VLAB', 'VLAB', 'Ground', '2026-05-12 08:56:50'),
(94, '', NULL, 'Row 5', 'Table 45', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:57:38'),
(95, '', NULL, 'Row 5', 'Table 46', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:57:52'),
(96, '', NULL, 'Row 5', 'Table 47', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:58:05'),
(97, '', NULL, 'Row 5', 'Table 48', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:58:22'),
(98, '', NULL, 'Row 5', 'Table 49', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:58:34'),
(99, '', NULL, 'Row 5', 'Table 50', NULL, NULL, NULL, 'VLAB', 'Vlab', 'Ground', '2026-05-12 08:58:49'),
(100, 'COLL-25', 'VLAB', 'Row5', 'Table 49', '2026-05-21 08:24:12', '100.2.6.25', '3E146680-77C2-11E0-8625-F231016B582E', 'VLAB', 'VLAB', 'Ground', '2026-05-21 03:24:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pc_location`
--
ALTER TABLE `pc_location`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pc` (`motherboard_serial`),
  ADD UNIQUE KEY `unique_mobo` (`motherboard_serial`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pc_location`
--
ALTER TABLE `pc_location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
