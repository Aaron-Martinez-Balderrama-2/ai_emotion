<?php
require_once __DIR__ . '/../core/colossus.php';
require_once __DIR__ . '/extractor.php';
require_once __DIR__ . '/../core/ai_engine.php';

$db = fn_conexion_bd();

echo "--- MOTOR ANTIGRAVITY ACTIVADO (Vigilando cola...) ---\n";

while (true) {
    // 1. Buscar video pendiente
    $stmt = $db->prepare("SELECT cod FROM tb_videos WHERE estado = 'pendiente' LIMIT 1");
    $stmt->execute();
    $video = $stmt->fetch();

    if ($video) {
        $video_id = $video['cod'];
        try {
            echo "\n[+] Detectado video ID: $video_id. Iniciando...\n";
            echo "--- FASE 1: EXTRACCIÓN ---\n";
            echo fn_procesar_videos_pendientes() . "\n";
            
            echo "--- FASE 2: INTELIGENCIA (OLLAMA) ---\n";
            $ai = new AIEngine();
            $resultado = $ai->analizar_video($video_id);
            
            if ($resultado) {
                echo "¡Análisis de IA completado con éxito!\n";
            } else {
                echo "La IA no devolvió resultados o hubo un error.\n";
            }
            echo "Esperando nuevo trabajo...\n";

        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }

    // Pausa de 5 segundos antes de volver a revisar
    sleep(5);
}
