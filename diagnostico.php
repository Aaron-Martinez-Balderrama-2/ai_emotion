<?php
/**
 * diagnostico.php - El Doctor de Antigravity
 * Este script verifica cada punto crítico del sistema para encontrar fallas.
 */

require_once __DIR__ . '/core/colossus.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Antigravity - Diagnóstico de Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f4f7f6; color: #333; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .check_item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f9f9f9; }
        .check_item i { font-size: 24px; margin-right: 20px; width: 30px; text-align: center; }
        .status_ok { color: #27ae60; }
        .status_error { color: #e74c3c; }
        .status_warning { color: #f39c12; }
        .details { font-size: 0.9em; color: #7f8c8d; margin-top: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; border-radius: 5px; text-decoration: none; margin-top: 20px; }
    </style>
</head>
<body>

<div class="card">
    <h1><i class="fas fa-user-md"></i> Doctor Antigravity</h1>
    <p>Analizando la salud de tu sistema de Inteligencia Artificial...</p>

    <!-- 1. Base de Datos -->
    <div class="check_item">
        <?php
        try {
            $db = fn_conexion_bd();
            echo '<i class="fas fa-database status_ok"></i>';
            echo '<div><strong>Base de Datos:</strong> Conectada correctamente.<div class="details">Conexión a db_antigravity establecida.</div></div>';
        } catch (Exception $e) {
            echo '<i class="fas fa-database status_error"></i>';
            echo '<div><strong>Base de Datos:</strong> ERROR de conexión.<div class="details">'.$e->getMessage().'</div></div>';
        }
        ?>
    </div>

    <!-- 2. Apify -->
    <div class="check_item">
        <?php
        if (defined('APIFY_TOKEN') && strlen(APIFY_TOKEN) > 10) {
            echo '<i class="fas fa-key status_ok"></i>';
            echo '<div><strong>Apify Token:</strong> Configurado.<div class="details">Token detectado en colossus.php</div></div>';
        } else {
            echo '<i class="fas fa-key status_error"></i>';
            echo '<div><strong>Apify Token:</strong> NO ENCONTRADO.<div class="details">Revisa la constante APIFY_TOKEN en core/colossus.php</div></div>';
        }
        ?>
    </div>

    <!-- 3. Python -->
    <div class="check_item">
        <?php
        $python_ver = shell_exec("python --version 2>&1");
        if ($python_ver) {
            echo '<i class="fab fa-python status_ok"></i>';
            echo '<div><strong>Python:</strong> Instalado.<div class="details">Versión: '.$python_ver.'</div></div>';
        } else {
            echo '<i class="fab fa-python status_error"></i>';
            echo '<div><strong>Python:</strong> NO DETECTADO.<div class="details">Asegúrate de que Python esté en el PATH del sistema.</div></div>';
        }
        ?>
    </div>

    <!-- 4. Ollama -->
    <div class="check_item">
        <?php
        $connection = @fsockopen('localhost', 11434, $errno, $errstr, 2);
        if ($connection) {
            fclose($connection);
            echo '<i class="fas fa-brain status_ok"></i>';
            echo '<div><strong>Ollama:</strong> ONLINE.<div class="details">El servidor está respondiendo en el puerto 11434.</div></div>';
        } else {
            echo '<i class="fas fa-brain status_error"></i>';
            echo '<div><strong>Ollama:</strong> OFFLINE / APAGADO.<div class="details">¡ERROR CRÍTICO! Debes abrir la aplicación Ollama en tu computadora.</div></div>';
        }
        ?>
    </div>

    <!-- 5. Binarios (yt-dlp, ffmpeg) -->
    <div class="check_item">
        <?php
        $bin_dir = realpath(__DIR__ . "/bin");
        $ytdlp = $bin_dir . DIRECTORY_SEPARATOR . "yt-dlp.exe";
        if (file_exists($ytdlp)) {
            echo '<i class="fas fa-file-exe status_ok"></i>';
            echo '<div><strong>Binarios:</strong> Encontrados.<div class="details">yt-dlp.exe detectado en /bin/</div></div>';
        } else {
            echo '<i class="fas fa-file-exe status_error"></i>';
            echo '<div><strong>Binarios:</strong> FALTAN ARCHIVOS.<div class="details">No se encontró yt-dlp.exe en '.$bin_dir.'</div></div>';
        }
        ?>
    </div>

    <a href="app/videos/" class="btn"><i class="fas fa-arrow-left"></i> Volver al Sistema</a>
    <a href="diagnostico.php" class="btn" style="background:#2ecc71;"><i class="fas fa-sync"></i> Re-escaneo</a>
</div>

</body>
</html>
