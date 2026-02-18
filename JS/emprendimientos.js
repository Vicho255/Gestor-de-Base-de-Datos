document.addEventListener("DOMContentLoaded", function() {
    const emprendimientosTable = document.querySelector('.emprendimientos tbody');
    const exportModal = document.getElementById('exportModal');
    const exportBtn = document.getElementById('exportarBtn');

    let emprendimientosCache = []; // cache global

    function loadEmprendimientos() {
        console.log('Cargando emprendimientos...');
        
        fetch('api/get_emprendimiento.php')
            .then(res => res.json())
            .then(data => {
                console.log('✅ Datos recibidos correctamente:', data);
                
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }

                emprendimientosCache = data; // guardar datos
                renderEmprendimientos(data);
            })
            .catch(err => {
                console.error('Error:', err);
                emprendimientosTable.innerHTML = '<tr><td colspan="7">Error al cargar los datos</td></tr>';
            });
    }
    
    // Cargar emprendimientos al iniciar
    loadEmprendimientos();
    
    // Manejar modal de exportación
    if (exportBtn && exportModal) {
        const closeExportModal = document.getElementById('btnCerrarExportModal');
        
        // Abrir modal de exportación
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            abrirModalExportacion();
        });
        
        // Cerrar modal de exportación
        closeExportModal.addEventListener('click', cerrarModalExportacion);
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target === exportModal) {
                cerrarModalExportacion();
            }
        });
        
        // Cerrar con Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && exportModal.style.display === 'block') {
                cerrarModalExportacion();
            }
        });
        
        // Inicializar contenido del modal
        inicializarModalExportacion();
    }
    
    function abrirModalExportacion() {
        exportModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function cerrarModalExportacion() {
        exportModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function inicializarModalExportacion() {
        const modalBody = exportModal.querySelector('.modal-content .form-grid');
        
        // Limpiar contenido existente
        modalBody.innerHTML = '';
        
        // Agregar opciones de exportación
        modalBody.innerHTML = `
            <div class="form-group">
                <label for="ordenEmprendimientos">
                    <i class="fas fa-sort-amount-down"></i> Ordenar por:
                </label>
                <select id="ordenEmprendimientos" class="form-select">
                    <option value="nombre">Nombre</option>
                    <option value="categoria_nombre">Categoría</option>
                    <option value="duenno_nombre">Dueño</option>
                    <option value="activo">Estado</option>
                    <option value="created_at">Fecha de Creación</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="direccionEmprendimientos">
                    <i class="fas fa-sort"></i> Orden:
                </label>
                <select id="direccionEmprendimientos" class="form-select">
                    <option value="ASC">Ascendente (A-Z)</option>
                    <option value="DESC">Descendente (Z-A)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="estadoEmprendimientos">
                    <i class="fas fa-filter"></i> Filtrar por estado:
                </label>
                <select id="estadoEmprendimientos" class="form-select">
                    <option value="todos">Todos los estados</option>
                    <option value="activos">Solo activos</option>
                    <option value="inactivos">Solo inactivos</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="limiteEmprendimientos">
                    <i class="fas fa-list-ol"></i> Cantidad máxima:
                </label>
                <input type="number" id="limiteEmprendimientos" class="form-control" 
                       value="50" min="1" max="1000" step="1" 
                       style="width: 100%; text-align: center; padding: 10px;">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    Máximo 1000 registros
                </small>
            </div>
        `;
        
        // Agregar botón de exportar si no existe
        const modalFooter = exportModal.querySelector('.modal-content');
        if (!modalFooter.querySelector('.modal-footer')) {
            const footer = document.createElement('div');
            footer.className = 'modal-footer';
            footer.style.cssText = 'display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;';
            footer.innerHTML = `
                <button type="button" class="btn-secondary" onclick="cerrarModalExportacion()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-submit" onclick="exportarEmprendimientos()">
                    <i class="fas fa-download"></i> Generar PDF
                </button>
            `;
            modalFooter.appendChild(footer);
        }
    }
    
    // Función principal para exportar emprendimientos
    window.exportarEmprendimientos = function() {
        const modal = document.getElementById('exportModal');
        if (!modal) return;
        
        // Obtener valores del formulario
        const datos = {
            orden: document.getElementById('ordenEmprendimientos').value,
            direccion: document.getElementById('direccionEmprendimientos').value,
            estado: document.getElementById('estadoEmprendimientos').value,
            limite: document.getElementById('limiteEmprendimientos').value
        };
        
        // Validaciones
        const limite = parseInt(datos.limite);
        if (isNaN(limite) || limite <= 0) {
            alert('⚠️ Por favor ingrese un número válido para el límite (1-1000)');
            document.getElementById('limiteEmprendimientos').focus();
            return;
        }
        
        if (limite > 1000) {
            alert('⚠️ El límite máximo es de 1000 registros');
            document.getElementById('limiteEmprendimientos').value = 1000;
            return;
        }
        
        // Mostrar indicador de carga
        const exportBtn = modal.querySelector('.btn-submit');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        exportBtn.disabled = true;
        
        // Enviar solicitud
        fetch('exportar_emprendimientos_pdf.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'text/html'
            },
            body: JSON.stringify(datos)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`Error ${res.status}: ${res.statusText}`);
            }
            return res.text();
        })
        .then(html => {
            // Crear ventana emergente para el reporte
            const nuevaVentana = window.open('', '_blank');
            if (!nuevaVentana) {
                throw new Error('El navegador bloqueó la ventana emergente. Por favor, permite ventanas emergentes para este sitio.');
            }
            
            // Escribir contenido en la nueva ventana
            nuevaVentana.document.write(html);
            nuevaVentana.document.close();
            
            // Cerrar modal
            cerrarModalExportacion();
            
            // Mostrar mensaje de éxito
            alert('✅ Reporte generado exitosamente.');
        })
        .catch(error => {
            console.error('Error al exportar:', error);
            alert('❌ Error al generar el reporte:\n' + error.message);
        })
        .finally(() => {
            // Restaurar botón
            if (exportBtn) {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            }
        });
    };
    
    //function loadEmprendimientos() {
    //    console.log('Cargando emprendimientos...');
    //    
    //    fetch('api/get_emprendimiento.php')
    //        .then(res => res.json())
    //        .then(data => {
    //            console.log('✅ Datos recibidos correctamente:', data);
    //            
    //            if (data.error) {
    //                alert('Error: ' + data.error);
    //                return;
    //            }
    //            
    //            renderEmprendimientos(data);
    //        })
    //        .catch(err => {
    //            console.error('Error:', err);
    //            emprendimientosTable.innerHTML = '<tr><td colspan="7">Error al cargar los datos</td></tr>';
    //        });
    //}
    
    function renderEmprendimientos(emprendimientos) {
        let html = '';
        
        if (!Array.isArray(emprendimientos) || emprendimientos.length === 0) {
            html = '<tr><td colspan="7" class="no-data">No hay emprendimientos registrados</td></tr>';
        } else {
            emprendimientos.forEach(emp => {
                let descripcionCorta = emp.descripcion || '';
                if (descripcionCorta.length > 100) {
                    descripcionCorta = descripcionCorta.substring(0, 100) + '...';
                }
                
                const estado = emp.activo === true ? 
                    '<span class="badge-activo">Activo</span>' :
                    '<span class="badge-inactivo">Inactivo</span>';
                
                const acciones = emp.activo === true ? 
                    `<button class="btn-eliminar" onclick="desactivarEmprendimiento(${emp.id})" title="Desactivar">
                        <i class="fas fa-ban"></i>
                    </button>` :
                    `<button class="btn-activar" onclick="activarEmprendimiento(${emp.id})" title="Activar">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn-eliminar" onclick="eliminarEmprendimiento(${emp.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>`;
                
                html += `
                    <tr>
                        <td>${emp.id || ''}</td>
                        <td>${emp.nombre || ''}</td>
                        <td><strong>${emp.categoria_nombre || 'Sin categoría'}</strong></td>
                        <td title="${emp.descripcion || ''}">${descripcionCorta}</td>
                        <td><strong>${emp.duenno_nombre || 'Sin dueño'}</strong></td>
                        <td>${estado}</td>
                        <td>
                            ${acciones}
                            <button class="btn-ver" onclick="verDetallesEmprendimiento(${emp.id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        }
        
        emprendimientosTable.innerHTML = html;
        console.log('✅ Tabla renderizada correctamente');
    }
    // Función para exportar PDF de Personas con Emprendimientos
    function initExportacionPersonaEmprendimiento() {
        const exportModalPE = document.getElementById('exportModalPE');
        const exportBtnPE = document.getElementById('exportarBtnPE');
        const closeModalBtnsPE = exportModalPE?.querySelectorAll('[data-bs-dismiss="modal"]');

        // Crear modal si no existe
        if (!exportModalPE && exportBtnPE) {
            createExportModalPE();
            bindModalEventsPE();
        }

        // Mostrar modal de exportación
        if (exportBtnPE) {
            exportBtnPE.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = document.getElementById('exportModalPE');
                if (modal) {
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                } else {
                    console.error('Modal no encontrado');
                }
            });
        }

        function bindModalEventsPE() {
            const modal = document.getElementById('exportModalPE');
            const closeBtns = modal?.querySelectorAll('[data-bs-dismiss="modal"]');
            
            if (closeBtns) {
                closeBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    });
                });
            }

            // Cerrar modal al hacer clic fuera
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }

        function createExportModalPE() {
            const modalHTML = `
            <div id="exportModalPE" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
                <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px; max-width: 90%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Exportar Personas con Emprendimientos</h3>
                        <span class="close-modal" data-bs-dismiss="modal" style="cursor: pointer; font-size: 24px; color: #aaa;">&times;</span>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="ordenPE">Ordenar por:</label>
                        <select id="ordenPE" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="p.nombre">Nombre</option>
                            <option value="p.apellido">Apellido</option>
                            <option value="p.RUT">RUT</option>
                            <option value="total_emprendimientos">Cantidad de Emprendimientos</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="direccionPE">Dirección:</label>
                        <select id="direccionPE" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="ASC">Ascendente (A-Z)</option>
                            <option value="DESC">Descendente (Z-A)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="estadoPE">Filtrar por estado:</label>
                        <select id="estadoPE" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="todos">Todos los estados</option>
                            <option value="activos">Solo emprendimientos activos</option>
                            <option value="inactivos">Solo emprendimientos inactivos</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="limitePE">Límite de registros:</label>
                        <input type="number" id="limitePE" class="form-control" 
                            style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"
                            min="1" max="100" value="20">
                        <small style="color: #666;">Máximo 100 registros</small>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                                style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportarPDFPersonaEmprendimiento()"
                                style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-file-pdf"></i> Generar Reporte
                        </button>
                    </div>
                </div>
            </div>`;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    }

    // Función principal de exportación
    window.exportarPDFPersonaEmprendimiento = function() {
        const datos = {
            orden: document.getElementById('ordenPE')?.value || 'p.nombre',
            direccion: document.getElementById('direccionPE')?.value || 'ASC',
            limite: document.getElementById('limitePE')?.value || 20,
            estado: document.getElementById('estadoPE')?.value || 'todos'
        };

        // Validar límite
        const limite = parseInt(datos.limite);
        if (isNaN(limite) || limite <= 0 || limite > 100) {
            alert('Por favor ingrese un número válido para el límite (1-100)');
            return;
        }

        // Mostrar loading
        const exportBtn = document.querySelector('#exportModalPE .btn-success');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        exportBtn.disabled = true;

        // Llamar al endpoint de exportación combinada
        fetch('exportar_persona_emprendimiento_pdf.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'text/html'
            },
            body: JSON.stringify(datos)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`Error ${res.status}: ${res.statusText}`);
            }
            return res.text();
        })
        .then(html => {
            // Crear una ventana nueva con el reporte
            const nuevaVentana = window.open('', '_blank');
            if (!nuevaVentana) {
                throw new Error('El navegador bloqueó la ventana emergente. Permite popups para este sitio.');
            }
            
            nuevaVentana.document.write(html);
            nuevaVentana.document.close();
            
            // Cerrar modal
            const modal = document.getElementById('exportModalPE');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Restaurar botón
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar:', error);
            alert('Error al generar el reporte: ' + error.message);
            
            // Restaurar botón
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        });
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExportacionPersonaEmprendimiento);
    } else {
        initExportacionPersonaEmprendimiento();
    }

    initExportacionPersonaEmprendimiento();
    // ============================================
// FUNCIONES PARA ACCIONES DE LA TABLA
// ============================================

/**
 * Desactivar emprendimiento (cambia estado a inactivo)
 */
window.desactivarEmprendimiento = function(id) {
    if (!confirm('¿Está seguro de desactivar este emprendimiento?')) return;
    
    fetch('api/desactivar_emprendimiento.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            loadEmprendimientos();
        } else {
            alert('❌ Error: ' + (data.error || data.message));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Error de conexión');
    });
};

/**
 * Activar emprendimiento (cambia estado a activo)
 */
window.activarEmprendimiento = function(id) {
    if (!confirm('¿Está seguro de activar este emprendimiento?')) return;
    
    fetch('api/activar_emprendimiento.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            loadEmprendimientos();
        } else {
            alert('❌ Error: ' + (data.error || data.message));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Error de conexión');
    });
};

/**
 * Eliminar emprendimiento permanentemente (solo si está inactivo)
 */
window.eliminarEmprendimiento = function(id) {
    if (!confirm('⚠️ ¿Está seguro de ELIMINAR PERMANENTEMENTE este emprendimiento?\nEsta acción no se puede deshacer.')) return;
    
    fetch('api/delete_emprendimiento.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            loadEmprendimientos();
        } else {
            alert('❌ Error: ' + (data.error || data.message));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Error de conexión');
    });
};

/**
 * Ver detalles del emprendimiento (abre modal con información completa)
 */
window.verDetallesEmprendimiento = function(id) {
    // Aquí puedes abrir un modal de detalles o redirigir a una página de detalle
    // Por ahora, simplemente mostraremos un alert con los datos (ejemplo)
    fetch('api/get_emprendimiento_detalle.php?id=' + id)
        .then(res => res.json())
        .then(emp => {
            // Construir mensaje con todos los campos
            let detalles = `📋 EMPRENDIMIENTO\n`;
            detalles += `ID: ${emp.id}\n`;
            detalles += `Nombre: ${emp.nombre}\n`;
            detalles += `Categoría: ${emp.categoria_nombre}\n`;
            detalles += `Dueño: ${emp.duenno_nombre} (rut: ${emp.duenno_rut})\n`;
            detalles += `Descripción: ${emp.descripcion || 'Sin descripción'}\n`;
            detalles += `Estado: ${emp.activo ? 'Activo' : 'Inactivo'}\n`;
            alert(detalles);
            
            // Si prefieres abrir un modal personalizado, aquí puedes mostrar el div oculto
        })
        .catch(err => {
            console.error('Error al cargar detalles:', err);
            alert('❌ No se pudieron cargar los detalles');
        });
};

// ============================================
// FUNCIONES PARA GUARDAR EMPRENDIMIENTO (modal)
// ============================================

/**
 * Función global para guardar emprendimiento desde el formulario
 * (llamada desde el script inline en emprendimientos.php)
 */
window.guardarEmprendimientoDesdeJS = function(formData, callback) {
    fetch('api/add_emprendimiento.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(async res => {
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta no válida del servidor');
        }
        
        if (data.success) {
            alert('✅ ' + data.message);
            if (callback) callback(true);
            loadEmprendimientos(); // recargar tabla
        } else {
            alert('❌ Error: ' + (data.error || data.message));
            if (callback) callback(false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión: ' + error.message);
        if (callback) callback(false);
    });
};

/**
 * Muestra una lista de personas disponibles para asignar como dueños
 */
window.mostrarPersonasDisponibles = function() {
    // Crear modal flotante con la lista de personas
    const modalLista = document.createElement('div');
    modalLista.className = 'modal';
    modalLista.style.display = 'block';
    modalLista.id = 'modalListaPersonas';
    
    fetch('api/buscar_personas_emprendimiento.php')
        .then(res => res.json())
        .then(personas => {
            let html = `
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2>Seleccionar Dueño</h2>
                        <button class="close-modal" onclick="cerrarModalLista()">&times;</button>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; text-align: left;">RUT</th>
                                    <th style="padding: 10px; text-align: left;">Nombre</th>
                                    <th style="padding: 10px; text-align: left;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            personas.forEach(p => {
                html += `
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">${p.value}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">${p.label}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                            <button onclick="seleccionarRut('${p.value}')" class="btn-submit" style="padding: 5px 10px;">
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            modalLista.innerHTML = html;
            document.body.appendChild(modalLista);
            document.body.style.overflow = 'hidden';
        })
        .catch(err => {
            console.error('Error cargando personas:', err);
            alert('❌ No se pudieron cargar las personas');
        });
};

// Función auxiliar para cerrar el modal de lista
window.cerrarModalLista = function() {
    const modal = document.getElementById('modalListaPersonas');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
};

// Función auxiliar para seleccionar un RUT de la lista
window.seleccionarRut = function(rut) {
    document.getElementById('duenno_rut').value = rut;
    cerrarModalLista();
};

// ============================================
// AUTOFORMATO DE RUT (mejora)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const rutInput = document.getElementById('duenno_rut');
    if (rutInput) {
        rutInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\dkK]/g, '').toUpperCase();
            if (value.length > 1) {
                let rut = value.slice(0, -1);
                let dv = value.slice(-1);
                // Formatear como 12345678-9
                e.target.value = rut + '-' + dv;
            }
        });
    }
});
// ===============================
// BUSCADOR DE EMPRENDIMIENTOS
// ===============================
const searchInput = document.getElementById('searchInput');
const btnBuscar = document.getElementById('btnBuscar');

if (btnBuscar && searchInput) {

    btnBuscar.addEventListener('click', buscarEmprendimientos);

    // Buscar al presionar Enter
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            buscarEmprendimientos();
        }
    });
}

function buscarEmprendimientos() {
    const texto = searchInput.value.toLowerCase().trim();

    if (texto === '') {
        renderEmprendimientos(emprendimientosCache);
        return;
    }

    const filtrados = emprendimientosCache.filter(emp => {
        return (
            (emp.nombre && emp.nombre.toLowerCase().includes(texto)) ||
            (emp.duenno_nombre && emp.duenno_nombre.toLowerCase().includes(texto)) ||
            (emp.duenno_rut && emp.duenno_rut.toLowerCase().includes(texto)) ||
            (emp.categoria_nombre && emp.categoria_nombre.toLowerCase().includes(texto))
        );
    });

    renderEmprendimientos(filtrados);
}

});