<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Permitir métodos POST y PUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Obtener datos del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos no válidos');
    }
    
    $db = getDB();
    
    // Validar datos requeridos
    if (empty($input['nombre'])) {
        throw new Exception('El nombre de la empresa es requerido');
    }
    
    if (empty($input['categoria_id'])) {
        throw new Exception('La categoría es requerida');
    }
    
    if (empty($input['duenno_rut'])) {
        throw new Exception('El RUT del dueño es requerido');
    }
    
    // Limpiar y validar datos
    $nombre = trim($input['nombre']);
    $categoria_id = (int)$input['categoria_id'];
    $duenno_rut = strtoupper(trim($input['duenno_rut']));
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;
    
    // Verificar si el RUT existe en la tabla Personas
    $stmt = $db->prepare("SELECT COUNT(*) FROM Personas WHERE RUT = ?");
    $stmt->execute([$duenno_rut]);
    $existePersona = $stmt->fetchColumn() > 0;
    
    if (!$existePersona) {
        throw new Exception('El RUT ingresado no existe en el sistema');
    }
    
    // Verificar si ya existe una empresa con este RUT
    $stmt = $db->prepare("SELECT id FROM emprendimiento WHERE duenno_rut = ?");
    $stmt->execute([$duenno_rut]);
    $empresaExistente = $stmt->fetch();
    
    // Determinar si es creación o actualización
    $esEdicion = ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($input['id']));
    
    if ($esEdicion) {
        // Modo edición
        $id = (int)$input['id'];
        
        // Verificar que la empresa existe
        $stmt = $db->prepare("SELECT id FROM emprendimiento WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            throw new Exception('La empresa no existe');
        }
        
        // Actualizar empresa
        $query = "UPDATE emprendimiento 
                  SET nombre = ?, 
                      descripcion = ?, 
                      categoria_id = ?, 
                      duenno_rut = ?,
                      updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $nombre,
            $descripcion,
            $categoria_id,
            $duenno_rut,
            $id
        ]);
        
        $message = 'Empresa actualizada correctamente';
    } else {
        // Modo creación
        if ($empresaExistente) {
            throw new Exception('Ya existe una empresa registrada con este RUT');
        }
        
        // Insertar nueva empresa
        $query = "INSERT INTO emprendimiento 
                  (nombre, descripcion, categoria_id, duenno_rut, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $nombre,
            $descripcion,
            $categoria_id,
            $duenno_rut
        ]);
        
        $message = 'Empresa creada correctamente';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>