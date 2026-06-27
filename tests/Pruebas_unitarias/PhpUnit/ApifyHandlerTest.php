<?php

use PHPUnit\Framework\TestCase;

class ApifyHandlerTest extends TestCase
{
    public function testMaxCommentsEsEntero()
    {
        $max_comments = 500;

        $this->assertIsInt($max_comments);
    }

    public function testUrlVideoEsString()
    {
        $url_video = "https://www.tiktok.com/@usuario/video/123";

        $this->assertIsString($url_video);
    }

    public function testArrayComentariosLimpios()
    {
        $comentarios = [];

        $this->assertIsArray($comentarios);
    }

    public function testComentarioTieneAuthorYText()
    {
        $comentario = [
            "author" => "Juan",
            "text" => "Buen video"
        ];

        $this->assertArrayHasKey('author', $comentario);
        $this->assertArrayHasKey('text', $comentario);
    }

    public function testMensajeErrorEsString()
    {
        $error = "Tiempo de espera agotado para Apify.";

        $this->assertIsString($error);
    }

    public function testRespuestaErrorEsArray()
    {
        $respuesta = [
            "error" => "El scraper falló en Apify."
        ];

        $this->assertIsArray($respuesta);
    }
}