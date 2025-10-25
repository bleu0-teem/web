-- Database setup for Blue0teem API
-- Run this SQL script to create the necessary tables

CREATE DATABASE IF NOT EXISTS blue0teem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blue0teem;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verification_token VARCHAR(64) NULL,
    email_verified_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT FALSE,
    failed_login_attempts INT DEFAULT 0,
    last_login_attempt TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_email_token (email_verification_token),
    INDEX idx_active (is_active)
);

-- Invite keys table
CREATE TABLE IF NOT EXISTS invite_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invite_key VARCHAR(16) UNIQUE NOT NULL,
    created_by INT NULL,
    used_by INT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invite_key (invite_key),
    INDEX idx_expires (expires_at),
    INDEX idx_used (is_used)
);

-- Login logs table
CREATE TABLE IF NOT EXISTS login_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
);

-- Registration logs table
CREATE TABLE IF NOT EXISTS registration_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
);

-- Sessions table (optional, for additional session management)
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    data TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
);

-- Insert some default invite keys (optional)
INSERT IGNORE INTO invite_keys (invite_key, expires_at) VALUES 
('INVITE1234567890', DATE_ADD(NOW(), INTERVAL 30 DAY)),
('WELCOME12345678', DATE_ADD(NOW(), INTERVAL 30 DAY)),
('BLUE0TEEM123456', DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Create a test user (optional - remove in production)
-- Password: test123
INSERT IGNORE INTO users (username, email, password_hash, is_active, email_verified_at) VALUES 
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, NOW());
