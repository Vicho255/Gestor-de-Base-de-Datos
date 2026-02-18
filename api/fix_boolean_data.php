<?php
// api/fix_boolean_data.php
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = getDB();
    
    echo "<h1>Corrigiendo datos booleanos en emprendimiento</h1>";
    
    // Primero, mostrar el estado actual
    echo "<h2>Estado actual:</h2>";
    $sql = "SELECT id, nombre, activo, pg_typeof(activo) as tipo FROM emprendimiento";
    $stmt = $db->query($sql);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Activo</th><th>Tipo</th><th>Valor crudo</th></tr>";
    foreach ($datos as $row) {
        $valor_crudo = var_export($row['activo'], true);
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['activo']}</td>";
        echo "<td>{$row['tipo']}</td>";
        echo "<td>{$valor_crudo}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Corregir los datos
    echo "<h2>Corrigiendo datos...</h2>";
    
    // Caso 1: Vacíos o NULL a 'f'
    $update1 = $db->prepare("UPDATE emprendimiento SET activo = 'f' WHERE activo IS NULL OR activo = ''");
    $afectados1 = $update1->execute();
    echo "<p>Vacios/NULL corregidos a 'f': " . ($afectados1 ? '✓' : '✗') . "</p>";
    
    // Caso 2: '1' a 't'
    $update2 = $db->prepare("UPDATE emprendimiento SET activo = 't' WHERE activo = '1' OR activo = 1");
    $afectados2 = $update2->execute();
    echo "<p>'1' corregidos a 't': " . ($afectados2 ? '✓' : '✗') . "</p>";
    
    // Caso 3: '0' a 'f'
    $update3 = $db->prepare("UPDATE emprendimiento SET activo = 'f' WHERE activo = '0' OR activo = 0");
    $afectados3 = $update3->execute();
    echo "<p>'0' corregidos a 'f': " . ($afectados3 ? '✓' : '✗') . "</p>";
    
    // Mostrar estado final
    echo "<h2>Estado después de la corrección:</h2>";
    $sql = "SELECT id, nombre, activo FROM emprendimiento ORDER BY id";
    $stmt = $db->query($sql);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Activo</th><th>Interpretación</th></tr>";
    foreach ($datos as $row) {
        $interpretacion = ($row['activo'] === 't') ? 'Activo' : 'Inactivo';
        $color = ($row['activo'] === 't') ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['activo']}</td>";
        echo "<td style='color:{$color}'><b>{$interpretacion}</b></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color:green'>✅ Corrección completada</h2>";
    echo "<p><a href='emprendimientos.php' target='_blank'>Ver emprendimientos</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>