<?php
/**
 * tests/Integration/AIEngineTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 2: Pruebas de Integración — Puente PHP → Python (emotion_analyzer.py)
 *
 * Objetivo: Verificar que AIEngine::analizar_video() pasa correctamente los
 * argumentos al script Python via CLI y procesa la respuesta JSON que devuelve.
 *
 * Doubles usados:
 *   - MOCK de Ollama: variable de entorno MOCK_OLLAMA=1 inyectada en el proceso
 *     hijo (Python) para evitar llamadas al modelo local.
 *   - STUB de video_id: se pasa un array de datos en lugar de un ID real de BD,
 *     usando la firma alternativa de analizar_video($video_data_array, $comentarios).
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class AIEngineTest extends TestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        $this->db = fn_conexion_bd();
        $this->db->beginTransaction();
        // Activar mock de Ollama para este proceso
        putenv('MOCK_OLLAMA=1');
    }

    protected function tearDown(): void
    {
        putenv('MOCK_OLLAMA');   // limpiar variable de entorno
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function analizarVideo_conDatosManuales_devuelveJsonValido(): void
    {
        // Arrange
        $videoData = [
            'cod'          => 9999,
            'transcripcion'=> 'El candidato habla sobre salud y educacion publica en el pais.',
            'candidato'    => 'Juan Pérez',
            'partido'      => 'Partido Ejemplo',
        ];
        $comentarios = [
            'excelente propuesta de salud',
            'necesitamos mas educacion publica',
            'apoyamos la propuesta',
        ];

        // Act — putenv('MOCK_OLLAMA=1') ya seteado en setUp
        $ai        = new AIEngine();
        $resultado = $ai->analizar_video($videoData, $comentarios);

        // Assert
        $this->assertNotFalse($resultado, "El motor IA debe devolver un resultado, no false");
        $data = json_decode($resultado, true);
        $this->assertIsArray($data, "El resultado debe ser JSON válido decodificable");
    }

    /** @test */
    public function analizarVideo_resultado_contieneNubeDePensamientos(): void
    {
        // Arrange
        $videoData   = [
            'cod'          => 9998,
            'transcripcion'=> 'La politica exterior y el comercio internacional son prioridad.',
            'candidato'    => '',
            'partido'      => '',
        ];
        $comentarios = ['comercio beneficia', 'politica exterior importante', 'exportaciones crecen'];

        // Act
        $ai   = new AIEngine();
        $data = json_decode($ai->analizar_video($videoData, $comentarios), true);

        // Assert — la nube de pensamientos es calculada por Python, no por Ollama
        $this->assertArrayHasKey('nube_pensamientos', $data);
        $this->assertArrayHasKey('nodos',    $data['nube_pensamientos']);
        $this->assertArrayHasKey('enlaces',  $data['nube_pensamientos']);
        $this->assertNotEmpty($data['nube_pensamientos']['nodos']);
    }

    /** @test */
    public function analizarVideo_resultado_contieneDetonantes(): void
    {
        // Arrange — "democracia" aparece en transcripción Y comentarios
        $videoData   = [
            'cod'          => 9997,
            'transcripcion'=> 'La democracia y la libertad son valores fundamentales.',
            'candidato'    => '',
            'partido'      => '',
        ];
        $comentarios = ['democracia es esencial para todos', 'libertad de expresion'];

        // Act
        $ai   = new AIEngine();
        $data = json_decode($ai->analizar_video($videoData, $comentarios), true);

        // Assert
        $this->assertArrayHasKey('detonantes', $data);
        $this->assertIsArray($data['detonantes']);
        $this->assertContains('democracia', $data['detonantes']);
    }

    /** @test */
    public function analizarVideo_conVideoIdArray_noIntentaEscribirEnBd(): void
    {
        // Arrange — cuando se pasa array como primer argumento, NO debe
        // intentar hacer UPDATE en la BD (solo analiza y devuelve)
        $videoData   = ['cod' => 0, 'transcripcion' => 'Test simple.', 'candidato' => '', 'partido' => ''];
        $comentarios = ['comentario uno', 'comentario dos'];

        // Act
        $ai        = new AIEngine();
        $resultado = $ai->analizar_video($videoData, $comentarios);

        // Assert — simplemente no debe fallar (cod=0 no existe en BD pero tampoco lo intentará)
        $this->assertNotFalse($resultado);
    }
}
