document.addEventListener("DOMContentLoaded", function(){
    const usersForm = document.getElementById('userForm');
    const usersTable = document.querySelector('.usuarios tbody');
    const editModal = document.getElementById('editModal');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCancelar = document.getElementById('btnCancelar');
    const usuarioForm = document.getElementById('usuarioForm');
    const addModal = document.getElementById('addModal');
    const btnAgregar = document.getElementById('btnAgregarUsuario');
    const btnCerrarAdd = document.getElementById('btnCerrarAddModal');
    const btnCancelarAdd = document.getElementById('btnCancelarAdd');
    const addUsuarioForm = document.getElementById('addUsuarioForm');


    loadUsuarios();

    function loadUsuarios() {
        fetch('api/get_usuarios.php')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Error del servidor', data.error);
                    return;
                }
                if (!Array.isArray(data)) {
                    console.error('Respuesta invalida', data);
                    return;
                }

                renderUsuarios(data);
                // Agregar eventos después de renderizar
                addEditEventListeners();
            })
            .catch(err => {
                console.error('Error al cargar usuarios', err);
                usersTable.innerHTML = '<tr><td colspan="5">Error al cargar los datos</td></tr>';
            });
    }

    function renderUsuarios(usuarios) {
        let html = '';

        if (usuarios.length === 0) {
            html = '<tr><td colspan="5">No hay Usuarios registrados</td></tr>';
        } else {
            usuarios.forEach(u => {

                html += `
                <tr>
                    <td>${u.id}</td>
                    <td>${u.username}</td>
                    <td>${u.created_at}</td>
                    <td>${u.rol}</td>
                    <td>
                        <button class="btn-editar" data-id="${u.id}" data-username="${u.username}" data-rol="${u.rol}">
                            <i class="fas fa-edit"></i>
                        </button> 
                    </td>
                </tr>`;
            });
        }

        usersTable.innerHTML = html; 
    }

    // Abrir modal al hacer clic en "Agregar Usuario"
    if (btnAgregar) {
        btnAgregar.addEventListener('click', function() {
            // Limpiar campos
            document.getElementById('addUsername').value = '';
            document.getElementById('addPassword').value = '';
            document.getElementById('addRol').value = 'user'; // valor por defecto

            addModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }

    // Cerrar modal de agregar
    function cerrarAddModal() {
        addModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    if (btnCerrarAdd) btnCerrarAdd.addEventListener('click', cerrarAddModal);
    if (btnCancelarAdd) btnCancelarAdd.addEventListener('click', cerrarAddModal);

    // Cerrar al hacer clic fuera del contenido
    window.addEventListener('click', function(event) {
        if (event.target === addModal) {
            cerrarAddModal();
        }
        // El cierre del editModal ya está manejado
    });

    // Enviar formulario de nuevo usuario
    if (addUsuarioForm) {
        addUsuarioForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const username = document.getElementById('addUsername').value.trim();
            const password = document.getElementById('addPassword').value;
            const rol = document.getElementById('addRol').value;

            if (!username || !password) {
                alert('Por favor completa todos los campos');
                return;
            }

            const formData = {
                username: username,
                password: password,
                rol: rol
            };

            fetch('api/create_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('Usuario creado exitosamente');
                    cerrarAddModal();
                    loadUsuarios(); // Recargar la tabla
                }
            })
            .catch(err => {
                console.error('Error al crear usuario:', err);
                alert('Ocurrió un error al crear el usuario');
            });
        });
    }

    // Función para agregar eventos a los botones de editar
    function addEditEventListeners() {
        const editButtons = document.querySelectorAll('.btn-editar');
        const deleteButtons = document.querySelectorAll('.btn-eliminar');

        // Eventos para botones de editar
editButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const id = this.getAttribute('data-id');
        console.log('ID del botón:', id); // <-- Agrega esto
        const username = this.getAttribute('data-username');
        const rol = this.getAttribute('data-rol');
        
        document.getElementById('usuarioId').value = id;
        document.getElementById('username').value = username;
        document.getElementById('rol').value = rol;
        document.getElementById('password').value = '';
        
        console.log('Valor de usuarioId después de asignar:', document.getElementById('usuarioId').value); // <-- y esto
        
        editModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });
});

        // Eventos para botones de eliminar
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                eliminarUsuario(id);
            });
        });
    }

    // Eventos para cerrar el modal
    if (btnCerrar) {
        btnCerrar.addEventListener('click', function() {
            editModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            editModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }

    // Cerrar modal al hacer clic fuera del contenido
    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
            editModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

        // Enviar formulario de edición
    if (usuarioForm) {
        usuarioForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('usuarioId').value;
            const username = document.getElementById('username').value.trim();
            const rol = document.getElementById('rol').value;
            const password = document.getElementById('password').value;

            if (!id || !username || !rol) {
                alert('Faltan datos requeridos (ID, usuario o rol)');
                return;
            }
            const formData = { id, username, rol };
            if (password.trim() !== '') {
                formData.password = password;
            }

            fetch('api/update_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('Usuario actualizado exitosamente');
                    editModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    loadUsuarios();
                }
            })
            .catch(err => {
                console.error('Error al actualizar:', err);
                alert('Ocurrió un error al actualizar el usuario');
            });
        });
    }

});