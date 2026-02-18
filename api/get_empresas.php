<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Obtener parámetro de búsqueda
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Construir query base
    $query = "
        SELECT 
            e.id,
            e.nombre,
            e.descripcion,
            e.categoria_id,
            e.duenno_rut,
            c.nombre as categoria_nombre,
            CONCAT(p.nombre, ' ', p.apellido) as duenno_nombre
        FROM emprendimiento e
        LEFT JOIN categoria c ON e.categoria_id = c.id
        LEFT JOIN Personas p ON e.duenno_rut = p.RUT
    ";
    
    // Agregar filtro de búsqueda si existe
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $query .= " WHERE e.nombre LIKE :search 
                    OR e.descripcion LIKE :search 
                    OR p.nombre LIKE :search 
                    OR p.apellido LIKE :search
                    OR e.duenno_rut LIKE :search";
    }
    
    $query .= " ORDER BY e.id DESC";
    
    $stmt = $db->prepare($query);
    
    if (!empty($search)) {
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $emprendimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear respuesta
    $result = [];
    foreach ($emprendimientos as $emp) {
        $result[] = [
            'id' => (int)$emp['id'],
            'nombre' => $emp['nombre'] ?? '',
            'descripcion' => $emp['descripcion'] ?? '',
            'categoria_id' => $emp['categoria_id'] ?? null,
            'categoria_nombre' => $emp['categoria_nombre'] ?? 'Sin categoría',
            'duenno_rut' => $emp['duenno_rut'] ?? '',
            'duenno_nombre' => $emp['duenno_nombre'] ?? 'Sin dueño'
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener empresas: ' . $e->getMessage()]);
}
?>