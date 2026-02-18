<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_GET['rut'])) {
    http_response_code(400);
    echo json_encode(['error' => 'RUT no proporcionado']);
    exit();
}

$rut = trim($_GET['rut']);  // conserva el formato (con puntos y guión)

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM get_personas(:rut)");
    $stmt->execute([':rut' => $rut]);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$persona) {
        http_response_code(404);
        echo json_encode(['error' => 'Persona no encontrada']);
        exit();
    }

    echo json_encode($persona);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>