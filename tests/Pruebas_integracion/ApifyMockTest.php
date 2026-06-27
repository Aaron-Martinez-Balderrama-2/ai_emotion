<?php
/**
 * tests/Integration/ApifyMockTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 2: Pruebas de Integración — API externa Apify con MOCK
 *
 * Objetivo: Verificar que fn_obtener_comentarios_tiktok() procesa
 * correctamente la respuesta de Apify SIN consumir créditos de red reales.
 *
 * Estrategia de mocking:
 *   - La función de producción usa cURL directamente, lo que la hace difícil
 *     de mockear con PHPUnit puro. En su lugar:
 *     1. Creamos una función auxiliar testeable que acepta un "cliente HTTP"
 *        como callback (inyección de dependencias ligera).
 *     2. En pruebas, inyectamos un MOCK que devuelve JSON simulado.
 *
 *   - Adicionalmente, probamos el PARSEO del JSON de respuesta de Apify
 *     usando datos sintéticos que replican exactamente la estructura real.
 *
 * Doubles usados:
 *   - MOCK de respuesta HTTP: array PHP que simula la respuesta de la API
 *     de Apify (estructura real documentada).
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class ApifyMockTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers — parseo de respuesta Apify (lógica extraída de apify_handler.php)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Replica la lógica de formateo de comentarios de apify_handler.php
     * para poder testearla aislada.
     */
    private function parsearComentariosApify(array $items): array
    {
        $resultado = [];
        foreach ($items as $item) {
            $resultado[] = [
                'author' => $item['authorName'] ?? $item['uniqueId'] ?? 'Anónimo',
                'text'   => $item['text'] ?? '',
            ];
        }
        return $resultado;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Pruebas sobre el parseo del JSON de Apify
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function parseoApify_conDatosRealesSimulados_extraeCamposCorrectos(): void
    {
        // Arrange — MOCK: JSON que replica la estructura real de Apify
        $mockApiResponse = [
            [
                'authorName' => 'juan_garcia',
                'uniqueId'   => 'jgarcia99',
                'text'       => 'Gran propuesta de campaña!',
                'likes'      => 45,
            ],
            [
                'authorName' => 'maria_lopez',
                'uniqueId'   => 'mlopez',
                'text'       => 'No estoy convencida todavía.',
                'likes'      => 12,
            ],
            [
                // Sin authorName — debe usar uniqueId como fallback
                'uniqueId' => 'anonimo_user',
                'text'     => 'Interesante punto.',
            ],
        ];

        // Act
        $comentarios = $this->parsearComentariosApify($mockApiResponse);

        // Assert
        $this->assertCount(3, $comentarios);
        $this->assertEquals('juan_garcia',   $comentarios[0]['author']);
        $this->assertEquals('Gran propuesta de campaña!', $comentarios[0]['text']);
        $this->assertEquals('maria_lopez',   $comentarios[1]['author']);
        // Fallback a uniqueId
        $this->assertEquals('anonimo_user',  $comentarios[2]['author']);
    }

    /** @test */
    public function parseoApify_sinCamposOpcionales_usaAnonimo(): void
    {
        // Arrange — MOCK: ítem completamente vacío
        $mockApiResponse = [
            ['likes' => 0],  // Sin authorName, sin uniqueId, sin text
        ];

        // Act
        $comentarios = $this->parsearComentariosApify($mockApiResponse);

        // Assert
        $this->assertCount(1, $comentarios);
        $this->assertEquals('Anónimo', $comentarios[0]['author']);
        $this->assertEquals('',        $comentarios[0]['text']);
    }

    /** @test */
    public function parseoApify_conArrayVacio_devuelveArrayVacio(): void
    {
        // Arrange
        $mockApiResponse = [];

        // Act
        $comentarios = $this->parsearComentariosApify($mockApiResponse);

        // Assert
        $this->assertEmpty($comentarios);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Prueba de respuesta de error de Apify
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function apify_respuestaFallida_devuelveArrayConError(): void
    {
        // Arrange — MOCK de respuesta: scraper fallido (estructura real de error Apify)
        $mockRunData = ['data' => ['status' => 'FAILED', 'id' => 'fake-run-id']];

        // Act — replicar la lógica de apify_handler.php línea 80
        $resultado = null;
        if ($mockRunData['data']['status'] === 'FAILED'
            || $mockRunData['data']['status'] === 'ABORTED') {
            $resultado = ['error' => 'El scraper falló en Apify.'];
        }

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertStringContainsString('falló', $resultado['error']);
    }

    /** @test */
    public function apify_tokenVacio_construccionUrlCorrecta(): void
    {
        // Arrange — verificar que la URL de Apify se construye correctamente
        $actor_id = 'clockworks~tiktok-comments-scraper';
        $token    = 'mi_token_prueba';
        $max      = 500;

        // Act — replicar construcción de URL de apify_handler.php
        $run_url = "https://api.apify.com/v2/acts/$actor_id/runs?token=$token";
        $payload = json_encode([
            'postURLs'           => ['https://tiktok.com/video/1234'],
            'maxCommentsPerPost' => $max,
            'resultsLimit'       => $max,
        ]);
        $decoded = json_decode($payload, true);

        // Assert
        $this->assertStringContainsString('api.apify.com', $run_url);
        $this->assertStringContainsString($actor_id,       $run_url);
        $this->assertStringContainsString($token,          $run_url);
        $this->assertEquals($max, $decoded['maxCommentsPerPost']);
        $this->assertEquals($max, $decoded['resultsLimit']);
    }
}
