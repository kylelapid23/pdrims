<?php
header('Content-Type: application/json');
require 'config.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT first_name, surname, is_member FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'firstName' => $user['first_name'],
            'surname' => $user['surname'],
            'isMember' => (bool)$user['is_member']
        ]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
