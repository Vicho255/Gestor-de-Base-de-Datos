<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$emprendimientos_activos = 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
</head>
<body>

    <?php include 'components/sideBar.php'; ?>

    <main class="main-content">

        <header class="header">
            <h1>Vista general de Los Datos</h1>
        </header>
        <!-- carts -->
        <section class="stats-cards">
            <div class="stats-grid">
                <div class="stats-card">
                    <div class="stat-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="stat-info">
                        <p>Emprendimientos Activos</p>
                        <p class="stat-value" id="emprendimientos-activos-count"></p>
                    </div>
                </div>
            </div>
        </section>
        <div class="content-grid">
        <!-- Tablas de datos -->
                <div class="chart-per">
                    <table class="table" id="personas-table">
                        <div class="table-header">
                            <h3 class="table-title">Personas</h3>
                            <button class="toggle-search-btn" data-target="personas-filters">
                                <i class="fas fa-search"></i>
                            </button>
                            <div class="search-filters-container" id="personas-filters">
                            <div class="filter-controls">
                                <div class="search-box">
                                    <input type="text" id="search-personas" placeholder="Buscar por nombre, email, teléfono...">
                                </div>
                            </div>
                        </div>
                        </div>
                        
                    </table>
                </div>

                <div class="chart-empr">
                    <table class="table" id="emprendimientos-table">
                        <div class="table-header">
                            <h3 class="table-title">Emprendimientos</h3>
                            <button class="toggle-search-btn" data-target="emprendimientos-filters">
                                <i class="fas fa-filter"></i>
                            </button>
                            <!-- Contenedor oculto para filtros de Emprendimientos -->
                            <div class="search-filters-container" id="emprendimientos-filters">
                                <div class="filter-controls">
                                    <div class="categorias-filter">
                                        <label for="filter-categoria">Categoría:</label>
                                        <select id="filter-categoria" class="filter-select">
                                            <option value="">Todas</option>
                                            <option value="Alimentos">Alimentos</option>
                                            <option value="Artesanía">Artesanía</option>
                                            <option value="Tecnología">Tecnología</option>
                                        </select>
                                    </div>
                                    <div class="estados-filter">
                                        <label for="filter-estado">Estado:</label>
                                        <select id="filter-estado" class="filter-select">
                                            <option value="">Todos</option>
                                            <option value="Activo">Activo</option>
                                            <option value="Inactivo">Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="search-box">
                                        <input type="text" id="search-emprendimientos" placeholder="Buscar por nombre, categoría...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </table>
                </div>
        </div>

        
    </main>

    <script src="js/dashboard.js"></script>
     <script>
        // Script para manejar la visibilidad de los filtros
        document.addEventListener('DOMContentLoaded', function() {
            // Seleccionar todos los botones de toggle
            const toggleButtons = document.querySelectorAll('.toggle-search-btn');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetContainer = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    const textSpan = this.querySelector('span');
                    
                    // Alternar la clase 'active' en el contenedor
                    targetContainer.classList.toggle('active');
                    
                    // Alternar la clase 'active' en el botón
                    this.classList.toggle('active');
                    
                    // Cambiar el texto e ícono del botón
                    if (targetContainer.classList.contains('active')) {
                        textSpan.textContent = 'Ocultar Filtros';
                        icon.className = 'fas fa-times';
                    } else {
                        textSpan.textContent = 'Mostrar Filtros';
                        icon.className = 'fas fa-filter';
                        
                        // Para el botón de personas, cambiar el ícono específico
                        if (targetId === 'personas-filters') {
                            icon.className = 'fas fa-search';
                        }
                    }
                });
            });
            
            // Funcionalidad de búsqueda y filtrado (ejemplo)
            document.getElementById('search-personas')?.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                // Aquí implementarías la lógica de filtrado de la tabla de personas
                console.log('Buscando personas:', searchTerm);
            });
            
            document.getElementById('search-emprendimientos')?.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                // Aquí implementarías la lógica de filtrado de la tabla de emprendimientos
                console.log('Buscando emprendimientos:', searchTerm);
            });
            
            // Funcionalidad para filtros select
            const filterSelects = document.querySelectorAll('.filter-select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const filterType = this.id;
                    const filterValue = this.value;
                    console.log(`Filtrando por ${filterType}: ${filterValue}`);
                    // Aquí implementarías la lógica de filtrado específica
                });
            });
        });
    </script>
</body>

</html>