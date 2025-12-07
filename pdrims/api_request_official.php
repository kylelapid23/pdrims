<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Required fields
$required = ['firstName', 'surname', 'email', 'role', 'password']; 
// middleName is optional
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

try {
    // Check if email already exists in officials
    $stmt = $pdo->prepare("SELECT id FROM officials WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $middleName = $data['middleName'] ?? ($data['middleInitial'] ?? null);

    $sql = "INSERT INTO officials (first_name, middle_name, surname, email, role, password_hash, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, 0)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['firstName'],
        $middleName,
        $data['surname'],
        $data['email'],
        $data['role'],
        $passwordHash
    ]);

    echo json_encode(['success' => true, 'message' => 'Account request submitted for approval']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
