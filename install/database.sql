-- Create database tables for the file management system
-- Created by: ulowa1986
-- Created at: 2025-03-16 14:45:41

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `is_admin` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for files
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `content` longtext NOT NULL,
    `views` int(11) DEFAULT 0,
    `user_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `files_user_fk` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(50) NOT NULL,
    `setting_value` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for advertisements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `advertisements` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ad_type` enum('javascript','banner') NOT NULL,
    `ad_content` longtext,
    `is_enabled` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ad_type` (`ad_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default settings
-- --------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', ''),
('site_url', ''),
('site_logo', ''),
('site_favicon', '');

-- --------------------------------------------------------
-- Insert default advertisement entries
-- --------------------------------------------------------
INSERT INTO `advertisements` (`ad_type`, `ad_content`, `is_enabled`) VALUES
('javascript', '', 0),
('banner', '', 0);
