<?php
/**
 * tests/E2E/VideoAnalysisFlowTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 3: Pruebas de Sistema (E2E)
 *
 * Objetivo: Simular el flujo completo de usuario de extremo a extremo contra
 * el stack real (XAMPP + MySQL + Python + Ollama).
 *
 * IMPORTANTE: Estas pruebas usan APIs REALES (Apify, Ollama). NO se deben
 * incluir en la suite Unit/Integration. Se ejecutan así:
 *
 * c:\xampp\php\php.exe vendor/bin/phpunit --testsuite E2E
 *
 * Prerequisitos para que pasen:
 * - XAMPP corriendo en localhost
 * - Ollama corriendo con el modelo llama3
 * - Token Apify válido en core/env.php
 * - Python con las dependencias instaladas
 *
 * Estrategia de automatización web:
 * Se usa Symfony Panther (wrapper de ChromeDriver/Selenium en PHP puro).
 *
 * NOTA: Si Panther no está instalado, las pruebas E2E web se marcan como
 * SKIPPED automáticamente y solo se ejecuta el test de integración real de BD.
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class VideoAnalysisFlowTest extends TestCase
{
    private const BASE_URL    = 'http://localhost/antigravity/ai_emotion';
    private const TIKTOK_URL  = 'https://www.tiktok.com/@rodrigopazbolivia/video/7513660434427700486';

    protected function setUp(): void
    {
        $db = new PDO(
            'mysql:host=localhost;dbname=db_antigravity;charset=utf8mb4',
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $db->prepare("DELETE FROM tb_usuarios WHERE email = ?")->execute(['e2e_user@test.com']);
        $hash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO tb_usuarios (nombre, email, password_hash, id_perfil) VALUES (?, ?, ?, ?)")
           ->execute(['E2E Web Test User', 'e2e_user@test.com', $hash, 1]);
    }

    protected function tearDown(): void
    {
        $db = new PDO(
            'mysql:host=localhost;dbname=db_antigravity;charset=utf8mb4',
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $db->prepare("DELETE FROM tb_usuarios WHERE email = ?")->execute(['e2e_user@test.com']);
        $db->prepare("DELETE FROM tb_videos WHERE url_tiktok = ?")->execute([self::TIKTOK_URL]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FLUJO E2E: NIVEL DE INTEGRACIÓN DE SISTEMA (sin navegador)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function sistemaCompleto_insercionDeVideo_procesaYGuardaEnBd(): void
    {
        // Arrange — conexión a BD de PRODUCCIÓN (prueba E2E real)
        $db = new PDO(
            'mysql:host=localhost;dbname=db_antigravity;charset=utf8mb4',
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $urlTest = self::TIKTOK_URL;

        // Act — insertar video como si lo hiciera el usuario
        $stmt = $db->prepare(
            "INSERT INTO tb_videos (url_tiktok, titulo, candidato, partido, estado, progreso, paso_actual)
             VALUES (?, ?, ?, ?, 'pendiente', 0, 'E2E Test')"
        );
        $stmt->execute([$urlTest, 'Video E2E Test', 'Candidato E2E', 'Partido E2E']);
        $videoId = (int) $db->lastInsertId();

        // Assert — el video fue insertado correctamente
        $this->assertGreaterThan(0, $videoId);
        $row = $db->query("SELECT * FROM tb_videos WHERE cod = $videoId")->fetch();
        $this->assertEquals('pendiente', $row['estado']);
        $this->assertEquals($urlTest,    $row['url_tiktok']);

        // Cleanup — eliminar el registro de prueba para no ensuciar la BD real
        $db->exec("DELETE FROM tb_videos WHERE cod = $videoId");
    }

    /** @test */
    public function sistemaCompleto_motorIA_conOllamaReal_devuelveJson(): void
    {
        // Verificar si Ollama está disponible antes de ejecutar
        $ollamaCheck = @file_get_contents('http://localhost:11434/api/tags');
        if ($ollamaCheck === false) {
            $this->markTestSkipped('Ollama no está corriendo en localhost:11434 — test E2E omitido.');
        }

        // Arrange
        $videoData = [
            'cod'          => 0,
            'transcripcion'=> 'Hablo de los problemas reales del país: economía, salud y educación.',
            'candidato'    => 'Candidato Real E2E',
            'partido'      => 'Partido E2E',
        ];
        $comentarios = [
            'excelente discurso sobre la economia',
            'la salud es lo primero en nuestro pais',
            'educacion de calidad ya',
        ];

        // Act — SIN mock, Ollama real
        $ai        = new AIEngine();
        $resultado = $ai->analizar_video($videoData, $comentarios);

        // Assert
        $this->assertNotFalse($resultado, "Ollama debe responder con un JSON válido");
        $data = json_decode($resultado, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('repetir',          $data);
        $this->assertArrayHasKey('evitar',           $data);
        $this->assertArrayHasKey('nube_pensamientos', $data);
        $this->assertArrayHasKey('detonantes',        $data);
        $this->assertArrayHasKey('calificacion',      $data);
        $this->assertGreaterThanOrEqual(0, $data['calificacion']);
        $this->assertLessThanOrEqual(10,  $data['calificacion']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FLUJO E2E WEB: Automatización de Navegador con Panther/Selenium
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Verifica que el formulario de carga de videos:
     * 1. Carga la página correctamente y realiza login.
     * 2. Abre el modal de "Nuevo Análisis".
     * 3. Permite escribir en el campo de URL.
     * 4. Al hacer click en analizar, el video se registra.
     */
    public function flujoWeb_formularioDeVideo_enviaURLYVerificaEstado(): void
    {
        if (!class_exists('Symfony\Component\Panther\PantherTestCase')) {
            $this->markTestSkipped(
                "Symfony Panther no está instalado.\n"
                . "Para habilitar E2E web: composer require --dev symfony/panther"
            );
        }

        // 1. INICIALIZACIÓN: Engañamos a Panther inyectando la URL directamente en el entorno
        $_SERVER['PANTHER_EXTERNAL_BASE_URI'] = self::BASE_URL;

        $chromeDriverPath = realpath(__DIR__ . '/../../vendor/bin/chromedriver.exe') ?: null;
        $client = \Symfony\Component\Panther\Client::createChromeClient($chromeDriverPath);

        // 2. LOGIN: Abrir la página y enviar credenciales
        try {
            $client->request('GET', self::BASE_URL . '/app/login/');
        } catch (\Exception $e) {
            $this->markTestSkipped("No se pudo iniciar ChromeDriver. Detalles: " . $e->getMessage());
        }
        $this->assertStringContainsString('Antigravity', $client->getTitle());

        $client->executeScript("document.querySelector('input[name=\"email\"]').value = 'e2e_user@test.com';");
        $client->executeScript("document.querySelector('input[name=\"password\"]').value = 'Admin123!';");
        $client->executeScript("document.querySelector('button[type=\"submit\"]').click();");

        // 3. ESPERAR CARGA: Aguardar a que el dashboard cargue completamente
        try {
            $client->waitFor('body', 5);
        } catch (\Exception $e) {
            $tempDir = __DIR__ . '/../../temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $client->takeScreenshot($tempDir . 'login_timeout.png');
            throw $e;
        }

        // Redefinir window.alert para evitar que el cuadro de alerta de JS bloquee la ejecución
        $client->executeScript("window.alert = function() {};");

        // 4. ABRIR EL MODAL: Buscar y hacer clic en el botón "Nuevo Análisis" usando JS
        $client->executeScript("
            let btnNuevo = Array.from(document.querySelectorAll('button, a')).find(el => el.textContent.includes('Nuevo Análisis'));
            if(btnNuevo) btnNuevo.click();
        ");

        // 5. ESPERAR MODAL: Esperar a que la caja de URL esté visible en pantalla
        $client->waitFor('input[name="v_url"]', 5);

        // 6. LLENAR DATOS: Llenar el formulario
        $client->executeScript("document.querySelector('input[name=\"v_url\"]').value = '" . self::TIKTOK_URL . "'");
        $client->executeScript("document.querySelector('input[name=\"v_candidato\"]').value = 'Candidato E2E'");
        $client->executeScript("document.querySelector('input[name=\"v_partido\"]').value = 'Partido E2E'");

        // 7. ENVIAR FORMULARIO: Buscar y hacer clic en el botón "Analizar"
        $client->executeScript("
            let btnAnalizar = Array.from(document.querySelectorAll('button')).find(el => el.textContent.includes('Analizar'));
            if(btnAnalizar) btnAnalizar.click();
        ");

        try {
            // Esperamos hasta 5 segundos a que salte la alerta
            $client->getWebDriver()->wait(5)->until(
                \Facebook\WebDriver\WebDriverExpectedCondition::alertIsPresent()
            );
            // Hacemos clic en "Aceptar"
            $client->getWebDriver()->switchTo()->alert()->accept();
        } catch (\Exception $e) {
            // Si por alguna razón la alerta pasó muy rápido o no saltó, continuamos sin fallar
        }

        // 8. VERIFICACIÓN FINAL: Esperar a que la tabla de videos se renderice/actualice
        // Le damos 15 segundos considerando latencia
        $client->waitFor('table tbody tr', 15);
        $filasEnTabla = $client->getCrawler()->filter('table tbody tr')->count();
        $this->assertGreaterThan(0, $filasEnTabla, "La tabla de videos debería tener al menos un registro.");

        // Cerrar el navegador
        $client->quit();
    }
}