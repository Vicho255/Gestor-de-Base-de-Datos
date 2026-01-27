<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Construir la consulta de actualización dinámicamente
    $updates = [];
    $params = [':id' => $id];
    
    if (isset($input['activo'])) {
        $updates[] = "activo = :activo";
        $params[':activo'] = (bool)$input['activo'];
    }
    
    if (isset($input['nombre'])) {
        $updates[] = "nombre = :nombre";
        $params[':nombre'] = trim($input['nombre']);
    }
    
    if (isset($input['descripcion'])) {
        $updates[] = "descripcion = :descripcion";
        $params[':descripcion'] = trim($input['descripcion']);
    }
    
    if (isset($input['categoria_id'])) {
        $updates[] = "categoria_id = :categoria_id";
        $params[':categoria_id'] = (int)$input['categoria_id'];
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No hay datos para actualizar']);
        exit();
    }
    
    $sql = "UPDATE Emprendimiento SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Emprendimiento actualizado exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontró el emprendimiento o no hubo cambios'
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