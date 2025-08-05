-- Create a new database named `simple_crud_db`
CREATE DATABASE IF NOT EXISTS `simple_crud_db`;

-- Use the `simple_crud_db` database
USE `simple_crud_db`;

-- Create the `users` table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Insert a default admin user
-- NOTE: The password is 'password123'
INSERT INTO `users` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$wE6y7N.j2H3P.4G5y7T8.w.nK9yG7xM.hJqR.pW.nK9yG7xM.hJqR', 'admin@example.com');