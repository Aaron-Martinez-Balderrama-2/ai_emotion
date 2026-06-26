<?php
/**
 * tests/Unit/EmotionAnalyzerUnitTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 1: Pruebas Unitarias — Módulo Python (puente CLI)
 *
 * Objetivo: Probar las funciones Python de emotion_analyzer.py que son puras
 * (sin llamadas a Ollama) invocando el script directamente desde PHP mediante
 * shell_exec con MOCK_OLLAMA=1 y datos JSON de entrada controlados.
 *
 * Doubles usados:
 *   - MOCK de Ollama: variable de entorno MOCK_OLLAMA=1 que hace que el script
 *     Python devuelva una respuesta JSON predefinida en lugar de llamar a Ollama.
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class EmotionAnalyzerUnitTest extends TestCase
{
    private string $tempDir;
    private string $scriptPath;

    protected function setUp(): void
    {
        $this->tempDir    = __DIR__ . '/../../temp/';
        $this->scriptPath = __DIR__ . '/../../python_ia/emotion_analyzer.py';
    }

    /**
     * Ejecuta emotion_analyzer.py con datos JSON de entrada y MOCK_OLLAMA=1.
     */
    private function ejecutarAnalizador(array $datos): array
    {
        $tempFile = $this->tempDir . 'test_unit_' . uniqid() . '.json';
        file_put_contents($tempFile, json_encode($datos, JSON_UNESCAPED_UNICODE));

        // Inyectar MOCK_OLLAMA en el entorno del proceso hijo (Windows compatible)
        putenv('MOCK_OLLAMA=1');

        $cmd    = sprintf('python %s %s 2>&1',
            escapeshellarg($this->scriptPath),
            escapeshellarg($tempFile)
        );
        $salida = shell_exec($cmd);

        // Limpiar variable de entorno
        putenv('MOCK_OLLAMA');

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        $decoded = json_decode($salida, true);
        $this->assertIsArray($decoded, "El script Python debe devolver JSON válido. Salida: $salida");
        return $decoded;
    }


    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function analizador_conComentariosSimples_devuelveNubeDePensamientos(): void
    {
        // Arrange
        $datos = [
            'transcripcion'        => 'El candidato habla sobre educacion y salud publica.',
            'comentarios'          => ['excelente propuesta educativa', 'apoyamos la salud publica', 'educacion primero'],
            'comentarios_con_autor'=> [['autor' => 'juan', 'texto' => 'excelente propuesta educativa']],
            'candidato'            => 'Test Candidato',
            'partido'              => 'Partido Test',
            'diccionario'          => [],
        ];

        // Act
        $resultado = $this->ejecutarAnalizador($datos);

        // Assert
        $this->assertArrayHasKey('nube_pensamientos', $resultado);
        $this->assertArrayHasKey('nodos',    $resultado['nube_pensamientos']);
        $this->assertArrayHasKey('enlaces',  $resultado['nube_pensamientos']);
        $this->assertNotEmpty($resultado['nube_pensamientos']['nodos']);
    }

    /** @test */
    public function analizador_calculaDemografiaConAutores(): void
    {
        // Arrange
        $datos = [
            'transcripcion'         => 'Apoyo a las mujeres en la politica.',
            'comentarios'           => ['bien dicho maria', 'apoyo juan'],
            'comentarios_con_autor' => [
                ['autor' => 'maria_gm', 'texto' => 'bien dicho maria'],
                ['autor' => 'juan_pk',  'texto' => 'apoyo juan'],
            ],
            'candidato'  => '',
            'partido'    => '',
            'diccionario'=> [],
        ];

        // Act
        $resultado = $this->ejecutarAnalizador($datos);

        // Assert
        $this->assertArrayHasKey('demografia', $resultado);
        $dem = $resultado['demografia'];
        $this->assertArrayHasKey('hombres',  $dem);
        $this->assertArrayHasKey('mujeres',  $dem);
        $this->assertArrayHasKey('total_comentarios', $dem);
        $this->assertEquals(2, $dem['total_comentarios']);
        // Los porcentajes deben sumar 100
        $this->assertEquals(100, $dem['hombres'] + $dem['mujeres']);
    }

    /** @test */
    public function analizador_sinArchivo_devuelveErrorJson(): void
    {
        // Arrange — archivo inexistente (caso de error)
        $cmd    = 'set MOCK_OLLAMA=1 && python '
                . escapeshellarg($this->scriptPath)
                . ' archivo_inexistente_99999.json 2>&1';
        $salida = shell_exec($cmd);

        // Act
        $decoded = json_decode($salida, true);

        // Assert
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('error', $decoded);
    }

    /** @test */
    public function analizador_conDetonantes_incluyeListaEnResultado(): void
    {
        // Arrange — la transcripción y los comentarios comparten la palabra "democracia"
        $datos = [
            'transcripcion'         => 'La democracia y la libertad son valores fundamentales.',
            'comentarios'           => ['democracia es lo primero', 'libertad total'],
            'comentarios_con_autor' => [],
            'candidato'             => '',
            'partido'               => '',
            'diccionario'           => [],
        ];

        // Act
        $resultado = $this->ejecutarAnalizador($datos);

        // Assert
        $this->assertArrayHasKey('detonantes', $resultado);
        $this->assertIsArray($resultado['detonantes']);
        // "democracia" aparece en transcripción Y en comentarios → debe ser detonante
        $this->assertContains('democracia', $resultado['detonantes']);
    }
}
