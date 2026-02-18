document.addEventListener("DOMContentLoaded", function() {
    const personaForm = document.getElementById('personaForm');
    const personasTable = document.querySelector('.personas tbody');
    let allPersonas = [];          // Almacena todas las personas cargadas
    let editando = false;
    
    // Elementos de búsqueda
    const searchInput = document.getElementById('searchInput');
    const btnBuscar = document.getElementById('btnBuscar');
    
    // Cargar personas al iniciar
    loadPersonas();
    
    // Manejar envío del formulario
    personaForm.addEventListener('submit', function(event) {
        event.preventDefault();
        guardarPersona();
    });
    
    // Eventos de búsqueda
    if (btnBuscar) {
        btnBuscar.addEventListener('click', filterPersonas);
    }
    if (searchInput) {
        searchInput.addEventListener('input', filterPersonas); // búsqueda en tiempo real
        // También puedes usar solo el botón: commentar la línea de arriba y descomentar la siguiente
        // searchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') filterPersonas(); });
    }
    
    // Cargar personas desde el servidor
    function loadPersonas() {
        fetch('api/get_personas.php')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Error del servidor:', data.error);
                    return;
                }
                
                if (!Array.isArray(data)) {
                    console.error('Respuesta inválida:', data);
                    return;
                }
                
                allPersonas = data;   // Guardamos todas
                renderPersonas(allPersonas);
            })
            .catch(err => {
                console.error('Error al cargar personas:', err);
                personasTable.innerHTML = '<tr><td colspan="7">Error al cargar los datos</td></tr>';
            });
    }
    
    // Filtrar personas según el término de búsqueda
    function filterPersonas() {
        const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
        
        if (!searchTerm) {
            renderPersonas(allPersonas);
            return;
        }
        
        const filtered = allPersonas.filter(p => {
            // Limpiamos el RUT de puntos y guión para comparar solo números y dígito verificador
            const rutRaw = (p.rut || '').replace(/[.-]/g, '').toLowerCase();
            const nombre = (p.nombre || '').toLowerCase();
            const apellido = (p.apellido || '').toLowerCase();
            
            return rutRaw.includes(searchTerm) || 
                   nombre.includes(searchTerm) || 
                   apellido.includes(searchTerm);
        });
        
        renderPersonas(filtered);
    }
    
    // Renderizar la tabla con las personas (ya sea todas o filtradas)
    function renderPersonas(personas) {
        let html = '';
        
        if (personas.length === 0) {
            html = '<tr><td colspan="7">No hay personas registradas</td></tr>';
        } else {
            personas.forEach(p => {
                html += `
                    <tr>
                        <td>${p.rut}</td>
                        <td>${p.nombre}</td>
                        <td>${p.apellido}</td>
                        <td>${p.edad || '-'}</td>
                        <td>${p.telefono || '-'}</td>
                        <td>${p.correo || '-'}</td>
                        <td>
                            <button class="btn-editar" onclick="editarPersona('${p.rut}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-eliminar" onclick="eliminarPersona('${p.rut}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        }
        
        personasTable.innerHTML = html;
    }
    
    // Función para guardar persona (llamada desde el modal)
    window.guardarPersonaDesdeJS = function(formData, callback) {
        const editMode = document.getElementById('editMode').value === '1';
        const url = editMode ? 'api/update_persona.php' : 'api/add_persona.php';

        console.log(editMode ? 'Actualizando persona:' : 'Guardando persona:', formData);
        
        fetch(url, {
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
                console.error('No se pudo parsear JSON:', text);
                alert('Error del servidor: ' + text.substring(0, 200));
                callback(false);
                return;
            }
            
            if (res.ok && data.success) {
                showSuccess(data.message || (editMode ? 'Persona actualizada' : 'Persona agregada'));
                loadPersonas();  // Recargar la lista completa después de guardar
                callback(true);
            } else {
                showError(data.error || data.message || 'Error desconocido');
                callback(false);
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            showError('❌ Error de conexión: ' + error.message);
            callback(false);
        });
    };
    
    // Funciones de utilidad
    function showError(message) {
        alert('❌ Error: ' + message);
    }
    
    function showSuccess(message) {
        alert('✅ ' + message);
    }
    
    // Funciones para editar/eliminar
    window.editarPersona = function(rut) {
        fetch(`api/get_persona.php?rut=${encodeURIComponent(rut)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(persona => {
                document.getElementById('rut').value = formatearRUT(persona.rut);
                document.getElementById('rut').disabled = true;
                document.getElementById('nombre').value = persona.nombre || '';
                document.getElementById('apellido').value = persona.apellido || '';
                document.getElementById('fecha_nac').value = persona.fecha_nac || '';
                document.getElementById('telefono').value = persona.telefono || '';
                document.getElementById('correo').value = persona.correo || '';
                document.getElementById('ciudad').value = persona.ciudad || '';
                document.getElementById('comuna').value = persona.comuna || '';
                document.getElementById('calle').value = persona.calle || '';
                document.getElementById('numero').value = persona.numero || '';
                
                document.getElementById('editMode').value = '1';
                document.getElementById('originalRut').value = persona.rut;
                document.getElementById('modalTitle').innerText = 'Editar Persona';
                
                const btnGuardar = document.getElementById('btnGuardar');
                btnGuardar.innerHTML = '<i class="fas fa-save"></i> Actualizar Persona';
                
                editando = true;
                
                document.getElementById('modalPersona').style.display = 'block';
                document.body.style.overflow = 'hidden';
            })
            .catch(err => {
                console.error('Error detallado al cargar persona:', err);
                alert('Error al cargar los datos de la persona: ' + err.message);
            });
    };
    
    window.eliminarPersona = function(rut) {
        if (!confirm('¿Está seguro de eliminar a la persona con RUT: ' + rut + '?')) {
            return;
        }
        
        fetch('api/delete_persona.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'rut=' + encodeURIComponent(rut)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                loadPersonas(); // recargar tabla
            } else {
                alert('❌ Error: ' + (data.error || 'No se pudo eliminar'));
            }
        })
        .catch(err => {
            console.error('Error al eliminar:', err);
            alert('❌ Error de conexión');
        });
    };
    
    // Modal de exportación (sin cambios)
    const exportModal = document.getElementById('exportModal');
    const exportBtn = document.getElementById('exportarBtn');
    const closeModalBtns = exportModal ? exportModal.querySelectorAll('[data-bs-dismiss="modal"]') : [];

    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (closeModalBtns.length > 0) {
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                exportModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === exportModal) {
            exportModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    window.exportarPDF = function() {
        const datos = {
            orden: document.getElementById('orden').value,
            direccion: document.getElementById('direccion').value,
            limite: document.getElementById('limite').value
        };

        const limite = parseInt(datos.limite);
        if (isNaN(limite) || limite <= 0) {
            alert('Por favor ingrese un número válido para el límite');
            return;
        }

        const exportBtn = document.querySelector('#exportModal .btn-success');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        exportBtn.disabled = true;

        fetch('exportar_pdf_simple.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'text/html'
            },
            body: JSON.stringify(datos)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return res.text();
        })
        .then(html => {
            const nuevaVentana = window.open('', '_blank');
            nuevaVentana.document.write(html);
            nuevaVentana.document.close();
            
            exportModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar:', error);
            alert('Error al generar el reporte: ' + error.message);
            
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        });
    };
});