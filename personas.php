 <?php
// personas.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['username'] ?? 'Usuario';

$pageTitle = "Gestion de Personas";

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
        <?php include 'components/header.php'; ?>
        <div>
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
                    <h2 id="modalTitle">Agregar Nueva Persona</h2>
                </div>
                
                <form id="personaForm">
                    <div class="form-grid">
                        <input type="hidden" id="editMode" name="editMode" value="0">
                        <input type="hidden" id="originalRut" name="originalRut" value="">
                        <div class="form-group">
                            <label for="rut">RUT:</label>
                            <input type="text" id="rut" name="rut" required 
                                placeholder="12.345.678-9" 
                                pattern="^\d{1,2}(?:\.?\d{3}){2}-[\dkK]$"
                                title="Formato: 12.345.678-9 o 1.234.567-8">
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
                            <input type="date" id="fecha_nac" name="fecha_nac" 
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
                        <div class="form-group">
                            <label for="ciudad">Ciudad:</label>
                            <input type="text" id="ciudad" name="ciudad" placeholder="Ciudad">
                        </div>
                        <div class="form-group">
                            <label for="comuna">Comuna:</label>
                            <input type="text" id="comuna" name="comuna" placeholder="Comuna">
                        </div>
                        <div class="form-group">
                            <label for="calle">Calle:</label>
                            <input type="text" id="calle" name="calle" placeholder="Calle">
                        </div>
                        <div class="form-group">
                            <label for="numero">Número:</label>
                            <input type="text" id="numero" name="numero" placeholder="Número">
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
    window.formatearRUT = function(rutSinFormato) {
        if (!rutSinFormato) return '';
        let rutLimpio = rutSinFormato.replace(/[^0-9kK]/g, '').toUpperCase();
        if (rutLimpio.length <= 1) return rutLimpio;
        let cuerpo = rutLimpio.slice(0, -1);
        let dv = rutLimpio.slice(-1);
        let cuerpoFormateado = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return cuerpoFormateado + '-' + dv;
    };

    window.limpiarRUT = function(rut) {
        return rut.replace(/\./g, '').replace('-', '');
    };
    console.log('Script de formateo RUT cargado');
    console.log('Elemento RUT encontrado:', document.getElementById('rut'));

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
        
        function cerrarModal() {
            const modal = document.getElementById('modalPersona');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Resetear formulario
            document.getElementById('personaForm').reset();
            
            // Restaurar modo agregar
            document.getElementById('editMode').value = '0';
            document.getElementById('originalRut').value = '';
            document.getElementById('ciudad').value = '';
            document.getElementById('comuna').value = '';
            document.getElementById('calle').value = '';
            document.getElementById('numero').value = '';
            document.getElementById('modalTitle').innerText = 'Agregar Nueva Persona';
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save"></i> Guardar Persona';
            document.getElementById('rut').disabled = false;
            editando = false;
        }

        btnCerrar.addEventListener('click', cerrarModal);
        btnCancelar.addEventListener('click', cerrarModal);
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                cerrarModal();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                cerrarModal();
            }
        });
        
        // Configurar fecha máxima como hoy
        document.getElementById('fecha_nac').max = new Date().toISOString().split('T')[0];

        // ========== FORMATEADOR DE RUT (CORREGIDO) ==========
        const rutInput = document.getElementById('rut');

        function formatRutInput(input) {
            let value = input.value;
            // Limpiar todo excepto números y K
            let clean = value.replace(/[^0-9kK]/g, '').toUpperCase();
            
            if (clean.length === 0) {
                input.value = '';
                return;
            }
            
            // Separar cuerpo y dígito verificador
            let cuerpo = clean.slice(0, -1);
            let dv = clean.slice(-1);
            
            // Formatear cuerpo con puntos cada 3 dígitos desde la derecha
            let cuerpoFormateado = '';
            if (cuerpo.length > 0) {
                // Invertir, agregar puntos cada 3 caracteres, revertir
                cuerpoFormateado = cuerpo.split('').reverse().join('')
                    .replace(/(\d{3})/g, '$1.')
                    .split('').reverse().join('')
                    .replace(/^\./, ''); // eliminar punto al inicio si existe
            }
            
            // Construir RUT formateado
            let rutFormateado = cuerpoFormateado + '-' + dv;
            input.value = rutFormateado;
        }

        // Asignar eventos
        if (rutInput) {
            // Formatear mientras se escribe
            rutInput.addEventListener('input', function(e) {
                formatRutInput(e.target);
            });
            
            // Formatear al perder el foco (por si acaso)
            rutInput.addEventListener('blur', function(e) {
                formatRutInput(e.target);
            });
            
            // Al pegar, formatear después de un pequeño retraso
            rutInput.addEventListener('paste', function(e) {
                setTimeout(() => formatRutInput(e.target), 10);
            });
        }
        
        // ========== FUNCIÓN PARA GUARDAR PERSONA ==========
        // Función para guardar persona (se llamará desde personas.js)
        window.guardarPersona = function() {
            const rutInput = document.getElementById('rut');
            let rutValue = rutInput ? rutInput.value.trim() : '';
            
            const formData = {
                RUT: rutValue,
                nombre: document.getElementById('nombre').value.trim(),
                apellido: document.getElementById('apellido').value.trim(),
                fecha_nac: document.getElementById('fecha_nac').value,
                telefono: document.getElementById('telefono').value.trim() || '',
                correo: document.getElementById('correo').value.trim() || '',
                ciudad: document.getElementById('ciudad').value.trim() || '',
                comuna: document.getElementById('comuna').value.trim() || '',
                calle: document.getElementById('calle').value.trim() || '',
                numero: document.getElementById('numero').value.trim() || ''
            };
            
            console.log('Datos a enviar:', formData);
            
            // Validación básica
            if (!formData.RUT || !formData.nombre || !formData.apellido) {
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
    });
    </script>
    <script>
        document.getElementById('inportPersonas').addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(e.target);

        fetch('importar.php', {
        method: 'POST',
        body: formData
        })
        .then(res => res.text()) // 👈 CAMBIO CLAVE
        .then(data => {
        console.log(data);     // 👈 VE EL ERROR REAL
        })
        .catch(err => console.error(err));
        });
        const input = document.getElementById('archivo');
        const preview = document.getElementById('preview');

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();

            reader.onload = e => {
                const text = e.target.result;
                const filas = text.split(/\r?\n/).filter(f => f.trim() !== '');

                const table = document.createElement('table');
                table.border = 1;

                filas.forEach((fila, index) => {
                    const tr = document.createElement('tr');

                    const columnas = parseCSVLine(fila);

                    columnas.forEach(col => {
                        const cell = document.createElement(index === 0 ? 'th' : 'td');
                        cell.textContent = col;
                        tr.appendChild(cell);
                    });

                    table.appendChild(tr);
                });

                preview.innerHTML = '';
                preview.appendChild(table);
            };

            reader.readAsText(file);
        });

        function parseCSVLine(line) {
            const regex = /,(?=(?:(?:[^"]*"){2})*[^"]*$)/g;
            return line.split(regex).map(col =>
                col.replace(/^"|"$/g, '')
            );
};
    </script>
</body>
</html>