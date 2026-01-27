<?php
// api/add_persona_simple.php
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

if (empty($input['RUT']) || empty($input['nombre']) || empty($input['apellido']) || empty($input['fecha_nac'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
    exit();
}

try {
    $rut = strtoupper(trim($input['RUT']));
    $nombre = trim($input['nombre']);
    $apellido = trim($input['apellido']);
    $fecha_nac = $input['fecha_nac'];
    $telefono = !empty($input['telefono']) ? trim($input['telefono']) : null;
    $correo = !empty($input['correo']) ? trim($input['correo']) : null;
    
    $db = getDB();
    
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
    
    // 3. Insertar teléfono (sin ID explícito)
    if ($telefono) {
        try {
            // Primero intentar sin especificar id
            $insertTel = $db->prepare("INSERT INTO telefono (numero, persona_rut) VALUES (:numero, :persona_rut)");
            $insertTel->execute([':numero' => $telefono, ':persona_rut' => $rut]);
        } catch (PDOException $e) {
            // Si falla, ignorar el error del teléfono pero continuar
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
                    INSERT INTO correo (cuerpo, dominio, persona_rut) 
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Persona registrada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>