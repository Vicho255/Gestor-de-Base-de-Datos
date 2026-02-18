<?php
session_start();
header('Content-Type: application/json');

// Incluir la clase Database (ajusta la ruta según tu estructura)
require_once __DIR__ . '/../config/database.php';

// Verificar que el usuario esté autenticado (opcional, pero recomendado)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Leer el cuerpo de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['username'], $data['password'], $data['rol'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$username = trim($data['username']);
$password = $data['password'];
$rol = $data['rol'];

// Validaciones básicas
if (empty($username) || empty($password) || !in_array($rol, ['user', 'admin'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit();
}

try {
    // Obtener conexión usando tu clase Database
    $db = Database::getInstance()->getConnection();

    // Verificar si el usuario ya existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'El nombre de usuario ya existe']);
        exit();
    }

    // Hashear la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $db->prepare("INSERT INTO usuarios (username, password, rol, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $hashedPassword, $rol]);

    echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);

} catch (PDOException $e) {
    // Registrar el error en el log del servidor
    error_log("Error en create_usuario.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos']);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>