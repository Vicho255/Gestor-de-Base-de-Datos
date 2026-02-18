<?php
// api/get_categorias.php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = getDB();
    
    $query = 'SELECT id, nombre, descripcion FROM categoria ORDER BY id';
    $stmt = $db->query($query);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($categorias);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener categorías: ' . $e->getMessage()]);
}
?>