-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 02, 2023 at 10:02 AM
-- Server version: 5.7.31
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dw_match`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_education_degree`
--

DROP TABLE IF EXISTS `user_education_degree`;
CREATE TABLE IF NOT EXISTS `user_education_degree` (
  `degree_id` int(11) NOT NULL AUTO_INCREMENT,
  `degree_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `education_level_id` int(11) NOT NULL,
  PRIMARY KEY (`degree_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user_education_degree`
--

INSERT INTO `user_education_degree` (`degree_id`, `degree_name`, `education_level_id`) VALUES
(1, 'PSC', 1),
(2, 'Ebtedayee (Madrasah)', 1),
(3, '5 Pass', 1),
(4, 'JSC', 2),
(5, 'JDC (Madrasah)', 2),
(6, '8 Pass', 2),
(7, 'SSC', 3),
(8, 'O Level', 3),
(9, 'Dakhil (Madrasah)', 3),
(10, 'SSC (Vocational)', 3),
(11, 'HSC', 4),
(12, 'A Level', 4),
(13, 'Alim (Madrasah)', 4),
(14, 'HSC (Vocational)', 4),
(15, 'HSC (BMT)', 4),
(16, 'Diploma in Engineering', 5),
(17, 'Diploma in Medical Technology', 5),
(18, 'Diploma in Nursing', 5),
(19, 'Diploma in Commerce', 5),
(20, 'Diploma in Business Studies', 5),
(21, 'Post Graduate Diploma (PGD)', 5),
(22, 'Diploma in Pathology', 5),
(23, 'Diploma (Vocational)', 5),
(24, 'Diploma in Hotel Management', 5),
(25, 'Diploma in Computer', 5),
(26, 'Diploma in Mechanical', 5),
(27, 'Diploma in Refrigeration and Air Conditioning', 5),
(28, 'Diploma in Electrical', 5),
(29, 'Diploma in Automobile', 5),
(30, 'Diploma in Power', 5),
(31, 'Diploma in Electronics', 5),
(32, 'Diploma in Architecture', 5),
(33, 'Diploma in Electro medical', 5),
(34, 'Diploma in Civil', 5),
(35, 'Diploma in Marine Engineering', 5),
(36, 'Diploma in Medical', 5),
(37, 'Diploma in Midwifery', 5),
(38, 'Diploma in Medical Ultrasound', 5),
(39, 'Diploma in Health Technology and Services', 5),
(40, 'Diploma in Agriculture', 5),
(41, 'Diploma in Fisheries', 5),
(42, 'Diploma in Livestock', 5),
(43, 'Diploma in Forestry', 5),
(44, 'Diploma in Textile Engineering', 5),
(45, 'Certificate in Marine Trade', 5),
(46, 'Bachelor of Science (BSc)', 6),
(47, 'Bachelor of Arts (BA)', 6),
(48, 'Bachelor of Commerce (BCom)', 6),
(49, 'Bachelor of Commerce (Pass)', 6),
(50, 'Bachelor of Business Administration (BBA)', 6),
(51, 'Bachelor of Medicine and Bachelor of Surgery (MBBS)', 6),
(52, 'Bachelor of Dental Surgery (BDS)', 6),
(53, 'Bachelor of Architecture (B.Arch)', 6),
(54, 'Bachelor of Pharmacy (B.Pharm)', 6),
(55, 'Bachelor of Education (B.Ed)', 6),
(56, 'Bachelor of Physical Education (BPEd)', 6),
(57, 'Bachelor of Law (LLB)', 6),
(58, 'Doctor of Veterinary Medicine (DVM)', 6),
(59, 'Bachelor of Social Science (BSS)', 6),
(60, 'Bachelor of Fine Arts (B.F.A)', 6),
(61, 'Bachelor of Business Studies (BBS)', 6),
(62, 'Bachelor of Computer Application (BCA)', 6),
(63, 'Fazil (Madrasah Hons.)', 6),
(64, 'Bachelor in Engineering (BEngg)', 6),
(65, 'Bachelor of Science (Pass)', 6),
(66, 'Bachelor of Arts (Pass)', 6),
(67, 'Bachelor of Law (Pass)', 6),
(68, 'Bachelor of Social Science (Pass)', 6),
(69, 'Bachelor of Business Studies (Pass)', 6),
(70, 'Fazil (Madrasah Pass)', 6),
(71, 'Master of Science (MSc)', 7),
(72, 'Master of Arts (MA)', 7),
(73, 'Master of Commerce (MCom)', 7),
(74, 'Master of Business Administration (MBA)', 7),
(75, 'Master of Architecture (M.Arch)', 7),
(76, 'Master of Pharmacy (M.Pharm)', 7),
(77, 'Master of Education (M.Ed)', 7),
(78, 'Master of Law (LLM)', 7),
(79, 'Master of Social Science (MSS)', 7),
(80, 'Master of Fine Arts (M.F.A)', 7),
(81, 'Master of Philosophy (M.Phil)', 7),
(82, 'Master of Business Management (MBM)', 7),
(83, 'Master of Development Studies (MDS)', 7),
(84, 'Master of Business Studies (MBS)', 7),
(85, 'Masters in Computer Application (MCA)', 7),
(86, 'Executive Master of Business Administration (EMBA)', 7),
(87, 'Fellowship of the College of Physicians and Surgeons (FCPS)', 7),
(88, 'Kamil (Madrasah)', 7),
(89, 'Masters in Engineering (MEngg)', 7),
(90, 'Masters in Bank Management (MBM)', 7),
(91, 'Masters in Information Systems Security (MISS)', 7),
(92, 'Master of Information & Communication Technology (MICT)', 7);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
