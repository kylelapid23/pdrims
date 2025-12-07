<?php
header('Content-Type: application/json');
require 'config.php';

try {
    // 1. Key Metrics
    // Total Profiled
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM households");
    $totalProfiled = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Totally Destroyed (Damage = 100)
    // Note: Assuming damage_status is stored as integer 100 or string '100'
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM households WHERE damage_status = '100'");
    $totalDestroyed = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // High Priority (Damage >= 75)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM households WHERE damage_status >= 75");
    $totalPriority = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 2. Recovery Percent / Aid Fulfillment
    // Logic: (Households with at least one aid record / Total Households) * 100
    // OR: (Total Aid Records / Target # of distributions)
    // Let's use: Percentage of households that have received at least one aid aid.
    
    // Count households with distinct aid recipients
    // Assuming aid_records has household_id column.
    // In viewer.php we see aidRecipientId mapping to household id.
    // Let's check if we can join or query aid_records.
    
    // We'll try to check if aid_records table exists and has household_id or similar.
    // Based on previous files, we haven't seen the schema for aid_records in the SQL create.
    // But typically it would be `aid_records`. 
    // Let's assume `aid_records` table exists with `household_id`.
    
    $aidRecipientsCount = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(DISTINCT household_id) as count FROM aid_records");
        $aidRecipientsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Table might not exist or column different
        $aidRecipientsCount = 0; 
    }

    $recoveryPercent = ($totalProfiled > 0) ? round(($aidRecipientsCount / $totalProfiled) * 100) : 0;


    // 3. Purok Stats (Damage Breakdown by Purok)
    // We want the total count of households per Purok to show in the bar chart
    // Or specifically "Damage Breakdown" might imply households with damage per purok?
    // The chart in viewer.php implies simple count per Purok.
    $purokStats = [];
    $stmt = $pdo->query("SELECT purok, COUNT(*) as count FROM households GROUP BY purok");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $purokStats[$row['purok']] = $row['count'];
    }

    // Response
    echo json_encode([
        'totalProfiled' => $totalProfiled,
        'totalDestroyed' => $totalDestroyed,
        'totalPriority' => $totalPriority,
        'recoveryPercent' => $recoveryPercent,
        'purokStats' => $purokStats,
        'aidRecipientsCount' => $aidRecipientsCount
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
