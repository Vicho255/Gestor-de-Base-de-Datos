<?php
// api/test_direct_query.php
require_once '../config/database.php';

echo "<pre>";

try {
    $db = getDB();
    
    // Ejecutar la consulta DIRECTAMENTE
    $sql = "SELECT e.id, e.nombre, e.activo, c.nombre as cat, p.nombre as per 
            FROM emprendimiento e 
            LEFT JOIN categoria c ON e.categoria_id = c.id 
            LEFT JOIN Personas p ON e.duenno_rut = p.RUT 
            LIMIT 3";
    
    echo "SQL: $sql\n\n";
    
    $result = $db->query($sql);
    
    // Probar diferentes métodos de fetch
    echo "=== MÉTODO 1: fetchAll(PDO::FETCH_ASSOC) ===\n";
    $data1 = $result->fetchAll(PDO::FETCH_ASSOC);
    print_r($data1);
    
    echo "\n=== MÉTODO 2: fetchAll(PDO::FETCH_NUM) ===\n";
    $result->execute(); // Re-ejecutar
    $data2 = $result->fetchAll(PDO::FETCH_NUM);
    print_r($data2);
    
    echo "\n=== MÉTODO 3: fetch(PDO::FETCH_OBJ) ===\n";
    $result->execute(); // Re-ejecutar
    while ($row = $result->fetch(PDO::FETCH_OBJ)) {
        echo "ID: {$row->id}, Nombre: {$row->nombre}, Categoría: {$row->cat}, Persona: {$row->per}\n";
    }
    
    echo "\n=== VERIFICANDO ENCODING ===\n";
    echo "Client encoding: " . $db->query("SHOW client_encoding")->fetchColumn() . "\n";
    echo "Server encoding: " . $db->query("SHOW server_encoding")->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";
?>