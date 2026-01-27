<?php
// api/debug_tables.php
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = getDB();
    
    echo "<h1>Debug de Tablas</h1>";
    
    // Verificar tabla emprendimiento
    echo "<h2>Tabla: emprendimiento</h2>";
    $stmt = $db->query("SELECT * FROM emprendimiento LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // Verificar tabla categoria
    echo "<h2>Tabla: categoria</h2>";
    $stmt = $db->query("SELECT * FROM categoria");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // Verificar tabla Personas
    echo "<h2>Tabla: Personas</h2>";
    $stmt = $db->query('SELECT rut, nombre, apellido FROM Personas LIMIT 5');
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // Verificar relaciones
    echo "<h2>Consulta JOIN completa:</h2>";
    $sql = "
        SELECT 
            e.id,
            e.nombre as emprendimiento_nombre,
            e.descripcion,
            e.activo,
            e.categoria_id,
            e.duenno_rut,
            c.nombre as categoria_nombre,
            p.nombre as persona_nombre,
            p.apellido as persona_apellido
        FROM emprendimiento e
        LEFT JOIN categoria c ON e.categoria_id = c.id
        LEFT JOIN Personas p ON e.duenno_rut = p.rut
        LIMIT 10
    ";
    
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>