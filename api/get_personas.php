<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = getDB();

    $sql = "SELECT * FROM get_personas()";
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($personas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
?>