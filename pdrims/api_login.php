<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['username'] ?? ''; // usage in frontend is username
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

try {
    // 1. Check Officials Table
    $stmt = $pdo->prepare("SELECT id, first_name, surname, role, is_verified, password_hash FROM officials WHERE email = ?");
    $stmt->execute([$email]);
    $official = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($official) {
        if (password_verify($password, $official['password_hash'])) {
            // Success Official
            echo json_encode([
                'id' => $official['id'],
                'name' => $official['first_name'] . ' ' . $official['surname'],
                'role' => ($official['role'] === 'System Administrator') ? 'admin' : 'official', // Map to frontend expectations
                'originalRole' => $official['role'], // Original role from database
                'verified' => (bool)$official['is_verified']
            ]);
            exit;
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            exit;
        }
    }

    // 2. Check Viewers/Users Table
    $stmt = $pdo->prepare("SELECT id, first_name, surname, role, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $viewer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($viewer) {
        if (password_verify($password, $viewer['password_hash'])) {
            // Success Viewer
            echo json_encode([
                'id' => $viewer['id'],
                'name' => $viewer['first_name'] . ' ' . $viewer['surname'],
                'role' => 'viewer',
                'verified' => true
            ]);
            exit;
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            exit;
        }
    }

    // Not found in either
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
