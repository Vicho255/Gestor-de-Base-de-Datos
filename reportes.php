<?php
// reportes.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir configuración de base de datos
require_once 'config/database.php';

$userName = $_SESSION['username'] ?? 'Usuario';
$pageTitle = "Reportes de Adición y Cambios";

// Obtener filtros desde GET
$tabla = $_GET['tabla'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;

// Validar límite
if ($limite < 1 || $limite > 500) {
    $limite = 100;
}

// Convertir fechas vacías a null
$desde = $desde !== '' ? $desde : null;
$hasta = $hasta !== '' ? $hasta : null;

$resultados = [];
$error = null;

try {
    $db = getDB();
    
    // Construir consulta base
    $sql = "SELECT id, tabla_afectada, operacion, id_registro, fecha_cambio, descripcion
            FROM Registro_de_Cambios
            WHERE 1=1";
    
    $params = [];
    
    // Filtro por tabla (si se seleccionó una específica)
    if (!empty($tabla)) {
        $sql .= " AND tabla_afectada = :tabla";
        $params[':tabla'] = $tabla;
    }
    
    // Filtro por fecha desde
    if ($desde !== null) {
        $sql .= " AND fecha_cambio >= :desde::date";
        $params[':desde'] = $desde;
    }
    
    // Filtro por fecha hasta (incluye todo el día)
    if ($hasta !== null) {
        $sql .= " AND fecha_cambio < (:hasta::date + interval '1 day')";
        $params[':hasta'] = $hasta;
    }
    
    // Orden y límite
    $sql .= " ORDER BY fecha_cambio DESC LIMIT :limite";
    $params[':limite'] = $limite;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al obtener los registros: " . $e->getMessage();
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/reportes.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
    <style>
        /* (los mismos estilos de antes, se mantienen) */
        .filters-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            border: 1px solid #dee2e6;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        .filter-group label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
        }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn-filter {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            height: 38px;
            transition: background-color 0.2s;
        }
        .btn-filter:hover {
            background-color: #0056b3;
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #343a40;
            color: white;
            padding: 12px;
            font-weight: 500;
            text-align: left;
            white-space: nowrap;
        }
        .table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-insert {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-update {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-delete {
            background-color: #f8d7da;
            color: #721c24;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .record-count {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .fecha-col {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include 'components/sideBar.php'; ?>
    <div class="main-content">
        <?php include 'components/header.php'; ?>

        <div class="content-wrapper" style="padding: 20px;">

            <!-- Formulario de filtros -->
            <form method="GET" class="filters-container">
                <div class="filter-group">
                    <label for="tabla">Tabla afectada</label>
                    <select name="tabla" id="tabla">
                        <option value="">Todas</option>
                        <option value="personas" <?php echo $tabla === 'personas' ? 'selected' : ''; ?>>Personas</option>
                        <option value="emprendimiento" <?php echo $tabla === 'emprendimiento' ? 'selected' : ''; ?>>Emprendimiento</option>
                        <option value="empresa" <?php echo $tabla === 'empresa' ? 'selected' : ''; ?>>Empresa</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="desde">Fecha desde</label>
                    <input type="date" name="desde" id="desde" value="<?php echo htmlspecialchars($desde ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="hasta">Fecha hasta</label>
                    <input type="date" name="hasta" id="hasta" value="<?php echo htmlspecialchars($hasta ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="limite">Registros a mostrar</label>
                    <input type="number" name="limite" id="limite" min="1" max="500" value="<?php echo $limite; ?>">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="reportes.php" class="btn-filter" style="background-color: #6c757d; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="fas fa-undo-alt"></i> Limpiar
                </a>
            </form>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($resultados) && !$error): ?>
                <div class="no-results">
                    <i class="fas fa-database" style="font-size: 3rem; opacity: 0.5; margin-bottom: 15px;"></i>
                    <p>No se encontraron registros de cambios con los filtros seleccionados.</p>
                </div>
            <?php elseif (!empty($resultados)): ?>
                <div class="record-count">
                    Mostrando <?php echo count($resultados); ?> registro(s)
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tabla</th>
                                <th>Operación</th>
                                <th>ID Registro</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultados as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tabla_afectada']); ?></td>
                                    <td>
                                        <?php 
                                            $operacion = $row['operacion'];
                                            $badgeClass = '';
                                            if ($operacion === 'INSERT') $badgeClass = 'badge-insert';
                                            elseif ($operacion === 'UPDATE') $badgeClass = 'badge-update';
                                            elseif ($operacion === 'DELETE') $badgeClass = 'badge-delete';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($operacion); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['id_registro']); ?></td>
                                    <td class="fecha-col">
                                        <?php 
                                            if ($row['fecha_cambio']) {
                                                $fecha = new DateTime($row['fecha_cambio']);
                                                echo $fecha->format('d/m/Y H:i:s');
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>