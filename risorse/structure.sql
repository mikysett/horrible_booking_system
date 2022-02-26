-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 26, 2022 at 05:34 PM
-- Server version: 8.0.28-0ubuntu0.20.04.3
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `camere`
--

CREATE TABLE `camere` (
  `id` bigint NOT NULL,
  `numero` smallint UNSIGNED NOT NULL DEFAULT '0',
  `piano` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `ubicazione` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `vestizione_max` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `pax_max` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `descrizione_breve` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `voto` float DEFAULT NULL,
  `colore` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gruppi`
--

CREATE TABLE `gruppi` (
  `id` bigint NOT NULL,
  `nome` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `data_arrivo` bigint NOT NULL DEFAULT '0',
  `data_partenza` bigint NOT NULL DEFAULT '0',
  `riepilogo` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `totale_camere` smallint UNSIGNED NOT NULL DEFAULT '0',
  `camere_non_assegnate` smallint UNSIGNED NOT NULL DEFAULT '0',
  `totale_pax` smallint UNSIGNED NOT NULL DEFAULT '0',
  `agenzia` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `colore` tinyint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prenotazioni`
--

CREATE TABLE `prenotazioni` (
  `id` bigint NOT NULL,
  `id_rif` bigint UNSIGNED NOT NULL DEFAULT '0',
  `tipo_pre` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `stile_spe` smallint UNSIGNED DEFAULT '0',
  `camera` smallint UNSIGNED NOT NULL DEFAULT '0',
  `nome` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `gruppo` bigint NOT NULL DEFAULT '0',
  `agenzia` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `vestizione` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `tipologia` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `pax` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `arrangiamento` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `primo_pasto` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `ultimo_pasto` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `data_arrivo` bigint NOT NULL DEFAULT '0',
  `data_partenza` bigint NOT NULL DEFAULT '0',
  `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `colore_note` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `problemi` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `data_ultima_modifica` bigint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tariffe`
--

CREATE TABLE `tariffe` (
  `id` bigint NOT NULL,
  `nome` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `data_inizio` bigint DEFAULT '0',
  `data_fine` bigint NOT NULL DEFAULT '0',
  `prezzo` smallint UNSIGNED NOT NULL DEFAULT '0',
  `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `camere`
--
ALTER TABLE `camere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `gruppi`
--
ALTER TABLE `gruppi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `tariffe`
--
ALTER TABLE `tariffe`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `camere`
--
ALTER TABLE `camere`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gruppi`
--
ALTER TABLE `gruppi`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prenotazioni`
--
ALTER TABLE `prenotazioni`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tariffe`
--
ALTER TABLE `tariffe`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
