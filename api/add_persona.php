<?php
// api/add_persona.php
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

if (empty($input['RUT']) || empty($input['nombre']) || empty($input['apellido'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
    exit();
}

try {
    $rut = strtoupper(trim($input['RUT']));
    $nombre = trim($input['nombre']);
    $apellido = trim($input['apellido']);
    $fecha_nac = !empty($input['fecha_nac']) ? trim($input['fecha_nac']) : null;
    $telefono = !empty($input['telefono']) ? trim($input['telefono']) : null;
    $correo = !empty($input['correo']) ? trim($input['correo']) : null;
    
    // Dirección: nuevos campos
    $ciudad = !empty($input['ciudad']) ? trim($input['ciudad']) : null;
    $comuna = !empty($input['comuna']) ? trim($input['comuna']) : null;
    $calle = !empty($input['calle']) ? trim($input['calle']) : null;
    $numero = !empty($input['numero']) ? trim($input['numero']) : null;

    if ($fecha_nac === '') $fecha_nac = null;

    // Validar fecha
    if ($fecha_nac !== null) {
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nac);
        if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha_nac) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
            exit();
        }
    }

    $db = getDB();
    $db->beginTransaction(); // Para asegurar consistencia

    // 1. Verificar RUT
    $check = $db->prepare("SELECT 1 FROM Personas WHERE rut = :rut");
    $check->execute([':rut' => $rut]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Ya existe una persona con el mismo RUT.']);
        exit();
    }

    // 2. Insertar persona
    $insertPersona = $db->prepare("
        INSERT INTO Personas (rut, nombre, apellido, fecha_nac) 
        VALUES (:rut, :nombre, :apellido, :fecha_nac)
    ");
    $insertPersona->execute([
        ':rut' => $rut,
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':fecha_nac' => $fecha_nac
    ]);

    // 3. Insertar teléfono
    if ($telefono) {
        try {
            $insertTel = $db->prepare("INSERT INTO Telefono (numero, persona_rut) VALUES (:numero, :persona_rut)");
            $insertTel->execute([':numero' => $telefono, ':persona_rut' => $rut]);
        } catch (PDOException $e) {
            error_log("Error insertando teléfono (ignorado): " . $e->getMessage());
        }
    }

    // 4. Insertar correo
    if ($correo && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $parts = explode('@', $correo, 2);
        $cuerpo = $parts[0] ?? '';
        $dominio = $parts[1] ?? '';
        if ($cuerpo && $dominio) {
            try {
                $insertCor = $db->prepare("
                    INSERT INTO Correo (cuerpo, dominio, persona_rut) 
                    VALUES (:cuerpo, :dominio, :persona_rut)
                ");
                $insertCor->execute([
                    ':cuerpo' => $cuerpo,
                    ':dominio' => $dominio,
                    ':persona_rut' => $rut
                ]);
            } catch (PDOException $e) {
                error_log("Error insertando correo (ignorado): " . $e->getMessage());
            }
        }
    }

    // 5. Insertar dirección (si al menos un campo no vacío)
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

        // Insertar en Direccion_Persona
        $insertLink = $db->prepare("
            INSERT INTO Direccion_Persona (persona_rut, direccion_id) 
            VALUES (:persona_rut, :direccion_id)
        ");
        $insertLink->execute([
            ':persona_rut' => $rut,
            ':direccion_id' => $direccion_id
        ]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Persona registrada exitosamente'
    ]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>