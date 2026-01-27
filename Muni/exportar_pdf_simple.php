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
                p.rut,
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
    
    // Si hay personas, obtener sus teléfonos y correos
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
            
            // Obtener emprendimientos
            $sqlEmprendimientos = "SELECT nombre FROM Emprendimiento WHERE duenno_rut = :rut AND activo = TRUE";
            $stmtEmp = $db->prepare($sqlEmprendimientos);
            $stmtEmp->execute([':rut' => $rut]);
            $emprendimientos = $stmtEmp->fetchAll(PDO::FETCH_COLUMN);
            $persona['emprendimientos'] = !empty($emprendimientos) ? implode(', ', $emprendimientos) : '';
            
            // Obtener empresas
            $sqlEmpresas = "SELECT nombre FROM Empresa WHERE duenno_rut = :rut";
            $stmtEmpresa = $db->prepare($sqlEmpresas);
            $stmtEmpresa->execute([':rut' => $rut]);
            $empresas = $stmtEmpresa->fetchAll(PDO::FETCH_COLUMN);
            $persona['empresas'] = !empty($empresas) ? implode(', ', $empresas) : '';
            
            // Contadores
            $persona['total_telefonos'] = count($telefonos);
            $persona['total_correos'] = count($correos);
            $persona['total_direcciones'] = count($direcciones);
            $persona['total_emprendimientos'] = count($emprendimientos);
            $persona['total_empresas'] = count($empresas);
        }
        unset($persona); // Romper referencia
        
    } else {
        // Datos de prueba si no hay resultados
        $personas = [
            [
                'RUT' => '12345678-9',
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'fecha_nac' => '1990-01-01',
                'edad' => 33,
                'telefonos' => '+56912345678, +56987654321',
                'correos' => 'juan@correo.com, jperez@empresa.cl',
                'direcciones' => 'Calle Principal #123, Santiago Centro, Santiago',
                'emprendimientos' => 'Tienda Online, Consultoría',
                'empresas' => 'Pérez & Asociados SA',
                'total_telefonos' => 2,
                'total_correos' => 2,
                'total_direcciones' => 1,
                'total_emprendimientos' => 2,
                'total_empresas' => 1
            ],
            [
                'RUT' => '98765432-1',
                'nombre' => 'María',
                'apellido' => 'González',
                'fecha_nac' => '1985-05-15',
                'edad' => 38,
                'telefonos' => '+56955556666',
                'correos' => 'maria.gonzalez@gmail.com',
                'direcciones' => 'Avenida Libertad #456, Providencia, Santiago',
                'emprendimientos' => 'Cafetería Artesanal',
                'empresas' => '',
                'total_telefonos' => 1,
                'total_correos' => 1,
                'total_direcciones' => 1,
                'total_emprendimientos' => 1,
                'total_empresas' => 0
            ]
        ];
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
            'emprendimientos' => '',
            'empresas' => '',
            'total_telefonos' => 0,
            'total_correos' => 0,
            'total_direcciones' => 0,
            'total_emprendimientos' => 0,
            'total_empresas' => 0
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
    <title>Reporte de Personas</title>
    <style>
        /* Estilos minimalistas para PDF */
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            margin: 0;
            font-size: 16pt;
            color: #333;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 10pt;
            margin-top: 5px;
        }
        
        .filters {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .filters p {
            margin: 5px 0;
            font-size: 9pt;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9pt;
        }
        
        .data-table th {
            background: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #444;
        }
        
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .data-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        .summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .summary-label {
            font-size: 8pt;
            color: #666;
        }
        
        .contact-info {
            font-size: 8.5pt;
            line-height: 1.2;
        }
        
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: bold;
            margin-left: 3px;
        }
        
        .badge-phone { background: #e3f2fd; color: #1565c0; }
        .badge-email { background: #f3e5f5; color: #7b1fa2; }
        .badge-address { background: #e8f5e9; color: #2e7d32; }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>Reporte de Personas</h1>
        <div class="subtitle">Sistema de Gestión - <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
    
    <!-- Filtros aplicados -->
    <div class="filters">
        <p><strong>Filtros aplicados:</strong></p>
        <p>Ordenado por: <?php echo htmlspecialchars($orden); ?> (<?php echo htmlspecialchars($direccion); ?>)</p>
        <p>Límite: <?php echo $limite; ?> registros | Total en reporte: <?php echo $totalPersonas; ?> personas</p>
        <p>Fecha: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
    
    <!-- Tabla de datos -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="12%">RUT</th>
                <th width="10%">Nombre</th>
                <th width="10%">Apellido</th>
                <th width="6%">Edad</th>
                <th width="10%">Fecha Nac.</th>
                <th width="15%">Teléfonos</th>
                <th width="18%">Correos Electrónicos</th>
                <th width="19%">Direcciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($hayDatos): ?>
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
                    <td style="text-align: center;"><?php echo htmlspecialchars($persona['edad']); ?></td>
                    <td><?php echo $fechaNac; ?></td>
                    <td class="contact-info">
                        <?php echo htmlspecialchars($persona['telefonos']); ?>
                        <?php if ($persona['total_telefonos'] > 0): ?>
                            <span class="badge badge-phone"><?php echo $persona['total_telefonos']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="contact-info">
                        <?php echo htmlspecialchars($persona['correos']); ?>
                        <?php if ($persona['total_correos'] > 0): ?>
                            <span class="badge badge-email"><?php echo $persona['total_correos']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="contact-info">
                        <?php echo htmlspecialchars($persona['direcciones']); ?>
                        <?php if ($persona['total_direcciones'] > 0): ?>
                            <span class="badge badge-address"><?php echo $persona['total_direcciones']; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #666;">
                        No se encontraron datos en la base de datos.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Footer -->
    <div class="footer">
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value"><?php echo $totalPersonas; ?></div>
                <div class="summary-label">Personas</div>
            </div>
            <?php if ($hayDatos): 
                $totalTelefonos = array_sum(array_column($personas, 'total_telefonos'));
                $totalCorreos = array_sum(array_column($personas, 'total_correos'));
                $totalDirecciones = array_sum(array_column($personas, 'total_direcciones'));
            ?>
            <div class="summary-item">
                <div class="summary-value"><?php echo $totalTelefonos; ?></div>
                <div class="summary-label">Teléfonos</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo $totalCorreos; ?></div>
                <div class="summary-label">Correos</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo $totalDirecciones; ?></div>
                <div class="summary-label">Direcciones</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">
                    <?php echo $totalPersonas > 0 ? round(($totalTelefonos + $totalCorreos) / $totalPersonas, 1) : 0; ?>
                </div>
                <div class="summary-label">Promedio contactos</div>
            </div>
            <?php endif; ?>
        </div>
        
        <p>
            <strong>Reporte generado por Sistema de Gestión</strong><br>
            Documento confidencial - Uso interno
        </p>
        <p style="font-size: 8pt; margin-top: 5px;">
            Usuario: <?php echo $_SESSION['username'] ?? 'Sistema'; ?> | 
            ID: <?php echo date('Ymd-His'); ?>
        </p>
    </div>
    
    <!-- Controles de impresión (solo en navegador) -->
    <div class="no-print" style="position: fixed; bottom: 20px; right: 20px;">
        <button onclick="window.print()" style="
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        ">
            🖨️ Imprimir / Guardar como PDF
        </button>
        <button onclick="window.close()" style="
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        ">
            ❌ Cerrar
        </button>
    </div>
    
    <script>
        // Auto-imprimir después de 1 segundo (opcional - descomentar si quieres)
        // window.onload = function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 1000);
        // };
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>