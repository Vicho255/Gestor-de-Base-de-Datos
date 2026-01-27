<?php
// api/test_join.php
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = getDB();
    
    echo "<h1>Test de JOIN entre tablas</h1>";
    
    // Test 1: Verificar la tabla Personas
    echo "<h2>1. Verificando tabla Personas</h2>";
    $sql1 = "SELECT column_name, data_type FROM information_schema.columns 
             WHERE table_name = 'Personas' AND table_schema = 'public' 
             ORDER BY ordinal_position";
    $stmt1 = $db->query($sql1);
    $columns = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Columna</th><th>Tipo</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['column_name']}</td><td>{$col['data_type']}</td></tr>";
    }
    echo "</table>";
    
    // Test 2: Probar el JOIN exacto
    echo "<h2>2. Probando JOIN exacto</h2>";
    $sql2 = "
        SELECT 
            e.id,
            e.nombre as emprendimiento,
            e.duenno_rut,
            p.RUT as persona_rut,
            p.nombre as persona_nombre,
            p.apellido
        FROM emprendimiento e
        LEFT JOIN Personas p ON e.duenno_rut = p.RUT
        LIMIT 5
    ";
    
    $stmt2 = $db->query($sql2);
    $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Emprendimiento</th><th>Dueño RUT (emp)</th><th>Persona RUT</th><th>Nombre</th><th>Apellido</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['emprendimiento']}</td>";
        echo "<td>{$row['duenno_rut']}</td>";
        echo "<td>{$row['persona_rut']}</td>";
        echo "<td>{$row['persona_nombre']}</td>";
        echo "<td>{$row['apellido']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Verificar valores booleanos
    echo "<h2>3. Verificando campo 'activo'</h2>";
    $sql3 = "SELECT id, nombre, activo, pg_typeof(activo) as tipo FROM emprendimiento LIMIT 5";
    $stmt3 = $db->query($sql3);
    $activos = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Activo</th><th>Tipo</th><th>Es 't'</th><th>Es 1</th><th>Es true</th></tr>";
    foreach ($activos as $row) {
        $es_t = $row['activo'] === 't' ? 'SI' : 'NO';
        $es_1 = $row['activo'] == 1 ? 'SI' : 'NO';
        $es_true = $row['activo'] == true ? 'SI' : 'NO';
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['activo']}</td>";
        echo "<td>{$row['tipo']}</td>";
        echo "<td>{$es_t}</td>";
        echo "<td>{$es_1}</td>";
        echo "<td>{$es_true}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>