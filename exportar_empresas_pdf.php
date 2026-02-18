<?php
// exportar_empresas_pdf.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado");
}

// Obtener parámetros directamente del POST
$postData = json_decode(file_get_contents('php://input'), true);

// Si no llegan datos por POST, usar valores por defecto
if (!$postData) {
    $postData = $_POST ?? [];
}

$orden = $postData['orden'] ?? 'nombre';
$direccion = $postData['direccion'] ?? 'ASC';
$limite = intval($postData['limite'] ?? 20);

// Validar límite
$limite = max(1, min(1000, $limite));

// Inicializar arrays
$empresas = [];
$estadisticas = ['total' => 0];
$filtros = [
    'orden' => $orden,
    'direccion' => $direccion,
    'limite' => $limite
];

try {
    $db = getDB();
    

    
    // Consulta para obtener empresas con JOINs - PostgreSQL
    $sql = "SELECT 
                e.id,
                e.nombre,
                e.descripcion,
                c.nombre as categoria_nombre,
                p.nombre || ' ' || p.apellido as duenno_nombre,
                p.rut as duenno_rut
            FROM Empresa e
            LEFT JOIN Categoria c ON e.categoria_id = c.id
            LEFT JOIN Personas p ON e.duenno_rut = p.rut";
    
    // Agregar ORDER BY según el campo seleccionado
    $columnasPermitidas = ['nombre', 'categoria_nombre', 'duenno_nombre', 'created_at'];
    $ordenCampo = in_array($orden, $columnasPermitidas) ? $orden : 'nombre';
    
    // Para PostgreSQL, necesitamos usar el nombre de la tabla o alias
    if ($ordenCampo === 'categoria_nombre' || $ordenCampo === 'duenno_nombre') {
        // Estos son alias de JOIN, los usamos directamente
        $sql .= " ORDER BY {$ordenCampo}";
    } elseif ($ordenCampo === 'created_at') {
        $sql .= " ORDER BY e.created_at";
    } else {
        $sql .= " ORDER BY e.{$ordenCampo}";
    }
    
    // Agregar dirección
    $direccion = strtoupper($direccion);
    $sql .= ($direccion === 'DESC') ? ' DESC' : ' ASC';
    
    // Agregar límite
    $sql .= " LIMIT :limite";
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contadores
    $sqlCount = "SELECT 
                    COUNT(*) as total
                FROM Empresa";
    
    $stmtCount = $db->query($sqlCount);
    $estadisticasData = $stmtCount->fetch(PDO::FETCH_ASSOC);
    
    if ($estadisticasData) {
        $estadisticas = [
            'total' => (int)($estadisticasData['total'] ?? 0)
        ];
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_empresas_pdf.php: " . $e->getMessage());
}

$totalEnReporte = count($empresas);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Empresas</title>
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
            color: #1F497D; /* Azul Excel */
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
            background: #E7E6E6; /* Gris claro Excel */
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
        
        /* Anchos de columnas ajustados */
        .data-table th:nth-child(1) { width: 12%; } /* RUT */
        .data-table th:nth-child(2) { width: 10%; } /* Nombre */
        .data-table th:nth-child(3) { width: 10%; } /* Apellido */
        .data-table th:nth-child(4) { width: 6%; }  /* Edad */
        .data-table th:nth-child(5) { width: 10%; } /* Fecha Nac */
        .data-table th:nth-child(6) { width: 15%; } /* Teléfonos */
        .data-table th:nth-child(7) { width: 18%; } /* Correos */
        .data-table th:nth-child(8) { width: 19%; } /* Direcciones */
        
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
            grid-template-columns: repeat(5, 1fr);
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
            background: #1F497D; /* Azul Excel */
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
            background: #C00000; /* Rojo Excel */
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
        <h1>Reporte de Empresas</h1>
        <div class="subtitle">Sistema de Gestión - <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
    
    <!-- Estadísticas generales -->
    <div class="info-grid">
        <div class="info-item">
            <span class="info-value"><?php echo $estadisticas['total']; ?></span>
            <span class="info-label">Total Registrados</span>
        </div>
    </div>
    
    <!-- Información del reporte -->
    <!--<div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Ordenado por:</span><br>
                <span class="info-value">
                    <?php 
                    $nombresColumnas = [
                        'nombre' => 'Nombre',
                        'categoria_nombre' => 'Categoría',
                        'duenno_nombre' => 'Dueño',
                    ];
                    echo htmlspecialchars($nombresColumnas[$filtros['orden']] ?? $filtros['orden']);
                    ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Orden:</span><br>
                <span class="info-value">
                    <?php echo $filtros['direccion'] === 'DESC' ? 'Descendente' : 'Ascendente'; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Límite:</span><br>
                <span class="info-value"><?php echo $filtros['limite']; ?> registros</span>
            </div>
        </div>
    </div>-->
    
    <!-- Tabla de datos -->
    <div class="table-container">
        <?php if (!empty($empresas)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Dueño</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empresas as $emp): 
                        $descripcionCorta = $emp['descripcion'] ?? '';
                        if (strlen($descripcionCorta) > 100) {
                            $descripcionCorta = substr($descripcionCorta, 0, 100) . '...';
                        }
                    ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $emp['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($emp['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($emp['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                        <td class="descripcion-cell" title="<?php echo htmlspecialchars($emp['descripcion'] ?? ''); ?>">
                            <?php echo htmlspecialchars($descripcionCorta); ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($emp['duenno_nombre'] ?? 'Sin dueño'); ?></strong><br>
                            <small style="color: #666; font-size: 8.5pt;"><?php echo $emp['duenno_rut'] ?? ''; ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 5px; margin: 20px 0;">
                <h3 style="color: #666;">No se encontraron empresas</h3>
                <p>No hay empresas que coincidan con los filtros seleccionados.</p>
                <p><small>Si acabas de agregar empresas, espera unos segundos y vuelve a intentarlo.</small></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer con estadísticas -->
    <!--<div class="footer">
        <div class="summary">
            <div class="summary-item">
                <span class="summary-value"><?php echo $totalEnReporte; ?></span>
                <span class="summary-label">En este reporte</span>
            </div>
            <div class="summary-item">
                <span class="summary-value">
                    <?php 
                    $promedio = $totalEnReporte > 0 ? 
                        round(strlen(implode('', array_column($empresas, 'descripcion'))) / $totalEnReporte, 0) : 0;
                    echo $promedio . ' chars';
                    ?>
                </span>
                <span class="summary-label">Promedio descripción</span>
            </div>
        </div>
        
        <p>
            <strong>Reporte generado por Sistema de Gestión de Empresas</strong><br>
            <small>Documento confidencial - Uso interno exclusivo</small>
        </p>
        <p style="font-size: 8pt; margin-top: 5px; color: #95a5a6;">
            ID Reporte: <?php echo date('Ymd-His'); ?> | 
            Usuario: <?php echo $_SESSION['username'] ?? 'Sistema'; ?> |
            Filtros aplicados: <?php echo $filtros['orden']; ?> (<?php echo $filtros['direccion']; ?>)
        </p>
    </div>-->
    
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
        // Configuración automática
        window.onload = function() {
            // Opcional: Auto-imprimir después de 1 segundo
            // setTimeout(() => {
            //     if (confirm('¿Deseas imprimir o guardar el reporte ahora?')) {
            //         window.print();
            //     }
            // }, 1000);
        };
        
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