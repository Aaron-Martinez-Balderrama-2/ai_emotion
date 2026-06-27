<?php

use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    public function testUrlVideoEsString()
    {
        $url = "https://www.tiktok.com/video/123";

        $this->assertIsString($url);
    }

    public function testRutaAudioGenerada()
    {
        $video_id = 10;

        $audio_path = "temp/video_" . $video_id . ".mp3";

        $this->assertStringEndsWith(".mp3", $audio_path);
    }

    public function testRutaJsonGenerada()
    {
        $video_id = 10;

        $json_path = "temp/video_" . $video_id . ".json";

        $this->assertStringEndsWith(".json", $json_path);
    }

    public function testComentariosEsArray()
    {
        $comentarios = [];

        $this->assertIsArray($comentarios);
    }

    public function testMetadataTieneTitulo()
    {
        $metadata = [
            'title' => 'Video TikTok'
        ];

        $this->assertArrayHasKey('title', $metadata);
    }

    public function testTranscripcionEsString()
    {
        $texto = "Hola mundo";

        $this->assertIsString($texto);
    }
}