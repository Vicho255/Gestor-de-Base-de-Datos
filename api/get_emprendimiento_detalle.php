<?php
session_start();
header('Content-Type: application/json');

// Mostrar errores (solo para depuración, luego quitar)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Incluir conexión PDO
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB(); // Obtiene el objeto PDO

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID invalido']);
        exit;
    }

    // Consulta con placeholders ? (PDO PostgreSQL)
    $sql = "SELECT 
                e.id,
                e.nombre,
                e.descripcion,
                e.activo,
                e.duenno_rut,
                e.categoria_id,
                c.nombre AS categoria_nombre,
                p.nombre AS duenno_nombre,
                p.apellido AS duenno_apellido
            FROM emprendimiento e
            LEFT JOIN categoria c ON e.categoria_id = c.id
            LEFT JOIN personas p ON e.duenno_rut = p.rut
            WHERE e.id = ?";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $emprendimiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emprendimiento) {
        echo json_encode(['success' => false, 'error' => 'Emprendimiento no encontrado']);
        exit;
    }

    // Formatear nombre completo del dueño
    $nombreCompleto = trim(($emprendimiento['duenno_nombre'] ?? '') . ' ' . ($emprendimiento['duenno_apellido'] ?? ''));
    $emprendimiento['duenno_nombre_completo'] = $nombreCompleto ?: 'Sin asignar';
    
    // Valores por defecto para campos nulos
    $emprendimiento['descripcion'] = $emprendimiento['descripcion'] ?? '';
    $emprendimiento['categoria_nombre'] = $emprendimiento['categoria_nombre'] ?? 'Sin categoría';
    $emprendimiento['duenno_rut'] = $emprendimiento['duenno_rut'] ?? '';
    $emprendimiento['created_at'] = $emprendimiento['created_at'] ?? null;

    echo json_encode($emprendimiento);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error general: ' . $e->getMessage()]);
}
?>