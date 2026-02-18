<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Obtener RUT (puede venir como parámetro GET o en el body)
$rut = null;
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteVars);
    $rut = $deleteVars['rut'] ?? $_GET['rut'] ?? null;
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $rut = $input['rut'] ?? $_POST['rut'] ?? $_GET['rut'] ?? null;
}

if (!$rut) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'RUT no proporcionado']);
    exit();
}

$rut = strtoupper(trim($rut));

try {
    $db = getDB();

    // Verificar existencia
    $check = $db->prepare("SELECT 1 FROM Personas WHERE rut = :rut");
    $check->execute([':rut' => $rut]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Persona no encontrada']);
        exit();
    }

    // Eliminar persona (las tablas relacionadas deberían borrarse en cascada)
    $delete = $db->prepare("DELETE FROM Personas WHERE rut = :rut");
    $delete->execute([':rut' => $rut]);

    echo json_encode([
        'success' => true,
        'message' => 'Persona eliminada correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}