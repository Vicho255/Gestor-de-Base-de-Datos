// js/emprendimientos_final.js
document.addEventListener("DOMContentLoaded", function() {
    const emprendimientosTable = document.querySelector('.emprendimientos tbody');
    
    // Cargar emprendimientos al iniciar
    loadEmprendimientos();
    
    // También cargar categorías para el modal si existe
    const categoriaSelect = document.getElementById('categoria_id');
    if (categoriaSelect) {
        loadCategorias();
    }
    
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
                
                renderEmprendimientos(data);
            })
            .catch(err => {
                console.error('Error:', err);
                emprendimientosTable.innerHTML = '<tr><td colspan="7">Error al cargar los datos</td></tr>';
            });
    }
    
    function loadCategorias() {
        fetch('api/get_categorias.php')
            .then(res => res.json())
            .then(categorias => {
                categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
                categorias.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.nombre;
                    categoriaSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Error cargando categorías:', err);
                categoriaSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
            });
    }
    
    function renderEmprendimientos(emprendimientos) {
        let html = '';
        
        if (!Array.isArray(emprendimientos) || emprendimientos.length === 0) {
            html = '<tr><td colspan="7" class="no-data">No hay emprendimientos registrados</td></tr>';
        } else {
            emprendimientos.forEach(emp => {
                // DEBUG: Verificar que tenemos los datos correctos
                console.log('Procesando:', {
                    id: emp.id,
                    nombre: emp.nombre,
                    categoria: emp.categoria_nombre,
                    duenno: emp.duenno_nombre,
                    activo: emp.activo
                });
                
                // Limitar descripción
                let descripcionCorta = emp.descripcion || '';
                if (descripcionCorta.length > 100) {
                    descripcionCorta = descripcionCorta.substring(0, 100) + '...';
                }
                
                // Estado (activo ya debe ser booleano desde PHP)
                const estado = emp.activo === true ? 
                    '<span class="badge-activo">Activo</span>' :
                    '<span class="badge-inactivo">Inactivo</span>';
                
                // Acciones
                const acciones = emp.activo === true ? 
                    `<button class="btn-editar" onclick="editarEmprendimiento(${emp.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-eliminar" onclick="desactivarEmprendimiento(${emp.id})" title="Desactivar">
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
    
    // Función para guardar desde el modal
    window.guardarEmprendimientoDesdeJS = function(formData, callback) {
        console.log('Guardando:', formData);
        
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
                throw new Error('Respuesta no válida');
            }
            
            if (data.success) {
                alert('✅ ' + data.message);
                loadEmprendimientos();
                callback(true);
            } else {
                alert('❌ ' + (data.error || 'Error desconocido'));
                callback(false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error: ' + error.message);
            callback(false);
        });
    };
    
    // Funciones CRUD básicas
    window.editarEmprendimiento = function(id) {
        alert('Editar emprendimiento ID: ' + id + ' (función por implementar)');
    };
    
    window.desactivarEmprendimiento = function(id) {
        if (confirm('¿Desactivar este emprendimiento?')) {
            alert('Desactivar ID: ' + id + ' (función por implementar)');
        }
    };
    
    window.activarEmprendimiento = function(id) {
        if (confirm('¿Activar este emprendimiento?')) {
            alert('Activar ID: ' + id + ' (función por implementar)');
        }
    };
    
    window.eliminarEmprendimiento = function(id) {
        if (confirm('¿Eliminar este emprendimiento?')) {
            alert('Eliminar ID: ' + id + ' (función por implementar)');
        }
    };
    
    window.verDetallesEmprendimiento = function(id) {
        alert('Ver detalles ID: ' + id + ' (función por implementar)');
    };
});