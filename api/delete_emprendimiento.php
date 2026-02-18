<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID requerido']);
    exit();
}

try {
    $db = getDB();
    
    $id = (int)$input['id'];
    
    $stmt = $db->prepare("DELETE FROM Emprendimiento WHERE id = :id");
    $result = $stmt->execute([':id' => $id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Emprendimiento eliminado exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontró el emprendimiento'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage()
    ]);
}
?>