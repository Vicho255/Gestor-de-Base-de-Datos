<?php
// api/get_emprendimientos.php - VERSIÓN TRANSFORMANDO DATOS
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = getDB();
    
    $query = "
        SELECT 
            e.id,
            e.nombre,
            e.descripcion,
            e.activo,
            c.nombre as categoria,
            CONCAT(p.nombre, ' ', p.apellido) as duenno
        FROM emprendimiento e
        LEFT JOIN categoria c ON e.categoria_id = c.id
        LEFT JOIN Personas p ON e.duenno_rut = p.RUT
        ORDER BY e.id
    ";
    
    $stmt = $db->query($query);
    $emprendimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformar los nombres de las claves a lo que espera JavaScript
    $result = [];
    foreach ($emprendimientos as $emp) {
        // Convertir activo a booleano
        $activo = false;
        if ($emp['activo'] === true || $emp['activo'] === 't' || $emp['activo'] === '1' || $emp['activo'] === 1) {
            $activo = true;
        }
        
        $result[] = [
            'id' => (int)$emp['id'],
            'nombre' => $emp['nombre'],
            'descripcion' => $emp['descripcion'],
            'activo' => $activo,  // <- ¡nombre correcto!
            'categoria_nombre' => $emp['categoria'] ?? 'Sin categoría',  // <- ¡nombre correcto!
            'duenno_nombre' => $emp['duenno'] ?? 'Sin dueño'  // <- ¡nombre correcto!
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>