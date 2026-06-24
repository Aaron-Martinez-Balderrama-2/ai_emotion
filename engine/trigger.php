<?php
// Permite que el script siga ejecutándose en segundo plano
ignore_user_abort(true);
set_time_limit(0);

// Cierra la conexión HTTP inmediatamente para que el navegador no se quede cargando
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ob_end_clean();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    header("Content-Length: 0");
    ob_start();
    echo "";
    ob_end_flush();
    flush();
}

require_once __DIR__ . '/../core/colossus.php';
require_once __DIR__ . '/extractor.php';
require_once __DIR__ . '/../core/ai_engine.php';

$db = fn_conexion_bd();

// Procesar la cola hasta que esté vacía
while (true) {
    $stmt = $db->prepare("SELECT cod FROM tb_videos WHERE estado = 'pendiente' LIMIT 1");
    $stmt->execute();
    $video = $stmt->fetch();

    if (!$video) {
        break; // No hay más videos pendientes, terminamos el proceso oculto
    }

    $video_id = $video['cod'];
    
    // Fase 1: Extracción y Whisper
    fn_procesar_videos_pendientes();
    
    // Fase 2: Inteligencia
    $ai = new AIEngine();
    $ai->analizar_video($video_id);
}
