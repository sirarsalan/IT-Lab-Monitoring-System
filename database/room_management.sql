-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 08:35 AM
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
-- Table structure for table `room_management`
--

CREATE TABLE `room_management` (
  `id` int(11) NOT NULL,
  `floor_no` varchar(50) NOT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `room_name` varchar(100) NOT NULL,
  `row_number` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `room_management`
--

INSERT INTO `room_management` (`id`, `floor_no`, `room_no`, `room_name`, `row_number`, `table_number`, `created_at`) VALUES
(1, 'Ground', '1', 'Reception', 10, 10, '2026-05-07 03:24:43'),
(2, 'Ground', '2', 'Admission Office', 10, 10, '2026-05-07 03:24:43'),
(3, 'Ground', '3', 'Office', 10, 10, '2026-05-07 03:24:43'),
(4, 'Ground', '4', 'Store', 10, 10, '2026-05-07 03:24:43'),
(5, 'First', '5', 'Class Room', 10, 10, '2026-05-07 03:24:43'),
(6, 'First', '6', 'Class Room', 10, 10, '2026-05-07 03:24:43'),
(7, 'First', '7', 'Acount', 10, 10, '2026-05-07 03:24:43'),
(8, 'First', '8', 'Rnd', 10, 10, '2026-05-07 03:24:43'),
(9, 'Second', '9', 'Vlab', 10, 10, '2026-05-07 03:24:43'),
(10, 'Second', '10', 'Server Room', 10, 10, '2026-05-07 03:24:43'),
(11, 'Second', '11', 'Director Room', 10, 10, '2026-05-07 03:24:43'),
(12, 'Second', '12', 'Office', 10, 10, '2026-05-07 03:24:43'),
(13, 'Third', '13', 'Class Room', 10, 10, '2026-05-07 03:24:43'),
(14, 'Third', '14', 'Class Room', 10, 10, '2026-05-07 03:24:43'),
(15, 'Third', '15', 'Store', 10, 10, '2026-05-07 03:24:43'),
(16, 'Third', '16', 'Office', 10, 10, '2026-05-07 03:24:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `room_management`
--
ALTER TABLE `room_management`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `room_management`
--
ALTER TABLE `room_management`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
