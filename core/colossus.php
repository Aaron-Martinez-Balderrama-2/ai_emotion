<?php
// colossus.php - Núcleo del sistema Antigravity

// Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_antigravity');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('APIFY_TOKEN', 'TU_APIFY_TOKEN_AQUI');

/**
 * Establece la conexión con la base de datos usando PDO
 */
function fn_conexion_bd()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $opciones);
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

/**
 * Cabecera Global del Sistema
 */
function fn_cabecera_sistema($titulo = "Antigravity AI")
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $titulo ?> | Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/antigravity/ai_emotion/assets/css/colossus.css">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar">
            <div class="nav_container">
                <a href="/antigravity/ai_emotion/app/videos/" class="logo">
                    <i class="fas fa-microchip"></i> Antigravity <span>AI</span>
                </a>
                <ul class="nav_links">
                    <li><a href="/antigravity/ai_emotion/app/videos/"><i class="fas fa-video"></i> Videos</a></li>
                    <li><a href="/antigravity/ai_emotion/app/diccionario/"><i class="fas fa-book"></i> Diccionario</a></li>
                    <li><a href="/antigravity/ai_emotion/app/reportes/"><i class="fas fa-chart-pie"></i> Reportes</a></li>
                </ul>
            </div>
        </nav>
        <main class="contenedor_principal">
    <?php
}

/**
 * Pie de Página Global
 */
function fn_pie_sistema()
{
    ?>
        </main>
        <script src="/antigravity/ai_emotion/assets/js/global.js"></script>
    </body>
    </html>
    <?php
}

function fn_limpiar_texto($texto)
{
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}
?>