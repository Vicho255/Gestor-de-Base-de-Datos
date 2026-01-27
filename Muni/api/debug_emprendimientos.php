<?php
// api/debug_emprendimientos.php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Primero, probemos la consulta DIRECTAMENTE
    $query = "
        SELECT 
            e.id,
            e.nombre,
            e.descripcion,
            e.activo,
            e.categoria_id,
            c.nombre as categoria_nombre,
            e.duenno_rut,
            CONCAT(p.nombre, ' ', p.apellido, ' (', e.duenno_rut, ')') as duenno_nombre
        FROM emprendimiento e
        LEFT JOIN categoria c ON e.categoria_id = c.id
        LEFT JOIN Personas p ON e.duenno_rut = p.RUT
        ORDER BY e.id
    ";
    
    $stmt = $db->query($query);
    $emprendimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "=== CONSULTA SQL ===\n";
    echo $query . "\n\n";
    
    echo "=== RESULTADOS EN BRUTO ===\n";
    print_r($emprendimientos);
    
    echo "\n=== PRIMER REGISTRO DETALLADO ===\n";
    if (!empty($emprendimientos)) {
        $first = $emprendimientos[0];
        foreach ($first as $key => $value) {
            echo "{$key}: " . ($value === null ? 'NULL' : "'{$value}'") . " (tipo: " . gettype($value) . ")\n";
        }
        
        echo "\n=== CONVERSIÓN A JSON ===\n";
        $json = json_encode([$first], JSON_PRETTY_PRINT);
        echo $json;
        
        echo "\n=== DECODIFICANDO EL JSON ===\n";
        $decoded = json_decode($json, true);
        print_r($decoded[0]);
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre>Error: " . $e->getMessage() . "</pre>";
}
?>