<?php
// Database Configuration
// Load generated configuration first so that installer provided values take precedence
$generatedConfig = __DIR__ . '/config.php';
if (file_exists($generatedConfig)) {
    require_once $generatedConfig;
}

// Provide sensible defaults only when the installer configuration is missing
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'robo_user');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', 'robo_pass123');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'robo_trade');
}

// Create connection
function getDBConnection() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (mysqli_sql_exception $exception) {
        throw new RuntimeException('Connection failed: ' . $exception->getMessage(), 0, $exception);
    }
}

// Initialize database and create tables if not exists
function initializeDatabase() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    $conn->query($sql);
    
    $conn->select_db(DB_NAME);
    $conn->set_charset("utf8mb4");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        dob DATE,
        pan_card VARCHAR(20),
        bank_name VARCHAR(255),
        account_number VARCHAR(50),
        ifsc_code VARCHAR(20),
        balance DECIMAL(15,2) DEFAULT 0,
        profile_image VARCHAR(255),
        status ENUM('active', 'suspended', 'blocked') DEFAULT 'active',
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Create deposits table
    $sql = "CREATE TABLE IF NOT EXISTS deposits (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        payment_method VARCHAR(50),
        transaction_id VARCHAR(100),
        upi_id VARCHAR(100),
        screenshot VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        approved_by INT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Create withdrawals table
    $sql = "CREATE TABLE IF NOT EXISTS withdrawals (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        bank_name VARCHAR(255),
        account_number VARCHAR(50),
        ifsc_code VARCHAR(20),
        status ENUM('pending', 'approved', 'rejected', 'processing', 'completed') DEFAULT 'pending',
        admin_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        processed_by INT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Create positions table
    $sql = "CREATE TABLE IF NOT EXISTS positions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        symbol VARCHAR(50) NOT NULL,
        trade_type ENUM('buy', 'sell') NOT NULL,
        quantity INT NOT NULL,
        entry_price DECIMAL(15,4) NOT NULL,
        exit_price DECIMAL(15,4),
        entry_date DATETIME NOT NULL,
        exit_date DATETIME,
        profit_loss DECIMAL(15,2),
        status ENUM('open', 'closed') DEFAULT 'open',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Create watchlist table
    $sql = "CREATE TABLE IF NOT EXISTS watchlist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        symbol VARCHAR(50) NOT NULL,
        exchange VARCHAR(20),
        sector VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_watchlist (user_id, symbol)
    )";
    $conn->query($sql);
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('deposit', 'withdrawal', 'trade', 'system') DEFAULT 'system',
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Create admin_logs table
    $sql = "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        target_user_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Create default admin user if not exists
    $check = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
    if ($check->num_rows == 0) {
        $admin_user_id = 'admin001';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (user_id, password, first_name, last_name, email, role, status) 
                VALUES ('$admin_user_id', '$admin_password', 'Admin', 'User', 'admin@robotrade.com', 'admin', 'active')";
        $conn->query($sql);
    }
    
    $conn->close();
}

// Call initialization
// initializeDatabase();
?>

