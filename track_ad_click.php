<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $adId = (int)$input['ad_id'];
    
    try {
        // زيادة عدد النقرات
        $db->query("UPDATE advertisements SET clicks_count = clicks_count + 1 WHERE id = ?", [$adId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
