<?php
require_once 'config/database.php';

$db = getDB();
$usuarios = $db->query("SELECT id, password FROM usuarios")->fetchAll();

foreach ($usuarios as $usuario) {
    // Si la contraseña actual no parece un hash (longitud < 60), la hasheamos
    if (strlen($usuario['password']) < 60) {
        $hash = password_hash($usuario['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $usuario['id']]);
        echo "Usuario ID {$usuario['id']} actualizado.<br>";
    } else {
        echo "Usuario ID {$usuario['id']} ya tiene hash.<br>";
    }
}
echo "Migración completada.";
?>
