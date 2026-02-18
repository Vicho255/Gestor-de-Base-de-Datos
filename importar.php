<?php

require_once 'config/database.php';

if (!isset($_FILES['archivo'])) {
    echo json_encode(['mensaje' => 'No se recibió archivo']);
    exit;
}

$archivo = $_FILES['archivo']['tmp_name'];

$handle = fopen($archivo, 'r');

// Si el CSV tiene encabezados
$encabezados = fgetcsv($handle, 1000, ',');

$db->beginTransaction();

try {
    while (($fila = fgetcsv($handle, 1000, ',')) !== false) {

        [$nombre, $email, $edad] = $fila;

        $stmt = $db->prepare(
            "INSERT INTO personas (nombre, email, edad)
             VALUES (:nombre, :email, :edad)"
        );

        $stmt->execute([
            ':nombre' => $nombre,
            ':email'  => $email,
            ':edad'   => $edad
        ]);
    }

    $db->commit();
    echo json_encode(['mensaje' => 'Datos importados correctamente']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['mensaje' => 'Error al importar']);
}

fclose($handle);

?>