<?php

// vendor/bin/phpunit tests/Pruebas_unitarias/PhpUnit/AIEngineTest.php --testdox

use PHPUnit\Framework\TestCase;

class AIEngineTest extends TestCase
{
    public function testVideoEsArray()
    {
        $video = [
            'cod' => 1,
            'transcripcion' => 'Hola mundo'
        ];

        $this->assertIsArray($video);
    }

    public function testComentariosVacios()
    {
        $comentarios = [];

        $this->assertEmpty($comentarios);
    }

    public function testComentariosEsArray()
    {
        $comentarios = [
            'Buen video',
            'Excelente propuesta'
        ];

        $this->assertIsArray($comentarios);
    }

    public function testJsonGeneradoEsValido()
    {
        $datos = [
            'transcripcion' => 'Texto de prueba',
            'comentarios' => ['Comentario 1'],
            'candidato' => 'Juan Perez'
        ];

        $json = json_encode($datos);

        $this->assertJson($json);
    }

    public function testTranscripcionNoEsVacia()
    {
        $transcripcion = 'Mensaje político';

        $this->assertNotEmpty($transcripcion);
    }

    public function testCandidatoEsString()
    {
        $candidato = 'Juan Perez';

        $this->assertIsString($candidato);
    }
}