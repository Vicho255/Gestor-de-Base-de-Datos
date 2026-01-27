<?php
// api/check_rut.php - MEJORADO
require_once '../config/database.php';
header('Content-Type: application/json');

$rut = $_GET['rut'] ?? '';

if (empty($rut)) {
    echo json_encode(['exists' => false]);
    exit();
}

try {
    $db = getDB();
    
    // Función para convertir RUT
    function convertirRUT($rut) {
        $rut = strtoupper(preg_replace('/[^0-9kK\-]/', '', $rut));
        if (strpos($rut, '-') !== false) {
            list($numero, $dv) = explode('-', $rut);
            $numero = number_format($numero, 0, '', '.');
            return $numero . '-' . $dv;
        }
        return $rut;
    }
    
    // Convertir al formato de la base de datos
    $rut_formateado = convertirRUT($rut);
    
    // Buscar en ambos formatos por si acaso
    $sql = 'SELECT "RUT", nombre, apellido FROM Personas WHERE "RUT" = :rut OR "RUT" = :rut_formateado';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':rut' => $rut,
        ':rut_formateado' => $rut_formateado
    ]);
    
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($persona) {
        echo json_encode([
            'exists' => true,
            'persona' => $persona,
            'rut_encontrado' => $persona['RUT']
        ]);
    } else {
        echo json_encode([
            'exists' => false,
            'mensaje' => 'RUT no encontrado. Formatos probados: ' . $rut . ' y ' . $rut_formateado
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
?>