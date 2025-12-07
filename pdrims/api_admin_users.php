<?php
header('Content-Type: application/json');
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $allUsers = [];

    // Fetch Officials
    try {
        $stmt = $pdo->query("SELECT id, first_name, middle_name, surname, role, email, is_verified FROM officials");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = $row['first_name'] . ' ' . $row['surname'];
            $status = $row['is_verified'] ? 'Active' : 'Pending';
            
            // Normalize Role for Frontend Consistency
            $dbRole = $row['role'];
            $displayRole = $dbRole;
            
            // Map variations to standard display roles
            if (stripos($dbRole, 'admin') !== false) {
                $displayRole = 'System Administrator';
            } elseif (stripos($dbRole, 'official') !== false || stripos($dbRole, 'captain') !== false || stripos($dbRole, 'kagawad') !== false) {
                // Determine if specifically designated, otherwise default to Barangay Official
                $displayRole = 'Barangay Official'; 
            }

            $allUsers[] = [
                'id' => 'off_' . $row['id'], // Prefix to avoid collision
                'original_id' => $row['id'],
                'type' => 'official',
                'name' => $name,
                'role' => $displayRole,
                'email' => $row['email'],
                'status' => $status,
                'lastActive' => 'N/A' // Not tracking activity yet
            ];
        }

        // Fetch Viewers
        $stmt = $pdo->query("SELECT id, first_name, surname, email FROM users");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = $row['first_name'] . ' ' . $row['surname'];
            $allUsers[] = [
                'id' => 'usr_' . $row['id'],
                'original_id' => $row['id'],
                'type' => 'viewer',
                'name' => $name,
                'role' => 'Beneficiary (Viewer)',
                'email' => $row['email'],
                'status' => 'Active', // Viewers are auto-active
                'lastActive' => 'N/A'
            ];
        }

        echo json_encode($allUsers);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'POST') {
    // Handle Create, Approve / Delete
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'create') {
        // Create new Account (Official or Viewer)
        $type = $input['type'] ?? 'official'; // Default to official
        $firstName = $input['firstName'] ?? '';
        $surname = $input['surname'] ?? '';
        $email = $input['email'] ?? '';
        $role = $input['role'] ?? '';
        $password = $input['password'] ?? '';
        
        if (!$firstName || !$surname || !$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($type === 'viewer') {
                // Check if email exists in users
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Email already registered in users']);
                    exit;
                }
                
                // Additional fields for Viewers
                $age = $input['age'] ?? 0;
                $contact = $input['contact'] ?? '';
                $isMember = $input['isMember'] ?? 0; // Default 0 for admin created unless specified
                
                // Insert into users table
                // Note: middle_initial is required in DB schema? Let's assume empty string if not provided.
                // Looking at api_signup_viewer, it inserts middle_initial.
                $stmt = $pdo->prepare("INSERT INTO users (first_name, surname, middle_initial, contact_number, age, email, password_hash, is_member, purok, is_head, household_head) 
                        VALUES (?, ?, '', ?, ?, ?, ?, ?, NULL, 0, NULL)");
                
                $stmt->execute([
                    $firstName,
                    $surname,
                    $contact,
                    $age,
                    $email,
                    $passwordHash,
                    $isMember
                ]);

            } else {
                // Officials
                // Check if email exists in officials
                $stmt = $pdo->prepare("SELECT id FROM officials WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Email already registered in officials']);
                    exit;
                }

                // Create as Verified (1) since Admin is creating it
                $stmt = $pdo->prepare("INSERT INTO officials (first_name, surname, email, role, password_hash, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$firstName, $surname, $email, $role, $passwordHash]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    $id = $input['id'] ?? ''; // e.g. 'off_5'

    if (!$action || !$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing action or id']);
        exit;
    }

    $parts = explode('_', $id);
    $type = $parts[0]; // 'off' or 'usr'
    $dbId = $parts[1];

    try {
        if ($action === 'approve' && $type === 'off') {
            $stmt = $pdo->prepare("UPDATE officials SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$dbId]);
            echo json_encode(['success' => true]);
        }
        elseif ($action === 'delete') {
            // Validate Admin Password (Hardcoded for Dev)
            $password = $input['password'] ?? '';

            if (!$password) {
                 echo json_encode(['error' => 'Password confirmation required']);
                 exit;
            }
            
            // Simplified check as per user request
            if ($password !== 'admin123') {
                 echo json_encode(['error' => 'Invalid password']);
                 exit;
            }

            if ($type === 'off') {
                $stmt = $pdo->prepare("DELETE FROM officials WHERE id = ?");
                $stmt->execute([$dbId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$dbId]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
