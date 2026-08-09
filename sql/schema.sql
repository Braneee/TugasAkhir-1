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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
