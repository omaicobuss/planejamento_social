-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 09/03/2026 às 11:17
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `mobuss_presenca`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `funcionarios`
--

INSERT INTO `funcionarios` (`id`, `nome`, `data_criacao`) VALUES
(1, 'Juliana', '2026-02-23 18:06:07'),
(2, 'Maico', '2026-02-23 18:06:07'),
(3, 'Rezende R.', '2026-02-23 18:06:07'),
(4, 'Ronaldo', '2026-02-23 18:06:07'),
(5, 'Valverde R.', '2026-02-23 18:06:07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_regime`
--

CREATE TABLE `registros_regime` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('manhã','tarde') COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('presencial','homeoffice','férias','afastamento') COLLATE utf8_unicode_ci NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `registros_regime`
--

INSERT INTO `registros_regime` (`id`, `funcionario_id`, `data`, `turno`, `status`, `data_registro`) VALUES
(8, 2, '2026-02-23', 'manhã', 'presencial', '2026-02-23 18:07:34'),
(9, 2, '2026-02-24', 'manhã', 'presencial', '2026-02-23 18:07:37'),
(10, 2, '2026-02-25', 'manhã', 'homeoffice', '2026-02-23 18:07:40'),
(11, 2, '2026-02-26', 'manhã', 'homeoffice', '2026-02-23 18:07:46'),
(12, 2, '2026-02-26', 'tarde', 'presencial', '2026-02-23 18:07:46'),
(15, 2, '2026-02-27', 'manhã', 'homeoffice', '2026-02-23 18:07:57'),
(16, 3, '2026-02-05', 'manhã', 'presencial', '2026-02-23 18:13:46'),
(17, 3, '2026-02-05', 'tarde', 'presencial', '2026-02-23 18:13:46'),
(18, 3, '2026-02-06', 'manhã', 'presencial', '2026-02-23 18:13:54'),
(19, 3, '2026-02-06', 'tarde', 'presencial', '2026-02-23 18:13:54'),
(20, 3, '2026-02-04', 'manhã', 'homeoffice', '2026-02-23 18:14:01'),
(21, 3, '2026-02-04', 'tarde', 'homeoffice', '2026-02-23 18:14:01'),
(22, 3, '2026-02-12', 'manhã', 'presencial', '2026-02-23 18:15:12'),
(23, 3, '2026-02-12', 'tarde', 'presencial', '2026-02-23 18:15:12'),
(24, 3, '2026-02-13', 'manhã', 'presencial', '2026-02-23 18:15:16'),
(25, 3, '2026-02-13', 'tarde', 'presencial', '2026-02-23 18:15:16'),
(26, 3, '2026-02-19', 'manhã', 'presencial', '2026-02-23 18:15:20'),
(27, 3, '2026-02-19', 'tarde', 'presencial', '2026-02-23 18:15:20'),
(28, 3, '2026-02-20', 'manhã', 'presencial', '2026-02-23 18:15:25'),
(29, 3, '2026-02-20', 'tarde', 'presencial', '2026-02-23 18:15:25'),
(30, 3, '2026-02-26', 'manhã', 'presencial', '2026-02-23 18:15:29'),
(31, 3, '2026-02-26', 'tarde', 'presencial', '2026-02-23 18:15:29'),
(32, 3, '2026-02-27', 'manhã', 'presencial', '2026-02-23 18:15:34'),
(33, 3, '2026-02-27', 'tarde', 'presencial', '2026-02-23 18:15:34'),
(34, 3, '2026-02-02', 'manhã', 'homeoffice', '2026-02-23 18:15:42'),
(35, 3, '2026-02-02', 'tarde', 'homeoffice', '2026-02-23 18:15:42'),
(36, 3, '2026-02-03', 'manhã', 'homeoffice', '2026-02-23 18:15:47'),
(37, 3, '2026-02-03', 'tarde', 'homeoffice', '2026-02-23 18:15:47'),
(38, 3, '2026-02-09', 'manhã', 'homeoffice', '2026-02-23 18:15:52'),
(39, 3, '2026-02-09', 'tarde', 'homeoffice', '2026-02-23 18:15:52'),
(40, 3, '2026-02-10', 'manhã', 'homeoffice', '2026-02-23 18:15:56'),
(41, 3, '2026-02-10', 'tarde', 'homeoffice', '2026-02-23 18:15:56'),
(42, 3, '2026-02-11', 'manhã', 'homeoffice', '2026-02-23 18:16:00'),
(43, 3, '2026-02-11', 'tarde', 'homeoffice', '2026-02-23 18:16:00'),
(44, 3, '2026-02-16', 'manhã', 'homeoffice', '2026-02-23 18:16:04'),
(45, 3, '2026-02-16', 'tarde', 'homeoffice', '2026-02-23 18:16:04'),
(46, 3, '2026-02-17', 'manhã', 'homeoffice', '2026-02-23 18:16:09'),
(47, 3, '2026-02-17', 'tarde', 'homeoffice', '2026-02-23 18:16:09'),
(48, 3, '2026-02-18', 'manhã', 'homeoffice', '2026-02-23 18:16:13'),
(49, 3, '2026-02-18', 'tarde', 'homeoffice', '2026-02-23 18:16:13'),
(50, 3, '2026-02-23', 'manhã', 'homeoffice', '2026-02-23 18:16:20'),
(51, 3, '2026-02-23', 'tarde', 'homeoffice', '2026-02-23 18:16:20'),
(52, 3, '2026-02-24', 'manhã', 'homeoffice', '2026-02-23 18:16:43'),
(53, 3, '2026-02-24', 'tarde', 'homeoffice', '2026-02-23 18:16:43'),
(54, 3, '2026-02-25', 'manhã', 'homeoffice', '2026-02-23 18:16:47'),
(55, 3, '2026-02-25', 'tarde', 'homeoffice', '2026-02-23 18:16:47'),
(56, 3, '2026-03-05', 'manhã', 'presencial', '2026-02-23 18:16:58'),
(57, 3, '2026-03-05', 'tarde', 'presencial', '2026-02-23 18:16:58'),
(58, 3, '2026-03-06', 'manhã', 'presencial', '2026-02-23 18:17:03'),
(59, 3, '2026-03-06', 'tarde', 'presencial', '2026-02-23 18:17:03'),
(60, 3, '2026-03-02', 'manhã', 'homeoffice', '2026-02-23 18:17:17'),
(61, 3, '2026-03-02', 'tarde', 'homeoffice', '2026-02-23 18:17:17'),
(62, 3, '2026-03-03', 'manhã', 'homeoffice', '2026-02-23 18:17:21'),
(63, 3, '2026-03-03', 'tarde', 'homeoffice', '2026-02-23 18:17:21'),
(64, 3, '2026-03-04', 'manhã', 'homeoffice', '2026-02-23 18:19:29'),
(65, 3, '2026-03-04', 'tarde', 'homeoffice', '2026-02-23 18:19:29'),
(66, 3, '2026-03-09', 'manhã', 'homeoffice', '2026-02-23 18:19:39'),
(67, 3, '2026-03-09', 'tarde', 'homeoffice', '2026-02-23 18:19:39'),
(68, 3, '2026-03-10', 'manhã', 'homeoffice', '2026-02-23 18:19:42'),
(69, 3, '2026-03-10', 'tarde', 'homeoffice', '2026-02-23 18:19:42'),
(70, 3, '2026-03-11', 'manhã', 'homeoffice', '2026-02-23 18:19:47'),
(71, 3, '2026-03-11', 'tarde', 'homeoffice', '2026-02-23 18:19:47'),
(72, 3, '2026-03-12', 'manhã', 'presencial', '2026-02-23 18:19:51'),
(73, 3, '2026-03-12', 'tarde', 'presencial', '2026-02-23 18:19:51'),
(74, 3, '2026-03-13', 'manhã', 'presencial', '2026-02-23 18:19:55'),
(75, 3, '2026-03-13', 'tarde', 'presencial', '2026-02-23 18:19:55'),
(76, 3, '2026-03-19', 'manhã', 'presencial', '2026-02-23 18:20:05'),
(77, 3, '2026-03-19', 'tarde', 'presencial', '2026-02-23 18:20:05'),
(78, 3, '2026-03-20', 'manhã', 'presencial', '2026-02-23 18:20:09'),
(79, 3, '2026-03-20', 'tarde', 'presencial', '2026-02-23 18:20:09'),
(80, 3, '2026-03-16', 'manhã', 'homeoffice', '2026-02-23 18:20:13'),
(81, 3, '2026-03-16', 'tarde', 'homeoffice', '2026-02-23 18:20:13'),
(82, 3, '2026-03-17', 'manhã', 'homeoffice', '2026-02-23 18:20:17'),
(83, 3, '2026-03-17', 'tarde', 'homeoffice', '2026-02-23 18:20:17'),
(84, 3, '2026-03-18', 'manhã', 'homeoffice', '2026-02-23 18:20:21'),
(85, 3, '2026-03-18', 'tarde', 'homeoffice', '2026-02-23 18:20:21'),
(88, 3, '2026-03-26', 'manhã', 'presencial', '2026-02-23 18:23:26'),
(89, 3, '2026-03-26', 'tarde', 'presencial', '2026-02-23 18:23:26'),
(90, 3, '2026-03-27', 'manhã', 'presencial', '2026-02-23 18:23:33'),
(91, 3, '2026-03-27', 'tarde', 'presencial', '2026-02-23 18:23:33'),
(92, 3, '2026-03-25', 'manhã', 'homeoffice', '2026-02-23 18:23:47'),
(93, 3, '2026-03-25', 'tarde', 'homeoffice', '2026-02-23 18:23:47'),
(94, 3, '2026-03-24', 'manhã', 'homeoffice', '2026-02-23 18:23:53'),
(95, 3, '2026-03-24', 'tarde', 'homeoffice', '2026-02-23 18:23:53'),
(96, 3, '2026-03-23', 'manhã', 'homeoffice', '2026-02-23 18:24:02'),
(97, 3, '2026-03-23', 'tarde', 'homeoffice', '2026-02-23 18:24:02'),
(98, 3, '2026-03-30', 'manhã', 'homeoffice', '2026-02-23 18:24:09'),
(99, 3, '2026-03-30', 'tarde', 'homeoffice', '2026-02-23 18:24:09'),
(100, 3, '2026-03-31', 'manhã', 'homeoffice', '2026-02-23 18:24:16'),
(101, 3, '2026-03-31', 'tarde', 'homeoffice', '2026-02-23 18:24:16'),
(102, 2, '2026-03-02', 'manhã', 'presencial', '2026-02-23 18:47:22'),
(104, 2, '2026-03-02', 'tarde', 'presencial', '2026-02-23 18:47:29'),
(105, 2, '2026-03-03', 'manhã', 'presencial', '2026-02-23 18:47:33'),
(106, 2, '2026-03-03', 'tarde', 'presencial', '2026-02-23 18:47:33'),
(107, 2, '2026-03-04', 'manhã', 'presencial', '2026-02-23 18:47:37'),
(108, 2, '2026-03-04', 'tarde', 'presencial', '2026-02-23 18:47:37'),
(109, 2, '2026-03-05', 'manhã', 'presencial', '2026-02-23 18:47:41'),
(110, 2, '2026-03-05', 'tarde', 'presencial', '2026-02-23 18:47:41'),
(111, 2, '2026-03-06', 'manhã', 'homeoffice', '2026-02-23 18:47:45'),
(112, 2, '2026-03-06', 'tarde', 'homeoffice', '2026-02-23 18:47:45'),
(113, 2, '2026-03-09', 'manhã', 'presencial', '2026-02-23 18:47:56'),
(114, 2, '2026-03-09', 'tarde', 'homeoffice', '2026-02-23 18:47:56'),
(115, 2, '2026-03-10', 'manhã', 'homeoffice', '2026-02-23 18:48:03'),
(116, 2, '2026-03-10', 'tarde', 'homeoffice', '2026-02-23 18:48:03'),
(117, 2, '2026-03-11', 'manhã', 'presencial', '2026-02-23 18:48:08'),
(118, 2, '2026-03-11', 'tarde', 'homeoffice', '2026-02-23 18:48:08'),
(119, 2, '2026-03-12', 'manhã', 'presencial', '2026-02-23 18:48:11'),
(120, 2, '2026-03-12', 'tarde', 'homeoffice', '2026-02-23 18:48:11'),
(121, 2, '2026-03-13', 'manhã', 'presencial', '2026-02-23 18:48:15'),
(122, 2, '2026-03-13', 'tarde', 'homeoffice', '2026-02-23 18:48:15'),
(123, 2, '2026-03-16', 'manhã', 'homeoffice', '2026-02-23 18:48:23'),
(124, 2, '2026-03-16', 'tarde', 'homeoffice', '2026-02-23 18:48:23'),
(125, 2, '2026-03-17', 'manhã', 'presencial', '2026-02-23 18:48:27'),
(126, 2, '2026-03-17', 'tarde', 'homeoffice', '2026-02-23 18:48:27'),
(127, 2, '2026-03-18', 'manhã', 'presencial', '2026-02-23 18:48:30'),
(128, 2, '2026-03-18', 'tarde', 'homeoffice', '2026-02-23 18:48:30'),
(129, 2, '2026-03-19', 'manhã', 'presencial', '2026-02-23 18:48:33'),
(130, 2, '2026-03-19', 'tarde', 'homeoffice', '2026-02-23 18:48:33'),
(131, 2, '2026-03-20', 'manhã', 'presencial', '2026-02-23 18:48:37'),
(132, 2, '2026-03-20', 'tarde', 'homeoffice', '2026-02-23 18:48:37'),
(133, 2, '2026-03-23', 'manhã', 'férias', '2026-02-23 18:48:49'),
(134, 2, '2026-03-23', 'tarde', 'férias', '2026-02-23 18:48:49'),
(135, 2, '2026-03-24', 'manhã', 'homeoffice', '2026-02-23 18:49:08'),
(136, 2, '2026-03-24', 'tarde', 'homeoffice', '2026-02-23 18:49:08'),
(137, 2, '2026-03-25', 'manhã', 'presencial', '2026-02-23 18:49:13'),
(138, 2, '2026-03-25', 'tarde', 'homeoffice', '2026-02-23 18:49:13'),
(139, 2, '2026-03-26', 'manhã', 'presencial', '2026-02-23 18:49:17'),
(140, 2, '2026-03-26', 'tarde', 'homeoffice', '2026-02-23 18:49:17'),
(141, 2, '2026-03-27', 'manhã', 'presencial', '2026-02-23 18:49:20'),
(142, 2, '2026-03-27', 'tarde', 'homeoffice', '2026-02-23 18:49:20'),
(143, 2, '2026-03-30', 'manhã', 'homeoffice', '2026-02-23 18:49:32'),
(144, 2, '2026-03-30', 'tarde', 'homeoffice', '2026-02-23 18:49:32'),
(145, 2, '2026-03-31', 'manhã', 'presencial', '2026-02-23 18:49:38'),
(146, 2, '2026-03-31', 'tarde', 'homeoffice', '2026-02-23 18:49:38'),
(147, 5, '2026-03-02', 'manhã', 'presencial', '2026-02-27 14:10:06'),
(148, 5, '2026-03-04', 'manhã', 'presencial', '2026-02-27 14:10:19'),
(149, 5, '2026-03-03', 'manhã', 'homeoffice', '2026-02-27 14:10:26'),
(150, 5, '2026-03-05', 'manhã', 'homeoffice', '2026-02-27 14:10:32'),
(151, 5, '2026-03-06', 'manhã', 'homeoffice', '2026-02-27 14:10:35'),
(152, 5, '2026-03-09', 'manhã', 'presencial', '2026-02-27 14:10:58'),
(153, 5, '2026-03-09', 'tarde', 'homeoffice', '2026-02-27 14:10:58'),
(154, 5, '2026-03-10', 'manhã', 'homeoffice', '2026-02-27 14:11:04'),
(155, 5, '2026-03-10', 'tarde', 'presencial', '2026-02-27 14:11:04'),
(156, 5, '2026-03-11', 'manhã', 'presencial', '2026-02-27 14:11:10'),
(157, 5, '2026-03-11', 'tarde', 'presencial', '2026-02-27 14:11:10'),
(158, 5, '2026-03-12', 'manhã', 'homeoffice', '2026-02-27 14:11:14'),
(159, 5, '2026-03-12', 'tarde', 'homeoffice', '2026-02-27 14:11:14'),
(160, 5, '2026-03-13', 'manhã', 'homeoffice', '2026-02-27 14:11:19'),
(161, 5, '2026-03-13', 'tarde', 'homeoffice', '2026-02-27 14:11:19'),
(162, 5, '2026-03-16', 'manhã', 'presencial', '2026-02-27 14:11:57'),
(163, 5, '2026-03-16', 'tarde', 'homeoffice', '2026-02-27 14:11:57'),
(164, 5, '2026-03-17', 'manhã', 'presencial', '2026-02-27 14:12:02'),
(165, 5, '2026-03-17', 'tarde', 'homeoffice', '2026-02-27 14:12:02'),
(166, 5, '2026-03-18', 'manhã', 'presencial', '2026-02-27 14:12:07'),
(167, 5, '2026-03-18', 'tarde', 'presencial', '2026-02-27 14:12:07'),
(168, 5, '2026-03-19', 'manhã', 'homeoffice', '2026-02-27 14:12:12'),
(169, 5, '2026-03-19', 'tarde', 'homeoffice', '2026-02-27 14:12:12'),
(170, 5, '2026-03-20', 'manhã', 'homeoffice', '2026-02-27 14:12:15'),
(171, 5, '2026-03-20', 'tarde', 'homeoffice', '2026-02-27 14:12:15'),
(172, 5, '2026-03-24', 'manhã', 'homeoffice', '2026-02-27 14:12:49'),
(173, 5, '2026-03-24', 'tarde', 'presencial', '2026-02-27 14:12:49'),
(174, 5, '2026-03-25', 'manhã', 'presencial', '2026-02-27 14:12:56'),
(175, 5, '2026-03-25', 'tarde', 'presencial', '2026-02-27 14:12:56'),
(176, 5, '2026-03-26', 'manhã', 'homeoffice', '2026-02-27 14:13:01'),
(177, 5, '2026-03-26', 'tarde', 'homeoffice', '2026-02-27 14:13:01'),
(178, 5, '2026-03-27', 'manhã', 'homeoffice', '2026-02-27 14:13:06'),
(179, 5, '2026-03-27', 'tarde', 'homeoffice', '2026-02-27 14:13:06'),
(180, 5, '2026-03-30', 'manhã', 'presencial', '2026-02-27 14:13:20'),
(181, 5, '2026-03-30', 'tarde', 'homeoffice', '2026-02-27 14:13:20'),
(182, 5, '2026-03-31', 'manhã', 'presencial', '2026-02-27 14:13:25'),
(183, 5, '2026-03-31', 'tarde', 'homeoffice', '2026-02-27 14:13:25'),
(184, 1, '2026-02-02', 'manhã', 'afastamento', '2026-02-27 15:00:36'),
(185, 1, '2026-02-02', 'tarde', 'presencial', '2026-02-27 15:00:36'),
(188, 1, '2026-02-03', 'manhã', 'homeoffice', '2026-02-27 15:01:26'),
(189, 1, '2026-02-03', 'tarde', 'homeoffice', '2026-02-27 15:01:26'),
(190, 1, '2026-02-04', 'manhã', 'afastamento', '2026-02-27 15:01:48'),
(191, 1, '2026-02-04', 'tarde', 'homeoffice', '2026-02-27 15:01:48'),
(192, 1, '2026-02-05', 'manhã', 'homeoffice', '2026-02-27 15:03:57'),
(193, 1, '2026-02-05', 'tarde', 'afastamento', '2026-02-27 15:03:57'),
(194, 1, '2026-02-06', 'manhã', 'afastamento', '2026-02-27 15:04:12'),
(195, 1, '2026-02-06', 'tarde', 'homeoffice', '2026-02-27 15:04:12'),
(196, 1, '2026-02-09', 'manhã', 'afastamento', '2026-02-27 15:09:20'),
(197, 1, '2026-02-09', 'tarde', 'homeoffice', '2026-02-27 15:09:20'),
(198, 1, '2026-02-10', 'manhã', 'afastamento', '2026-02-27 15:09:37'),
(199, 1, '2026-02-10', 'tarde', 'homeoffice', '2026-02-27 15:09:37'),
(208, 1, '2026-02-11', 'manhã', 'afastamento', '2026-02-27 15:11:05'),
(209, 1, '2026-02-11', 'tarde', 'presencial', '2026-02-27 15:11:05'),
(210, 1, '2026-02-12', 'manhã', 'presencial', '2026-02-27 15:11:35'),
(211, 1, '2026-02-12', 'tarde', 'afastamento', '2026-02-27 15:11:35'),
(216, 1, '2026-02-13', 'manhã', 'afastamento', '2026-02-27 15:12:23'),
(217, 1, '2026-02-13', 'tarde', 'homeoffice', '2026-02-27 15:12:23'),
(218, 1, '2026-02-16', 'manhã', 'afastamento', '2026-02-27 15:12:37'),
(219, 1, '2026-02-16', 'tarde', 'presencial', '2026-02-27 15:12:37'),
(222, 1, '2026-02-17', 'manhã', 'afastamento', '2026-02-27 15:12:56'),
(223, 1, '2026-02-17', 'tarde', 'presencial', '2026-02-27 15:12:56'),
(224, 1, '2026-02-18', 'manhã', 'afastamento', '2026-02-27 15:13:10'),
(225, 1, '2026-02-18', 'tarde', 'homeoffice', '2026-02-27 15:13:10'),
(226, 1, '2026-02-19', 'manhã', 'homeoffice', '2026-02-27 15:13:49'),
(227, 1, '2026-02-19', 'tarde', 'afastamento', '2026-02-27 15:13:49'),
(228, 1, '2026-02-20', 'manhã', 'afastamento', '2026-02-27 15:14:01'),
(229, 1, '2026-02-20', 'tarde', 'homeoffice', '2026-02-27 15:14:01'),
(230, 1, '2026-03-02', 'tarde', 'presencial', '2026-02-27 15:14:59'),
(231, 1, '2026-03-02', 'manhã', 'afastamento', '2026-02-27 15:15:08'),
(233, 1, '2026-03-11', 'manhã', 'afastamento', '2026-02-27 15:15:23'),
(234, 1, '2026-03-11', 'tarde', 'presencial', '2026-02-27 15:15:23'),
(235, 1, '2026-03-12', 'manhã', 'presencial', '2026-02-27 15:15:31'),
(236, 1, '2026-03-12', 'tarde', 'afastamento', '2026-02-27 15:15:31'),
(237, 1, '2026-03-16', 'manhã', 'afastamento', '2026-02-27 15:15:42'),
(238, 1, '2026-03-16', 'tarde', 'presencial', '2026-02-27 15:15:42'),
(239, 1, '2026-03-17', 'manhã', 'afastamento', '2026-02-27 15:15:54'),
(240, 1, '2026-03-17', 'tarde', 'presencial', '2026-02-27 15:15:54'),
(241, 1, '2026-03-24', 'manhã', 'afastamento', '2026-02-27 15:16:11'),
(242, 1, '2026-03-24', 'tarde', 'presencial', '2026-02-27 15:16:11'),
(243, 1, '2026-03-26', 'manhã', 'presencial', '2026-02-27 15:16:24'),
(244, 1, '2026-03-26', 'tarde', 'afastamento', '2026-02-27 15:16:24'),
(245, 1, '2026-03-30', 'manhã', 'afastamento', '2026-02-27 15:16:35'),
(246, 1, '2026-03-30', 'tarde', 'presencial', '2026-02-27 15:16:35');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `registros_regime`
--
ALTER TABLE `registros_regime`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registro` (`funcionario_id`,`data`,`turno`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `registros_regime`
--
ALTER TABLE `registros_regime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `registros_regime`
--
ALTER TABLE `registros_regime`
  ADD CONSTRAINT `registros_regime_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
