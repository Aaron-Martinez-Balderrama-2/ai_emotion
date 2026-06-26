<?php
/**
 * tests/Unit/ProcessorTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 1: Pruebas Unitarias
 *
 * Objetivo: Probar las funciones puras de engine/processor.php de forma
 * totalmente aislada — sin base de datos, sin red, sin dependencias.
 *
 * Patrón: AAA (Arrange · Act · Assert)
 * Doubles usados:
 *   - Ninguno: las funciones son puras (entrada → salida).
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    //  fn_limpiar_ruido()
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function limpiarRuido_eliminaPuntuacionYLowercase(): void
    {
        // Arrange
        $texto_sucio = "Hola, mundo! Este es un TEST... con puntuacion: si o no?";

        // Act
        $resultado = fn_limpiar_ruido($texto_sucio);

        // Assert
        $this->assertStringNotContainsString('!',  $resultado, "Debe eliminar signos de exclamación");
        $this->assertStringNotContainsString(',',  $resultado, "Debe eliminar comas");
        $this->assertStringNotContainsString('?',  $resultado, "Debe eliminar signos de interrogación");
        $this->assertStringNotContainsString(':',  $resultado, "Debe eliminar dos puntos");
        $this->assertEquals(
            strtolower($resultado), $resultado,
            "La salida debe estar en minúsculas"
        );
    }

    /** @test */
    public function limpiarRuido_normalizaEspaciosMultiples(): void
    {
        // Arrange
        $texto_con_espacios = "palabra1    palabra2\t\tpalabra3";

        // Act
        $resultado = fn_limpiar_ruido($texto_con_espacios);

        // Assert
        $this->assertStringNotContainsString(
            '  ', $resultado,
            "No debe haber dos espacios consecutivos después de limpiar"
        );
        $this->assertEquals('palabra1 palabra2 palabra3', trim($resultado));
    }

    /** @test */
    public function limpiarRuido_conTextoVacio_devuelveVacio(): void
    {
        // Arrange
        $texto = "   ";

        // Act
        $resultado = fn_limpiar_ruido($texto);

        // Assert
        $this->assertSame('', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  fn_extraer_keywords()
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function extraerKeywords_ordenaPorFrecuenciaDescendente(): void
    {
        // Arrange
        $texto = "coche casa coche arbol casa coche perro";

        // Act
        $keywords = fn_extraer_keywords($texto, 10);

        // Assert
        $this->assertArrayHasKey('coche', $keywords);
        $this->assertArrayHasKey('casa',  $keywords);
        $this->assertEquals(3, $keywords['coche']);
        $this->assertEquals(2, $keywords['casa']);
        // coche debe aparecer primero (más frecuente)
        $this->assertEquals('coche', array_key_first($keywords));
    }

    /** @test */
    public function extraerKeywords_filtraStopwords(): void
    {
        // Arrange
        // NOTA: str_word_count() en PHP no incluye palabras de 1 letra (como 'y', 'e', 'u').
        // Solo probamos stopwords de 2+ letras que str_word_count sí indexa.
        $texto = "el coche la casa de un perro que los las";

        // Act
        $keywords = fn_extraer_keywords($texto, 10);

        // Assert — stopwords de 2+ letras que sí procesa str_word_count
        foreach (['el', 'la', 'un', 'que', 'los', 'las', 'de'] as $sw) {
            $this->assertArrayNotHasKey($sw, $keywords, "Stopword '$sw' no debe aparecer");
        }
        $this->assertArrayHasKey('coche', $keywords);
        $this->assertArrayHasKey('casa',  $keywords);
        $this->assertArrayHasKey('perro', $keywords);
    }

    /** @test */
    public function extraerKeywords_respetaLimiteDeResultados(): void
    {
        // Arrange
        $texto = "alfa beta gamma delta epsilon zeta eta theta iota kappa lambda";

        // Act
        $keywords = fn_extraer_keywords($texto, 5);

        // Assert
        $this->assertLessThanOrEqual(5, count($keywords));
    }

    /** @test */
    public function extraerKeywords_conTextoVacio_devuelveArrayVacio(): void
    {
        // Arrange
        $texto = "";

        // Act
        $resultado = fn_extraer_keywords($texto);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}
