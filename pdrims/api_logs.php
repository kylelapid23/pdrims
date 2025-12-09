<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Fetch latest 100 logs
        $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY timestamp DESC LIMIT 100");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($logs);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $userId = $data['user_id'] ?? 'Unknown';
    $userName = $data['user_name'] ?? 'System';
    $action = $data['action'] ?? 'Unknown Action';
    $target = $data['target'] ?? 'N/A';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, target) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $userName, $action, $target]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
