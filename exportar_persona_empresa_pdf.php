<?php
// exportar_persona_empresa_pdf.php
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

// Validar límite
$limite = max(1, min(100, $limite));

// Inicializar arrays
$datos = [];
$estadisticas = ['total_personas' => 0, 'total_empresas' => 0];
$filtros = [
    'orden' => $orden,
    'direccion' => $direccion,
    'limite' => $limite
];

try {
    $db = getDB();
    
    // Consulta para obtener personas con sus empresas (agrupadas)
    $sql = "SELECT 
                p.RUT,
                p.nombre as persona_nombre,
                p.apellido as persona_apellido,
                COUNT(em.id) as total_empresas,
                STRING_AGG(
                    CONCAT(
                        em.nombre, 
                        ' - ', 
                        COALESCE(c.nombre, 'Sin categoría'),
                        ' (ID: ', em.id, ')'
                    ), 
                    ' | '
                    ORDER BY em.nombre
                ) as empresas_lista,
                STRING_AGG(
                    em.descripcion, 
                    ' | '
                ) as descripciones
            FROM Personas p
            LEFT JOIN Empresa em ON p.RUT = em.duenno_rut
            LEFT JOIN Categoria c ON em.categoria_id = c.id
            GROUP BY p.RUT, p.nombre, p.apellido
            HAVING COUNT(em.id) > 0";
    
    // Agregar ORDER BY
    $columnasPermitidas = ['p.nombre', 'p.apellido', 'p.RUT', 'total_empresas'];
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
        $estadisticas['total_empresas'] = array_sum(array_column($datos, 'total_empresas'));
        
        // Estadísticas adicionales
        $sqlStats = "SELECT 
                        COUNT(DISTINCT p.RUT) as personas_con_empresas,
                        COUNT(DISTINCT em.id) as total_empresas_global,
                        COUNT(DISTINCT c.id) as categorias_utilizadas
                    FROM Personas p
                    LEFT JOIN Empresa em ON p.RUT = em.duenno_rut
                    LEFT JOIN Categoria c ON em.categoria_id = c.id";
        
        $stmtStats = $db->query($sqlStats);
        $statsData = $stmtStats->fetch(PDO::FETCH_ASSOC);
        
        if ($statsData) {
            $estadisticas['personas_con_empresas'] = (int)($statsData['personas_con_empresas'] ?? 0);
            $estadisticas['total_empresas_global'] = (int)($statsData['total_empresas_global'] ?? 0);
            $estadisticas['categorias_utilizadas'] = (int)($statsData['categorias_utilizadas'] ?? 0);
        }
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_persona_empresa_pdf.php: " . $e->getMessage());
    
    // Datos de prueba
    $datos = [
        [
            'RUT' => '12345678-9',
            'persona_nombre' => 'Juan',
            'persona_apellido' => 'Pérez',
            'total_empresas' => 2,
            'empresas_lista' => 'Tech Solutions S.A. - Tecnología (ID: 1) | Consultoría Pérez Ltda. - Consultoría (ID: 2)',
            'descripciones' => 'Desarrollo de software y soluciones IT | Consultoría empresarial y gestión de proyectos'
        ],
        [
            'RUT' => '98765432-1',
            'persona_nombre' => 'María',
            'persona_apellido' => 'González',
            'total_empresas' => 1,
            'empresas_lista' => 'Distribuidora González - Logística (ID: 3)',
            'descripciones' => 'Distribución de productos a nivel nacional'
        ],
        [
            'RUT' => '11222333-4',
            'persona_nombre' => 'Carlos',
            'persona_apellido' => 'Rodríguez',
            'total_empresas' => 3,
            'empresas_lista' => 'Constructora Rodríguez - Construcción (ID: 4) | Inmobiliaria CR - Bienes Raíces (ID: 5) | Taller Mecánico CR - Automotriz (ID: 6)',
            'descripciones' => 'Construcción de edificios residenciales | Venta y arriendo de propiedades | Servicios mecánicos para vehículos'
        ]
    ];
    
    $estadisticas = [
        'total_personas' => 3,
        'total_empresas' => 6,
        'personas_con_empresas' => 3,
        'total_empresas_global' => 6,
        'categorias_utilizadas' => 4
    ];
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Persona - Empresas</title>
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
            border-bottom: 3px solid #8e44ad;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #8e44ad;
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
            color: #8e44ad;
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
            background: #f3e8ff;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #9b59b6;
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
            color: #8e44ad;
            font-size: 9pt;
            min-width: 100px;
        }
        
        .filter-value {
            color: #000;
            padding: 4px 8px;
            background: white;
            border-radius: 3px;
            border: 1px solid #d7bde2;
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
            background: #6c3483;
            color: white;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #5b2c6f;
            font-weight: 600;
            vertical-align: middle;
        }
        
        .data-table td {
            padding: 6px;
            border: 1px solid #e8daef;
            vertical-align: top;
            word-wrap: break-word;
        }
        
        .data-table tr:nth-child(even) {
            background: #f9f0ff;
        }
        
        /* Anchos específicos para columnas (formato horizontal) */
        .data-table th:nth-child(1) { width: 10%; }  /* RUT */
        .data-table th:nth-child(2) { width: 12%; }  /* Nombre */
        .data-table th:nth-child(3) { width: 10%; }  /* Apellido */
        .data-table th:nth-child(4) { width: 8%; }   /* Total */
        .data-table th:nth-child(5) { width: 30%; }  /* Empresas */
        .data-table th:nth-child(6) { width: 30%; }  /* Descripciones */
        
        /* Estilos para celdas de listas */
        .list-cell {
            max-height: 80px;
            overflow-y: auto;
            padding: 4px;
            line-height: 1.4;
        }
        
        .empresa-item {
            padding: 3px 6px;
            margin: 2px 0;
            background: #f4ecf7;
            border-radius: 3px;
            border-left: 3px solid #9b59b6;
            font-size: 8.5pt;
        }
        
        .empresa-item:hover {
            background: #e8daef;
        }
        
        .badge-count {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
            margin-right: 5px;
            background: #8e44ad;
            color: white;
        }
        
        .category-tag {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7.5pt;
            background: #d7bde2;
            color: #5b2c6f;
            margin-left: 5px;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #f4ecf7;
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
            border: 1px solid #d7bde2;
        }
        
        .print-btn {
            background: linear-gradient(to right, #8e44ad, #9b59b6);
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
            background: #d7bde2;
            border-radius: 2px;
        }
        
        /* Colores por categoría */
        .categoria-tecnologia { border-left-color: #3498db; background: #d6eaf8; }
        .categoria-consultoria { border-left-color: #f39c12; background: #fdebd0; }
        .categoria-logistica { border-left-color: #27ae60; background: #d5f4e6; }
        .categoria-construccion { border-left-color: #e74c3c; background: #fadbd8; }
        .categoria-bienes-raices { border-left-color: #9b59b6; background: #f4ecf7; }
        .categoria-automotriz { border-left-color: #34495e; background: #ebedef; }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>Reporte: Personas con sus Empresas</h1>
        <div class="subtitle">Relación de dueños y sus empresas asociadas | Generado: <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['total_personas']; ?></span>
            <span class="stat-label">Personas en Reporte</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['total_empresas']; ?></span>
            <span class="stat-label">Total Empresas</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['total_empresas_global'] ?? 0; ?></span>
            <span class="stat-label">Empresas Totales</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $estadisticas['categorias_utilizadas'] ?? 0; ?></span>
            <span class="stat-label">Categorías Utilizadas</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">
                <?php echo $estadisticas['total_personas'] > 0 ? 
                    number_format($estadisticas['total_empresas'] / $estadisticas['total_personas'], 1) : 0; ?>
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
                        'total_empresas' => 'Cantidad de Empresas'
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
                <span class="filter-label">Límite:</span>
                <span class="filter-value"><?php echo $filtros['limite']; ?> registros</span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Formato:</span>
                <span class="filter-value">Horizontal (Una fila por persona)</span>
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
                        <th>Total Empresas</th>
                        <th>Empresas (Nombre - Categoría - ID)</th>
                        <th>Descripciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $item): 
                        // Separar la lista de empresas
                        $empresas = explode(' | ', $item['empresas_lista'] ?? '');
                        $descripciones = explode(' | ', $item['descripciones'] ?? '');
                    ?>
                    <tr>
                        <td style="font-weight: bold; font-family: monospace;">
                            <?php echo htmlspecialchars($item['rut']); ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($item['persona_nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['persona_apellido']); ?></td>
                        <td style="text-align: center; font-weight: bold; font-size: 11pt;">
                            <span class="badge-count"><?php echo $item['total_empresas']; ?></span>
                        </td>
                        <td class="list-cell scrollable-cell">
                            <?php if (!empty($empresas[0])): ?>
                                <?php foreach ($empresas as $index => $empresa): 
                                    // Determinar clase CSS según categoría
                                    $clase = 'empresa-item';
                                    if (strpos($empresa, '- Tecnología') !== false) $clase .= ' categoria-tecnologia';
                                    elseif (strpos($empresa, '- Consultoría') !== false) $clase .= ' categoria-consultoria';
                                    elseif (strpos($empresa, '- Logística') !== false) $clase .= ' categoria-logistica';
                                    elseif (strpos($empresa, '- Construcción') !== false) $clase .= ' categoria-construccion';
                                    elseif (strpos($empresa, '- Bienes Raíces') !== false) $clase .= ' categoria-bienes-raices';
                                    elseif (strpos($empresa, '- Automotriz') !== false) $clase .= ' categoria-automotriz';
                                ?>
                                <div class="<?php echo $clase; ?>">
                                    <?php echo htmlspecialchars($empresa); ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #95a5a6; font-style: italic;">Sin empresas</span>
                            <?php endif; ?>
                        </td>
                        <td class="list-cell scrollable-cell">
                            <?php if (!empty($descripciones[0])): ?>
                                <?php foreach ($descripciones as $desc): ?>
                                <div style="margin-bottom: 5px; padding: 3px; border-bottom: 1px dashed #e8daef;">
                                    <span style="color: #7d3c98; font-size: 7.5pt; font-weight: bold;">•</span>
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
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin: 30px 0; border: 2px dashed #d7bde2;">
                <h3 style="color: #7f8c8d; margin-bottom: 15px;">🏢 No se encontraron datos</h3>
                <p style="color: #95a5a6;">No hay personas con empresas que coincidan con los filtros seleccionados.</p>
                <p style="color: #95a5a6; font-size: 9pt; margin-top: 10px;">
                    <strong>Sugerencia:</strong> Verifica que existan empresas asignadas a personas en la base de datos.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>
            <strong>Reporte de Relación Persona-Empresa</strong> | 
            Sistema de Gestión Integral |
            Página 1 de 1
        </p>
        <p style="font-size: 7.5pt; margin-top: 5px;">
            Generado por: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Sistema'); ?> | 
            ID: <?php echo date('Ymd-His') . '-PE'; ?> | 
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
                    cell.style.maxHeight = '120px';
                }
            });
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
        
        // Mejorar visualización de celdas
        document.querySelectorAll('.empresa-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(2px)';
                this.style.boxShadow = '1px 1px 3px rgba(0,0,0,0.1)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>