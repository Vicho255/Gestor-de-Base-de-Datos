<?php
// api/get_emprendimientos_exportar.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$orden = $data['orden'] ?? 'nombre';
$direccion = $data['direccion'] ?? 'ASC';
$limite = intval($data['limite'] ?? 20);
$filtroEstado = $data['estado'] ?? 'todos'; // 'todos', 'activos', 'inactivos'

// Validar límite
$limite = max(1, min(1000, $limite));

// Columnas permitidas para ordenar
$columnasPermitidas = ['nombre', 'categoria_nombre', 'duenno_nombre', 'activo', 'created_at'];
if (!in_array($orden, $columnasPermitidas)) {
    $orden = 'nombre';
}

// Validar dirección
$direccion = strtoupper($direccion);
$direccion = ($direccion === 'DESC') ? 'DESC' : 'ASC';

$emprendimientos = [];

try {
    $db = getDB();
    
    // Construir WHERE según filtro de estado
    $whereClause = '';
    if ($filtroEstado === 'activos') {
        $whereClause = 'WHERE e.activo = TRUE';
    } elseif ($filtroEstado === 'inactivos') {
        $whereClause = 'WHERE e.activo = FALSE';
    }
    
    // Consulta para obtener emprendimientos con JOINs
    $sql = "SELECT 
                e.id,
                e.nombre,
                e.descripcion,
                e.activo,
                c.nombre as categoria_nombre,
                p.nombre || ' ' || p.apellido as duenno_nombre,
                p.rut as duenno_rut
            FROM Emprendimiento e
            LEFT JOIN Categoria c ON e.categoria_id = c.id
            LEFT JOIN Personas p ON e.duenno_rut = p.rut
            {$whereClause}
            ORDER BY e.{$orden} {$direccion}
            LIMIT :limite";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $emprendimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contadores
    $sqlCount = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN activo = TRUE THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN activo = FALSE THEN 1 ELSE 0 END) as inactivos
                FROM Emprendimiento";
    
    $stmtCount = $db->query($sqlCount);
    $estadisticas = $stmtCount->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error en get_emprendimientos_exportar.php: " . $e->getMessage());
    $emprendimientos = [];
    $estadisticas = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
}

echo json_encode([
    'emprendimientos' => $emprendimientos,
    'estadisticas' => $estadisticas,
    'filtros' => [
        'orden' => $orden,
        'direccion' => $direccion,
        'limite' => $limite,
        'estado' => $filtroEstado
    ],
    'total_en_reporte' => count($emprendimientos)
]);
?>