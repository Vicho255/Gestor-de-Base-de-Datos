<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="sidebar">
<div class="sidebar">
    <div class="sidebar-header">
        <h2 class="sidebar-title">MENÚ</h2> 
        <div class="logo-container">
            <img src="https://muniriobueno.cl/wp-content/uploads/2024/12/LogoMuniRioBueno2025SF-300x300.png" alt="Logo" class="logo">
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-user-alt"></i>    
                <span>Dashboard</span>
            </a> 
        </li>
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'personas.php' ? 'active' : ''; ?>">
            <a href="personas.php">
                <i class="fas fa-users"></i>
                <span>Personas</span>
            </a>
        </li>
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'emprendimientos.php' ? 'active' : ''; ?>">
            <a href="emprendimientos.php">
                <i class="fas fa-warehouse"></i>
                <span>Emprendimientos</span>
            </a>
        </li>
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'empresas.php' ? 'active' : ''; ?>">
            <a href="empresas.php">
                <i class="fas fa-building"></i>
                <span>Empresas</span>
            </a>
        </li>
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
            <a href="reportes.php">
                <i class="fas fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
        </li>
    </ul>
    <div class="logout-section">
        <a href="logout.php" class="logout-button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
</div>
</nav>