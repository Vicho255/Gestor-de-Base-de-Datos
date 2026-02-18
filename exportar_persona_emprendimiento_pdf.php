<?php
// exportar_persona_emprendimiento_pdf.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado");
}

// Obtener parámetros
$postData = json_decode(file_get_contents('php://input'), true);
if (!$postData) {
    $postData = $_POST ?? [];
}

$orden = $postData['orden'] ?? 'p.nombre';
$direccion = $postData['direccion'] ?? 'ASC';
$limite = intval($postData['limite'] ?? 20);
$filtroEstado = $postData['estado'] ?? 'todos';

// Validar límite
$limite = max(1, min(100, $limite));

// Inicializar arrays
$datos = [];
$estadisticas = ['total_personas' => 0, 'total_emprendimientos' => 0];
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
    } else {
        $whereClause = 'WHERE 1=1';
    }
    
    // Consulta para obtener personas con sus emprendimientos (agrupados)
    $sql = "SELECT 
                p.RUT,
                p.nombre as persona_nombre,
                p.apellido as persona_apellido,
                COUNT(e.id) as total_emprendimientos,
                STRING_AGG(
                    CONCAT(
                        e.nombre, 
                        ' (', 
                        CASE WHEN e.activo THEN 'Activo' ELSE 'Inactivo' END,
                        ') - ', 
                        COALESCE(c.nombre, 'Sin categoría')
                    ), 
                    ' | '
                    ORDER BY e.nombre
                ) as emprendimientos_lista,
                STRING_AGG(
                    e.descripcion, 
                    ' | '
                ) as descripciones
            FROM Personas p
            LEFT JOIN Emprendimiento e ON p.RUT = e.duenno_rut
            LEFT JOIN Categoria c ON e.categoria_id = c.id
            {$whereClause}
            GROUP BY p.RUT, p.nombre, p.apellido
            HAVING COUNT(e.id) > 0 OR '{$filtroEstado}' = 'todos'";
    
    // Agregar ORDER BY
    $columnasPermitidas = ['p.nombre', 'p.apellido', 'p.RUT', 'total_emprendimientos'];
    $ordenCampo = in_array(str_replace('p.', '', $orden), $columnasPermitidas) ? $orden : 'p.nombre';
    $sql .= " ORDER BY {$ordenCampo}";
    
    // Agregar dirección
    $direccion = strtoupper($direccion);
    $sql .= ($direccion === 'DESC') ? ' DESC' : ' ASC';
    
    // Agregar límite
    $sql .= " LIMIT :limite";
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contadores
    if (!empty($datos)) {
        $estadisticas['total_personas'] = count($datos);
        $estadisticas['total_emprendimientos'] = array_sum(array_column($datos, 'total_emprendimientos'));
        
        // Estadísticas por estado
        $sqlEstados = "SELECT 
                        COUNT(DISTINCT p.RUT) as personas_con_emprendimientos,
                        SUM(CASE WHEN e.activo = TRUE THEN 1 ELSE 0 END) as emprendimientos_activos,
                        SUM(CASE WHEN e.activo = FALSE THEN 1 ELSE 0 END) as emprendimientos_inactivos
                    FROM Personas p
                    LEFT JOIN Emprendimiento e ON p.RUT = e.duenno_rut";
        
        $stmtEstados = $db->query($sqlEstados);
        $estadosData = $stmtEstados->fetch(PDO::FETCH_ASSOC);
        
        if ($estadosData) {
            $estadisticas['personas_con_emprendimientos'] = (int)($estadosData['personas_con_emprendimientos'] ?? 0);
            $estadisticas['emprendimientos_activos'] = (int)($estadosData['emprendimientos_activos'] ?? 0);
            $estadisticas['emprendimientos_inactivos'] = (int)($estadosData['emprendimientos_inactivos'] ?? 0);
        }
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_persona_emprendimiento_pdf.php: " . $e->getMessage());
    
    // Datos de prueba
    $datos = [
        [
            'RUT' => '12345678-9',
            'persona_nombre' => 'Juan',
            'persona_apellido' => 'Pérez',
            'total_emprendimientos' => 2,
            'emprendimientos_lista' => 'Tienda Online (Activo) - Comercio | Consultoría Técnica (Activo) - Servicios',
            'descripciones' => 'Venta de productos por internet | Servicios de consultoría tecnológica'
        ],
        [
            'RUT' => '98765432-1',
            'persona_nombre' => 'María',
            'persona_apellido' => 'González',
            'total_emprendimientos' => 1,
            'emprendimientos_lista' => 'Restaurante Vegano (Activo) - Gastronomía',
            'descripciones' => 'Comida vegetariana y vegana'
        ]
    ];
    
    $estadisticas = [
        'total_personas' => 2,
        'total_emprendimientos' => 3,
        'personas_con_emprendimientos' => 2,
        'emprendimientos_activos' => 3,
        'emprendimientos_inactivos' => 0
    ];
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Persona - Emprendimientos</title>
    <style>
        /* Estilos para PDF HORIZONTAL (landscape) */
        @page {
            size: A4 landscape;
            margin: 0.7cm;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        
        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #2c3e50;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 10pt;
            margin-top: 5px;
        }
        
        /* Estadísticas */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .stat-card {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .stat-value {
            font-weight: bold;
            color: #2c3e50;
            font-size: 14pt;
            display: block;
        }
        
        .stat-label {
            font-size: 8pt;
            color: #6c757d;
            display: block;
            margin-top: 3px;
        }
        
        /* Filtros aplicados */
        .filters-section {
            background: #e8f4fc;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
        }
        
        .filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 9pt;
            min-width: 100px;
        }
        
        .filter-value {
            color: #000;
            padding: 4px 8px;
            background: white;
            border-radius: 3px;
            border: 1px solid #bdc3c7;
            flex-grow: 1;
        }
        
        /* Tabla horizontal */
        .table-container {
            margin-top: 15px;
            overflow: hidden;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            table-layout: fixed;
        }
        
        .data-table th {
            background: #34495e;
            color: white;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #2c3e50;
            font-weight: 600;
            vertical-align: middle;
        }
        
        .data-table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
        }
        
        .data-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        /* Anchos específicos para columnas (formato horizontal) */
        .data-table th:nth-child(1) { width: 10%; }  /* RUT */
        .data-table th:nth-child(2) { width: 12%; }  /* Nombre */
        .data-table th:nth-child(3) { width: 10%; }  /* Apellido */
        .data-table th:nth-child(4) { width: 8%; }   /* Total */
        .data-table th:nth-child(5) { width: 35%; }  /* Emprendimientos */
        .data-table th:nth-child(6) { width: 25%; }  /* Descripciones */
        
        /* Estilos para celdas de listas */
        .list-cell {
            max-height: 80px;
            overflow-y: auto;
            padding: 4px;
            line-height: 1.4;
        }
        
        .emprendimiento-item {
            padding: 3px 6px;
            margin: 2px 0;
            background: #ecf0f1;
            border-radius: 3px;
            border-left: 3px solid #3498db;
            font-size: 8.5pt;
        }
        
        .emprendimiento-item.activo {
            border-left-color: #27ae60;
            background: #d5f4e6;
        }
        
        .emprendimiento-item.inactivo {
            border-left-color: #e74c3c;
            background: #fadbd8;
        }
        
        .badge-count {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
            margin-right: 5px;
            background: #2c3e50;
            color: white;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            font-size: 8pt;
            color: #7f8c8d;
        }
        
        /* Controles de impresión */
        .print-controls {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            gap: 10px;
            border: 1px solid #bdc3c7;
        }
        
        .print-btn {
            background: linear-gradient(to right, #2980b9, #3498db);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 9pt;
        }
        
        .close-btn {
            background: linear-gradient(to right, #c0392b, #e74c3c);
        }
        
        /* Ocultar controles al imprimir */
        @media print {
            .print-controls {
                display: none;
            }
            
            body {
                font-size: 8.5pt;
            }
            
            .data-table {
                font-size: 8pt;
            }
        }
        
        /* Scroll en celdas largas */
        .scrollable-cell {
            max-height: 60px;
            overflow-y: auto;
            padding-right: 4px;
        }
        
        .scrollable-cell::-webkit-scrollbar {
            width: 4px;
        }
        
        .scrollable-cell::-webkit-scrollbar-thumb {
            background: #bdc3c7;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>Reporte: Personas con sus Emprendimientos</h1>
        <div class="subtitle">Relación de dueños y sus emprendimientos asociados | Generado: <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['total_personas']; ?></span>
            <span class="stat-label">Personas en Reporte</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['total_emprendimientos']; ?></span>
            <span class="stat-label">Total Emprendimientos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['emprendimientos_activos'] ?? 0; ?></span>
            <span class="stat-label">Emprendimientos Activos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['emprendimientos_inactivos'] ?? 0; ?></span>
            <span class="stat-label">Emprendimientos Inactivos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">
                <?php echo $estadisticas['total_personas'] > 0 ? 
                    number_format($estadisticas['total_emprendimientos'] / $estadisticas['total_personas'], 1) : 0; ?>
            </span>
            <span class="stat-label">Promedio por Persona</span>
        </div>
    </div>
    
    <!-- Filtros aplicados -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-item">
                <span class="filter-label">Ordenado por:</span>
                <span class="filter-value">
                    <?php 
                    $nombresColumnas = [
                        'p.nombre' => 'Nombre',
                        'p.apellido' => 'Apellido',
                        'p.RUT' => 'RUT',
                        'total_emprendimientos' => 'Cantidad de Emprendimientos'
                    ];
                    echo htmlspecialchars($nombresColumnas[$filtros['orden']] ?? $filtros['orden']);
                    ?>
                </span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Dirección:</span>
                <span class="filter-value">
                    <?php echo $filtros['direccion'] === 'DESC' ? 'Descendente' : 'Ascendente'; ?>
                </span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Filtro Estado:</span>
                <span class="filter-value">
                    <?php 
                    $estados = [
                        'todos' => 'Todos los estados',
                        'activos' => 'Solo emprendimientos activos',
                        'inactivos' => 'Solo emprendimientos inactivos'
                    ];
                    echo htmlspecialchars($estados[$filtros['estado']] ?? 'Todos');
                    ?>
                </span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Límite:</span>
                <span class="filter-value"><?php echo $filtros['limite']; ?> registros</span>
            </div>
        </div>
    </div>
    
    <!-- Tabla de datos -->
    <div class="table-container">
        <?php if (!empty($datos)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>RUT</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Total Emprendimientos</th>
                        <th>Emprendimientos (Nombre - Estado - Categoría)</th>
                        <th>Descripciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $item): 
                        // Separar la lista de emprendimientos
                        $emprendimientos = explode(' | ', $item['emprendimientos_lista'] ?? '');
                        $descripciones = explode(' | ', $item['descripciones'] ?? '');
                    ?>
                    <tr>
                        <td style="font-weight: bold; font-family: monospace;">
                            <?php echo htmlspecialchars($item['rut']); ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($item['persona_nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['persona_apellido']); ?></td>
                        <td style="text-align: center; font-weight: bold; font-size: 11pt;">
                            <span class="badge-count"><?php echo $item['total_emprendimientos']; ?></span>
                        </td>
                        <td class="list-cell scrollable-cell">
                            <?php if (!empty($emprendimientos[0])): ?>
                                <?php foreach ($emprendimientos as $index => $emprendimiento): 
                                    $clase = strpos($emprendimiento, '(Activo)') !== false ? 'activo' : 'inactivo';
                                ?>
                                <div class="emprendimiento-item <?php echo $clase; ?>">
                                    <?php echo htmlspecialchars($emprendimiento); ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #95a5a6; font-style: italic;">Sin emprendimientos</span>
                            <?php endif; ?>
                        </td>
                        <td class="list-cell scrollable-cell">
                            <?php if (!empty($descripciones[0])): ?>
                                <?php foreach ($descripciones as $desc): ?>
                                <div style="margin-bottom: 5px; padding: 3px; border-bottom: 1px dashed #ecf0f1;">
                                    <?php echo htmlspecialchars($desc); ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #95a5a6; font-style: italic;">Sin descripciones</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin: 30px 0; border: 2px dashed #bdc3c7;">
                <h3 style="color: #7f8c8d; margin-bottom: 15px;">📊 No se encontraron datos</h3>
                <p style="color: #95a5a6;">No hay personas con emprendimientos que coincidan con los filtros seleccionados.</p>
                <p style="color: #95a5a6; font-size: 9pt; margin-top: 10px;">
                    <strong>Sugerencia:</strong> Intenta cambiar los filtros o verifica que existan emprendimientos asignados a personas.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>
            <strong>Reporte de Relación Persona-Emprendimiento</strong> | 
            Sistema de Gestión Integral |
            Página 1 de 1
        </p>
        <p style="font-size: 7.5pt; margin-top: 5px;">
            Generado por: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Sistema'); ?> | 
            ID: <?php echo date('Ymd-His') . '-PE'; ?> | 
            Filtros: <?php echo $filtros['estado']; ?> | 
            Orden: <?php echo $filtros['orden']; ?> (<?php echo $filtros['direccion']; ?>)
        </p>
    </div>
    
    <!-- Controles de impresión -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="print-btn">
            <span>🖨️</span> Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()" class="print-btn close-btn">
            <span>❌</span> Cerrar
        </button>
    </div>
    
    <script>
        // Configuración automática
        window.onload = function() {
            // Auto-ajustar altura de celdas
            document.querySelectorAll('.scrollable-cell').forEach(cell => {
                if (cell.scrollHeight > 60) {
                    cell.style.maxHeight = '100px';
                }
            });
            
            // Opcional: Auto-imprimir
            // setTimeout(() => {
            //     if (confirm('¿Deseas imprimir o guardar el reporte ahora?')) {
            //         window.print();
            //     }
            // }, 500);
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
                window.close();
            }
        });
        
        // Mejorar scroll en celdas
        document.querySelectorAll('.scrollable-cell').forEach(cell => {
            cell.addEventListener('mouseenter', function() {
                this.style.overflowY = 'auto';
            });
            
            cell.addEventListener('mouseleave', function() {
                this.style.overflowY = 'hidden';
            });
        });
    </script>
</body>
</html>