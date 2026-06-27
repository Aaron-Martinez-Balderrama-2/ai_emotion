<?php

// vendor/bin/phpunit tests/Pruebas_unitarias/PhpUnit/ProcessorTest.php --testdox

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../engine/processor.php';

class ProcessorTest extends TestCase
{
    public function testLimpiarRuidoConvierteAMinusculas()
    {
        $texto = "HOLA MUNDO";

        $resultado = fn_limpiar_ruido($texto);

        $this->assertEquals('hola mundo', $resultado);
    }

    public function testLimpiarRuidoEliminaPuntuacion()
    {
        $texto = "Hola!!! Mundo???";

        $resultado = fn_limpiar_ruido($texto);

        $this->assertEquals('hola mundo', $resultado);
    }

    public function testLimpiarRuidoEliminaEspaciosExtras()
    {
        $texto = "Hola     Mundo";

        $resultado = fn_limpiar_ruido($texto);

        $this->assertEquals('hola mundo', $resultado);
    }

    public function testExtraerKeywordsRetornaArray()
    {
        $resultado = fn_extraer_keywords(
            "economia economia politica"
        );

        $this->assertIsArray($resultado);
    }

    public function testExtraerKeywordsCuentaFrecuencias()
    {
        $resultado = fn_extraer_keywords(
            "economia economia politica"
        );

        $this->assertEquals(2, $resultado['economia']);
    }

    public function testExtraerKeywordsEliminaStopwords()
    {
        $resultado = fn_extraer_keywords(
            "la economia de bolivia"
        );

        $this->assertArrayNotHasKey('la', $resultado);
    }
}