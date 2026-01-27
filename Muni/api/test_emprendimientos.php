<?php
// api/test_minimal.php
header('Content-Type: application/json');

// Datos de prueba HARDCODEADOS
$datos_prueba = [
    [
        'id' => 1,
        'nombre' => 'Panadería Don Juan',
        'descripcion' => 'Pan artesanal y pastelería',
        'activo' => true,
        'categoria_id' => 1,
        'categoria_nombre' => 'Alimentos',
        'duenno_rut' => '12.345.678-9',
        'duenno_nombre' => 'Juan Pérez (12.345.678-9)'
    ],
    [
        'id' => 2,
        'nombre' => 'TechSolutions',
        'descripcion' => 'Soporte y desarrollo TI',
        'activo' => true,
        'categoria_id' => 2,
        'categoria_nombre' => 'Tecnología',
        'duenno_rut' => '9.876.543-2',
        'duenno_nombre' => 'María González (9.876.543-2)'
    ]
];

echo json_encode($datos_prueba, JSON_PRETTY_PRINT);
?>