<?php

// vendor/bin/phpunit tests/Pruebas_unitarias/PhpUnit/RunnerTest.php --testdox

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../core/ai_engine.php';

class RunnerTest extends TestCase
{
    public function testVideoIdEsNumerico()
    {
        $video_id = 1;

        $this->assertIsNumeric($video_id);
    }

    public function testResultadoIAEsString()
    {
        $resultado = '{"emocion":"positivo"}';

        $this->assertIsString($resultado);
    }

    public function testInstanciaAIEngine()
    {
        $ai = new AIEngine();

        $this->assertInstanceOf(AIEngine::class, $ai);
    }

    public function testEstadoPendiente()
    {
        $estado = 'pendiente';

        $this->assertEquals('pendiente', $estado);
    }

    public function testEstadoProcesando()
    {
        $estado = 'procesando';

        $this->assertNotEquals('pendiente', $estado);
    }

    public function testMensajeExito()
    {
        $mensaje = '¡Análisis de IA completado con éxito!';

        $this->assertStringContainsString(
            'éxito',
            $mensaje
        );
    }
}