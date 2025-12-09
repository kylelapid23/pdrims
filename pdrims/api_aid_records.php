<?php
header('Content-Type: application/json');
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch all aid records with household head name
    try {
        $sql = "SELECT ar.*, 
                TRIM(CONCAT(h.head_surname, ', ', h.head_firstname, ' ', COALESCE(CONCAT(h.head_middle_name, '.'), ''))) as recipient_name,
                h.purok as recipient_purok
                FROM aid_records ar
                LEFT JOIN households h ON ar.household_id = h.id
                ORDER BY ar.date_distributed DESC, ar.created_at DESC";
        $stmt = $pdo->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($records);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'POST') {
    // Save new aid record
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Basic validation
    if (empty($input['householdId']) || empty($input['aidType']) || empty($input['dateDistributed'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields (householdId, aidType, dateDistributed)']);
        exit;
    }

    try {
        // Handle multiple households (bulk distribution)
        $householdIds = is_array($input['householdId']) ? $input['householdId'] : [$input['householdId']];
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO aid_records (household_id, recipient_name, aid_type, quantity, date_distributed, distributed_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $insertedCount = 0;
        foreach ($householdIds as $hId) {
            // Get recipient name from households table
            $nameStmt = $pdo->prepare("SELECT TRIM(CONCAT(head_surname, ', ', head_firstname, ' ', COALESCE(CONCAT(head_middle_name, '.'), ''))) as full_name FROM households WHERE id = ?");
            $nameStmt->execute([$hId]);
            $nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $recipientName = $nameRow ? $nameRow['full_name'] : 'Unknown';
            
            $stmt->execute([
                $hId,
                $recipientName,
                $input['aidType'],
                $input['quantity'] ?? '',
                $input['dateDistributed'],
                $input['distributedBy'] ?? '',
                $input['notes'] ?? ''
            ]);
            $insertedCount++;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'insertedCount' => $insertedCount]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'PUT') {
    // Update existing aid record
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id']) || empty($input['aidType']) || empty($input['dateDistributed'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields (id, aidType, dateDistributed)']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE aid_records SET 
            aid_type = ?, quantity = ?, date_distributed = ?, distributed_by = ?, notes = ?
            WHERE id = ?");
        
        $stmt->execute([
            $input['aidType'],
            $input['quantity'] ?? '',
            $input['dateDistributed'],
            $input['distributedBy'] ?? '',
            $input['notes'] ?? '',
            $input['id']
        ]);

        echo json_encode(['success' => true, 'id' => $input['id']]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'DELETE') {
    // Delete aid record
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing aid record ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM aid_records WHERE id = ?");
        $stmt->execute([$input['id']]);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
