<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../core/auth.php';

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testUsuarioSinPerfilNoTienePermiso()
    {
        $resultado = fn_tiene_permiso('usuarios.ver');

        $this->assertFalse($resultado);
    }

    public function testPasswordHashValido()
    {
        $hash = password_hash('123456', PASSWORD_DEFAULT);

        $this->assertTrue(
            password_verify('123456', $hash)
        );
    }

    public function testPasswordHashInvalido()
    {
        $hash = password_hash('123456', PASSWORD_DEFAULT);

        $this->assertFalse(
            password_verify('admin', $hash)
        );
    }

    public function testSesionVaciaAlIniciar()
    {
        $this->assertEmpty($_SESSION);
    }
}