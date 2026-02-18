<?php
// usuarios.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['username'] ?? 'Usuario';

$pageTitle = "Gestion de Usuarios";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!--<link rel="stylesheet" href="styles.css"> -->
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php include 'components/sideBar.php'; ?>

    <div class="main-content">
        <?php include 'components/header.php'; ?>

        <div class="table-container">
            <div class="table-header">
                <button class="btn-agregar" id="btnAgregarUsuario">
                    <i class="fas fa-plus"></i> Agregar Usuario
                </button>
            </div>
            <table class="usuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Creado</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan = "7">Cargando datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal de Edicion -->

        <div class="modal" id="editModal" style="display: none;">
            <div class="modal-content">
                <button class="close-modal" id="btnCerrarModal">&times;</button>

                <div class="modal-header">
                    <h3>Editar Usuario</h3>
                </div>

                <form id="usuarioForm">
                    <input type="hidden" id="usuarioId">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Nombre de Usuario:</label>
                            <input type="text" id="username" required>
                        </div>
                        <div class="form-group">
                            <label for="rol">Rol:</label>
                            <select id="rol" required>
                                <option value="user">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password">Nueva Contraseña (dejar en blanco si no desea cambiarla):</label>
                            <input type="password" id="password" placeholder="Ingrese nueva contraseña">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-submit" id="btnCancelar" 
                                style="background-color: #6c757d; margin-right: 10px;">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal de Agregar Usuario -->
        <div class="modal" id="addModal" style="display: none;">
            <div class="modal-content">
                <button class="close-modal" id="btnCerrarAddModal">&times;</button>

                <div class="modal-header">
                    <h3>Nuevo Usuario</h3>
                </div>

                <form id="addUsuarioForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="addUsername">Nombre de Usuario:</label>
                            <input type="text" id="addUsername" required>
                        </div>

                        <div class="form-group">
                            <label for="addPassword">Contraseña:</label>
                            <input type="password" id="addPassword" required>
                        </div>

                        <div class="form-group">
                            <label for="addRol">Rol:</label>
                            <select id="addRol" required>
                                <option value="user">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-submit" id="btnCancelarAdd" 
                                style="background-color: #6c757d; margin-right: 10px;">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="btnGuardarAdd">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="JS/usuarios.js"></script>
</body>