// js/test_simple.js
document.addEventListener("DOMContentLoaded", function() {
    console.log('=== PRUEBA SIMPLE ===');
    
    // PRUEBA 1: Datos hardcodeados
    const testData = [
        {id: 1, nombre: 'TEST', categoria_nombre: 'CATEGORIA', duenno_nombre: 'DUEÑO', activo: true}
    ];
    
    console.log('Datos hardcodeados:', testData[0]);
    console.log('Claves:', Object.keys(testData[0]));
    
    // PRUEBA 2: Fetch del endpoint simple
    fetch('api/test_simple.php')
        .then(res => res.json())
        .then(data => {
            console.log('Datos del endpoint simple:', data);
            if (data.length > 0) {
                console.log('Primer elemento:', data[0]);
                console.log('Tiene categoria_nombre?', 'categoria_nombre' in data[0]);
                console.log('Valor:', data[0].categoria_nombre);
            }
        })
        .catch(err => console.error('Error:', err));
    
    // PRUEBA 3: Fetch del endpoint real
    fetch('api/get_emprendimientos.php')
        .then(async res => {
            const text = await res.text();
            console.log('=== RESPUESTA CRUDA ===');
            console.log(text.substring(0, 500));
            
            try {
                const data = JSON.parse(text);
                console.log('=== JSON PARSEADO ===');
                console.log('Es array?', Array.isArray(data));
                if (Array.isArray(data) && data.length > 0) {
                    console.log('Primer elemento COMPLETO:', data[0]);
                    console.log('Todas las claves del primer elemento:', Object.keys(data[0]));
                    
                    // Buscar claves que contengan "categoria" (case insensitive)
                    const categoriaKey = Object.keys(data[0]).find(k => 
                        k.toLowerCase().includes('categoria')
                    );
                    console.log('Clave de categoría encontrada:', categoriaKey);
                    
                    // Buscar claves que contengan "duenno" o "dueño"
                    const duennoKey = Object.keys(data[0]).find(k => 
                        k.toLowerCase().includes('duenno') || k.toLowerCase().includes('dueño')
                    );
                    console.log('Clave de dueño encontrada:', duennoKey);
                }
            } catch (e) {
                console.error('Error parseando:', e);
            }
        })
        .catch(err => console.error('Fetch error:', err));
});