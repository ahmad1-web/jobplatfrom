-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 04:16 PM
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
-- Database: `job_platform`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Shortlisted applicant', 'Application #2 for Web Developer', '::1', '2026-05-30 16:25:26'),
(2, 1, 'Initiated audit', 'System check performed by administrator', '::1', '2026-05-30 16:30:45'),
(3, 1, 'Reviewed application', 'Application #3 for Web Developer', '::1', '2026-05-30 16:33:18'),
(4, 1, 'Shortlisted applicant', 'Application #3 for Web Developer', '::1', '2026-05-30 16:34:56'),
(5, 5, 'Reviewed application', 'Application #5 for Web Developer', '::1', '2026-06-17 08:50:40'),
(6, 5, 'Reviewed application', 'Application #5 for Web Developer', '::1', '2026-06-17 08:50:44'),
(7, 5, 'Reviewed application', 'Application #5 for Web Developer', '::1', '2026-06-17 08:54:03'),
(8, 5, 'Shortlisted applicant', 'Application #5 for Web Developer', '::1', '2026-06-17 08:56:35'),
(9, 5, 'Rejected applicant', 'Application #5 for Web Developer', '::1', '2026-06-17 08:59:38'),
(10, 5, 'Shortlisted applicant', 'Application #5 for Web Developer', '::1', '2026-06-17 11:48:31'),
(11, 5, 'Rejected applicant', 'Application #4 for IT Support Officer', '::1', '2026-06-17 14:16:58'),
(12, 5, 'Reviewed application', 'Application #4 for IT Support Officer', '::1', '2026-06-17 14:17:10'),
(13, 5, 'Reviewed application', 'Application #6 for IT Support Officer', '::1', '2026-06-19 14:06:21'),
(14, 5, 'Shortlisted applicant', 'Application #6 for IT Support Officer', '::1', '2026-06-19 14:08:38'),
(15, 5, 'Rejected applicant', 'Application #6 for IT Support Officer', '::1', '2026-06-19 14:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `cv_path` varchar(255) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `shortlist_message` text DEFAULT NULL,
  `reviewed_date` timestamp NULL DEFAULT NULL,
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `job_id`, `cv_path`, `cover_letter`, `qualification`, `experience_years`, `address`, `status`, `admin_notes`, `shortlist_message`, `reviewed_date`, `applied_date`) VALUES
(2, 2, 1, 'uploads/cv_2_1_1780156736.docx', 'We are looking for a skilled Web Developer to join our dynamic team at Iya Abubakar ICT Center, Zaria. The successful candidate will be responsible for developing and maintaining web-based applications that support our institutional operations. You will work closely with the IT team to design, build, and deploy robust solutions that enhance service…', 'B.Sc Computer Science', 5, 'Kaduna, Nigeria', 'shortlisted', NULL, NULL, NULL, '2026-05-30 15:58:56'),
(3, 4, 1, 'uploads/cv_4_1_1780158751.pdf', '', '0', 3, 'No. 10 Samaru Zaria', 'shortlisted', 'Congratulations! You have been shortlisted. Please visit the institute for interview on 15/6/2026', 'Congratulations! You have been shortlisted. Please check your dashboard for next steps.', '2026-05-30 16:34:56', '2026-05-30 16:32:31'),
(4, 8, 2, 'uploads/cv_8_2_1781684649.docx', '', 'HND', 2, 'samaru', 'reviewed', NULL, NULL, '2026-06-17 14:17:10', '2026-06-17 08:24:10'),
(5, 9, 1, 'uploads/cv_9_1_1781685655.docx', 'Dear Hiring Manager,\r\n\r\nI am writing to express my interest in the Web Developer position at Iya Abubakar ICT Center...\r\n\r\n[Continue your cover letter here]', 'BSC computer science', 3, 'samuru zaria', 'shortlisted', 'sorry', 'congrati', '2026-06-17 11:48:30', '2026-06-17 08:40:55'),
(6, 10, 2, 'uploads/cv_10_2_1781877834.docx', 'Cover Letter (optional but recommended)\r\nDear Hiring Manager,\r\n\r\nI am writing to express my interest in the IT Support Officer position at Iya Abubakar ICT Center...\r\n\r\n[Continue your cover letter here]\r\n A compelling cover letter increases your chances significantly', 'software', 2, 'Current Address *\r\ne.g. No. 123, Ahmadu Bello Way, Zaria, Kaduna State', 'rejected', 'Admin Notes (internal note for your reference)\r\nAdd any internal notes about this application...', 'Shortlist Message to Applicant (this will be sent as a notification)\r\ne.g. Congratulations! You have been shortlisted. Please check your dashboard for next steps.', '2026-06-19 14:10:05', '2026-06-19 14:03:54');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `location` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `posted_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `description`, `requirements`, `location`, `type`, `status`, `posted_date`) VALUES
(1, 'Web Developer', 'We are looking for a skilled Web Developer to join our dynamic team at Iya Abubakar ICT Center, Zaria. The successful candidate will be responsible for developing and maintaining web-based applications that support our institutional operations. You will work closely with the IT team to design, build, and deploy robust solutions that enhance service delivery across the center.\n\nKey Responsibilities:\n- Design, develop, and maintain responsive web applications\n- Collaborate with stakeholders to gather requirements and deliver solutions\n- Ensure web applications are optimized for performance and scalability\n- Maintain documentation for developed systems\n- Provide technical support and troubleshoot issues', 'Required Qualifications & Skills:\n- Bachelor\'s degree in Computer Science, Software Engineering, or related field\n- Minimum 2 years of experience in web development\n- Proficiency in PHP, HTML5, CSS3, JavaScript, and MySQL\n- Experience with frameworks such as Laravel or Bootstrap\n- Knowledge of RESTful APIs and version control (Git)\n- Strong problem-solving and communication skills\n- Familiarity with Linux server environments is an advantage', 'Zaria, Kaduna State', 'Full-time', 'open', '2026-05-04 20:41:27'),
(2, 'IT Support Officer', 'Iya Abubakar ICT Center, Zaria, is seeking a motivated and detail-oriented IT Support Officer to provide technical assistance and support to staff and students. The ideal candidate will be responsible for the day-to-day IT operations, including hardware and software support, network maintenance, and user training.\n\nKey Responsibilities:\n- Provide first-line technical support to end users via phone, email, or in person\n- Install, configure, and maintain computer hardware and software\n- Monitor and maintain network infrastructure including LAN, Wi-Fi, and servers\n- Maintain an accurate inventory of all ICT equipment\n- Conduct periodic training sessions for non-technical staff\n- Ensure data backup and disaster recovery procedures are followed', 'Required Qualifications & Skills:\n- OND/HND/B.Sc in Computer Science, Information Technology, or related discipline\n- Minimum 1 year of IT support experience\n- Strong knowledge of Windows OS (10/11) and Microsoft Office Suite\n- Basic networking skills (TCP/IP, DNS, DHCP, routers, switches)\n- Experience with Active Directory and helpdesk ticketing systems is a plus\n- Excellent interpersonal and communication skills\n- Ability to work independently and under pressure\n- CompTIA A+ or Network+ certification is an added advantage', 'Zaria, Kaduna State', 'Full-time', 'open', '2026-05-04 20:41:27'),
(3, 'System Analyst', 'we looking for a skilled system analyst.', 'degree in computer science.', 'Remote', 'Full-time', 'open', '2026-05-04 20:45:56');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('shortlisted','rejected','reviewed','general') NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `application_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(2, 4, 3, '📋 Application Reviewed', 'Your application for Web Developer has been reviewed. Status: Reviewed.', 'reviewed', 1, '2026-05-30 16:33:18'),
(3, 4, 3, '🎉 Congratulations - Shortlisted!', 'Congratulations! You have been shortlisted. Please check your dashboard for next steps.', 'shortlisted', 1, '2026-05-30 16:34:56'),
(4, 9, 5, '📋 Application Reviewed', 'Your application for Web Developer has been reviewed. Status: Reviewed.', 'reviewed', 1, '2026-06-17 08:50:40'),
(5, 9, 5, '📋 Application Reviewed', 'Your application for Web Developer has been reviewed. Status: Reviewed.', 'reviewed', 1, '2026-06-17 08:50:43'),
(6, 9, 5, '📋 Application Reviewed', 'Your application for Web Developer has been reviewed. Status: Reviewed.', 'reviewed', 1, '2026-06-17 08:54:03'),
(7, 9, 5, '🎉 Congratulations - Shortlisted!', 'contras', 'shortlisted', 0, '2026-06-17 08:56:35'),
(8, 9, 5, '❌ Application Update', 'sorry', 'rejected', 0, '2026-06-17 08:59:38'),
(9, 9, 5, '🎉 Congratulations - Shortlisted!', 'congrati', 'shortlisted', 0, '2026-06-17 11:48:31'),
(10, 8, 4, '❌ Application Update', 'Thank you for applying for the IT Support Officer position. Unfortunately, you have not been selected to proceed at this time.', 'rejected', 0, '2026-06-17 14:16:58'),
(11, 8, 4, '📋 Application Reviewed', 'Your application for IT Support Officer has been reviewed. Status: Reviewed.', 'reviewed', 0, '2026-06-17 14:17:10'),
(12, 10, 6, '📋 Application Reviewed', 'Your application for IT Support Officer has been reviewed. Status: Reviewed.', 'reviewed', 0, '2026-06-19 14:06:21'),
(13, 10, 6, '🎉 Congratulations - Shortlisted!', 'Shortlist Message to Applicant (this will be sent as a notification)\r\ne.g. Congratulations! You have been shortlisted. Please check your dashboard for next steps.', 'shortlisted', 0, '2026-06-19 14:08:37'),
(14, 10, 6, '❌ Application Update', 'Thank you for applying for the IT Support Officer position. Unfortunately, you have not been selected to proceed at this time.', 'rejected', 0, '2026-06-19 14:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('applicant','admin') NOT NULL DEFAULT 'applicant',
  `status` enum('active','suspended','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `address`, `qualification`, `experience_years`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin', 'admin', '+234 800 000 0001', NULL, NULL, NULL, '$2y$10$vI8A7v3AUP4wDpyc9NlR1u2tCymP6yEw2gR3M/qTzQ9V6aYm07y26', 'admin', 'active', '2026-05-04 20:41:27'),
(2, 'Aminu Suleiman', 'applicant@test.com', '+234 803 123 4567', 'Zaria, Kaduna State', 'B.Sc Computer Science', 3, '$2y$10$nOJW2hLnNDiy6HDJsfYWr.GplhhYbS3fPXZw.473FV3KxyCOWIjNG', 'applicant', 'active', '2026-05-04 20:41:27'),
(4, 'Salisu Iliyasu', 'salisuiliyasu101@gmail.com', '09037470906', NULL, NULL, NULL, '$2y$10$TjSFvj/hADXHrn6l/X5BQOFDh4e2BL7Sy1SZEGP4kgGTThq7.1xSK', 'applicant', 'active', '2026-05-30 16:31:26'),
(5, 'Sulaiman Iliya', 'iliyasusulaiman2006@gmail.com', '08058089563', NULL, NULL, NULL, '$2y$10$aNSinMJOxVK/mEQPz9b.8eRKFGnV/RbM4BEFotbpORo/DEfHCJvkK', 'admin', 'active', '2026-06-17 07:44:45'),
(7, 'Secondary Admin', 'admin2@jobportal.com', '+234 800 000 0002', NULL, NULL, NULL, '$2y$10$wK2VvVWhwzYsh8X2D3pEKeWlHhI2j7h87fS1eO29YvGZf1.A8n6V6', 'applicant', 'active', '2026-06-17 08:10:48'),
(8, 'Ahmad Isa', 'isah84902@gmail.com', '+234 9126234926', NULL, NULL, NULL, '$2y$10$813kWp./9ie/YXPCfdHUcehzNsG6Ffxkr2ZV62rugQmgjTqf5NwnC', 'applicant', 'active', '2026-06-17 08:21:31'),
(9, 'ista gebrei', 'istagebreicom@gmail.com', '08062229', NULL, NULL, NULL, '$2y$10$4posCAjHD6rlqVEIKNXJtOQ2fdJHs6MEzJ9XsfuF1/uiP72XYJIM6', 'applicant', 'active', '2026-06-17 08:34:20'),
(10, 'aliyu musa', 'aliyumusa123@gmail.com', '08012345678', NULL, NULL, NULL, '$2y$10$m6uSWvRjtB0za8LTmqUuH.ImOvRpx8PKtnU0h7NivHQuBcc1ElDlm', 'applicant', 'active', '2026-06-19 13:59:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`user_id`,`job_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_applied_date` (`applied_date`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
