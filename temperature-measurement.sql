-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Počítač: 127.0.0.1
-- Vytvořeno: Úte 01. bře 2022, 22:51
-- Verze serveru: 10.4.13-MariaDB
-- Verze PHP: 7.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `temperature-measurement`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `device`
--

CREATE TABLE `device` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_connection` datetime DEFAULT NULL,
  `last_connection` datetime DEFAULT NULL,
  `is_allowed` tinyint(1) NOT NULL,
  `mac_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unique_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `local_ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_notifications`
--

CREATE TABLE `device_notifications` (
  `id` int(11) NOT NULL,
  `parent_device_id` int(11) DEFAULT NULL,
  `sensor_id` int(11) DEFAULT NULL,
  `notification_content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurrence` datetime NOT NULL DEFAULT current_timestamp(),
  `state` tinyint(1) NOT NULL,
  `notificationType` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_options`
--

CREATE TABLE `device_options` (
  `id` int(11) NOT NULL,
  `parent_device_id` int(11) DEFAULT NULL,
  `notifications_target_user_id` int(11) DEFAULT NULL,
  `notifications_status` tinyint(1) NOT NULL,
  `temperature_limit` int(11) DEFAULT NULL,
  `write_interval` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `sensor`
--

CREATE TABLE `sensor` (
  `id` int(11) NOT NULL,
  `parent_device_id` int(11) DEFAULT NULL,
  `hardware_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `sensor_data`
--

CREATE TABLE `sensor_data` (
  `id` int(11) NOT NULL,
  `parent_sensor_id` int(11) DEFAULT NULL,
  `sensor_data` double NOT NULL,
  `write_timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_92FB68E173059B4` (`unique_hash`);

--
-- Klíče pro tabulku `device_notifications`
--
ALTER TABLE `device_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_91A6277665EFE83A` (`parent_device_id`),
  ADD KEY `IDX_91A62776A247991F` (`sensor_id`);

--
-- Klíče pro tabulku `device_options`
--
ALTER TABLE `device_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_5A83231A65EFE83A` (`parent_device_id`),
  ADD KEY `IDX_5A83231A61EB8EDF` (`notifications_target_user_id`);

--
-- Klíče pro tabulku `sensor`
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_BC8617B0C9CC762B` (`hardware_id`),
  ADD KEY `IDX_BC8617B065EFE83A` (`parent_device_id`);

--
-- Klíče pro tabulku `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_801762CC530CB6F1` (`parent_sensor_id`);

--
-- Klíče pro tabulku `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  ADD UNIQUE KEY `UNIQ_8D93D649F85E0677` (`username`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `device`
--
ALTER TABLE `device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `device_notifications`
--
ALTER TABLE `device_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `device_options`
--
ALTER TABLE `device_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `sensor`
--
ALTER TABLE `sensor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `sensor_data`
--
ALTER TABLE `sensor_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `device_notifications`
--
ALTER TABLE `device_notifications`
  ADD CONSTRAINT `FK_91A6277665EFE83A` FOREIGN KEY (`parent_device_id`) REFERENCES `device` (`id`),
  ADD CONSTRAINT `FK_91A62776A247991F` FOREIGN KEY (`sensor_id`) REFERENCES `sensor` (`id`);

--
-- Omezení pro tabulku `device_options`
--
ALTER TABLE `device_options`
  ADD CONSTRAINT `FK_5A83231A61EB8EDF` FOREIGN KEY (`notifications_target_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_5A83231A65EFE83A` FOREIGN KEY (`parent_device_id`) REFERENCES `device` (`id`);

--
-- Omezení pro tabulku `sensor`
--
ALTER TABLE `sensor`
  ADD CONSTRAINT `FK_BC8617B065EFE83A` FOREIGN KEY (`parent_device_id`) REFERENCES `device` (`id`);

--
-- Omezení pro tabulku `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD CONSTRAINT `FK_801762CC530CB6F1` FOREIGN KEY (`parent_sensor_id`) REFERENCES `sensor` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
