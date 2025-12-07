<?php
header('Content-Type: application/json');
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch all households with member count
    try {
        $sql = "SELECT h.*, 
                TRIM(CONCAT(head_surname, ', ', head_firstname, ' ', COALESCE(CONCAT(head_middle_name, '.'), ''))) as full_name,
                (SELECT COUNT(*) FROM household_members WHERE household_id = h.id) as computed_member_count 
                FROM households h 
                ORDER BY h.created_at DESC";
        $stmt = $pdo->query($sql);
        $households = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch members for all households
        // Optimization: Fetch all members and group by household_id in PHP
        // This avoids N+1 query problem
        if (!empty($households)) {
            $sqlMembers = "SELECT household_id, surname, first_name, middle_initial, age, gender, relationship FROM household_members";
            $stmtMembers = $pdo->query($sqlMembers);
            $allMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

            // Group members by household_id
            $membersByHousehold = [];
            foreach ($allMembers as $member) {
                $membersByHousehold[$member['household_id']][] = $member;
            }

            // Attach members to households
            foreach ($households as &$household) {
                $hId = $household['id'];
                $household['members'] = $membersByHousehold[$hId] ?? [];
            }
        }
        
        echo json_encode($households);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'POST') {
    // Save new household profile
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Basic validation
    if (empty($input['headSurname']) || empty($input['purok'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert Household
        $stmt = $pdo->prepare("INSERT INTO households (head_surname, head_firstname, head_middle_name, head_age, head_gender, contact_number, purok, post_disaster_condition, livelihood_status, damage_status, initial_needs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $input['headSurname'],
            $input['headFirstname'],
            $input['headMiddleInitial'] ?? '',
            $input['headAge'],
            $input['headGender'],
            $input['contactNumber'] ?? '',
            $input['purok'],
            $input['headCondition'],
            $input['headLivelihood'],
            $input['damageStatus'],
            $input['initialNeeds'] ?? ''
        ]);
        
        $householdId = $pdo->lastInsertId();

        // 2. Insert Members
        if (!empty($input['members']) && is_array($input['members'])) {
            $memberStmt = $pdo->prepare("INSERT INTO household_members (household_id, surname, first_name, middle_initial, age, gender, relationship, livelihood_status, condition_status, residence_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($input['members'] as $member) {
                $memberStmt->execute([
                    $householdId,
                    $member['surname'],
                    $member['firstName'],
                    $member['middleInitial'] ?? '',
                    $member['age'],
                    $member['gender'],
                    $member['relationship'],
                    $member['livelihoodStatus'],
                    $member['conditionStatus'],
                    $member['residenceStatus']
                ]);
            }
            
            // Update member count in households table
            $countStmt = $pdo->prepare("UPDATE households SET member_count = ? WHERE id = ?");
            $countStmt->execute([count($input['members']), $householdId]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $householdId]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'PUT') {
    // Update existing household profile
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['id']) || empty($input['headSurname']) || empty($input['purok'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields (id, headSurname, purok)']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $householdId = $input['id'];

        // 1. Update Household
        $stmt = $pdo->prepare("UPDATE households SET 
            head_surname = ?, head_firstname = ?, head_middle_name = ?, 
            head_age = ?, head_gender = ?, contact_number = ?, purok = ?, 
            post_disaster_condition = ?, livelihood_status = ?, damage_status = ?, initial_needs = ?
            WHERE id = ?");
        
        $stmt->execute([
            $input['headSurname'],
            $input['headFirstname'],
            $input['headMiddleInitial'] ?? '',
            $input['headAge'],
            $input['headGender'],
            $input['contactNumber'] ?? '',
            $input['purok'],
            $input['headCondition'],
            $input['headLivelihood'],
            $input['damageStatus'],
            $input['initialNeeds'] ?? '',
            $householdId
        ]);

        // 2. Delete old members and insert new ones
        $deleteStmt = $pdo->prepare("DELETE FROM household_members WHERE household_id = ?");
        $deleteStmt->execute([$householdId]);
        
        if (!empty($input['members']) && is_array($input['members'])) {
            $memberStmt = $pdo->prepare("INSERT INTO household_members (household_id, surname, first_name, middle_initial, age, gender, relationship, livelihood_status, condition_status, residence_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($input['members'] as $member) {
                $memberStmt->execute([
                    $householdId,
                    $member['surname'],
                    $member['firstName'],
                    $member['middleInitial'] ?? '',
                    $member['age'],
                    $member['gender'],
                    $member['relationship'],
                    $member['livelihoodStatus'],
                    $member['conditionStatus'],
                    $member['residenceStatus']
                ]);
            }
            
            $countStmt = $pdo->prepare("UPDATE households SET member_count = ? WHERE id = ?");
            $countStmt->execute([count($input['members']), $householdId]);
        } else {
            $countStmt = $pdo->prepare("UPDATE households SET member_count = 0 WHERE id = ?");
            $countStmt->execute([$householdId]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $householdId]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'DELETE') {
    // Delete household and its members
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing household ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $householdId = $input['id'];

        // Delete members first (foreign key constraint)
        $deleteMembers = $pdo->prepare("DELETE FROM household_members WHERE household_id = ?");
        $deleteMembers->execute([$householdId]);
        
        // Delete aid records for this household
        $deleteAid = $pdo->prepare("DELETE FROM aid_records WHERE household_id = ?");
        $deleteAid->execute([$householdId]);
        
        // Delete household
        $deleteHousehold = $pdo->prepare("DELETE FROM households WHERE id = ?");
        $deleteHousehold->execute([$householdId]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
