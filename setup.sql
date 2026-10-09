-- ============================================================
-- StudyBuddy — Database Setup Script
-- Run this in phpMyAdmin (http://localhost/phpmyadmin) via the
-- "SQL" tab, OR import this file using the "Import" tab.
-- ============================================================

-- Create a fresh database just for authentication.
CREATE DATABASE IF NOT EXISTS studybuddy_auth
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

-- Use it for the statements below.
USE studybuddy_auth;

-- Users table matching the register form fields.
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    last_name   VARCHAR(50)  NOT NULL,
    first_name  VARCHAR(50)  NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- plain text (school practical only)
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: a quick test row so login can be tried immediately.
-- INSERT INTO users (last_name, first_name, username, password)
-- VALUES ('Doe', 'Jane', 'jane', 'test123');
