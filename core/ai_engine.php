<?php
/**
 * ai_engine.php - Puente con la IA Local (Ollama) a través de Python.
 * Se encarga de enviar la transcripción y comentarios para el análisis profundo.
 */

require_once __DIR__ . '/colossus.php';

class AIEngine {
    /**
     * Realiza el análisis completo de un video usando el puente de Python.
     * SOPORTA HASTA 500 COMENTARIOS PARA UN ANÁLISIS REAL.
     */
    public function analizar_video($video_id, $comentarios_manuales = null, $transcripcion_manual = null) {
        $db = fn_conexion_bd();
        
        // 1. Obtener datos
        if (is_array($video_id)) {
            $video = $video_id;
            $comentarios = $comentarios_manuales ?? [];
            $comentarios_con_autor = [];
        } else {
            $stmt_v = $db->prepare("SELECT * FROM tb_videos WHERE cod = ?");
            $stmt_v->execute([$video_id]);
            $video = $stmt_v->fetch();

            $stmt_c = $db->prepare("SELECT usuario, comentario FROM tb_comentarios WHERE cod_video = ? LIMIT 500");
            $stmt_c->execute([$video_id]);
            $rows = $stmt_c->fetchAll();
            $comentarios = array_column($rows, 'comentario');
            $comentarios_con_autor = [];
            foreach ($rows as $r) {
                $comentarios_con_autor[] = ['autor' => $r['usuario'], 'texto' => $r['comentario']];
            }
        }

        if (!$video) return false;

        // 2. Preparar archivo JSON temporal (Para evitar límites de ARG_MAX en Windows)
        $temp_dir = __DIR__ . '/../temp/';
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
        
        $temp_file = $temp_dir . 'ai_input_' . ($video['cod'] ?? 'manual') . '_' . time() . '.json';
        
        // Cargar diccionario para la IA
        $stmt_dic = $db->query("SELECT palabra, categoria, peso FROM tb_diccionario");
        $diccionario = $stmt_dic->fetchAll(PDO::FETCH_ASSOC);

        $data_to_send = [
            'transcripcion' => $video['transcripcion'] ?? '',
            'comentarios' => $comentarios,
            'comentarios_con_autor' => $comentarios_con_autor,
            'candidato' => $video['candidato'] ?? '',
            'partido' => $video['partido'] ?? '',
            'diccionario' => $diccionario
        ];
        
        file_put_contents($temp_file, json_encode($data_to_send, JSON_UNESCAPED_UNICODE));

        // 3. Ejecutar el puente de Python
        $python_path = "python"; 
        $script_path = __DIR__ . '/../python_ia/emotion_analyzer.py';
        
        // Redirigimos stderr a stdout para capturar errores de Python
        $cmd = sprintf('%s %s %s 2>&1', 
            $python_path, 
            escapeshellarg($script_path), 
            escapeshellarg($temp_file)
        );

        $resultado_ia = shell_exec($cmd);

        // Limpieza: Borrar archivo temporal
        if (file_exists($temp_file)) unlink($temp_file);

        if ($resultado_ia) {
            // Intentar detectar si lo devuelto es JSON válido
            $json_test = json_decode($resultado_ia, true);
            if ($json_test) {
                if (!is_array($video_id) && $video_id > 0) {
                    $stmt = $db->prepare("UPDATE tb_videos SET analisis_ia = ?, estado = 'completado', progreso = 100, paso_actual = 'Finalizado' WHERE cod = ?");
                    $stmt->execute([$resultado_ia, $video_id]);
                }
                return $resultado_ia;
            } else {
                // Si no es JSON, probablemente sea un error de Python capturado
                error_log("Error en IA Engine (Python Output): " . $resultado_ia);
                if (!is_array($video_id) && $video_id > 0) {
                    $db->prepare("UPDATE tb_videos SET estado = 'error', paso_actual = 'Error en IA' WHERE cod = ?")->execute([$video_id]);
                }
            }
        } else {
            if (!is_array($video_id) && $video_id > 0) {
                $db->prepare("UPDATE tb_videos SET estado = 'error', paso_actual = 'Ollama no respondió' WHERE cod = ?")->execute([$video_id]);
            }
        }

        return false;
    }
}
?>

