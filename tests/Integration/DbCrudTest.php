<?php
/**
 * tests/Integration/DbCrudTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 2: Pruebas de Integración — CRUD en base de datos
 *
 * Objetivo: Verificar la interacción real entre el código PHP y la BD de
 * pruebas (db_antigravity_test). Cubre:
 *   - Creación exitosa de registros (Happy path)
 *   - Captura de excepciones PDO por columnas inválidas (Unhappy path)
 *   - Relaciones de FK entre tablas (videos → comentarios)
 *   - Validez de datos del diccionario de sentimientos
 *
 * Estrategia de aislamiento:
 *   Cada test se envuelve en una TRANSACCIÓN que se revierte en tearDown()
 *   → la BD de pruebas regresa a su estado inicial después de cada test.
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class DbCrudTest extends TestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        $this->db = fn_conexion_bd();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  tb_usuarios
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function crearUsuario_insercionExitosa_loPuedoRecuperar(): void
    {
        // Arrange
        $nombre  = 'Usuario Integración';
        $email   = 'integracion@test.com';
        $hash    = password_hash('clave123', PASSWORD_DEFAULT);
        $perfil  = 1;

        // Act
        $stmt = $this->db->prepare(
            "INSERT INTO tb_usuarios (nombre, email, password_hash, id_perfil)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nombre, $email, $hash, $perfil]);
        $id = (int) $this->db->lastInsertId();

        // Assert
        $this->assertGreaterThan(0, $id);
        $row = $this->db
            ->query("SELECT * FROM tb_usuarios WHERE id = $id")
            ->fetch();
        $this->assertEquals($nombre, $row['nombre']);
        $this->assertEquals($email,  $row['email']);
        $this->assertTrue(password_verify('clave123', $row['password_hash']));
    }

    /**
     * @test
     * Caso NEGATIVO: Columna inexistente → PDO debe lanzar excepción.
     * (Equivalente al ejemplo del video guía: cambiar el campo correcto por uno falso)
     */
    public function crearUsuario_columnaInvalida_lanzaPdoException(): void
    {
        // Assert primero (patrón expectException)
        $this->expectException(PDOException::class);

        // Arrange + Act — columna "nombre_mal_escrito" NO existe en tb_usuarios
        $stmt = $this->db->prepare(
            "INSERT INTO tb_usuarios (nombre_mal_escrito, email, password_hash, id_perfil)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute(['Test', 'error@test.com', 'hash', 1]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  tb_videos + tb_comentarios (relación FK)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function crearVideoConComentarios_relacionFK_esCorrecto(): void
    {
        // Arrange
        $stmtV = $this->db->prepare(
            "INSERT INTO tb_videos (url_tiktok, titulo, candidato, partido, estado, progreso, paso_actual)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtV->execute([
            'https://www.tiktok.com/@test/video/999',
            'Video de Prueba Integración',
            'Candidato Test',
            'Partido Test',
            'pendiente', 0, 'En cola'
        ]);
        $videoCod = (int) $this->db->lastInsertId();
        $this->assertGreaterThan(0, $videoCod);

        // Act — insertar comentarios asociados
        $stmtC = $this->db->prepare(
            "INSERT INTO tb_comentarios (cod_video, comentario, usuario, sentimiento, puntuacion)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmtC->execute([$videoCod, 'Excelente propuesta!', 'user_a', 'positivo', 10]);
        $stmtC->execute([$videoCod, 'No estoy de acuerdo.',  'user_b', 'negativo', -5]);

        // Assert
        $comentarios = $this->db
            ->query("SELECT * FROM tb_comentarios WHERE cod_video = $videoCod")
            ->fetchAll();

        $this->assertCount(2, $comentarios);
        $this->assertEquals('Excelente propuesta!', $comentarios[0]['comentario']);
        $this->assertEquals('positivo',             $comentarios[0]['sentimiento']);
        $this->assertEquals(-5,                     $comentarios[1]['puntuacion']);
    }

    /** @test */
    public function actualizarEstadoVideo_refleja_cambioEnBd(): void
    {
        // Arrange — insertar video pendiente
        $this->db->prepare(
            "INSERT INTO tb_videos (url_tiktok, titulo, candidato, partido, estado, progreso, paso_actual)
             VALUES ('https://tiktok.com/x','T','C','P','pendiente',0,'En cola')"
        )->execute();
        $id = (int) $this->db->lastInsertId();

        // Act — actualizar a 'completado'
        $this->db->prepare(
            "UPDATE tb_videos SET estado = 'completado', progreso = 100 WHERE cod = ?"
        )->execute([$id]);

        // Assert
        $row = $this->db->query("SELECT estado, progreso FROM tb_videos WHERE cod = $id")->fetch();
        $this->assertEquals('completado', $row['estado']);
        $this->assertEquals(100,          $row['progreso']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  tb_diccionario
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function diccionario_tieneRegistros_conCamposObligatorios(): void
    {
        // Act
        $filas = $this->db
            ->query("SELECT palabra, categoria, peso FROM tb_diccionario LIMIT 10")
            ->fetchAll();

        // Assert
        $this->assertNotEmpty($filas, "El diccionario debe tener registros de semilla");
        foreach ($filas as $fila) {
            $this->assertArrayHasKey('palabra',   $fila);
            $this->assertArrayHasKey('categoria', $fila);
            $this->assertArrayHasKey('peso',      $fila);
            $this->assertContains($fila['categoria'], ['buena', 'mala', 'neutra'],
                "La categoría debe ser 'buena', 'mala' o 'neutra'"
            );
        }
    }
}
