<?php
// api/add_emprendimiento.php - CON FORMATO DE RUT CON PUNTOS
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (empty($input['nombre']) || empty($input['categoria_id']) || empty($input['duenno_rut'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
    exit();
}

try {
    $db = getDB();
    
    $nombre = trim($input['nombre']);
    $categoria_id = (int)$input['categoria_id'];
    $duenno_rut = trim($input['duenno_rut']);
    $descripcion = !empty($input['descripcion']) ? trim($input['descripcion']) : null;
    $activo = isset($input['activo']) ? (bool)$input['activo'] : true;
    
    // CONVERTIR RUT AL FORMATO CON PUNTOS (como está en tu base de datos)
    // Si viene como 12345678-9, convertirlo a 12.345.678-9
    $duenno_rut = convertirRUTaFormatoConPuntos($duenno_rut);
    
    // Verificar categoría
    $checkCat = $db->prepare('SELECT id, nombre FROM categoria WHERE id = :id');
    $checkCat->execute([':id' => $categoria_id]);
    $categoria = $checkCat->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoria) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Categoría no encontrada']);
        exit();
    }
    
    // Verificar persona (usando el RUT convertido)
    $checkPer = $db->prepare('SELECT rut, nombre, apellido FROM Personas WHERE rut = :rut');
    $checkPer->execute([':rut' => $duenno_rut]);
    $persona = $checkPer->fetch(PDO::FETCH_ASSOC);
    
    if (!$persona) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'error' => 'Persona no encontrada con RUT: ' . $duenno_rut,
            'sugerencia' => 'Asegúrese de usar el formato correcto (ej: 12.345.678-9)'
        ]);
        exit();
    }
    
    // Obtener próximo ID
    $sqlNextId = 'SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM emprendimiento';
    $stmtNextId = $db->query($sqlNextId);
    $nextId = $stmtNextId->fetch(PDO::FETCH_ASSOC)['next_id'];
    
    // Insertar
    $sql = 'INSERT INTO emprendimiento (id, nombre, descripcion, categoria_id, duenno_rut, activo) 
            VALUES (:id, :nombre, :descripcion, :categoria_id, :duenno_rut, :activo)';
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':id' => $nextId,
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':categoria_id' => $categoria_id,
        ':duenno_rut' => $duenno_rut,
        ':activo' => $activo ? 't' : 'f'
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Emprendimiento registrado exitosamente',
            'id' => $nextId,
            'data' => [
                'nombre' => $nombre,
                'categoria' => $categoria['nombre'],
                'duenno' => $persona['nombre'] . ' ' . $persona['apellido'],
                'rut_formateado' => $duenno_rut
            ]
        ]);
    } else {
        throw new Exception("Error al insertar emprendimiento");
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Función para convertir RUT al formato con puntos
function convertirRUTaFormatoConPuntos($rut) {
    // Limpiar el RUT
    $rut = strtoupper(preg_replace('/[^0-9kK\-]/', '', $rut));
    
    // Separar número y dígito verificador
    if (strpos($rut, '-') !== false) {
        list($numero, $dv) = explode('-', $rut);
    } else {
        $numero = substr($rut, 0, -1);
        $dv = substr($rut, -1);
    }
    
    // Formatear con puntos
    $numero = number_format($numero, 0, '', '.');
    
    return $numero . '-' . $dv;
}
?>