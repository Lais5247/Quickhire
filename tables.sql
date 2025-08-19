CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('homeowner','maid','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

-- Activities table
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('homeowner','maid','admin') NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

-- Jobs table
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `homeowner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

-- Proposals table
CREATE TABLE `proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `maid_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','rejected','countered') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

-- Statistics table
CREATE TABLE `statistics` (
  `user_id` int(11) NOT NULL,
  `user_type` enum('homeowner','maid') NOT NULL,
  `hired_maids` int(11) DEFAULT 0,
  `money_spent` decimal(10,2) DEFAULT 0,
  `total_jobs` int(11) DEFAULT 0,
  `total_earnings` decimal(10,2) DEFAULT 0,
  PRIMARY KEY (`user_id`)
);

-- Create activities table
CREATE TABLE `activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `user_type` ENUM('homeowner', 'maid') NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);