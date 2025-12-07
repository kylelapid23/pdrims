<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Required fields
$required = ['firstName', 'surname', 'middleInitial', 'contact', 'age', 'email', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Prepare INSERT
    $sql = "INSERT INTO users (first_name, surname, middle_initial, contact_number, age, email, password_hash, is_member, purok, is_head, household_head) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $isMember = !empty($data['isMember']);
    $purok = $data['purok'] ?? null;
    $isHead = !empty($data['isHead']);
    $householdHead = $data['householdHead'] ?? null;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['firstName'],
        $data['surname'],
        $data['middleInitial'],
        $data['contact'],
        $data['age'],
        $data['email'],
        $passwordHash,
        $isMember ? 1 : 0,
        $purok,
        $isHead ? 1 : 0,
        $householdHead
    ]);

    echo json_encode(['success' => true, 'message' => 'Registration successful']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
