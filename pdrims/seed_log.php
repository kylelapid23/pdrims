<?php
require 'config.php';

try {
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, target) VALUES (?, ?, ?, ?)");
    $stmt->execute(['SYSTEM', 'System Admin', 'System Initialized', 'Logs Integration Complete']);
    echo "Test log entry created.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
