<?php
/**
 * tests/Unit/ColossusTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 1: Pruebas Unitarias
 *
 * Objetivo: Probar las funciones puras de core/colossus.php de forma aislada.
 *
 * Patrón: AAA (Arrange · Act · Assert)
 * Doubles usados:
 *   - Ninguno para fn_limpiar_texto (función pura).
 *   - La prueba de fn_conexion_bd usa la BD de pruebas (es un test de contrato
 *     mínimo: "el objeto PDO devuelto tiene los atributos correctos").
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class ColossusTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    //  fn_limpiar_texto() — función pura, sin dependencias
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function limpiarTexto_escapaHtmlYRecortaEspacios(): void
    {
        // Arrange
        $entrada = "  <script>alert('xss')</script> & texto  ";

        // Act
        $salida = fn_limpiar_texto($entrada);

        // Assert
        $this->assertStringNotContainsString('<script>', $salida);
        $this->assertStringNotContainsString("'",        $salida);
        $this->assertStringContainsString('&amp;',      $salida);
        $this->assertStringContainsString('&lt;script&gt;', $salida);
        // Sin espacios al inicio ni al final
        $this->assertEquals(trim($salida), $salida);
    }

    /** @test */
    public function limpiarTexto_conTextoNormal_noModificaContenido(): void
    {
        // Arrange
        $entrada = "Texto normal sin HTML";

        // Act
        $salida = fn_limpiar_texto($entrada);

        // Assert
        $this->assertEquals("Texto normal sin HTML", $salida);
    }

    /** @test */
    public function limpiarTexto_escapaComillasDobles(): void
    {
        // Arrange
        $entrada = 'Atributo con "comillas dobles"';

        // Act
        $salida = fn_limpiar_texto($entrada);

        // Assert
        $this->assertStringNotContainsString('"', $salida);
        $this->assertStringContainsString('&quot;', $salida);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  fn_conexion_bd() — contrato mínimo (PDO con atributos correctos)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function conexionBd_devuelveInstanciaPdo(): void
    {
        // Arrange — usa constantes de bootstrap.php (DB de pruebas)
        // Act
        $db = fn_conexion_bd();

        // Assert
        $this->assertInstanceOf(PDO::class, $db);
    }

    /** @test */
    public function conexionBd_tieneErrorModeException(): void
    {
        // Arrange
        $db = fn_conexion_bd();

        // Act
        $errorMode = $db->getAttribute(PDO::ATTR_ERRMODE);

        // Assert
        $this->assertEquals(
            PDO::ERRMODE_EXCEPTION, $errorMode,
            "La conexión debe lanzar excepciones en errores PDO"
        );
    }

    /** @test */
    public function conexionBd_esSingleton_mismaInstancia(): void
    {
        // Arrange + Act
        $db1 = fn_conexion_bd();
        $db2 = fn_conexion_bd();

        // Assert — verifica patrón Singleton (misma referencia de objeto)
        $this->assertSame($db1, $db2, "fn_conexion_bd debe retornar siempre la misma instancia");
    }
}
