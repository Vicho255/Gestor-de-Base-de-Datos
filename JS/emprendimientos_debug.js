// js/emprendimientos.js - VERSIÓN ROBUSTA
document.addEventListener("DOMContentLoaded", function() {
    const emprendimientosTable = document.querySelector('.emprendimientos tbody');
    
    // Cargar emprendimientos al iniciar
    loadEmprendimientos();
    
    function loadEmprendimientos() {
        console.log('Cargando emprendimientos...');
        fetch('api/get_emprendimientos.php')
            .then(async res => {
                console.log('Status:', res.status);
                const text = await res.text();
                console.log('Respuesta completa:', text);
                
                try {
                    const data = JSON.parse(text);
                    console.log('Datos parseados:', data);
                    
                    if (data.error) {
                        console.error('Error del servidor:', data.error);
                        showError(data.error);
                        return;
                    }
                    
                    // VERIFICAR: ¿Qué claves tiene el primer elemento?
                    if (data.length > 0) {
                        console.log('Primer elemento claves:', Object.keys(data[0]));
                        console.log('Primer elemento valores:', data[0]);
                        
                        // Verificar específicamente las claves que necesitamos
                        const neededKeys = ['categoria_nombre', 'duenno_nombre', 'activo'];
                        neededKeys.forEach(key => {
                            console.log(`¿Tiene ${key}?`, key in data[0], 'Valor:', data[0][key]);
                        });
                    }
                    
                    renderEmprendimientos(data);
                    
                } catch (parseError) {
                    console.error('Error parseando JSON:', parseError);
                    console.error('Texto que falló:', text.substring(0, 500));
                    showError('Error en el formato de datos recibidos');
                }
            })
            .catch(err => {
                console.error('Error en fetch:', err);
                emprendimientosTable.innerHTML = '<tr><td colspan="7">Error de conexión</td></tr>';
            });
    }
    
    function renderEmprendimientos(emprendimientos) {
        console.log('Renderizando', emprendimientos.length, 'emprendimientos');
        
        let html = '';
        
        if (!Array.isArray(emprendimientos) || emprendimientos.length === 0) {
            html = '<tr><td colspan="7" class="no-data">No hay emprendimientos</td></tr>';
        } else {
            emprendimientos.forEach(emp => {
                // OBTENER VALORES de forma SEGURA (case-insensitive)
                const getValue = (obj, key) => {
                    // Buscar la clave ignorando mayúsculas/minúsculas
                    const lowerKey = key.toLowerCase();
                    for (const k in obj) {
                        if (k.toLowerCase() === lowerKey) {
                            return obj[k];
                        }
                    }
                    return undefined;
                };
                
                // Usar valores seguros
                const id = getValue(emp, 'id') || '';
                const nombre = getValue(emp, 'nombre') || '';
                const descripcion = getValue(emp, 'descripcion') || '';
                const categoria_nombre = getValue(emp, 'categoria_nombre') || 'Sin categoría';
                const duenno_nombre = getValue(emp, 'duenno_nombre') || 'Sin dueño';
                const activo_raw = getValue(emp, 'activo');
                
                // Determinar estado
                let activo = false;
                if (activo_raw === true || activo_raw === 'true' || activo_raw === 't' || activo_raw === 1 || activo_raw === '1') {
                    activo = true;
                }
                
                console.log(`ID ${id}:`, {
                    categoria: categoria_nombre,
                    duenno: duenno_nombre,
                    activo: activo,
                    activo_raw: activo_raw,
                    tipo: typeof activo_raw
                });
                
                // Limitar descripción
                let descripcionCorta = descripcion;
                if (descripcionCorta.length > 100) {
                    descripcionCorta = descripcionCorta.substring(0, 100) + '...';
                }
                
                // Estado
                const estado = activo ? 
                    '<span class="badge-activo">Activo</span>' :
                    '<span class="badge-inactivo">Inactivo</span>';
                
                html += `
                    <tr>
                        <td>${id}</td>
                        <td>${nombre}</td>
                        <td><strong>${categoria_nombre}</strong></td>
                        <td title="${descripcion}">${descripcionCorta}</td>
                        <td><strong>${duenno_nombre}</strong></td>
                        <td>${estado}</td>
                        <td>
                            <button class="btn-ver" onclick="verDetalles(${id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        }
        
        emprendimientosTable.innerHTML = html;
        console.log('Renderización completada');
    }
    
    function showError(message) {
        alert('Error: ' + message);
    }
    
    function verDetalles(id) {
        console.log('Ver detalles:', id);
    }
});