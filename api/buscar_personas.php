<?php
// api/buscar_personas.php
require_once '../config/database.php';
header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode([]);
    exit();
}

try {
    $db = getDB();
    
    // Buscar personas por RUT o nombre
    $sql = '
        SELECT 
            "RUT" as rut,
            nombre,
            apellido,
            CONCAT(nombre, \' \', apellido, \' (\', "RUT", \')\') as label
        FROM Personas 
        WHERE "RUT" ILIKE :query 
           OR nombre ILIKE :query 
           OR apellido ILIKE :query
        ORDER BY apellido, nombre
        LIMIT 10
    ';
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':query' => "%$query%"]);
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($personas);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>