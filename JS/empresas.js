document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const modal = document.getElementById('modalEmprendimiento');
    const btnAbrirModal = document.getElementById('btnAbrirModal');
    const btnCerrarModal = document.getElementById('btnCerrarModal');
    const btnCancelar = document.getElementById('btnCancelar');
    const btnGuardar = document.getElementById('btnGuardar');
    const empresaForm = document.getElementById('empresaForm');
    const categoriaSelect = document.getElementById('categoria_id');
    const btnBuscar = document.getElementById('btnBuscar');
    const searchInput = document.getElementById('searchInput');
    const exportarBtn = document.getElementById('exportarBtn');
    const duennoRutInput = document.getElementById('duenno_rut');

    // Variables globales
    let empresasData = [];
    let isEditMode = false;
    let empresaEditId = null;
    let currentPage = 1;
    const itemsPerPage = 10;

    // Formatear RUT
    function formatearRUT(rut) {
        if (!rut) return '';
        
        // Limpiar caracteres no numéricos excepto 'k' y 'K'
        let cleanRut = rut.replace(/[^0-9kK]/g, '');
        
        if (cleanRut.length <= 1) return cleanRut;
        
        // Separar cuerpo y dígito verificador
        let cuerpo = cleanRut.slice(0, -1);
        let dv = cleanRut.slice(-1).toUpperCase();
        
        // Formatear con puntos
        cuerpo = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        
        return cuerpo + '-' + dv;
    }

    // Formatear RUT mientras se escribe
    duennoRutInput.addEventListener('input', function(e) {
        let cursorPosition = e.target.selectionStart;
        let originalLength = this.value.length;
        
        this.value = formatearRUT(this.value);
        
        // Mantener cursor en posición correcta
        let newLength = this.value.length;
        let lengthDifference = newLength - originalLength;
        let newCursorPosition = cursorPosition + lengthDifference;
        
        this.setSelectionRange(newCursorPosition, newCursorPosition);
    });

    // Validar RUT
    function validarRUT(rut) {
        if (!rut) return false;
        
        // Limpiar y convertir a mayúsculas
        rut = rut.replace(/[^0-9kK]/g, '').toUpperCase();
        
        if (rut.length < 8) return false;
        
        let cuerpo = rut.slice(0, -1);
        let dv = rut.slice(-1);
        
        // Calcular DV esperado
        let suma = 0;
        let multiplo = 2;
        
        for (let i = 1; i <= cuerpo.length; i++) {
            let index = multiplo * parseInt(corteza[cuerpo.length - i]);
            suma += index;
            
            if (multiplo < 7) {
                multiplo += 1;
            } else {
                multiplo = 2;
            }
        }
        
        let dvEsperado = 11 - (suma % 11);
        
        if (dvEsperado === 11) dvEsperado = '0';
        if (dvEsperado === 10) dvEsperado = 'K';
        
        return dvEsperado.toString() === dv;
    }

    // Cargar categorías en el select
    async function cargarCategorias() {
        try {
            const response = await fetch('api/get_categorias.php');
            const categorias = await response.json();
            
            categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
            
            categorias.forEach(categoria => {
                const option = document.createElement('option');
                option.value = categoria.id;
                option.textContent = categoria.nombre;
                categoriaSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error al cargar categorías:', error);
            categoriaSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
        }
    }

    // Cargar empresas desde la API
    async function cargarEmpresas(busqueda = '') {
        try {
            let url = 'api/get_empresas.php';
            if (busqueda) {
                url += `?search=${encodeURIComponent(busqueda)}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            empresasData = data;
            mostrarEmpresas();
        } catch (error) {
            console.error('Error al cargar empresas:', error);
            mostrarError('Error al cargar los datos de empresas');
        }
    }

    // Mostrar empresas en la tabla
    function mostrarEmpresas() {
        const tbody = document.querySelector('.empresas tbody');
        
        if (!empresasData.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="no-data">
                        No hay empresas registradas
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        
        empresasData.forEach(empresa => {
            html += `
                <tr data-id="${empresa.id}">
                    <td>${empresa.id}</td>
                    <td>${empresa.nombre}</td>
                    <td>${empresa.categoria_nombre}</td>
                    <td>${empresa.descripcion ? empresa.descripcion.substring(0, 50) + (empresa.descripcion.length > 50 ? '...' : '') : ''}</td>
                    <td>${empresa.duenno_nombre}</td>
                    <td>
                        <button class="btn-eliminar" onclick="eliminarEmpresa(${empresa.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }

    window.mostrarPersonasDisponibles = function() {
    // Crear modal flotante con la lista de personas
    const modalLista = document.createElement('div');
    modalLista.className = 'modal';
    modalLista.style.display = 'block';
    modalLista.id = 'modalListaPersonas';
    
    fetch('api/buscar_personas_empresa.php')
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

window.addEventListener('click', (e) => {
    const modal = document.getElementById('modalListaPersonas');
    if (e.target === modal) {
        cerrarModalLista();
    }
});

// Función auxiliar para seleccionar un RUT de la lista
window.seleccionarRut = function(rut) {
    document.getElementById('duenno_rut').value = rut;
    cerrarModalLista();
};
    // Mostrar mensaje de error
    function mostrarError(mensaje) {
        const tbody = document.querySelector('.empresas tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="color: #dc3545; text-align: center; padding: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> ${mensaje}
                </td>
            </tr>
        `;
    }

    // Limpiar formulario
    function limpiarFormulario() {
        empresaForm.reset();
        isEditMode = false;
        empresaEditId = null;
        btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Empresa';
        document.querySelector('.modal-header h2').textContent = 'Agregar Nueva Empresa';
    }

    // Abrir modal
    function abrirModal() {
        limpiarFormulario();
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // Cerrar modal
    function cerrarModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Guardar empresa
    async function guardarEmpresa(e) {
        e.preventDefault();
        
        // Validaciones básicas
        if (!empresaForm.nombre.value.trim()) {
            alert('Por favor ingrese el nombre de la empresa');
            return;
        }
        
        if (!empresaForm.categoria_id.value) {
            alert('Por favor seleccione una categoría');
            return;
        }
        
        if (!empresaForm.duenno_rut.value.trim()) {
            alert('Por favor ingrese el RUT del dueño');
            return;
        }
        
        // Validar RUT
        if (!validarRUT(empresaForm.duenno_rut.value)) {
            alert('El RUT ingresado no es válido');
            return;
        }
        
        // Preparar datos
        const formData = new FormData(empresaForm);
        const datos = Object.fromEntries(formData.entries());
        
        // Limpiar RUT para guardar
        datos.duenno_rut = datos.duenno_rut.replace(/[^0-9kK]/g, '').toUpperCase();
        
        // Determinar URL y método
        let url = 'api/guardar_empresa.php';
        let method = 'POST';
        
        if (isEditMode) {
            datos.id = empresaEditId;
            method = 'PUT';
        }
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                alert(resultado.message);
                cerrarModal();
                cargarEmpresas();
            } else {
                alert('Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error al guardar empresa:', error);
            alert('Error al guardar la empresa. Por favor intente nuevamente.');
        }
    }

    // Editar empresa
    window.editarEmpresa = async function(id) {
        try {
            const response = await fetch(`api/get_empresa.php?id=${id}`);
            const empresa = await response.json();
            
            if (empresa.error) {
                throw new Error(empresa.error);
            }
            
            // Llenar formulario
            document.getElementById('nombre').value = empresa.nombre || '';
            document.getElementById('descripcion').value = empresa.descripcion || '';
            document.getElementById('categoria_id').value = empresa.categoria_id || '';
            
            // Formatear RUT para mostrar
            const rutFormateado = formatearRUT(empresa.duenno_rut);
            document.getElementById('duenno_rut').value = rutFormateado;
            
            // Cambiar a modo edición
            isEditMode = true;
            empresaEditId = empresa.id;
            btnGuardar.innerHTML = '<i class="fas fa-save"></i> Actualizar Empresa';
            document.querySelector('.modal-header h2').textContent = 'Editar Empresa';
            
            // Abrir modal
            abrirModal();
            
        } catch (error) {
            console.error('Error al cargar empresa para editar:', error);
            alert('Error al cargar los datos de la empresa');
        }
    }

    // Eliminar empresa
    window.eliminarEmpresa = async function(id) {
        if (!confirm('¿Está seguro de que desea eliminar esta empresa?')) {
            return;
        }
        
        try {
            const response = await fetch(`api/eliminar_empresa.php`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                alert(resultado.message);
                cargarEmpresas();
            } else {
                alert('Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error al eliminar empresa:', error);
            alert('Error al eliminar la empresa');
        }
    }


    // Event Listeners
    btnAbrirModal.addEventListener('click', abrirModal);
    btnCerrarModal.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    empresaForm.addEventListener('submit', guardarEmpresa);
    
    btnBuscar.addEventListener('click', () => {
        cargarEmpresas(searchInput.value);
    });
    
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            cargarEmpresas(searchInput.value);
        }
    });

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    });

        // Manejar modal de exportación
    if (exportarBtn && exportModal) {
        const closeExportModal = document.getElementById('btnCerrarExportModal');
        
        // Abrir modal de exportación
        exportarBtn.addEventListener('click', function(e) {
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
                <label for="ordenEmpresas">
                    <i class="fas fa-sort-amount-down"></i> Ordenar por:
                </label>
                <select id="ordenEmpresas" class="form-select">
                    <option value="nombre">Nombre</option>
                    <option value="categoria_nombre">Categoría</option>
                    <option value="duenno_nombre">Dueño</option>
                    <option value="created_at">Fecha de Creación</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="direccionEmpresas">
                    <i class="fas fa-sort"></i> Orden:
                </label>
                <select id="direccionEmpresas" class="form-select">
                    <option value="ASC">Ascendente (A-Z)</option>
                    <option value="DESC">Descendente (Z-A)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="limiteEmpresas">
                    <i class="fas fa-list-ol"></i> Cantidad máxima:
                </label>
                <input type="number" id="limiteEmpresas" class="form-control" 
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
                <button class="btn-submit" onclick="exportarEmpresas()">
                    <i class="fas fa-download"></i> Generar PDF
                </button>
            `;
            modalFooter.appendChild(footer);
        }
    }
    
    // Función principal para exportar emprendimientos
    window.exportarEmpresas = function() {
        const modal = document.getElementById('exportModal');
        if (!modal) return;
        
        // Obtener valores del formulario
        const datos = {
            orden: document.getElementById('ordenEmpresas').value,
            direccion: document.getElementById('direccionEmpresas').value,
            limite: document.getElementById('limiteEmpresas').value
        };
        
        // Validaciones
        const limite = parseInt(datos.limite);
        if (isNaN(limite) || limite <= 0) {
            alert('⚠️ Por favor ingrese un número válido para el límite (1-1000)');
            document.getElementById('limiteEmpresas').focus();
            return;
        }
        
        if (limite > 1000) {
            alert('⚠️ El límite máximo es de 1000 registros');
            document.getElementById('limiteEmpresas').value = 1000;
            return;
        }
        
        // Mostrar indicador de carga
        const exportarBtn = modal.querySelector('.btn-submit');
        const originalText = exportarBtn.innerHTML;
        exportarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        exportarBtn.disabled = true;
        
        // Enviar solicitud
        fetch('exportar_empresas_pdf.php', {
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
            if (exportarBtn) {
                exportarBtn.innerHTML = originalText;
                exportarBtn.disabled = false;
            }
        });
    };
    // Función para exportar PDF de Personas con Empresas
function initExportacionPersonaEmpresa() {
    const exportModalPEM = document.getElementById('exportModalPEM');
    const exportBtnPEM = document.getElementById('exportarBtnPEM');
    const closeModalBtnsPEM = exportModalPEM?.querySelectorAll('[data-bs-dismiss="modal"]');

    // Crear modal si no existe
    if (!exportModalPEM && exportBtnPEM) {
        createExportModalPEM();
        bindModalEventsPEM();
    }

    // Mostrar modal de exportación
    if (exportBtnPEM) {
        exportBtnPEM.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = document.getElementById('exportModalPEM');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                console.error('Modal no encontrado');
            }
        });
    }

    function bindModalEventsPEM() {
        const modal = document.getElementById('exportModalPEM');
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

    function createExportModalPEM() {
        const modalHTML = `
        <div id="exportModalPEM" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px; max-width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">Exportar Personas con Empresas</h3>
                    <span class="close-modal" data-bs-dismiss="modal" style="cursor: pointer; font-size: 24px; color: #aaa;">&times;</span>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="ordenPEM">Ordenar por:</label>
                    <select id="ordenPEM" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="p.nombre">Nombre</option>
                        <option value="p.apellido">Apellido</option>
                        <option value="p.RUT">RUT</option>
                        <option value="total_empresas">Cantidad de Empresas</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="direccionPEM">Dirección:</label>
                    <select id="direccionPEM" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="ASC">Ascendente (A-Z)</option>
                        <option value="DESC">Descendente (Z-A)</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="limitePEM">Límite de registros:</label>
                    <input type="number" id="limitePEM" class="form-control" 
                           style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"
                           min="1" max="100" value="20">
                    <small style="color: #666;">Máximo 100 registros</small>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                            style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarPDFPersonaEmpresa()"
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
window.exportarPDFPersonaEmpresa = function() {
    const datos = {
        orden: document.getElementById('ordenPEM')?.value || 'p.nombre',
        direccion: document.getElementById('direccionPEM')?.value || 'ASC',
        limite: document.getElementById('limitePEM')?.value || 20
    };

    // Validar límite
    const limite = parseInt(datos.limite);
    if (isNaN(limite) || limite <= 0 || limite > 100) {
        alert('Por favor ingrese un número válido para el límite (1-100)');
        return;
    }

    // Mostrar loading
    const exportBtn = document.querySelector('#exportModalPEM .btn-success');
    const originalText = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    exportBtn.disabled = true;

    // Llamar al endpoint de exportación combinada
    fetch('exportar_persona_empresa_pdf.php', {
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
        const modal = document.getElementById('exportModalPEM');
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
    document.addEventListener('DOMContentLoaded', initExportacionPersonaEmpresa);
} else {
    initExportacionPersonaEmpresa();
}

    // Inicializar
    cargarCategorias();
    cargarEmpresas();
});