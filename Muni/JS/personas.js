// personas.js
document.addEventListener("DOMContentLoaded", function() {
    const personaForm = document.getElementById('personaForm');
    const personasTable = document.querySelector('.personas tbody');
    
    // Cargar personas al iniciar
    loadPersonas();
    
    // Manejar envío del formulario
    personaForm.addEventListener('submit', function(event) {
        event.preventDefault();
        guardarPersona();
    });
    
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
                
                renderPersonas(data);
            })
            .catch(err => {
                console.error('Error al cargar personas:', err);
                personasTable.innerHTML = '<tr><td colspan="6">Error al cargar los datos</td></tr>';
            });
    }
    
    function renderPersonas(personas) {
        let html = '';
        
        if (personas.length === 0) {
            html = '<tr><td colspan="6">No hay personas registradas</td></tr>';
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
        console.log('Guardando persona:', formData);
        
        fetch('api/add_persona.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(async res => {
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('Error parseando JSON:', parseError);
                showError('Error: La respuesta no es JSON válido\n' + text.substring(0, 200));
                callback(false);
                return;
            }
            
            if (data.success) {
                showSuccess(data.message || 'Persona agregada exitosamente');
                loadPersonas(); // Recargar la tabla
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
        // Puedes reemplazar esto con un toast más elegante
        alert('❌ Error: ' + message);
    }
    
    function showSuccess(message) {
        alert('✅ ' + message);
    }
    
    // Funciones para editar/eliminar (puedes implementarlas después)
    window.editarPersona = function(rut) {
        console.log('Editar persona con RUT:', rut);
        alert('Función de edición para RUT: ' + rut + ' (por implementar)');
    };
    
    window.eliminarPersona = function(rut) {
        if (confirm('¿Está seguro de eliminar a la persona con RUT: ' + rut + '?')) {
            console.log('Eliminar persona con RUT:', rut);
            // Aquí puedes agregar la llamada a la API para eliminar
            alert('Función de eliminación para RUT: ' + rut + ' (por implementar)');
        }
    };
    function exportarPDF() {
    const datos = {
        orden: document.getElementById('orden').value,
        direccion: document.getElementById('direccion').value,
        limite: document.getElementById('limite').value
    };

    fetch('exportar_pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.blob())
    .then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'reporte.pdf';
        a.click();
    });
}
// Modal de exportación
const exportModal = document.getElementById('exportModal');
const exportBtn = document.getElementById('exportarBtn');
const closeModalBtns = exportModal.querySelectorAll('[data-bs-dismiss="modal"]');

// Mostrar modal de exportación
if (exportBtn) {
    exportBtn.addEventListener('click', function(e) {
        e.preventDefault();
        exportModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });
}

// Cerrar modal
if (closeModalBtns.length > 0) {
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            exportModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    });
}

// Cerrar modal al hacer clic fuera
window.addEventListener('click', function(event) {
    if (event.target === exportModal) {
        exportModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// Función para exportar PDF
window.exportarPDF = function() {
    const datos = {
        orden: document.getElementById('orden').value,
        direccion: document.getElementById('direccion').value,
        limite: document.getElementById('limite').value
    };

    // Validar límite
    const limite = parseInt(datos.limite);
    if (isNaN(limite) || limite <= 0) {
        alert('Por favor ingrese un número válido para el límite');
        return;
    }

    // Mostrar loading
    const exportBtn = document.querySelector('#exportModal .btn-success');
    const originalText = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    exportBtn.disabled = true;

    // Opción 1: Usar el método simple (HTML para imprimir)
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
        // Crear una ventana nueva con el reporte
        const nuevaVentana = window.open('', '_blank');
        nuevaVentana.document.write(html);
        nuevaVentana.document.close();
        
        // Cerrar modal
        exportModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
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
});