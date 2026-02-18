<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try{
    $db=getDB();

    $sql = "SELECT * FROM get_usuarios()";
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($usuarios);
} catch (Exception $e){
    http_response_code(500);
    echo json_encode([]);
}
?>