<?php
header('Content-Type: application/json');
require 'config.php';

try {
    // 1. Total households profiled
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM households");
    $totalHouseholds = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Totally destroyed homes (100% damage)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM households WHERE damage_status = '100'");
    $destroyedHomes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. High priority households (>= 75% damage)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM households WHERE damage_status >= '75'");
    $highPriority = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 4. Household count by purok
    $stmt = $pdo->query("SELECT purok, COUNT(*) as count FROM households GROUP BY purok ORDER BY purok");
    $purokData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $purokBreakdown = [];
    foreach ($purokData as $row) {
        $purokBreakdown[$row['purok']] = (int)$row['count'];
    }

    // 5. Aid distribution rate (unique households that received aid / total households)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT household_id) as unique_recipients FROM aid_records");
    $uniqueRecipients = $stmt->fetch(PDO::FETCH_ASSOC)['unique_recipients'];
    $aidDistributionRate = $totalHouseholds > 0 ? round(($uniqueRecipients / $totalHouseholds) * 100) : 0;

    // 6. Total aid records count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aid_records");
    $totalAidRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 7. Aid fulfillment rate (this could be calculated as: households with aid / total households)
    // For now, using the same as distribution rate, but can be adjusted based on business logic
    $aidFulfillmentRate = $totalHouseholds > 0 ? round(($uniqueRecipients / $totalHouseholds) * 100) : 0;

    echo json_encode([
        'totalHouseholds' => (int)$totalHouseholds,
        'destroyedHomes' => (int)$destroyedHomes,
        'highPriority' => (int)$highPriority,
        'purokBreakdown' => $purokBreakdown,
        'aidDistributionRate' => $aidDistributionRate,
        'totalAidRecords' => (int)$totalAidRecords,
        'aidFulfillmentRate' => $aidFulfillmentRate
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

