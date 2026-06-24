<?php
/**
 * extractor.php - Motor de extracción de datos de TikTok
 * Este script se encarga de descargar el video, extraer audio, 
 * transcribir con Whisper y bajar los comentarios.
 */

require_once __DIR__ . '/../core/colossus.php';
require_once __DIR__ . '/apify_handler.php';

function fn_procesar_videos_pendientes() {
    $conexion_bd = fn_conexion_bd();
    
    // Obtener videos pendientes
    $stmt = $conexion_bd->prepare("SELECT * FROM tb_videos WHERE estado = 'pendiente' LIMIT 1");
    $stmt->execute();
    $video = $stmt->fetch();

    if (!$video) {
        return "No hay videos pendientes por procesar.";
    }

    $video_id = $video['cod'];
    $url = $video['url_tiktok'];

    // 1. Marcar como procesando
    $conexion_bd->prepare("UPDATE tb_videos SET estado = 'procesando', progreso = 10, paso_actual = 'Iniciando extracción...' WHERE cod = ?")->execute([$video_id]);

    try {
        // RUTAS ACTUALIZADAS (Nueva Estructura)
        $temp_dir = __DIR__ . "/../temp/";
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
        
        $output_base = $temp_dir . "video_" . $video_id;
        $audio_path = $output_base . ".mp3";
        $json_comments = $output_base . ".json";

        // Buscamos los binarios en la nueva carpeta /bin/
        $bin_dir = realpath(__DIR__ . "/../bin");
        $ytdlp_path = $bin_dir . DIRECTORY_SEPARATOR . "yt-dlp.exe";
        
        if (!file_exists($ytdlp_path)) {
            throw new Exception("No se encontró yt-dlp.exe en la ruta: $ytdlp_path");
        }

        // --- EXTRACCIÓN DE METADATOS ---
        if (!file_exists($json_comments)) {
            $cmd_ytdlp = "\"$ytdlp_path\" --write-comments --print-json --skip-download \"$url\" > \"$json_comments\"";
            exec($cmd_ytdlp, $output_yt, $status_yt);
            $conexion_bd->prepare("UPDATE tb_videos SET progreso = 25, paso_actual = 'Metadatos extraídos' WHERE cod = ?")->execute([$video_id]);
        } else {
            $status_yt = 0;
        }

        if ($status_yt !== 0) throw new Exception("Error al obtener comentarios con yt-dlp.");

        $metadata = json_decode(file_get_contents($json_comments), true);
        $titulo = $metadata['title'] ?? 'TikTok Video';
        $thumbnail = $metadata['thumbnail'] ?? '';
        
        // --- EXTRAER 500 COMENTARIOS CON APIFY ---
        echo "[Apify] Iniciando descarga de 500 comentarios...\n";
        $comments_data = fn_obtener_comentarios_tiktok($url, 500); 
        
        if (isset($comments_data['error'])) {
            echo "[!] Error en Apify: " . $comments_data['error'] . ". Continuando solo con transcripción.\n";
            $comments_data = [];
        } else {
            echo "[Apify] " . count($comments_data) . " comentarios obtenidos con éxito.\n";
        }

        // --- DESCARGAR AUDIO ---
        if (!file_exists($audio_path)) {
            $conexion_bd->prepare("UPDATE tb_videos SET progreso = 40, paso_actual = 'Descargando audio...' WHERE cod = ?")->execute([$video_id]);
            $cmd_audio = "\"$ytdlp_path\" --ffmpeg-location \"$bin_dir\" -x --audio-format mp3 -o \"$audio_path\" \"$url\"";
            exec($cmd_audio, $output_audio, $status_audio);
            $conexion_bd->prepare("UPDATE tb_videos SET progreso = 60, paso_actual = 'Audio descargado' WHERE cod = ?")->execute([$video_id]);
        }

        // --- TRANSCRIPCIÓN (WHISPER) ---
        $txt_path = $output_base . ".txt";
        if (!file_exists($txt_path)) {
            $conexion_bd->prepare("UPDATE tb_videos SET progreso = 75, paso_actual = 'Transcribiendo voz (Whisper)...' WHERE cod = ?")->execute([$video_id]);
            if (file_exists($audio_path)) {
                // Agregar /bin/ al PATH para que Whisper encuentre ffmpeg
                putenv("PATH=" . $bin_dir . ";" . getenv("PATH"));
                
                $cmd_whisper = "python -m whisper \"$audio_path\" --model base --language es --output_format txt --output_dir \"$temp_dir\" 2>&1";
                exec($cmd_whisper, $out_whisper, $status_whisper);
                
                if (!file_exists($txt_path)) {
                    error_log("Error en Whisper para video $video_id. Salida: " . implode("\n", $out_whisper));
                }
            }
        }
        
        $transcription_text = file_exists($txt_path) ? file_get_contents($txt_path) : "(No se pudo generar transcripción - Revisa el log de PHP)";
        $conexion_bd->prepare("UPDATE tb_videos SET progreso = 90, paso_actual = 'Transcripción lista' WHERE cod = ?")->execute([$video_id]);

        // 5. GUARDAR DATOS EN BD
        $conexion_bd->beginTransaction();

        $sql_upd = "UPDATE tb_videos SET titulo = ?, transcripcion = ?, thumbnail = ?, progreso = 90, paso_actual = 'Analizando con IA...' WHERE cod = ?";
        $conexion_bd->prepare($sql_upd)->execute([$titulo, $transcription_text, $thumbnail, $video_id]);

        $sql_comm = "INSERT INTO tb_comentarios (cod_video, usuario, comentario) VALUES (?, ?, ?)";
        $stmt_comm = $conexion_bd->prepare($sql_comm);
        foreach ($comments_data as $c) {
            $stmt_comm->execute([$video_id, $c['author'] ?? 'Anónimo', $c['text']]);
        }

        $conexion_bd->commit();
        return "Video $video_id procesado con éxito.";

    } catch (Exception $e) {
        if ($conexion_bd->inTransaction()) $conexion_bd->rollBack();
        $conexion_bd->prepare("UPDATE tb_videos SET estado = 'error' WHERE cod = ?")->execute([$video_id]);
        return "Error en video $video_id: " . $e->getMessage();
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo fn_procesar_videos_pendientes();
}
?>
