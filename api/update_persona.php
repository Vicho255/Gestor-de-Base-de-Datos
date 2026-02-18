<?php
// api/update_persona.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}
if (!$input) {
    parse_str(file_get_contents('php://input'), $input);
}

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit();
}

if (empty($input['RUT']) || empty($input['nombre']) || empty($input['apellido'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Faltan datos obligatorios (RUT, nombre, apellido)',
        'received' => array_keys($input)
    ]);
    exit();
}

$rut = strtoupper(trim($input['RUT']));
$nombre = trim($input['nombre']);
$apellido = trim($input['apellido']);
$fecha_nac = !empty($input['fecha_nac']) ? trim($input['fecha_nac']) : null;
$telefono = !empty($input['telefono']) ? trim($input['telefono']) : null;
$correo = !empty($input['correo']) ? trim($input['correo']) : null;

// Dirección
$ciudad = !empty($input['ciudad']) ? trim($input['ciudad']) : null;
$comuna = !empty($input['comuna']) ? trim($input['comuna']) : null;
$calle = !empty($input['calle']) ? trim($input['calle']) : null;
$numero = !empty($input['numero']) ? trim($input['numero']) : null;

if ($fecha_nac !== null) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nac);
    if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha_nac) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
        exit();
    }
}

try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Verificar existencia
    $check = $db->prepare("SELECT 1 FROM Personas WHERE rut = :rut");
    $check->execute([':rut' => $rut]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'La persona no existe']);
        exit();
    }

    // 2. Actualizar persona
    $update = $db->prepare("
        UPDATE Personas 
        SET nombre = :nombre, apellido = :apellido, fecha_nac = :fecha_nac
        WHERE rut = :rut
    ");
    $update->execute([
        ':rut' => $rut,
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':fecha_nac' => $fecha_nac
    ]);

    // 3. Teléfono: reemplazar
    $db->prepare("DELETE FROM Telefono WHERE persona_rut = :rut")->execute([':rut' => $rut]);
    if ($telefono) {
        $insertTel = $db->prepare("INSERT INTO Telefono (numero, persona_rut) VALUES (:numero, :rut)");
        $insertTel->execute([':numero' => $telefono, ':rut' => $rut]);
    }

    // 4. Correo: reemplazar
    $db->prepare("DELETE FROM Correo WHERE persona_rut = :rut")->execute([':rut' => $rut]);
    if ($correo && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $parts = explode('@', $correo, 2);
        $cuerpo = $parts[0] ?? '';
        $dominio = $parts[1] ?? '';
        if ($cuerpo && $dominio) {
            $insertCor = $db->prepare("
                INSERT INTO Correo (cuerpo, dominio, persona_rut) 
                VALUES (:cuerpo, :dominio, :rut)
            ");
            $insertCor->execute([':cuerpo' => $cuerpo, ':dominio' => $dominio, ':rut' => $rut]);
        }
    }

    // 5. Dirección: eliminar relación anterior y posible dirección huérfana
    // Obtener IDs de direcciones asociadas a esta persona
    $stmt = $db->prepare("SELECT direccion_id FROM Direccion_Persona WHERE persona_rut = :rut");
    $stmt->execute([':rut' => $rut]);
    $old_direccion_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Eliminar relaciones
    $db->prepare("DELETE FROM Direccion_Persona WHERE persona_rut = :rut")->execute([':rut' => $rut]);

    // Eliminar direcciones que no estén vinculadas a ninguna otra persona
    foreach ($old_direccion_ids as $id) {
        $check = $db->prepare("SELECT 1 FROM Direccion_Persona WHERE direccion_id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            $db->prepare("DELETE FROM Direccion WHERE id = :id")->execute([':id' => $id]);
        }
    }

    // Insertar nueva dirección si se proporcionaron todos los campos
    if ($ciudad && $comuna && $calle && $numero) {
        // Insertar en Direccion
        $insertDir = $db->prepare("
            INSERT INTO Direccion (ciudad, comuna, calle, numero) 
            VALUES (:ciudad, :comuna, :calle, :numero)
            RETURNING id
        ");
        $insertDir->execute([
            ':ciudad' => $ciudad,
            ':comuna' => $comuna,
            ':calle' => $calle,
            ':numero' => $numero
        ]);
        $direccion_id = $insertDir->fetchColumn();

        // Vincular
        $insertLink = $db->prepare("
            INSERT INTO Direccion_Persona (persona_rut, direccion_id) 
            VALUES (:rut, :direccion_id)
        ");
        $insertLink->execute([':rut' => $rut, ':direccion_id' => $direccion_id]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Persona actualizada exitosamente'
    ]);

} catch (PDOException $e) {
    if (isset($db)) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en BD: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error general: ' . $e->getMessage()]);
}
?>