-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2025 at 02:31 AM
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
-- Database: `sensory`
--

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_assignments`
--

CREATE TABLE `evaluation_assignments` (
  `id` int(11) NOT NULL,
  `request_no` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `triad_no` int(11) DEFAULT NULL,
  `triad_type` varchar(10) DEFAULT NULL,
  `code1` varchar(50) DEFAULT NULL,
  `code2` varchar(50) DEFAULT NULL,
  `code3` varchar(50) DEFAULT NULL,
  `is_submitted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_assignments`
--

INSERT INTO `evaluation_assignments` (`id`, `request_no`, `user_id`, `triad_no`, `triad_type`, `code1`, `code2`, `code3`, `is_submitted`) VALUES
(71, 'R10-042025-SHL-0207', 104, 1, 'ABB', '824', '840', '336', 1),
(72, 'R10-042025-SHL-0207', 105, 2, 'AAB', '538', '342', '459', 1),
(73, 'R10-042025-SHL-0207', 106, 3, 'ABA', '819', '750', '717', 1),
(74, 'R10-042025-SHL-0207', 107, 4, 'BAA', '583', '345', '793', 1),
(75, 'R10-042025-SHL-0207', 108, 5, 'BBA', '461', '732', '305', 1),
(76, 'R10-042025-SHL-0207', 109, 6, 'BAB', '239', '698', '642', 1),
(77, 'R10-042025-SHL-0207', 110, 7, 'ABB', '982', '859', '988', 1),
(78, 'R10-042025-SHL-0207', 111, 8, 'AAB', '385', '002', '220', 1),
(79, 'R10-042025-SHL-0207', 112, 9, 'ABA', '021', '711', '904', 1),
(80, 'R10-042025-SHL-0207', 113, 10, 'BAA', '494', '176', '328', 1),
(81, 'R10-042025-SHL-0207', 114, 11, 'BBA', '303', '444', '154', 1),
(84, 'R10-042025-SHL-0207', 115, 12, 'BAB', '768', '838', '913', 1);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_requests`
--

CREATE TABLE `evaluation_requests` (
  `s_id` int(11) NOT NULL,
  `request_no` varchar(100) NOT NULL,
  `lab_code_no` varchar(100) NOT NULL,
  `sample_code_no` varchar(100) NOT NULL,
  `date_of_computation` datetime DEFAULT current_timestamp(),
  `user_input_codes` text DEFAULT NULL COMMENT 'JSON of A1…O3 values',
  `sample_a_label` varchar(100) DEFAULT NULL,
  `sample_b_label` varchar(100) DEFAULT NULL,
  `samples_container` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`samples_container`)),
  `sensory_type` varchar(50) DEFAULT NULL,
  `status` enum('inactive','active') DEFAULT 'inactive',
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_requests`
--

INSERT INTO `evaluation_requests` (`s_id`, `request_no`, `lab_code_no`, `sample_code_no`, `date_of_computation`, `user_input_codes`, `sample_a_label`, `sample_b_label`, `samples_container`, `sensory_type`, `status`, `user_id`) VALUES
(34, 'R10-042025-SHL-0207', 'SHL-0207', 'food, coded as KS Cookies', '2025-05-06 10:40:34', '[{\"triad_no\":1,\"triad_type\":\"ABB\",\"code1\":\"824\",\"code2\":\"840\",\"code3\":\"336\"},{\"triad_no\":2,\"triad_type\":\"AAB\",\"code1\":\"538\",\"code2\":\"342\",\"code3\":\"459\"},{\"triad_no\":3,\"triad_type\":\"ABA\",\"code1\":\"819\",\"code2\":\"750\",\"code3\":\"717\"},{\"triad_no\":4,\"triad_type\":\"BAA\",\"code1\":\"583\",\"code2\":\"345\",\"code3\":\"793\"},{\"triad_no\":5,\"triad_type\":\"BBA\",\"code1\":\"461\",\"code2\":\"732\",\"code3\":\"305\"},{\"triad_no\":6,\"triad_type\":\"BAB\",\"code1\":\"239\",\"code2\":\"698\",\"code3\":\"642\"},{\"triad_no\":7,\"triad_type\":\"ABB\",\"code1\":\"982\",\"code2\":\"859\",\"code3\":\"988\"},{\"triad_no\":8,\"triad_type\":\"AAB\",\"code1\":\"385\",\"code2\":\"002\",\"code3\":\"220\"},{\"triad_no\":9,\"triad_type\":\"ABA\",\"code1\":\"021\",\"code2\":\"711\",\"code3\":\"904\"},{\"triad_no\":10,\"triad_type\":\"BAA\",\"code1\":\"494\",\"code2\":\"176\",\"code3\":\"328\"},{\"triad_no\":11,\"triad_type\":\"BBA\",\"code1\":\"303\",\"code2\":\"444\",\"code3\":\"154\"},{\"triad_no\":12,\"triad_type\":\"BAB\",\"code1\":\"768\",\"code2\":\"838\",\"code3\":\"913\"}]', 'Control Sample', 'SHL-0207', '{\"sample_a\":[{\"code\":\"824\",\"code_id\":\"A1\",\"triad_no\":1},{\"code\":\"538\",\"code_id\":\"B1\",\"triad_no\":2},{\"code\":\"342\",\"code_id\":\"B2\",\"triad_no\":\"2*\"},{\"code\":\"819\",\"code_id\":\"C1\",\"triad_no\":3},{\"code\":\"717\",\"code_id\":\"C3\",\"triad_no\":3},{\"code\":\"345\",\"code_id\":\"D2\",\"triad_no\":\"4*\"},{\"code\":\"793\",\"code_id\":\"D3\",\"triad_no\":4},{\"code\":\"305\",\"code_id\":\"E3\",\"triad_no\":5},{\"code\":\"698\",\"code_id\":\"F2\",\"triad_no\":\"6*\"},{\"code\":\"982\",\"code_id\":\"G1\",\"triad_no\":7},{\"code\":\"385\",\"code_id\":\"H1\",\"triad_no\":8},{\"code\":\"002\",\"code_id\":\"H2\",\"triad_no\":\"8*\"},{\"code\":\"021\",\"code_id\":\"I1\",\"triad_no\":9},{\"code\":\"904\",\"code_id\":\"I3\",\"triad_no\":9},{\"code\":\"176\",\"code_id\":\"J2\",\"triad_no\":\"10*\"},{\"code\":\"328\",\"code_id\":\"J3\",\"triad_no\":10},{\"code\":\"154\",\"code_id\":\"K3\",\"triad_no\":11},{\"code\":\"838\",\"code_id\":\"L2\",\"triad_no\":\"12*\"}],\"sample_b\":[{\"code\":\"840\",\"code_id\":\"A2\",\"triad_no\":\"1*\"},{\"code\":\"336\",\"code_id\":\"A3\",\"triad_no\":1},{\"code\":\"459\",\"code_id\":\"B3\",\"triad_no\":2},{\"code\":\"750\",\"code_id\":\"C2\",\"triad_no\":\"3*\"},{\"code\":\"583\",\"code_id\":\"D1\",\"triad_no\":4},{\"code\":\"461\",\"code_id\":\"E1\",\"triad_no\":5},{\"code\":\"732\",\"code_id\":\"E2\",\"triad_no\":\"5*\"},{\"code\":\"239\",\"code_id\":\"F1\",\"triad_no\":6},{\"code\":\"642\",\"code_id\":\"F3\",\"triad_no\":6},{\"code\":\"859\",\"code_id\":\"G2\",\"triad_no\":\"7*\"},{\"code\":\"988\",\"code_id\":\"G3\",\"triad_no\":7},{\"code\":\"220\",\"code_id\":\"H3\",\"triad_no\":8},{\"code\":\"711\",\"code_id\":\"I2\",\"triad_no\":\"9*\"},{\"code\":\"494\",\"code_id\":\"J1\",\"triad_no\":10},{\"code\":\"303\",\"code_id\":\"K1\",\"triad_no\":11},{\"code\":\"444\",\"code_id\":\"K2\",\"triad_no\":\"11*\"},{\"code\":\"768\",\"code_id\":\"L1\",\"triad_no\":12},{\"code\":\"913\",\"code_id\":\"L3\",\"triad_no\":12}]}', 'Triangle Test', 'active', NULL),
(35, '11', '11', '11', '2025-05-09 01:08:06', NULL, NULL, NULL, NULL, 'Hedonic Scale', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `triangle_results`
--

CREATE TABLE `triangle_results` (
  `result_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `sample_code1` varchar(50) DEFAULT NULL,
  `sample_code2` varchar(50) DEFAULT NULL,
  `sample_code3` varchar(50) DEFAULT NULL,
  `odd_sample` varchar(100) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `request_no` varchar(100) DEFAULT NULL,
  `triad_no` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `triangle_results`
--

INSERT INTO `triangle_results` (`result_id`, `user_id`, `code_name`, `product_code`, `sample_code1`, `sample_code2`, `sample_code3`, `odd_sample`, `comments`, `date`, `request_no`, `triad_no`) VALUES
(54, 104, 'p1', 'SHL-0207', '824', '840', '336', '1', 'Darker color; Less sweeter', '2025-05-06', 'R10-042025-SHL-0207', 1),
(55, 105, 'p2', 'SHL-0207', '538', '342', '459', '3', 'Significantly sweeter', '2025-05-06', 'R10-042025-SHL-0207', 2),
(56, 106, 'p3', 'SHL-0207', '819', '750', '717', '2', 'N/A', '2025-05-06', 'R10-042025-SHL-0207', 3),
(57, 107, 'p4', 'SHL-0207', '583', '345', '793', '1', 'Harder; Not fresh', '2025-05-06', 'R10-042025-SHL-0207', 4),
(58, 108, 'p5', 'SHL-0207', '461', '732', '305', '3', 'Milkier', '2025-05-06', 'R10-042025-SHL-0207', 5),
(59, 109, 'p6', 'SHL-0207', '239', '698', '642', '2', 'Lighter color; Harder to bite', '2025-05-06', 'R10-042025-SHL-0207', 6),
(60, 110, 'p7', 'SHL-0207', '982', '859', '988', '1', 'Fresher taste', '2025-05-06', 'R10-042025-SHL-0207', 7),
(61, 111, 'p8', 'SHL-0207', '385', '002', '220', '3', 'Lighter color; Harder to chew', '2025-05-06', 'R10-042025-SHL-0207', 8),
(62, 112, 'p9', 'SHL-0207', '021', '711', '904', '2', 'Strong burnt taste', '2025-05-06', 'R10-042025-SHL-0207', 9),
(63, 113, 'p10', 'SHL-0207', '494', '176', '328', '1', 'More buttery', '2025-05-06', 'R10-042025-SHL-0207', 10),
(64, 114, 'p11', 'SHL-0207', '303', '444', '154', '3', 'N/A', '2025-05-06', 'R10-042025-SHL-0207', 11),
(67, 115, 'p12', 'SHL-0207', '768', '838', '913', '1', 'Sofer yummya ng food', '2025-05-09', 'R10-042025-SHL-0207', 12);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` tinyint(4) NOT NULL CHECK (`role` in (1,2,3)),
  `code_name` varchar(50) DEFAULT NULL,
  `user_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `code_name`, `user_picture`, `created_at`) VALUES
(1, 'analyst', 'analyst@gmail.com', '$2y$10$sp9PJC0lGOl8UbiMy/A6peG3x6brrjMPEbsGj9LRgsoqI/aY0V8ea', 2, 'Analyst', '', '2025-04-15 05:01:56'),
(50, 'technical', 'technical@gmail.com', '$2y$10$ZuS57zsqiBeu0rG7ZH1pBeNkLhlNLJ4stwv7YNH2J0VEU7Iz8O6TK', 2, 'Technical', '', '2025-04-15 05:01:25'),
(100, 'admin', 'admin@gmail.com', '$2y$10$2DDnIxbEGPmu5ThG/JHzW.GP1NIokpA9zs8pCS7.qOSqD7vh0otsq', 1, 'Marl Andrian Patrick H. Dalinao', '', '2025-04-15 05:00:40'),
(102, 'mark', 'emdovz02@gmail.com', '$2y$10$CzvhUUS5hKZfqxxpG8CCFO/AFSOE5Sso.TWcoOzwPM3wxryK/kPGS', 3, 'Mark_Boi', '', '2025-04-22 05:54:42'),
(103, 'ruel', 'ruel@gmail.com', '$2y$10$7fCYTO0rF9WFY4YACjamQeJPqhuRmU.VBvVl/rekLR96PRWXhKJ.q', 3, 'ruel', '', '2025-04-23 06:10:56'),
(104, 'p1', 'p1@gmail.com', '$2y$10$OO5Ejtru3J64OWjaVv4yt.OiLBTmh8J8uJ43W8J/wHDHFiP8B/e/q', 3, 'p1', '', '2025-04-28 01:35:19'),
(105, 'p2', 'p2@gmail.com', '$2y$10$uWeFx3aWBDY97n2jz9bm5.vtwso5inTFvtRGKymWGBSBUcdtGk2.m', 3, 'p2', '', '2025-04-28 01:39:53'),
(106, 'p3', 'p3@gmail.com', '$2y$10$Gl.R.atSWfbJHLG7bj5T4eFFxwBqtGuhVxzVHT5qnvF27OcSERx7y', 3, 'p3', '', '2025-04-28 01:40:16'),
(107, 'p4', 'p4@gmail.com', '$2y$10$gDxxFusTSBm2iQrXEFt4WOyfJ10Q7.byJQ/pZpWI98fXk/5wFZnSu', 3, 'p4', '', '2025-04-28 01:40:43'),
(108, 'p5', 'p5@gmail.com', '$2y$10$zlkQNj0japeZW8FtZquNuu09GWOwtoOLaDG1gUdb2f9/0DxkHISue', 3, 'p5', '', '2025-04-28 01:41:16'),
(109, 'p6', 'p6@gmail.com', '$2y$10$zYkVHnWtSxwH9apAxxo6s.XcSUxkx/CmD5Gchd0YEmYbBJmLLI.B6', 3, 'p6', '', '2025-04-28 01:41:40'),
(110, 'p7', 'p7@gmail.com', '$2y$10$anL4yJeHiszBpp1Lp2AheOTK6/EVuWbcFWGDn/spiN7CgRixjPMdO', 3, 'p7', '', '2025-04-28 01:42:10'),
(111, 'p8', 'p8@gmail.com', '$2y$10$LyKJ1GX9jS2sG4J84uZeIuNbn.6UmNc2fNN6WCUFDvx6amElohwHu', 3, 'p8', '', '2025-04-28 01:42:40'),
(112, 'p9', 'p9@gmail.com', '$2y$10$vuWU54fPosO2e5taPyjGRuyF9B162J7hmP7KyQGdzwLB9fTk4reiu', 3, 'p9', '', '2025-04-28 01:43:50'),
(113, 'p10', 'p10@gmail.com', '$2y$10$nGspOfNH6HqnPZnAXGoRge5I7CvLbQlJ3a4eey0.d9wRH8WJv.i/2', 3, 'p10', '', '2025-04-28 01:44:13'),
(114, 'p11', 'p11@gmail.com', '$2y$10$UVrG9LYWcevK0AtnW7Hlu.Lrx1K1jaSa4M91eMS5Ae2r5wvoiQgWW', 3, 'p11', '', '2025-04-28 01:44:35'),
(115, 'p12', 'p12@gmail.com', '$2y$10$IuK93izKhefzPq.XTsJNr.4lxL27qH/J5MKtNQTwCc/Qof/IFaSKe', 3, 'p12', '', '2025-04-28 01:45:12'),
(116, 'p13', 'p13@gmail.com', '$2y$10$pLGYMDWesHgYGf.J7TptKeyjVV89FRErd0eCfwBzzRy6MJR8r/XQi', 3, 'p13', '', '2025-04-28 01:45:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `evaluation_assignments`
--
ALTER TABLE `evaluation_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`,`user_id`);

--
-- Indexes for table `evaluation_requests`
--
ALTER TABLE `evaluation_requests`
  ADD PRIMARY KEY (`s_id`);

--
-- Indexes for table `triangle_results`
--
ALTER TABLE `triangle_results`
  ADD PRIMARY KEY (`result_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `evaluation_assignments`
--
ALTER TABLE `evaluation_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `evaluation_requests`
--
ALTER TABLE `evaluation_requests`
  MODIFY `s_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `triangle_results`
--
ALTER TABLE `triangle_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
