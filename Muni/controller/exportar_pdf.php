<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Acceso denegado";
    exit();
}

// Obtener parámetros del POST
$data = json_decode(file_get_contents('php://input'), true);
$orden = $data['orden'] ?? 'nombre';
$direccion = $data['direccion'] ?? 'ASC';
$limite = intval($data['limite'] ?? 20);

// Validar límite
if ($limite <= 0) $limite = 20;
if ($limite > 1000) $limite = 1000;

// Conexión a la base de datos (ajusta estos valores)
$host = 'localhost';
$dbname = 'tu_base_datos';
$username = 'tu_usuario';
$password = 'tu_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Validar columnas para ordenar (prevenir SQL injection)
    $columnasPermitidas = ['nombre', 'apellido', 'rut', 'fecha_nac', 'edad', 'telefono', 'correo'];
    if (!in_array($orden, $columnasPermitidas)) {
        $orden = 'nombre';
    }
    
    // Validar dirección
    $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';
    
    // Obtener datos
    $sql = "SELECT rut, nombre, apellido, fecha_nac, 
                   TIMESTAMPDIFF(YEAR, fecha_nac, CURDATE()) as edad,
                   telefono, correo 
            FROM personas 
            ORDER BY $orden $direccion 
            LIMIT :limite";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay datos, usar datos de ejemplo
    if (empty($personas)) {
        $personas = [
            ['rut' => '12345678-9', 'nombre' => 'Ejemplo', 'apellido' => 'Uno', 
             'fecha_nac' => '1990-01-01', 'edad' => 33, 'telefono' => '+56912345678', 
             'correo' => 'ejemplo@correo.com']
        ];
    }
    
    // Crear PDF
    generarPDF($personas, $orden, $direccion, $limite);
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function generarPDF($personas, $orden, $direccion, $limite) {
    // Cabeceras para PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_personas_' . date('Y-m-d_H-i') . '.pdf"');
    
    // Iniciar buffer de salida
    ob_start();
    
    // Generar contenido PDF manualmente
    echo "%PDF-1.4\n";
    echo "1 0 obj\n";
    echo "<< /Type /Catalog /Pages 2 0 R >>\n";
    echo "endobj\n";
    
    echo "2 0 obj\n";
    echo "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
    echo "endobj\n";
    
    echo "3 0 obj\n";
    echo "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\n";
    echo "endobj\n";
    
    echo "4 0 obj\n";
    echo "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";
    echo "endobj\n";
    
    // Contenido de la página
    $contenido = "BT\n";
    $contenido .= "/F1 12 Tf\n";
    
    // Título
    $contenido .= "50 750 Td\n";
    $contenido .= "(Reporte de Personas) Tj\n";
    
    // Filtros aplicados
    $contenido .= "50 730 Td\n";
    $contenido .= "(Filtros: Ordenado por: " . $orden . " " . $direccion . ", Límite: " . $limite . " registros) Tj\n";
    
    $contenido .= "50 710 Td\n";
    $contenido .= "(Fecha de generación: " . date('d/m/Y H:i:s') . ") Tj\n";
    
    // Tabla de datos
    $y = 680;
    $contenido .= "50 " . $y . " Td\n";
    $contenido .= "(" . str_pad("RUT", 15) . str_pad("Nombre", 20) . str_pad("Apellido", 20) . str_pad("Edad", 8) . ") Tj\n";
    
    $contenido .= "50 " . ($y - 20) . " Td\n";
    $contenido .= "(--------------------------------------------------------------------------------------------------------) Tj\n";
    
    $y -= 40;
    foreach ($personas as $persona) {
        if ($y < 50) {
            // Nueva página si es necesario
            $contenido .= "ET\n";
            // ... código para nueva página ...
            $y = 750;
            $contenido .= "BT\n";
        }
        
        $contenido .= "50 " . $y . " Td\n";
        $linea = str_pad(substr($persona['rut'], 0, 15), 15) . 
                 str_pad(substr($persona['nombre'], 0, 20), 20) . 
                 str_pad(substr($persona['apellido'], 0, 20), 20) . 
                 str_pad($persona['edad'], 8);
        $contenido .= "(" . $linea . ") Tj\n";
        
        $y -= 20;
    }
    
    $contenido .= "ET\n";
    
    // Convertir contenido a flujo PDF
    $stream = $contenido;
    $length = strlen($stream);
    
    echo "5 0 obj\n";
    echo "<< /Length $length >>\n";
    echo "stream\n";
    echo $stream;
    echo "endstream\n";
    echo "endobj\n";
    
    // Cross-reference table
    $xref = "xref\n";
    $xref .= "0 6\n";
    $xref .= "0000000000 65535 f \n";
    $xref .= "0000000010 00000 n \n";
    $xref .= "0000000053 00000 n \n";
    $xref .= "0000000125 00000 n \n";
    $xref .= "0000000254 00000 n \n";
    $xref .= "0000000350 00000 n \n";
    
    // Trailer
    $trailer = "trailer\n";
    $trailer .= "<< /Size 6 /Root 1 0 R >>\n";
    $trailer .= "startxref\n";
    $trailer .= "420\n"; // Posición de xref (ajustar según necesidad)
    $trailer .= "%%EOF\n";
    
    // Imprimir todo
    $output = ob_get_clean();
    echo $output . $xref . $trailer;
    
    exit();
}
?>