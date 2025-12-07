<?php
require 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50),
        user_name VARCHAR(100),
        action VARCHAR(100),
        target VARCHAR(255),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "Table 'system_logs' created successfully (or already exists).";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
