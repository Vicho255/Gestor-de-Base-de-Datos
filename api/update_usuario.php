<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit();
}

$id = $input['id'] ?? null;
$username = trim($input['username'] ?? '');
$rol = $input['rol'] ?? null;
$password = $input['password'] ?? '';

if (!$id || !$username || !$rol) {
    echo json_encode(['error' => 'Faltan datos requeridos']);
    exit();
}
if (!in_array($rol, ['admin', 'user'])) {
    echo json_encode(['error' => 'Rol inválido']);
    exit();
}

try {
    require_once '../config/database.php';
    $db = getDB();

    // Verificar existencia del usuario
    $checkStmt = $db->prepare("SELECT id FROM usuarios WHERE id = :id");
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['error' => 'El usuario no existe']);
        exit();
    }

    // Verificar si el nuevo username ya existe en otro usuario
    $checkUsernameStmt = $db->prepare("SELECT id FROM usuarios WHERE username = :username AND id != :id");
    $checkUsernameStmt->execute([':username' => $username, ':id' => $id]);
    if ($checkUsernameStmt->fetch()) {
        echo json_encode(['error' => 'El nombre de usuario ya está en uso']);
        exit();
    }

    // Construir consulta dinámica
    $sql = "UPDATE usuarios SET username = :username, rol = :rol";
    $params = [':username' => $username, ':rol' => $rol, ':id' => $id];
    
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = :password";
        $params[':password'] = $hashedPassword;
    }
    $sql .= " WHERE id = :id";

    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado']);
    } else {
        echo json_encode(['error' => 'No se pudo actualizar el usuario']);
    }
} catch (Exception $e) {
    error_log("Error en update_usuario: " . $e->getMessage());
    echo json_encode(['error' => 'Error en el servidor']);
}