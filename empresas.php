<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userName = $_SESSION['username'] ?? 'Usuario';

$pageTitle = "Gestion de Empresas";

?>
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>empresas</title>
    <link rel="stylesheet" href="css/empresas.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
    <style>
        /* Estilos para el modal (los mismos que antes) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 800px;
            position: relative;
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover { color: #000; }

        .btn-agregar {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .btn-agregar:hover { background-color: #45a049; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .btn-submit:hover { background-color: #45a049; }
        .btn-submit:disabled { background-color: #cccccc; cursor: not-allowed; }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 10px;
            transition: background-color 0.3s;
        }

        .btn-secondary:hover { background-color: #5a6268; }

        .modal-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 { margin: 0; color: #333; }

        /* Estilos para la tabla */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .empresas {
            width: 100%;
            border-collapse: collapse;
        }

        .empresas th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .empresas td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        .empresas tbody tr:hover { background-color: #f8f9fa; }

        .badge-activo, .badge-inactivo {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-activo { background-color: #d4edda; color: #155724; }
        .badge-inactivo { background-color: #f8d7da; color: #721c24; }

        .btn-editar, .btn-eliminar, .btn-activar {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 5px 8px;
            border-radius: 4px;
            margin: 0 2px;
            transition: background-color 0.3s;
        }

        .btn-editar { color: #2196F3; }
        .btn-editar:hover { background-color: rgba(33, 150, 243, 0.1); }

        .btn-eliminar { color: #f44336; }
        .btn-eliminar:hover { background-color: rgba(244, 67, 54, 0.1); }

        .btn-activar { color: #4CAF50; }
        .btn-activar:hover { background-color: rgba(76, 175, 80, 0.1); }

        .btn-ver { color: #6c757d; }
        .btn-ver:hover { background-color: rgba(108, 117, 125, 0.1); }

        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 20px;
            }
            
            .form-grid { grid-template-columns: 1fr; }
            
            .btn-agregar {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/sideBar.php'; ?>
    
    <div class="main-content">
        <?php include 'components/header.php'; ?>
        <div>
            <button class="btn-agregar" id="btnAbrirModal">
                <i class="fas fa-plus-circle"></i>
                Agregar Nueva Empresa
            </button>
        </div>

        <div class="table-container">
            <div class="table-header">
                <input type="text" id="searchInput" placeholder="Buscar por RUT, Nombre o Apellido...">
                <button id="btnBuscar"><i class="fas fa-search"></i></button>
                <button id="exportarBtn"><i class="fas fa-file-export"></i></button>
            </div>
            <table class="empresas">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Dueño</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los datos se cargarán aquí mediante JavaScript -->
                    <tr>
                        <td colspan="7">Cargando datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal para agregar emprendimiento -->
        <div id="modalEmprendimiento" class="modal">
            <div class="modal-content">
                <button class="close-modal" id="btnCerrarModal">&times;</button>
                
                <div class="modal-header">
                    <h2>Agregar Nueva Empresa</h2>
                </div>
                
                <form id="empresaForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre de la Empresa:</label>
                            <input type="text" id="nombre" name="nombre" required 
                                   placeholder="Ej: Panadería Doña Maria">
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria_id">Categoría:</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Cargando categorías...</option>
                                <!-- Las categorías se cargarán dinámicamente -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="duenno_rut">RUT del Dueño:</label>
                            <input type="text" id="duenno_rut" name="duenno_rut" required 
                                placeholder="12.345.678-9"
                                pattern="^(\d{1,2}\.\d{3}\.\d{3}-[\dkK]|\d{7,8}-[\dkK])$"
                                title="Formato: 12.345.678-9 o 12345678-9">
                            <small style="color: #666; font-size: 12px; margin-top: 5px;">
                                Ingrese RUT con formato (se formateará automáticamente)
                            </small>
                                <button type="button" onclick="mostrarPersonasDisponibles()" 
                                        class="btn-secondary" style="white-space: nowrap;">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" 
                                     placeholder="Describa el emprendimiento..."></textarea>
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-secondary" id="btnCancelar">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar Empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>
            <div id="exportModal" class="modal">
                <div class="modal-content">
                    <button class="close-modal" id="btnCerrarExportModal">&times;</button>
                    
                    <div class="modal-header">
                        <h2>Exportar Empresas</h2>
                    </div>
                    
                    <form id="exportForm">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="exportFormat">Formato de Exportación:</label>
                                <select id="exportFormat" name="exportFormat" required>
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                </select>
                            </div>                
                        </div>
                    </form>
                </div>
            </div>
    </div>

    <script src="JS/empresas.js"></script>
</body>
</html>