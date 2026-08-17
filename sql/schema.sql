CREATE DATABASE IF NOT EXISTS campus_search;
USE campus_search;

-- Table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL,
    name VARCHAR(100),
    nim VARCHAR(20) UNIQUE NULL -- Null for admins
);

-- Table for academic data (Academic API targets this)
CREATE TABLE IF NOT EXISTS academic_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL,
    mata_kuliah VARCHAR(100) NOT NULL,
    nilai VARCHAR(2) NOT NULL,
    semester INT NOT NULL,
    FOREIGN KEY (nim) REFERENCES users(nim)
);

-- Table for finance data (Academic API targets this)
CREATE TABLE IF NOT EXISTS finance_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    bill DECIMAL(10,2) NOT NULL,
    status ENUM('lunas', 'belum_lunas') NOT NULL,
    FOREIGN KEY (nim) REFERENCES users(nim)
);

-- Table for search history
CREATE TABLE IF NOT EXISTS search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL,
    query_text TEXT NOT NULL,
    result_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for search feedback (Precision/Recall metric evaluation)
CREATE TABLE IF NOT EXISTS search_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_text TEXT NOT NULL,
    document_title VARCHAR(255) NOT NULL,
    document_url VARCHAR(255) NULL,
    feedback_type ENUM('up', 'down') NOT NULL,
    nim VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for AI Tuning Configuration
CREATE TABLE IF NOT EXISTS system_config (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
);

-- Table for Web Crawler Logs
CREATE TABLE IF NOT EXISTS crawler_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_url VARCHAR(255) NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    error_message TEXT NULL,
    documents_indexed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
