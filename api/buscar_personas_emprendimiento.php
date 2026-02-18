<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

try {
    $db = getDB();
    
    if (empty($query)) {
        // Si no hay query, devolver las primeras 20 personas
        $sql = 'SELECT rut, nombre, apellido FROM personas ORDER BY apellido, nombre LIMIT 20';
        $stmt = $db->query($sql);
    } else {
        // Buscar por RUT o nombre
        $sql = 'SELECT rut, nombre, apellido FROM personas 
                WHERE rut ILIKE :query 
                   OR nombre ILIKE :query 
                   OR apellido ILIKE :query
                ORDER BY apellido, nombre 
                LIMIT 20';
        $stmt = $db->prepare($sql);
        $stmt->execute([':query' => "%$query%"]);
    }
    
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear para autocompletado
    $result = [];
    foreach ($personas as $persona) {
        $result[] = [
            'value' => $persona['rut'],
            'label' => $persona['nombre'] . ' ' . $persona['apellido'] . ' (' . $persona['rut'] . ')',
            'nombre' => $persona['nombre'],
            'apellido' => $persona['apellido']
        ];
    }
    
    echo json_encode($result);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>