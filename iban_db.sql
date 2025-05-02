-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 02 May 2025, 22:53:41
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `iban_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ibans`
--

CREATE TABLE `ibans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `iban_number` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `bank_logo` varchar(255) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ibans`
--

INSERT INTO `ibans` (`id`, `user_id`, `bank_name`, `account_name`, `iban_number`, `description`, `bank_logo`, `slug`, `is_active`, `created_at`) VALUES
(14, 1, 'Garanti', 'Burak Kılıç', 'TR123456789101112131415161', 'Açıklamayı boş bırakın', 'garanti.png', '', 1, '2025-05-02 20:34:34'),
(15, 1, 'Yapı Kredi', 'Burak Kılıç', 'TR123456789101112131415161', 'Açıklamayı boş bırakın', 'yapikredi.png', '', 1, '2025-05-02 20:35:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `iban_shares`
--

CREATE TABLE `iban_shares` (
  `id` int(11) NOT NULL,
  `iban_id` int(11) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `share_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `iban_views`
--

CREATE TABLE `iban_views` (
  `id` int(11) NOT NULL,
  `iban_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `view_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `company_name`, `phone`, `created_at`) VALUES
(1, 'admin', '$2y$10$O/vZ/ND65vG3WF3IBHNVsOpJKDCkr2NRZi5P/lFPf.eSfPhFdhDAu', 'admin@example.com', NULL, NULL, '2025-05-01 21:09:19');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ibans`
--
ALTER TABLE `ibans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `iban_shares`
--
ALTER TABLE `iban_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iban_id` (`iban_id`);

--
-- Tablo için indeksler `iban_views`
--
ALTER TABLE `iban_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iban_id` (`iban_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ibans`
--
ALTER TABLE `ibans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `iban_shares`
--
ALTER TABLE `iban_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `iban_views`
--
ALTER TABLE `iban_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ibans`
--
ALTER TABLE `ibans`
  ADD CONSTRAINT `ibans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `iban_shares`
--
ALTER TABLE `iban_shares`
  ADD CONSTRAINT `iban_shares_ibfk_1` FOREIGN KEY (`iban_id`) REFERENCES `ibans` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `iban_views`
--
ALTER TABLE `iban_views`
  ADD CONSTRAINT `iban_views_ibfk_1` FOREIGN KEY (`iban_id`) REFERENCES `ibans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
