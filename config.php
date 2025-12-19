<?php
// config.php - Add ML API configuration

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'blocksight');
define('DB_USER', 'root');
define('DB_PASS', '');

// ML API configuration
define('ML_API_URL', 'http://localhost:5000');

// Upload configuration
$config = [
    'upload_dir' => __DIR__ . '/uploads',
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'allowed_types' => ['pdf']
];

// PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>