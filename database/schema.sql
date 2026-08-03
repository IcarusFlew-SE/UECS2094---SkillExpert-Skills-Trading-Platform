-- SkillSwap Platform – Database Schema
-- Run once against a local MySQL server: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS swapexpert
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci; -- multi-language sorting, ci = case-insensitive, emoji support

USE swapexpert;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    passwordHash    VARCHAR(255)    NOT NULL,
    creditsBalance  INT             NOT NULL DEFAULT 0,
    createdAt       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
