// js/personas_debug.js
document.addEventListener("DOMContentLoaded", function() {
    const personaForm = document.getElementById('personaForm');
    const personasTable = document.querySelector('.personas tbody');
    
    // Test del endpoint primero
    testEndpoint();
    
    personaForm.addEventListener('submit', function(event) {
        event.preventDefault();
        guardarPersona();
    });
    
    function testEndpoint() {
        console.log('Probando endpoint...');
        fetch('api/test_receive.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({test: 'data', number: 123})
        })
        .then(res => res.text())
        .then(text => {
            console.log('Test endpoint response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Test endpoint JSON:', data);
            } catch (e) {
                console.error('No es JSON:', text);
            }
        })
        .catch(err => console.error('Test endpoint error:', err));
    }
    
    function guardarPersona() {
        console.log('=== INICIANDO ENVÍO ===');
        
        if (!personaForm.checkValidity()) {
            personaForm.reportValidity();
            return;
        }
        
        // Recolectar datos
        const formData = {
            RUT: document.getElementById('rut').value.trim(),
            nombre: document.getElementById('nombre').value.trim(),
            apellido: document.getElementById('apellido').value.trim(),
            fecha_nac: document.getElementById('fecha_nac').value,
            telefono: document.getElementById('telefono').value.trim() || '',
            correo: document.getElementById('correo').value.trim() || ''
        };
        
        console.log('Datos a enviar:', formData);
        console.log('JSON stringified:', JSON.stringify(formData));
        
        // Mostrar loading
        const submitBtn = personaForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        submitBtn.disabled = true;
        
        // Enviar primero a test_receive para ver qué llega
        fetch('api/test_receive.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(async res => {
            console.log('Test receive status:', res.status, res.statusText);
            const text = await res.text();
            console.log('Test receive response:', text);
            return JSON.parse(text);
        })
        .then(testData => {
            console.log('Test receive data:', testData);
            
            // Ahora enviar al endpoint real
            return fetch('api/add_persona.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });
        })
        .then(async res => {
            console.log('Real endpoint status:', res.status, res.statusText);
            console.log('Real endpoint headers:', Object.fromEntries(res.headers.entries()));
            
            const text = await res.text();
            console.log('Real endpoint raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
                console.log('Real endpoint parsed:', data);
            } catch (e) {
                console.error('Error parseando JSON:', e);
                console.error('Texto recibido:', text.substring(0, 500));
                throw new Error('Respuesta no es JSON válido: ' + text.substring(0, 100));
            }
            
            if (!res.ok) {
                throw new Error(data.error || data.message || `Error ${res.status}: ${text.substring(0, 200)}`);
            }
            
            return data;
        })
        .then(data => {
            console.log('Respuesta exitosa:', data);
            
            if (data.success) {
                alert('✅ ' + (data.message || 'Persona agregada exitosamente'));
                personaForm.reset();
                loadPersonas();
            } else {
                alert('❌ Error: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Error completo en guardarPersona:', err);
            alert('❌ Error: ' + err.message);
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.log('=== FIN ENVÍO ===');
        });
    }
    
    function loadPersonas() {
        console.log('Cargando personas...');
        fetch('api/get_personas.php')
            .then(res => {
                console.log('Get personas status:', res.status);
                return res.json();
            })
            .then(data => {
                console.log('Personas cargadas:', data);
                renderPersonas(data);
            })
            .catch(err => {
                console.error('Error cargando personas:', err);
                personasTable.innerHTML = '<tr><td colspan="6">Error al cargar los datos</td></tr>';
            });
    }
    
    function renderPersonas(personas) {
        let html = '';
        
        if (!Array.isArray(personas) || personas.length === 0) {
            html = '<tr><td colspan="6">No hay personas registradas</td></tr>';
        } else {
            personas.forEach(p => {
                let telefonosHtml = '-';
                if (p.telefonos && p.telefonos.length > 0 && p.telefonos[0] !== '') {
                    telefonosHtml = Array.isArray(p.telefonos) ? p.telefonos.join('<br>') : p.telefonos;
                }
                
                let correosHtml = '-';
                if (p.correos && p.correos.length > 0 && p.correos[0] !== '') {
                    correosHtml = Array.isArray(p.correos) ? p.correos.join('<br>') : p.correos;
                }
                
                html += `
                    <tr>
                        <td>${p.rut || ''}</td>
                        <td>${p.nombre || ''}</td>
                        <td>${p.apellido || ''}</td>
                        <td>${p.edad || '-'}</td>
                        <td>${telefonosHtml}</td>
                        <td>${correosHtml}</td>
                    </tr>`;
            });
        }
        
        personasTable.innerHTML = html;
    }
});