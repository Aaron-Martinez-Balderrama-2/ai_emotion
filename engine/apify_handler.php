<?php
/**
 * apify_handler.php - Controlador de conexión con Apify
 * Se encarga de lanzar el scraper de comentarios y devolver los resultados.
 */

// Ruta actualizada al nuevo orden de carpetas
require_once __DIR__ . '/../core/colossus.php';

/**
 * Obtiene comentarios de TikTok. 
 * AUMENTADO A 500 para análisis profundo de audiencias.
 */
function fn_obtener_comentarios_tiktok($url_video, $max_comments = 500) {
    $token = APIFY_TOKEN;
    $actor_id = "clockworks~tiktok-comments-scraper"; 

    // 1. Lanzar el "Actor" en Apify
    $run_url = "https://api.apify.com/v2/acts/$actor_id/runs?token=$token";
    $payload = json_encode([
        "postURLs" => [$url_video],
        "maxCommentsPerPost" => $max_comments,
        "resultsLimit" => $max_comments # Algunos actores usan este parámetro
    ]);

    $ch = curl_init($run_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $run_data = json_decode($response, true);
    curl_close($ch);

    if (!isset($run_data['data']['id'])) {
        return ["error" => "No se pudo iniciar el scraper en Apify. Respuesta: " . $response];
    }

    $run_id = $run_data['data']['id'];

    // 2. Esperar a que termine (Polling)
    $status_url = "https://api.apify.com/v2/actor-runs/$run_id?token=$token";
    $max_attempts = 40; # Aumentamos intentos porque 500 comentarios tardan un poco más
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        // Log de consola para saber qué pasa
        // echo "   [Apify] Procesando " . $max_comments . " comentarios (Intento " . ($attempt + 1) . "/$max_attempts)...\n";
        
        sleep(5); 
        $ch = curl_init($status_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $status_res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ($status_res['data']['status'] === 'SUCCEEDED') {
            // 3. Descargar los resultados
            $dataset_id = $status_res['data']['defaultDatasetId'];
            $results_url = "https://api.apify.com/v2/datasets/$dataset_id/items?token=$token";
            
            $ch = curl_init($results_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $items = json_decode(curl_exec($ch), true);
            curl_close($ch);

            // Formatear para nuestro sistema
            $comentarios_limpios = [];
            if(is_array($items)) {
                foreach ($items as $item) {
                    $comentarios_limpios[] = [
                        "author" => $item['authorName'] ?? $item['uniqueId'] ?? 'Anónimo',
                        "text" => $item['text'] ?? ''
                    ];
                }
            }
            return $comentarios_limpios;
        }

        if ($status_res['data']['status'] === 'FAILED' || $status_res['data']['status'] === 'ABORTED') {
            return ["error" => "El scraper falló en Apify."];
        }

        $attempt++;
    }

    return ["error" => "Tiempo de espera agotado para Apify."];
}
?>
