<?php
// personas.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personas</title>
    <link rel="stylesheet" href="css/personas.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
</head>
<body>
    <?php include 'components/sideBar.php'; ?>
    <div class="main-content">
        <div>
            <h1>Gestión de Personas</h1>
            <button class="btn-agregar" id="btnAbrirModal">
                <i class="fas fa-user-plus"></i>
                Agregar Nueva Persona
            </button>
        </div>

        <div class="table-container">
            <div class="table-header">
                <input type="text" id="searchInput" placeholder="Buscar por RUT, Nombre o Apellido...">
                <button id="btnBuscar"><i class="fas fa-search"></i></button>
                <button id="exportarBtn" data-bs-toggle="modal" data-bs-target="#modalExportar"><i class="fas fa-file-export"></i></button>
            </div>
            <table class="personas">
                <thead>
                    <tr>
                        <th>RUT</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Edad</th>
                        <th>Teléfono</th>
                        <th>Correo Electrónico</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los datos se cargarán aquí mediante JavaScript -->
                    <tr>
                        <td colspan="7">Cargando datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal para agregar persona -->
        <div id="modalPersona" class="modal">
            <div class="modal-content">
                <button class="close-modal" id="btnCerrarModal">&times;</button>
                
                <div class="modal-header">
                    <h2>Agregar Nueva Persona</h2>
                </div>
                
                <form id="personaForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="rut">RUT:</label>
                            <input type="text" id="rut" name="rut" required 
                                   placeholder="12345678-9" 
                                   pattern="^\d{7,8}-[\dkK]$"
                                   title="Formato: 12345678-9">
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="apellido">Apellido:</label>
                            <input type="text" id="apellido" name="apellido" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_nac">Fecha de Nacimiento:</label>
                            <input type="date" id="fecha_nac" name="fecha_nac" required 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono:</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   placeholder="+56912345678">
                        </div>
                        
                        <div class="form-group">
                            <label for="correo">Correo Electrónico:</label>
                            <input type="email" id="correo" name="correo" 
                                   placeholder="ejemplo@dominio.com">
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-submit" id="btnCancelar" 
                                style="background-color: #6c757d; margin-right: 10px;">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar Persona
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div id="exportModal" class="modal" style="display: none;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Exportar Reporte</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="orden">Ordenar por:</label>
                            <select id="orden" class="form-select">
                                <option value="nombre">Nombre</option>
                                <option value="apellido">Apellido</option>
                                <option value="rut">RUT</option>
                                <option value="edad">Edad</option>
                                <option value="fecha_nac">Fecha de Nacimiento</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="direccion">Orden:</label>
                            <select id="direccion" class="form-select">
                                <option value="ASC">Ascendente (A-Z)</option>
                                <option value="DESC">Descendente (Z-A)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="limite">Cantidad máxima de registros:</label>
                            <input type="number" id="limite" class="form-control" 
                                value="20" min="1" max="1000" step="1">
                            <small class="text-muted">Máximo 1000 registros</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal" 
                                style="background: #6c757d; color: white; margin-right: 10px;">
                            Cancelar
                        </button>
                        <button class="btn btn-success" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> Generar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/personas.js"></script>
        <script>
        // Script para manejar el modal
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalPersona');
            const btnAbrir = document.getElementById('btnAbrirModal');
            const btnCerrar = document.getElementById('btnCerrarModal');
            const btnCancelar = document.getElementById('btnCancelar');
            const btnGuardar = document.getElementById('btnGuardar');
            const personaForm = document.getElementById('personaForm');
            
            // Abrir modal
            btnAbrir.addEventListener('click', function() {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevenir scroll
            });
            
            // Cerrar modal (tres formas)
            btnCerrar.addEventListener('click', cerrarModal);
            btnCancelar.addEventListener('click', cerrarModal);
            
            // Cerrar modal al hacer clic fuera del contenido
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    cerrarModal();
                }
            });
            
            // Cerrar con Escape
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.style.display === 'block') {
                    cerrarModal();
                }
            });
            
            function cerrarModal() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Restaurar scroll
                personaForm.reset(); // Limpiar formulario
            }
            
            // Manejar envío del formulario
            personaForm.addEventListener('submit', function(event) {
                event.preventDefault();
                guardarPersona();
            });
            
            // Función para guardar persona (se llamará desde personas.js)
            window.guardarPersona = function() {
                const formData = {
                    RUT: document.getElementById('rut').value.trim(),
                    nombre: document.getElementById('nombre').value.trim(),
                    apellido: document.getElementById('apellido').value.trim(),
                    fecha_nac: document.getElementById('fecha_nac').value,
                    telefono: document.getElementById('telefono').value.trim() || '',
                    correo: document.getElementById('correo').value.trim() || ''
                };
                
                // Validación básica
                if (!formData.RUT || !formData.nombre || !formData.apellido || !formData.fecha_nac) {
                    alert('Por favor complete todos los campos obligatorios');
                    return;
                }
                
                // Mostrar loading en botón
                const originalText = btnGuardar.innerHTML;
                btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                btnGuardar.disabled = true;
                
                // Llamar a la función de guardado del archivo personas.js
                if (typeof guardarPersonaDesdeJS === 'function') {
                    guardarPersonaDesdeJS(formData, function(success) {
                        // Restaurar botón
                        btnGuardar.innerHTML = originalText;
                        btnGuardar.disabled = false;
                        
                        if (success) {
                            cerrarModal(); // Cerrar modal si fue exitoso
                        }
                    });
                } else {
                    console.error('La función guardarPersonaDesdeJS no está definida');
                    btnGuardar.innerHTML = originalText;
                    btnGuardar.disabled = false;
                }
            };
            
            // Configurar fecha máxima como hoy
            document.getElementById('fecha_nac').max = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>