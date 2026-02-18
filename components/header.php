<?php
// header.php (o donde tengas tu header)
if (!isset($_SESSION)) {
    session_start();
}

$header_config = [
    'pageTitle' => $pageTitle ?? 'Centro de Negocios',
    'userName' => $userName ?? 'Usuario_H',
];

if (isset($header_config)) {
    $pageTitle = $header_config['pageTitle'];
    $userName = $header_config['userName'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
</head>
<body>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>

    <div class="header-right">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
        </div>
    </div>
</header>