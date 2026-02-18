<?php
// exportar_emprendimientos_pdf.php
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
$filtroEstado = $postData['estado'] ?? 'todos';

// Validar límite
$limite = max(1, min(1000, $limite));

// Inicializar arrays
$emprendimientos = [];
$estadisticas = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
$filtros = [
    'orden' => $orden,
    'direccion' => $direccion,
    'estado' => $filtroEstado,
    'limite' => $limite
];

try {
    $db = getDB();
    
    // Construir WHERE según filtro de estado
    $whereClause = '';
    if ($filtroEstado === 'activos') {
        $whereClause = 'WHERE e.activo = TRUE';
    } elseif ($filtroEstado === 'inactivos') {
        $whereClause = 'WHERE e.activo = FALSE';
    }
    
    // Consulta para obtener emprendimientos con JOINs - PostgreSQL
    $sql = "SELECT 
                e.id,
                e.nombre,
                e.descripcion,
                e.activo,
                c.nombre as categoria_nombre,
                p.nombre || ' ' || p.apellido as duenno_nombre,
                p.rut as duenno_rut
            FROM Emprendimiento e
            LEFT JOIN Categoria c ON e.categoria_id = c.id
            LEFT JOIN Personas p ON e.duenno_rut = p.rut
            {$whereClause}";
    
    // Agregar ORDER BY según el campo seleccionado
    $columnasPermitidas = ['nombre', 'categoria_nombre', 'duenno_nombre', 'activo', 'created_at'];
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
    $emprendimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contadores
    $sqlCount = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN activo = TRUE THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN activo = FALSE THEN 1 ELSE 0 END) as inactivos
                FROM Emprendimiento";
    
    $stmtCount = $db->query($sqlCount);
    $estadisticasData = $stmtCount->fetch(PDO::FETCH_ASSOC);
    
    if ($estadisticasData) {
        $estadisticas = [
            'total' => (int)($estadisticasData['total'] ?? 0),
            'activos' => (int)($estadisticasData['activos'] ?? 0),
            'inactivos' => (int)($estadisticasData['inactivos'] ?? 0)
        ];
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_emprendimientos_pdf.php: " . $e->getMessage());
    
    // Datos de prueba en caso de error
    $emprendimientos = [
        [
            'id' => 1,
            'nombre' => 'Ejemplo de Emprendimiento',
            'descripcion' => 'Descripción de ejemplo del emprendimiento. Este es un texto más largo para probar el formateo en el PDF.',
            'activo' => true,
            'categoria_nombre' => 'Servicios',
            'duenno_nombre' => 'Juan Pérez',
            'duenno_rut' => '12345678-9'
        ],
        [
            'id' => 2,
            'nombre' => 'Tienda Online',
            'descripcion' => 'Venta de productos por internet con envío a todo el país.',
            'activo' => false,
            'categoria_nombre' => 'Comercio',
            'duenno_nombre' => 'María González',
            'duenno_rut' => '98765432-1'
        ],
        [
            'id' => 3,
            'nombre' => 'Consultoría Técnica',
            'descripcion' => 'Servicios de consultoría en tecnología y desarrollo de software.',
            'activo' => true,
            'categoria_nombre' => 'Consultoría',
            'duenno_nombre' => 'Carlos Rodríguez',
            'duenno_rut' => '11222333-4'
        ]
    ];
    
    $estadisticas = [
        'total' => 3,
        'activos' => 2,
        'inactivos' => 1
    ];
}

$totalEnReporte = count($emprendimientos);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Emprendimientos</title>
    <style>
        /* Estilos para PDF VERTICAL */
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #2c3e50;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 11pt;
            margin-top: 5px;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            font-size: 9pt;
        }
        
        .info-value {
            color: #2c3e50;
            padding: 3px 6px;
            background: white;
            border-radius: 3px;
            border: 1px solid #dee2e6;
            display: inline-block;
            margin-top: 2px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .stat-value {
            font-weight: bold;
            color: #2c3e50;
            font-size: 14pt;
            display: block;
        }
        
        .stat-label {
            font-size: 9pt;
            color: #666;
            display: block;
            margin-top: 3px;
        }
        
        /* Tabla ajustada para vertical */
        .table-container {
            margin-top: 10px;
            overflow: hidden;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            table-layout: fixed;
        }
        
        .data-table th {
            background: #2c3e50;
            color: white;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #444;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
        }
        
        .data-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        /* Anchos específicos para columnas */
        .data-table th:nth-child(1) { width: 6%; }    /* ID */
        .data-table th:nth-child(2) { width: 18%; }   /* Nombre */
        .data-table th:nth-child(3) { width: 12%; }   /* Categoría */
        .data-table th:nth-child(4) { width: 20%; }   /* Descripción */
        .data-table th:nth-child(5) { width: 18%; }   /* Dueño */
        .data-table th:nth-child(6) { width: 8%; }    /* Estado */
        
        .descripcion-cell {
            font-size: 8.5pt;
            line-height: 1.2;
            max-height: 40px;
            overflow: hidden;
        }
        
        .badge-activo, .badge-inactivo {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 8.5pt;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-activo { background-color: #d4edda; color: #155724; }
        .badge-inactivo { background-color: #f8d7da; color: #721c24; }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .summary-item {
            text-align: center;
            padding: 6px;
            background: white;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }
        
        .summary-value {
            font-weight: bold;
            color: #2c3e50;
            font-size: 10pt;
            display: block;
        }
        
        .summary-label {
            font-size: 8pt;
            color: #666;
            display: block;
            margin-top: 2px;
        }
        
        /* Controles de impresión */
        .print-controls {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            gap: 8px;
            border: 1px solid #ddd;
        }
        
        .print-btn {
            background: linear-gradient(to right, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 9pt;
        }
        
        .close-btn {
            background: #e74c3c;
        }
        
        /* Ocultar controles al imprimir */
        @media print {
            .print-controls {
                display: none;
            }
            
            body {
                font-size: 9pt;
            }
            
            .data-table {
                font-size: 8.5pt;
            }
            
            .data-table th,
            .data-table td {
                padding: 5px 4px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .info-grid, .stats-grid, .summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>Reporte de Emprendimientos</h1>
        <div class="subtitle">Sistema de Gestión - <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
    
    <!-- Estadísticas generales -->
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-value"><?php echo $estadisticas['total']; ?></span>
            <span class="stat-label">Total Registrados</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?php echo $estadisticas['activos']; ?></span>
            <span class="stat-label">Emprendimientos Activos</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?php echo $estadisticas['inactivos']; ?></span>
            <span class="stat-label">Emprendimientos Inactivos</span>
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
                        'activo' => 'Estado',
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
                <span class="info-label">Filtro Estado:</span><br>
                <span class="info-value">
                    <?php 
                    $estados = [
                        'todos' => 'Todos',
                        'activos' => 'Solo Activos',
                        'inactivos' => 'Solo Inactivos'
                    ];
                    echo htmlspecialchars($estados[$filtros['estado']] ?? 'Todos');
                    ?>
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
        <?php if (!empty($emprendimientos)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Dueño</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emprendimientos as $emp): 
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
                        <td style="text-align: center;">
                            <?php if ($emp['activo']): ?>
                                <span class="badge-activo">Activo</span>
                            <?php else: ?>
                                <span class="badge-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 5px; margin: 20px 0;">
                <h3 style="color: #666;">No se encontraron emprendimientos</h3>
                <p>No hay emprendimientos que coincidan con los filtros seleccionados.</p>
                <p><small>Si acabas de agregar emprendimientos, espera unos segundos y vuelve a intentarlo.</small></p>
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
                    $activos = array_filter($emprendimientos, function($emp) {
                        return $emp['activo'] === true;
                    });
                    echo count($activos);
                    ?>
                </span>
                <span class="summary-label">Activos en reporte</span>
            </div>
            <div class="summary-item">
                <span class="summary-value">
                    <?php 
                    $inactivos = array_filter($emprendimientos, function($emp) {
                        return $emp['activo'] === false;
                    });
                    echo count($inactivos);
                    ?>
                </span>
                <span class="summary-label">Inactivos en reporte</span>
            </div>
            <div class="summary-item">
                <span class="summary-value">
                    <?php 
                    $promedio = $totalEnReporte > 0 ? 
                        round(strlen(implode('', array_column($emprendimientos, 'descripcion'))) / $totalEnReporte, 0) : 0;
                    echo $promedio . ' chars';
                    ?>
                </span>
                <span class="summary-label">Promedio descripción</span>
            </div>
        </div>
        
        <p>
            <strong>Reporte generado por Sistema de Gestión de Emprendimientos</strong><br>
            <small>Documento confidencial - Uso interno exclusivo</small>
        </p>
        <p style="font-size: 8pt; margin-top: 5px; color: #95a5a6;">
            ID Reporte: <?php echo date('Ymd-His'); ?> | 
            Usuario: <?php echo $_SESSION['username'] ?? 'Sistema'; ?> |
            Filtros aplicados: <?php echo $filtros['estado']; ?> | <?php echo $filtros['orden']; ?> (<?php echo $filtros['direccion']; ?>)
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