<?php
/**
 * tests/Integration/AuthIntegrationTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 2: Pruebas de Integración — Autenticación completa con BD
 *
 * Objetivo: Probar el flujo completo de fn_login(), fn_logout() y
 * fn_tiene_permiso() contra la base de datos de pruebas real.
 *
 * Doubles usados:
 *   - STUB de $_SESSION: sobreescribimos el array global de sesión para
 *     controlar el estado sin iniciar sesión HTTP real.
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class AuthIntegrationTest extends TestCase
{
    protected PDO    $db;
    protected int    $perfilId  = 8801;
    protected int    $permisoId = 8802;
    protected string $email     = 'integ_auth@test.com';
    protected string $clave     = 'ClavePrueba_2024!';

    protected function setUp(): void
    {
        $this->db = fn_conexion_bd();
        $this->db->beginTransaction();
        $_SESSION = [];

        // 1. Perfil de prueba
        $this->db->prepare(
            "INSERT INTO tb_perfiles (id, nombre) VALUES (?, ?)"
        )->execute([$this->perfilId, 'Perfil Integración']);

        // 2. Usuario de prueba
        $hash = password_hash($this->clave, PASSWORD_DEFAULT);
        $this->db->prepare(
            "INSERT INTO tb_usuarios (nombre, email, password_hash, id_perfil)
             VALUES (?, ?, ?, ?)"
        )->execute(['Usuario Integración', $this->email, $hash, $this->perfilId]);

        // 3. Permiso
        $this->db->prepare(
            "INSERT INTO tb_permisos (id, llave_permiso, descripcion) VALUES (?, ?, ?)"
        )->execute([$this->permisoId, 'test_integracion_perm', 'Permiso de prueba integración']);

        // 4. Asignar permiso al perfil
        $this->db->prepare(
            "INSERT INTO tb_perfil_permisos (id_perfil, id_permiso) VALUES (?, ?)"
        )->execute([$this->perfilId, $this->permisoId]);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function login_conCredencialesValidas_estableceSesionCorrectamente(): void
    {
        // Act
        $resultado = fn_login($this->email, $this->clave);

        // Assert
        $this->assertTrue($resultado);
        $this->assertArrayHasKey('usuario_id',      $_SESSION);
        $this->assertArrayHasKey('usuario_nombre',  $_SESSION);
        $this->assertArrayHasKey('usuario_email',   $_SESSION);
        $this->assertArrayHasKey('usuario_perfil',  $_SESSION);
        $this->assertEquals($this->email,    $_SESSION['usuario_email']);
        $this->assertEquals($this->perfilId, $_SESSION['usuario_perfil']);
    }

    /** @test */
    public function login_conContrasenaIncorrecta_devuelveFalse(): void
    {
        // Act
        $resultado = fn_login($this->email, 'clave_incorrecta_xyz');

        // Assert
        $this->assertFalse($resultado);
        $this->assertArrayNotHasKey('usuario_id', $_SESSION);
    }

    /** @test */
    public function login_conEmailInexistente_devuelveFalse(): void
    {
        // Act
        $resultado = fn_login('noexiste_nunca@test.com', $this->clave);

        // Assert
        $this->assertFalse($resultado);
        $this->assertArrayNotHasKey('usuario_id', $_SESSION);
    }

    /** @test */
    public function logout_destruyeVariablesDeSesion(): void
    {
        // Arrange — simular sesión activa (STUB de $_SESSION)
        $_SESSION['usuario_id']     = 9999;
        $_SESSION['usuario_nombre'] = 'Test User';
        $_SESSION['usuario_email']  = 'x@x.com';

        // Act
        fn_logout();

        // Assert
        $this->assertEmpty($_SESSION);
    }

    /** @test */
    public function tienePermiso_conPerfilAutorizado_devuelveTrue(): void
    {
        // Arrange — STUB de sesión con el perfil que SÍ tiene el permiso
        $_SESSION['usuario_perfil'] = $this->perfilId;

        // Act
        $resultado = fn_tiene_permiso('test_integracion_perm');

        // Assert
        $this->assertTrue($resultado);
    }

    /** @test */
    public function tienePermiso_conPermisoInexistente_devuelveFalse(): void
    {
        // Arrange — STUB de sesión con perfil válido
        $_SESSION['usuario_perfil'] = $this->perfilId;

        // Act
        $resultado = fn_tiene_permiso('permiso_que_no_existe_xyzabc');

        // Assert
        $this->assertFalse($resultado);
    }
}
