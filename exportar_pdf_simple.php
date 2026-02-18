<?php
// exportar_pdf_simple.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado");
}

// Obtener parámetros
$data = json_decode(file_get_contents('php://input'), true);
$orden = $data['orden'] ?? 'nombre';
$direccion = $data['direccion'] ?? 'ASC';
$limite = intval($data['limite'] ?? 20);

// Validar límite
$limite = max(1, min(1000, $limite));

// Columnas permitidas para ordenar
$columnasPermitidas = ['nombre', 'apellido', 'rut', 'fecha_nac'];
if (!in_array($orden, $columnasPermitidas)) {
    $orden = 'nombre';
}

// Validar dirección
$direccion = strtoupper($direccion);
$direccion = ($direccion === 'DESC') ? 'DESC' : 'ASC';

$personas = [];

try {
    $db = getDB();
    
    // Consulta SIMPLE solo para personas primero - PostgreSQL
    $sql = "SELECT 
                p.RUT,
                p.nombre,
                p.apellido,
                p.fecha_nac,
                EXTRACT(YEAR FROM AGE(CURRENT_DATE, p.fecha_nac)) as edad
            FROM Personas p
            ORDER BY p.$orden $direccion 
            LIMIT :limite";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si hay personas, obtener sus teléfonos, correos, direcciones y beneficios
    if (!empty($personas)) {
        foreach ($personas as &$persona) {
            $rut = $persona['rut'];
            
            // Obtener teléfonos
            $sqlTelefonos = "SELECT numero FROM Telefono WHERE persona_rut = :rut";
            $stmtTel = $db->prepare($sqlTelefonos);
            $stmtTel->execute([':rut' => $rut]);
            $telefonos = $stmtTel->fetchAll(PDO::FETCH_COLUMN);
            $persona['telefonos'] = !empty($telefonos) ? implode(', ', $telefonos) : 'Sin teléfono';
            
            // Obtener correos
            $sqlCorreos = "SELECT CONCAT(cuerpo, '@', dominio) as correo FROM Correo WHERE persona_rut = :rut";
            $stmtCor = $db->prepare($sqlCorreos);
            $stmtCor->execute([':rut' => $rut]);
            $correos = $stmtCor->fetchAll(PDO::FETCH_COLUMN);
            $persona['correos'] = !empty($correos) ? implode(', ', $correos) : 'Sin correo';
            
            // Obtener direcciones
            $sqlDirecciones = "SELECT 
                                    d.calle || ' #' || d.numero || ', ' || d.comuna || ', ' || d.ciudad as direccion
                               FROM Direccion_Persona dp
                               JOIN Direccion d ON dp.direccion_id = d.id
                               WHERE dp.persona_rut = :rut";
            $stmtDir = $db->prepare($sqlDirecciones);
            $stmtDir->execute([':rut' => $rut]);
            $direcciones = $stmtDir->fetchAll(PDO::FETCH_COLUMN);
            $persona['direcciones'] = !empty($direcciones) ? implode('; ', $direcciones) : 'Sin dirección';
            
            // Obtener beneficios accedidos
            $sqlBeneficios = "SELECT 
                                    b.nombre,
                                    bp.fecha_post
                               FROM Beneficios_Persona bp
                               JOIN Beneficios b ON bp.Beneficio_id = b.id
                               WHERE bp.Persona_rut = :rut
                               ORDER BY bp.fecha_post DESC";
            $stmtBen = $db->prepare($sqlBeneficios);
            $stmtBen->execute([':rut' => $rut]);
            $beneficios = $stmtBen->fetchAll();
            
            if (!empty($beneficios)) {
                $listaBeneficios = [];
                foreach ($beneficios as $b) {
                    $fecha = date('d/m/Y', strtotime($b['fecha_post']));
                    $listaBeneficios[] = $b['nombre'] . ' (' . $fecha . ')';
                }
                $persona['beneficios'] = implode('; ', $listaBeneficios);
                $persona['total_beneficios'] = count($beneficios);
            } else {
                $persona['beneficios'] = 'Sin beneficios';
                $persona['total_beneficios'] = 0;
            }
            
            // Contadores
            $persona['total_telefonos'] = count($telefonos);
            $persona['total_correos'] = count($correos);
            $persona['total_direcciones'] = count($direcciones);
        }
        unset($persona); // Romper referencia
        
    }
    
} catch (Exception $e) {
    // Si hay error, mostrar datos de prueba con mensaje
    error_log("Error en exportar_pdf_simple.php: " . $e->getMessage());
    $personas = [
        [
            'RUT' => 'ERROR-DB',
            'nombre' => 'Error en',
            'apellido' => 'Base de Datos',
            'fecha_nac' => '2000-01-01',
            'edad' => 23,
            'telefonos' => 'Verificar conexión: ' . $e->getMessage(),
            'correos' => 'Error al conectar con la base de datos',
            'direcciones' => 'Revisar config/database.php',
            'beneficios' => 'No disponibles',
            'total_telefonos' => 0,
            'total_correos' => 0,
            'total_direcciones' => 0,
            'total_beneficios' => 0
        ]
    ];
}

// Estadísticas
$hayDatos = !empty($personas);
$totalPersonas = count($personas);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Personas con Beneficios</title>
    <style>
        /* Estilos para PDF VERTICAL (portrait) estilo Excel */
        @page {
            size: A4 portrait;
            margin: 0.5cm;
        }
        
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            color: #000000;
            background-color: #FFFFFF;
        }
        
        /* Encabezado estilo Excel */
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #CCCCCC;
        }
        
        .header h1 {
            margin: 5px 0;
            font-size: 16pt;
            color: #1F497D;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #7F7F7F;
            font-size: 10pt;
            margin-top: 2px;
        }
        
        /* Información en formato de tabla */
        .info-section {
            background: #F2F2F2;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid #D9D9D9;
            border-left: 3px solid #1F497D;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 6px;
        }
        
        .info-item {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
        }
        
        .info-label {
            font-weight: bold;
            color: #000000;
            font-size: 9pt;
            width: 120px;
            min-width: 120px;
        }
        
        .info-value {
            color: #000000;
            padding: 2px 4px;
            background: white;
            border: 1px solid #BFBFBF;
            display: inline-block;
            flex-grow: 1;
            font-size: 9pt;
            height: 18px;
            line-height: 14px;
        }
        
        /* Tabla estilo Excel */
        .table-container {
            margin-top: 10px;
            overflow: hidden;
            border: 1px solid #BFBFBF;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            table-layout: fixed;
        }
        
        /* Encabezados de tabla estilo Excel */
        .data-table th {
            background: #E7E6E6;
            color: #000000;
            padding: 4px 4px;
            text-align: left;
            border: 1px solid #BFBFBF;
            font-weight: bold;
            font-size: 9pt;
            height: 22px;
            vertical-align: middle;
        }
        
        .data-table th:first-child {
            border-left: 1px solid #BFBFBF;
        }
        
        .data-table th:last-child {
            border-right: 1px solid #BFBFBF;
        }
        
        .data-table td {
            padding: 2px 4px;
            border: 1px solid #D9D9D9;
            vertical-align: middle;
            word-wrap: break-word;
            height: 20px;
            line-height: 16px;
        }
        
        /* Filas alternas estilo Excel */
        .data-table tr:nth-child(even) {
            background: #F9F9F9;
        }
        
        .data-table tr:nth-child(odd) {
            background: #FFFFFF;
        }
        
        /* Efecto hover solo para pantalla (no impresión) */
        @media screen {
            .data-table tr:hover {
                background: #E6F2FF;
            }
        }
        
        /* Anchos de columnas ajustados (incluyendo beneficios) */
        .data-table th:nth-child(1) { width: 10%; } /* RUT */
        .data-table th:nth-child(2) { width: 9%; }  /* Nombre */
        .data-table th:nth-child(3) { width: 9%; }  /* Apellido */
        .data-table th:nth-child(4) { width: 5%; }  /* Edad */
        .data-table th:nth-child(5) { width: 8%; }  /* Fecha Nac */
        .data-table th:nth-child(6) { width: 12%; } /* Teléfonos */
        .data-table th:nth-child(7) { width: 14%; } /* Correos */
        .data-table th:nth-child(8) { width: 15%; } /* Direcciones */
        .data-table th:nth-child(9) { width: 18%; } /* Beneficios */
        
        .contact-cell {
            font-size: 8.5pt;
            line-height: 1.2;
            max-height: 40px;
            overflow: hidden;
        }
        
        /* Pie de página */
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #D9D9D9;
            text-align: center;
            font-size: 8pt;
            color: #7F7F7F;
        }
        
        /* Resumen estilo Excel */
        .summary {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
            margin-bottom: 10px;
            padding: 8px;
            background: #F2F2F2;
            border: 1px solid #D9D9D9;
        }
        
        .summary-item {
            text-align: center;
            padding: 4px;
            background: white;
            border: 1px solid #BFBFBF;
        }
        
        .summary-value {
            font-weight: bold;
            color: #1F497D;
            font-size: 10pt;
            display: block;
        }
        
        .summary-label {
            font-size: 8pt;
            color: #000000;
            display: block;
            margin-top: 1px;
        }
        
        /* Badges estilo Excel */
        .badge {
            display: inline-block;
            padding: 0px 3px;
            border-radius: 2px;
            font-size: 7.5pt;
            font-weight: normal;
            margin-left: 2px;
            border: 1px solid transparent;
        }
        
        .badge-phone { background: #DAE8FC; color: #000000; border-color: #6C8EBF; }
        .badge-email { background: #F8CECC; color: #000000; border-color: #B85450; }
        .badge-address { background: #D5E8D4; color: #000000; border-color: #82B366; }
        .badge-benefit { background: #FFF2CC; color: #000000; border-color: #D6B656; }
        
        /* Controles de impresión */
        .print-controls {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            gap: 8px;
            border: 1px solid #BFBFBF;
        }
        
        .print-btn {
            background: #1F497D;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 2px;
            cursor: pointer;
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 9pt;
            font-family: Calibri, Arial, sans-serif;
        }
        
        .close-btn {
            background: #C00000;
        }
        
        /* Ocultar controles al imprimir */
        @media print {
            .print-controls {
                display: none;
            }
            
            body {
                font-size: 9pt;
                font-family: Calibri, Arial, sans-serif;
            }
            
            .data-table {
                font-size: 8.5pt;
            }
            
            .data-table th,
            .data-table td {
                padding: 2px 3px;
            }
            
            .info-value {
                border: 1px solid #BFBFBF;
            }
        }
        
        /* Para pantallas pequeñas */
        @media screen and (max-width: 768px) {
            body {
                font-size: 9pt;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .summary {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 2px;
            }
        }
        
        /* Líneas de cuadrícula más finas */
        .gridlines {
            background-image: 
                linear-gradient(to right, #E0E0E0 1px, transparent 1px),
                linear-gradient(to bottom, #E0E0E0 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>Reporte de Personas con Beneficios</h1>
        <div class="subtitle">Sistema de Gestión - <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
    
    <!-- Información del reporte -->
    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
            </div>
        </div>
    </div>
    
    <!-- Tabla de datos -->
    <div class="table-container">
        <?php if ($hayDatos): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>RUT</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Edad</th>
                        <th>Fecha Nac.</th>
                        <th>Teléfonos</th>
                        <th>Correos</th>
                        <th>Direcciones</th>
                        <th>Beneficios Accedidos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($personas as $persona): 
                        $fechaNac = !empty($persona['fecha_nac']) && $persona['fecha_nac'] != '0000-00-00' ? 
                                   date('d/m/Y', strtotime($persona['fecha_nac'])) : 
                                   'N/A';
                    ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: bold;">
                            <?php echo htmlspecialchars($persona['rut']); ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($persona['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($persona['apellido']); ?></td>
                        <td style="text-align: center; font-weight: 600;"><?php echo htmlspecialchars($persona['edad']); ?></td>
                        <td><?php echo $fechaNac; ?></td>
                        <td class="contact-cell">
                            <?php echo htmlspecialchars($persona['telefonos']); ?>
                            <?php if ($persona['total_telefonos'] > 0): ?>
                                <span class="badge badge-phone"><?php echo $persona['total_telefonos']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="contact-cell">
                            <?php echo htmlspecialchars($persona['correos']); ?>
                            <?php if ($persona['total_correos'] > 0): ?>
                                <span class="badge badge-email"><?php echo $persona['total_correos']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="contact-cell">
                            <?php echo htmlspecialchars($persona['direcciones']); ?>
                            <?php if ($persona['total_direcciones'] > 0): ?>
                                <span class="badge badge-address"><?php echo $persona['total_direcciones']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="contact-cell">
                            <?php echo htmlspecialchars($persona['beneficios']); ?>
                            <?php if ($persona['total_beneficios'] > 0): ?>
                                <span class="badge badge-benefit"><?php echo $persona['total_beneficios']; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 5px; margin: 20px 0;">
                <h3 style="color: #666;">No se encontraron datos</h3>
                <p>No hay personas registradas en la base de datos.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer con estadísticas -->
    <div class="footer">
        <div class="summary">
            <div class="summary-item">
                <span class="summary-value"><?php echo $totalPersonas; ?></span>
                <span class="summary-label">Personas</span>
            </div>
            <?php if ($hayDatos): 
                $totalTelefonos = array_sum(array_column($personas, 'total_telefonos'));
                $totalCorreos = array_sum(array_column($personas, 'total_correos'));
                $totalDirecciones = array_sum(array_column($personas, 'total_direcciones'));
                $totalBeneficios = array_sum(array_column($personas, 'total_beneficios'));
            ?>
            <div class="summary-item">
                <span class="summary-value"><?php echo $totalTelefonos; ?></span>
                <span class="summary-label">Teléfonos</span>
            </div>
            <div class="summary-item">
                <span class="summary-value"><?php echo $totalCorreos; ?></span>
                <span class="summary-label">Correos</span>
            </div>
            <div class="summary-item">
                <span class="summary-value"><?php echo $totalDirecciones; ?></span>
                <span class="summary-label">Direcciones</span>
            </div>
            <div class="summary-item">
                <span class="summary-value"><?php echo $totalBeneficios; ?></span>
                <span class="summary-label">Beneficios</span>
            </div>
            <div class="summary-item">
                <span class="summary-value">
                    <?php echo $totalPersonas > 0 ? round(($totalTelefonos + $totalCorreos + $totalDirecciones + $totalBeneficios) / $totalPersonas, 1) : 0; ?>
                </span>
                <span class="summary-label">Promedio</span>
            </div>
            <?php endif; ?>
        </div>
        
        <p>
            <strong>Reporte generado por Sistema de Gestión de Personas</strong><br>
            <small>Documento confidencial - Uso interno exclusivo</small>
        </p>
        <p style="font-size: 8pt; margin-top: 5px; color: #95a5a6;">
            ID Reporte: <?php echo date('Ymd-His'); ?> | 
            Usuario: <?php echo $_SESSION['username'] ?? 'Sistema'; ?>
        </p>
    </div>
    
    <!-- Controles de impresión (solo en navegador) -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="print-btn">
            <span>🖨️</span> Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()" class="print-btn close-btn">
            <span>❌</span> Cerrar Ventana
        </button>
    </div>
    
    <script>
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+P para imprimir
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Escape para cerrar
            if (e.key === 'Escape') {
                if (confirm('¿Cerrar la ventana del reporte?')) {
                    window.close();
                }
            }
        });
    </script>
</body>
</html>