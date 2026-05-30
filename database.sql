CREATE DATABASE IF NOT EXISTS `vodrf` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vodrf`;

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `login` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `fullname` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    PRIMARY KEY (`id`),
    UNIQUE KEY `login_unique` (`login`)
);

CREATE TABLE `applications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `address` TEXT NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `app_date` DATE NOT NULL,
    `app_time` TIME NOT NULL,
    `service` VARCHAR(255) NOT NULL,
    `payment` VARCHAR(50) NOT NULL,
    `status` ENUM('Новая', 'Идет обучение', 'Завершено') NOT NULL DEFAULT 'Новая',
    `review` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

INSERT INTO `users` (`login`, `password`, `fullname`, `phone`, `email`, `role`)
VALUES (
    'Admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin26',
    '8(999)123-12-34',
    'admin@vodrf.ru',
    'admin'
);