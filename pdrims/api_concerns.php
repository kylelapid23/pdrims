<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require 'config.php';

try {
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS concerns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        purok VARCHAR(50),
        status VARCHAR(50) DEFAULT 'Pending',
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        // Check if this is an update action (Acknowledge/Resolve)
        if (isset($input['action']) && $input['action'] === 'acknowledge') {
            $id = $input['id'] ?? null;
            $response = $input['response'] ?? '';
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Concern ID required']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE concerns SET status = 'Acknowledged', response = ? WHERE id = ?");
            $stmt->execute([$response, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Standard Submission
        $userId = $input['userId'] ?? null;
        $subject = $input['subject'] ?? '';
        $description = $input['description'] ?? '';
        $purok = $input['purok'] ?? '';

        if (!$userId || !$subject || !$description) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO concerns (user_id, subject, description, purok) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $subject, $description, $purok]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = $_GET['userId'] ?? null;
        $type = $_GET['type'] ?? 'user'; // 'user' or 'all'

        if ($type === 'all') {
            // Fetch ALL concerns for Admin with User details
            // We need to join with users table to get names
            $query = "
                SELECT 
                    c.id, c.subject, c.description, c.purok, c.status, c.response, 
                    DATE_FORMAT(c.created_at, '%m/%d/%Y') as created_at,
                    CONCAT(u.first_name, ' ', u.surname) as sender_name
                FROM concerns c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
        } else {
            // Fetch User specific concerns
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, subject, description, purok, status, response, DATE_FORMAT(created_at, '%m/%d/%Y') as created_at FROM concerns WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
        }

        $concerns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'concerns' => $concerns]);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
