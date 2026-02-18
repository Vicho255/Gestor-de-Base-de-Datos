<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['username'] ?? 'Usuario';
$pageTitle = "Beneficios";

// Incluir la conexión a la base de datos
require_once 'config/database.php';

$db = getDB();

// Procesar formularios POST
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Agregar nuevo beneficio
    if ($action === 'add_beneficio') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            $_SESSION['flash_message'] = 'El nombre del beneficio es obligatorio.';
            $_SESSION['flash_type'] = 'error';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO Beneficios (nombre, descripcion) VALUES (:nombre, :descripcion)");
                $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
                $_SESSION['flash_message'] = 'Beneficio agregado correctamente.';
                $_SESSION['flash_type'] = 'success';
            } catch (PDOException $e) {
                error_log("Error al insertar beneficio: " . $e->getMessage());
                $_SESSION['flash_message'] = 'Error al agregar el beneficio. Intente nuevamente.';
                $_SESSION['flash_type'] = 'error';
            }
        }
        // Redirigir manteniendo el RUT si existe
        $redirect = $_SERVER['PHP_SELF'];
        if (!empty($_GET['rut'])) {
            $redirect .= '?rut=' . urlencode($_GET['rut']);
        }
        header("Location: $redirect");
        exit();
    }

    // Asignar beneficio a persona
    if ($action === 'assign_beneficio') {
        $rut = trim($_POST['rut'] ?? '');
        $beneficio_id = intval($_POST['beneficio_id'] ?? 0);
        $fecha_post = date('Y-m-d');

        if (empty($rut) || $beneficio_id <= 0) {
            $_SESSION['flash_message'] = 'Datos incompletos para asignar el beneficio.';
            $_SESSION['flash_type'] = 'error';
        } else {
            try {
                $stmtCheck = $db->prepare("SELECT 1 FROM Beneficios_Persona WHERE Persona_rut = :rut AND Beneficio_id = :bid");
                $stmtCheck->execute([':rut' => $rut, ':bid' => $beneficio_id]);
                if ($stmtCheck->fetch()) {
                    $_SESSION['flash_message'] = 'Este beneficio ya está asignado a la persona.';
                    $_SESSION['flash_type'] = 'warning';
                } else {
                    $stmt = $db->prepare("INSERT INTO Beneficios_Persona (Persona_rut, Beneficio_id, fecha_post) VALUES (:rut, :bid, :fecha)");
                    $stmt->execute([':rut' => $rut, ':bid' => $beneficio_id, ':fecha' => $fecha_post]);
                    $_SESSION['flash_message'] = 'Beneficio asignado correctamente.';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                error_log("Error al asignar beneficio: " . $e->getMessage());
                if ($e->getCode() == 23505) { // Código de duplicado en PostgreSQL
                    $_SESSION['flash_message'] = 'El beneficio ya estaba asignado a esta persona.';
                } else {
                    $_SESSION['flash_message'] = 'Error al asignar el beneficio. Intente nuevamente.';
                }
                $_SESSION['flash_type'] = 'error';
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?rut=" . urlencode($rut));
        exit();
    }

    // Eliminar beneficio de una persona (desasignar)
    if ($action === 'remove_from_person') {
        $rut = trim($_POST['rut'] ?? '');
        $beneficio_id = intval($_POST['beneficio_id'] ?? 0);

        if (empty($rut) || $beneficio_id <= 0) {
            $_SESSION['flash_message'] = 'Datos incompletos para desasignar el beneficio.';
            $_SESSION['flash_type'] = 'error';
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM Beneficios_Persona WHERE Persona_rut = :rut AND Beneficio_id = :bid");
                $stmt->execute([':rut' => $rut, ':bid' => $beneficio_id]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = 'Beneficio desasignado correctamente.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'No se encontró la asignación.';
                    $_SESSION['flash_type'] = 'warning';
                }
            } catch (PDOException $e) {
                error_log("Error al desasignar beneficio: " . $e->getMessage());
                $_SESSION['flash_message'] = 'Error al desasignar el beneficio.';
                $_SESSION['flash_type'] = 'error';
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?rut=" . urlencode($rut));
        exit();
    }

    // Eliminar beneficio globalmente (de la tabla Beneficios)
    if ($action === 'delete_beneficio') {
        $beneficio_id = intval($_POST['beneficio_id'] ?? 0);

        if ($beneficio_id <= 0) {
            $_SESSION['flash_message'] = 'ID de beneficio inválido.';
            $_SESSION['flash_type'] = 'error';
        } else {
            try {
                // Verificar si está asignado a alguna persona (opcional, pero la FK puede bloquear)
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM Beneficios_Persona WHERE Beneficio_id = :bid");
                $stmtCheck->execute([':bid' => $beneficio_id]);
                $count = $stmtCheck->fetchColumn();

                if ($count > 0) {
                    // Si hay asignaciones, preguntar antes (en el front ya hay confirmación)
                    // Se eliminará en cascada si la FK tiene ON DELETE CASCADE, o dará error
                }

                $stmt = $db->prepare("DELETE FROM Beneficios WHERE id = :id");
                $stmt->execute([':id' => $beneficio_id]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = 'Beneficio eliminado correctamente.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'El beneficio no existe.';
                    $_SESSION['flash_type'] = 'warning';
                }
            } catch (PDOException $e) {
                error_log("Error al eliminar beneficio: " . $e->getMessage());
                // Si hay restricción de FK sin cascada, el error puede ser 23503
                if ($e->getCode() == 23503) {
                    $_SESSION['flash_message'] = 'No se puede eliminar el beneficio porque está asignado a personas. Primero desasigna todas sus ocurrencias.';
                } else {
                    $_SESSION['flash_message'] = 'Error al eliminar el beneficio.';
                }
                $_SESSION['flash_type'] = 'error';
            }
        }
        // Redirigir manteniendo el RUT si existe
        $redirect = $_SERVER['PHP_SELF'];
        if (!empty($_GET['rut'])) {
            $redirect .= '?rut=' . urlencode($_GET['rut']);
        }
        header("Location: $redirect");
        exit();
    }
}

// Recuperar mensaje flash
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Obtener todos los beneficios
$beneficios = [];
try {
    $stmt = $db->query("SELECT id, nombre, descripcion FROM Beneficios ORDER BY nombre");
    $beneficios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener beneficios: " . $e->getMessage());
}

// Procesar búsqueda por RUT
$beneficiosPersona = [];
$personaInfo = null;
$searchRut = isset($_GET['rut']) ? trim($_GET['rut']) : '';
$asignados = [];
if (!empty($searchRut)) {
    try {
        $stmtPersona = $db->prepare("SELECT nombre, apellido FROM Personas WHERE RUT = :rut");
        $stmtPersona->execute([':rut' => $searchRut]);
        $personaInfo = $stmtPersona->fetch();

        if ($personaInfo) {
            $stmtBen = $db->prepare("
                SELECT b.id, b.nombre, b.descripcion, bp.fecha_post
                FROM Beneficios_Persona bp
                JOIN Beneficios b ON bp.Beneficio_id = b.id
                WHERE bp.Persona_rut = :rut
                ORDER BY bp.fecha_post DESC
            ");
            $stmtBen->execute([':rut' => $searchRut]);
            $beneficiosPersona = $stmtBen->fetchAll();
            $asignados = array_column($beneficiosPersona, 'id');
        }
    } catch (PDOException $e) {
        error_log("Error en búsqueda por RUT: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficios</title>
    <link rel="stylesheet" href="css/beneficios.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
</head>
<body>
    <?php include 'components/sideBar.php'; ?>
    <div class="main-content">
        <?php include 'components/header.php'; ?>

        <!-- Mostrar mensajes flash -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>" id="flash-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Botón para abrir el modal de agregar beneficio -->
        <div class="action-bar">
            <button class="btn-agregar" id="btnAbrirModal">
                <i class="fas fa-plus"></i>
                Agregar Beneficio
            </button>
        </div>
<!-- Formulario de búsqueda por RUT -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <label for="rut">Buscar por RUT de persona:</label>
                <input type="text" id="rut" name="rut" placeholder="Ej: 12345678-9" value="<?php echo htmlspecialchars($searchRut); ?>" required>
                <button type="submit" class="btn-buscar"><i class="fas fa-search"></i> Buscar</button>
                <?php if (!empty($searchRut)): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-buscar" style="background-color: #6c757d;"><i class="fas fa-times"></i> Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Resultados de la búsqueda (beneficios asignados a la persona) -->
        <?php if (!empty($searchRut)): ?>
            <div class="search-results">
                <?php if ($personaInfo): ?>
                    <h3>
                        Beneficios de <?php echo htmlspecialchars($personaInfo['nombre'] . ' ' . $personaInfo['apellido']); ?> (RUT: <?php echo htmlspecialchars($searchRut); ?>)
                    </h3>
                    <?php if (count($beneficiosPersona) > 0): ?>
                        <div class="cards-container">
                            <?php foreach ($beneficiosPersona as $ben): ?>
                                <div class="card-beneficio">
                                    <i class="fas fa-gift card-icon"></i>
                                    <h4><?php echo htmlspecialchars($ben['nombre']); ?></h4>
                                    <p><?php echo htmlspecialchars($ben['descripcion']); ?></p>
                                    <small>Accedido el: <?php echo date('d/m/Y', strtotime($ben['fecha_post'])); ?></small>
                                    <div class="action-icons">
                                        <!-- Botón para desasignar (eliminar de la persona) -->
                                        <form method="POST" class="delete-form" onsubmit="return confirm('¿Desasignar este beneficio de la persona?');">
                                            <input type="hidden" name="action" value="remove_from_person">
                                            <input type="hidden" name="rut" value="<?php echo htmlspecialchars($searchRut); ?>">
                                            <input type="hidden" name="beneficio_id" value="<?php echo $ben['id']; ?>">
                                            <button type="submit" class="action-icon delete-icon" title="Desasignar beneficio"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Esta persona no tiene beneficios registrados.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="error">No se encontró ninguna persona con el RUT ingresado.</p>
                <?php endif; ?>
            </div>
            <hr>
        <?php endif; ?>
        
        <!-- Todos los beneficios disponibles -->
        <h2>Todos los beneficios</h2>
        <div class="card-ben cards-container">
            <?php if (count($beneficios) > 0): ?>
                <?php foreach ($beneficios as $ben): ?>
                    <div class="card-beneficio">
                        <i class="fas fa-user-plus card-icon"></i>
                        <h4><?php echo htmlspecialchars($ben['nombre']); ?></h4>
                        <p><?php echo htmlspecialchars($ben['descripcion']); ?></p>
                        <div class="action-icons">
                            <!-- Botón para eliminar beneficio globalmente -->
                            <form method="POST" class="delete-form" onsubmit="return confirm('¿Eliminar permanentemente este beneficio? También se eliminarán todas sus asignaciones.');">
                                <input type="hidden" name="action" value="delete_beneficio">
                                <input type="hidden" name="beneficio_id" value="<?php echo $ben['id']; ?>">
                                <button type="submit" class="action-icon delete-icon" title="Eliminar beneficio"><i class="fas fa-trash-alt"></i></button>
                            </form>
                            
                            <!-- Icono de asignación, visible solo si hay una persona seleccionada -->
                            <?php if (!empty($searchRut) && $personaInfo): 
                                $asignado = in_array($ben['id'], $asignados);
                            ?>
                                <?php if ($asignado): ?>
                                    <span class="action-icon assign-icon disabled" title="Beneficio ya asignado"><i class="fas fa-check"></i></span>
                                <?php else: ?>
                                    <form method="POST" class="assign-form" onsubmit="return confirm('¿Asignar este beneficio a <?php echo htmlspecialchars($personaInfo['nombre'] . ' ' . $personaInfo['apellido']); ?>?');">
                                        <input type="hidden" name="action" value="assign_beneficio">
                                        <input type="hidden" name="rut" value="<?php echo htmlspecialchars($searchRut); ?>">
                                        <input type="hidden" name="beneficio_id" value="<?php echo $ben['id']; ?>">
                                        <button type="submit" class="action-icon assign-icon" title="Asignar a esta persona"><i class="fas fa-plus"></i></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No hay beneficios cargados.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para agregar beneficio -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2>Agregar Nuevo Beneficio</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_beneficio">
                <div class="form-group">
                    <label for="nombre">Nombre del beneficio *</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <button type="submit" class="btn-submit">Guardar Beneficio</button>
            </form>
        </div>
    </div>

    <script>
        // Manejar el modal de agregar beneficio
        const modal = document.getElementById('modalAgregar');
        const btnAbrir = document.getElementById('btnAbrirModal');
        const btnCerrar = document.getElementById('closeModal');

        btnAbrir.onclick = function() {
            modal.style.display = 'block';
        }

        btnCerrar.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Ocultar mensajes flash después de 3 segundos
        const flashMessage = document.getElementById('flash-message');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.transition = 'opacity 0.5s ease';
                flashMessage.style.opacity = '0';
                setTimeout(() => {
                    flashMessage.remove(); // Elimina el mensaje del DOM
                }, 500);
            }, 3000);
        }
    </script>
</body>
</html>