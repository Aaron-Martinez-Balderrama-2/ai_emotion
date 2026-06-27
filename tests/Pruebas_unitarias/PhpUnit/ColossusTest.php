<?php

// vendor/bin/phpunit tests/Pruebas_unitarias/PhpUnit/ColossusTest.php --testdox

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../core/colossus.php';

class ColossusTest extends TestCase
{
    public function testEliminarEspacios()
    {
        $resultado = fn_limpiar_texto('   hola mundo   ');

        $this->assertEquals('hola mundo', $resultado);
    }

    public function testEscaparHtml()
    {
        $resultado = fn_limpiar_texto('<script>alert("x")</script>');

        $this->assertStringContainsString('&lt;script&gt;', $resultado);
    }

    public function testTextoNormal()
    {
        $resultado = fn_limpiar_texto('Antigravity AI');

        $this->assertEquals('Antigravity AI', $resultado);
    }

    public function testTextoVacio()
    {
        $resultado = fn_limpiar_texto('');

        $this->assertEmpty($resultado);
    }

    public function testComillasConvertidas()
    {
        $resultado = fn_limpiar_texto('"admin"');

        $this->assertStringContainsString('&quot;', $resultado);
    }
}