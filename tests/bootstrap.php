<?php
/**
 * tests/bootstrap.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Bootstrap para PHPUnit – Pirámide de Pruebas – ai_emotion
 *
 * Responsabilidades:
 *  1. Definir las constantes de BD apuntando a db_antigravity_TEST (aislamiento).
 *  2. Crear y resetear la BD de pruebas antes de cada suite.
 *  3. Cargar el autoloader de vendor y los archivos del core.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── 1. Variables de entorno de pruebas ───────────────────────────────────────
define('DB_HOST',      'localhost');
define('DB_NAME',      'db_antigravity_test');  // BD AISLADA – nunca toca producción
define('DB_USER',      'root');
define('DB_PASS',      '');
define('APIFY_TOKEN',  'FAKE_TOKEN_PARA_TESTS');  // mock – no llama a Apify real
define('PHPUNIT_RUNNING', true);                   // flag para detectar entorno test

// ── 1b. Silenciar warnings de sesión HTTP en entorno CLI ─────────────────────
// PHP lanza warnings de headers/session en CLI porque no existe contexto HTTP.
// Esto no afecta la lógica de los tests.
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// ── 2. Suprimir salida HTML del core ─────────────────────────────────────────
ob_start();

// ── 3. Autoloader de vendor ───────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

// ── 4. Archivos del core (sin re-definir constantes ya seteadas arriba) ───────
require_once __DIR__ . '/../core/colossus.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/ai_engine.php';
require_once __DIR__ . '/../engine/processor.php';
require_once __DIR__ . '/../engine/apify_handler.php';
// extractor.php requiere herramientas externas (yt-dlp/Whisper) — no se carga en tests

ob_end_clean();

// ── 5. Crear y poblar db_antigravity_test ────────────────────────────────────
try {
    // Conexión root sin BD para crearla si no existe
    $pdo_root = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo_root->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_root->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME
        . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Conectar a la BD de pruebas
    $pdo_test = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS
    );
    $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Limpiar tablas anteriores para garantizar estado limpio
    $pdo_test->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($pdo_test->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $pdo_test->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo_test->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Importar esquema + datos semilla desde db_antigravity.sql
    $sql_file = __DIR__ . '/../db_antigravity.sql';
    if (!file_exists($sql_file)) {
        throw new \RuntimeException("No se encontró db_antigravity.sql en: $sql_file");
    }
    $sql_raw  = file_get_contents($sql_file);
    // Detectar UTF-16 y convertir si es necesario
    if (str_starts_with($sql_raw, "\xFF\xFE") || str_starts_with($sql_raw, "\xFE\xFF")) {
        $sql_raw = mb_convert_encoding($sql_raw, 'UTF-8', 'UTF-16');
    }

    // Dividir en sentencias y ejecutar
    $delimiter = ';';
    $buffer    = '';
    foreach (preg_split('/\r\n|\n/', $sql_raw) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (str_ends_with(rtrim($line), $delimiter)) {
            try {
                $pdo_test->exec($buffer);
            } catch (\PDOException $ignored) {
                // Ignorar SET de variables MySQL no soportadas en versiones antiguas
            }
            $buffer = '';
        }
    }

    echo "[Bootstrap] ✓ db_antigravity_test inicializada correctamente.\n";

} catch (\Exception $e) {
    echo "[Bootstrap] ✗ Error al inicializar BD de pruebas: " . $e->getMessage() . "\n";
    exit(1);
}
