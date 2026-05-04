<?php
/**
 * processor.php - Lógica de pre-procesamiento de texto
 * Limpia el ruido de comentarios y transcripciones antes del análisis de IA.
 */

function fn_limpiar_ruido($texto) {
    // Eliminar emojis, URLs y caracteres especiales innecesarios
    $texto = preg_replace('/[[:punct:]]/', '', $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return strtolower(trim($texto));
}

function fn_extraer_keywords($texto, $limite = 20) {
    $stopwords = ['que', 'el', 'la', 'en', 'de', 'un', 'una', 'con', 'por', 'para', 'los', 'las'];
    $palabras = str_word_count($texto, 1);
    $filtradas = array_diff($palabras, $stopwords);
    $conteo = array_count_values($filtradas);
    arsort($conteo);
    return array_slice($conteo, 0, $limite);
}
?>
