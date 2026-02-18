document.addEventListener("DOMContentLoaded", function() {
    // Función para cargar datos de personas
function loadPersonas() {
    fetch('api/get_personas.php')
        .then(res => res.json())
        .then(data => {

            if (!Array.isArray(data)) {
                console.error('Respuesta inválida:', data);
                return;
            }

            const table = document.getElementById('personas-table');
            let html = `
                <tr>
                    <th>RUT</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Edad</th>
                </tr>`;

            data.forEach(p => {
                html += `
                    <tr>
                        <td>${p.rut}</td>
                        <td>${p.nombre}</td>
                        <td>${p.apellido}</td>
                        <td>${p.edad ?? '-'}</td>
                    </tr>`;
            });

            table.innerHTML = html;
        })
        .catch(err => console.error('Error al cargar personas:', err));
}


    // Función para cargar datos de emprendimientos
function loadEmprendimientos() {
    fetch('api/get_emprendimiento.php')
        .then(res => res.json())
        .then(data => {

            if (!Array.isArray(data)) {
                console.error('Respuesta inválida:', data);
                return;
            }

            const table = document.getElementById('emprendimientos-table');
            let html = `
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Categoria</th>
                    <th>Estado</th>
                    <th>Dueño</th>
                </tr>`;

            let activoText = {true: 'Activo', false: 'Inactivo'};

            data.forEach(e => {
                html += `
                    <tr>
                        <td>${e.id}</td>
                        <td>${e.nombre}</td>
                        <td>${e.descripcion}</td>
                        <td>${e.categoria_nombre}</td>
                        <td>${activoText[e.activo]}</td>
                        <td>${e.duenno_nombre}</td>
                    </tr>`;
            });

            table.innerHTML = html;
        })
        .catch(err => console.error('Error al cargar emprendimientos:', err));
}
function loadEmprendimientosCount() {
    fetch('api/get_emprendimiento.php')
        .then(res => res.json())
        .then(data => {

            if (!Array.isArray(data)) {
                console.error('Respuesta inválida:', data);
                return;
            }
            const countElement = document.getElementById('emprendimientos-activos-count');
            let activoText = {true: 'Activo', false: 'Inactivo'};
            countElement.textContent = data.filter(e => e.activo).length;

        })
        .catch(err => console.error('Error al cargar conteo de emprendimientos:', err));
}
    // Cargar datos al iniciar
    loadPersonas();
    loadEmprendimientos();
    loadEmprendimientosCount();
});