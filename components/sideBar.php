<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="sidebar">
<div class="sidebar">
    <div class="sidebar-header"> 
        <div class="logo-container">
            <img src="https://muniriobueno.cl/wp-content/uploads/2024/12/LogoMuniRioBueno2025SF-300x300.png" alt="Logo" class="logo">
        </div>
        <h2 class="sidebar-title">Centro de Negocios</h2>
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
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'beneficios.php' ? 'active' : ''; ?>">
            <a href="beneficios.php">
                <i class="fas fa-user-plus"></i>
                <span>Beneficios</span>
            </a>
        </li>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
            <a href="usuarios.php">
                <i class="fas fa-users"></i>
                <span>Usuarios</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <div class="logout-section">
        <a href="logout.php" class="logout-button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
</div>
</nav>