<?php
// api/test_simple.php
header('Content-Type: application/json');

// Datos SIMPLES y DIRECTOS
$data = [
    [
        'id' => 1,
        'nombre' => 'TEST 1',
        'descripcion' => 'Descripción test 1',
        'activo' => true,
        'categoria_nombre' => 'CATEGORIA TEST 1',
        'duenno_nombre' => 'DUEÑO TEST 1 (11.111.111-1)'
    ],
    [
        'id' => 2,
        'nombre' => 'TEST 2',
        'descripcion' => 'Descripción test 2',
        'activo' => false,
        'categoria_nombre' => 'CATEGORIA TEST 2',
        'duenno_nombre' => 'DUEÑO TEST 2 (22.222.222-2)'
    ]
];

echo json_encode($data, JSON_PRETTY_PRINT);
?>