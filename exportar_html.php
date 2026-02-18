<?php
// exportar_html.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado");
}

$data = json_decode(file_get_contents('php://input'), true);
$orden = $data['orden'] ?? 'nombre';
$direccion = $data['direccion'] ?? 'ASC';
$limite = intval($data['limite'] ?? 20);

// ... (misma lógica de obtención de datos que exportar_pdf_simple.php)

// En lugar de un diseño para PDF, hacemos uno más simple para vista previa
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - Reporte de Personas</title>
    <style>
        /* Estilos simples para vista previa */
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .controls { margin: 20px 0; text-align: center; }
        .btn { padding: 10px 20px; background: #28a745; color: white; 
               border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vista Previa del Reporte</h1>
        <p>Esta es una vista previa simple. Para el PDF final, usa la opción "Imprimir/Guardar como PDF"</p>
    </div>
    
    <!-- ... (contenido similar al exportar_pdf_simple.php pero simplificado) ... -->
    
    <div class="controls">
        <button class="btn" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir o Guardar como PDF
        </button>
        <button class="btn" onclick="window.close()" style="background: #dc3545; margin-left: 10px;">
            <i class="fas fa-times"></i> Cerrar
        </button>
    </div>
</body>
</html>