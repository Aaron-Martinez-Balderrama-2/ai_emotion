<?php
// colossus.php - Núcleo del sistema Antigravity

// Cargar variables de entorno locales si existe el archivo (ignorado por Git)
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// Configuración de la Base de Datos
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'db_antigravity');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Claves de API
if (!defined('APIFY_TOKEN')) define('APIFY_TOKEN', 'apify_api');

/**
 * Establece la conexión con la base de datos usando PDO
 */
function fn_conexion_bd()
{
    static $db_conexion = null;
    if ($db_conexion !== null) {
        return $db_conexion;
    }
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $db_conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        return $db_conexion;
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
                    <li><a href="/antigravity/ai_emotion/app/predicciones/"><i class="fas fa-vote-yea"></i> Electoral</a></li>
                    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1): ?>
                        <li><a href="/antigravity/ai_emotion/app/admin/usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li><a href="/antigravity/ai_emotion/app/admin/roles.php"><i class="fas fa-user-shield"></i> Roles</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['usuario_nombre'])): ?>
                    <li style="margin-left: 20px; border-left: 1px solid #e2e8f0; padding-left: 20px;">
                        <span style="font-weight: 600; color: var(--primario); margin-right: 10px;">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                        </span>
                        <a href="/antigravity/ai_emotion/app/login/logout.php" style="display: inline; color: var(--error);">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </li>
                    <?php endif; ?>
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