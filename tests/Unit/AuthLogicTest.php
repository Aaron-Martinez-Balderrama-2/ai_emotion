<?php
/**
 * tests/Unit/AuthLogicTest.php
 * ─────────────────────────────────────────────────────────────────────────────
 * NIVEL 1: Pruebas Unitarias — Lógica pura de autenticación
 *
 * Objetivo: Probar las ramas lógicas de fn_tiene_permiso() y las reglas de
 * validación de sesión sin usar la base de datos real.
 *
 * Doubles usados:
 *   - STUB de sesión ($_SESSION): reemplazamos el array global de sesión con
 *     valores controlados para forzar cada rama del código.
 *   - DUMMY: cuando una función necesita un argumento de BD que no se usa
 *     en la rama que estamos probando (p.ej. una llave de permiso vacía).
 *
 * Nota: fn_tiene_permiso() consulta la BD → en este nivel unitario solo
 * probamos las ramas que NO llegan a la consulta (guard clauses).
 * Las ramas con BD se cubren en Integration/AuthIntegrationTest.php.
 * ─────────────────────────────────────────────────────────────────────────────
 */

use PHPUnit\Framework\TestCase;

class AuthLogicTest extends TestCase
{
    protected function setUp(): void
    {
        // Limpiar sesión antes de cada test para aislamiento total
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  fn_tiene_permiso() — Guard clause: sin sesión iniciada
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function tienePermiso_sinSesionIniciada_devuelveFalse(): void
    {
        // Arrange — STUB: sesión vacía (sin usuario_perfil)
        // $_SESSION = [] ya seteado en setUp()
        $dummy_llave = 'cualquier_permiso';   // DUMMY: no importa el valor

        // Act
        $resultado = fn_tiene_permiso($dummy_llave);

        // Assert
        $this->assertFalse($resultado, "Sin sesión activa debe devolver false inmediatamente");
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  fn_limpiar_texto() — Función pura: validación de inputs del sistema
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * @dataProvider proveedorInputsMaliciosos
     *
     * fn_limpiar_texto() usa htmlspecialchars(): convierte < en &lt; y > en &gt;.
     * Esto hace que las etiquetas HTML sean INERTES (el navegador no las ejecuta).
     * El texto como "onerror" puede aparecer como texto escapado, lo cual es seguro.
     */
    public function limpiarTexto_bloqueaInputsMaliciosos(string $input): void
    {
        // Arrange — STUB implícito: input controlado con payload XSS
        // Act
        $salida = fn_limpiar_texto($input);

        // Assert — las etiquetas HTML deben estar ESCAPADAS (inertes)
        // htmlspecialchars convierte '<' en '&lt;' → no hay etiquetas ejecutables
        $this->assertStringNotContainsString('<script',  $salida, 'La etiqueta <script> debe estar escapada');
        $this->assertStringNotContainsString('<img',     $salida, 'La etiqueta <img> debe estar escapada');
        $this->assertStringNotContainsString('<svg',     $salida, 'La etiqueta <svg> debe estar escapada');
        $this->assertStringNotContainsString('<a ',      $salida, 'La etiqueta <a> debe estar escapada');
        // Verificar que la función convierte correctamente los caracteres peligrosos
        if (str_contains($input, '<')) {
            $this->assertStringContainsString('&lt;', $salida, 'El signo < debe estar escapado como &lt;');
        }
    }

    /**
     * Proveedor de datos con payloads XSS comunes para prueba parametrizada.
     */
    public static function proveedorInputsMaliciosos(): array
    {
        return [
            'script_tag'         => ['<script>alert(1)</script>'],
            'img_onerror'        => ['<img src=x onerror=alert(1)>'],
            'javascript_href'    => ['<a href="javascript:void(0)">click</a>'],
            'svg_onload'         => ['<svg onload=alert(document.cookie)>'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  password_verify() — Lógica de verificación de contraseña (pura PHP)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function passwordHash_yVerify_funcionanCorrectamente(): void
    {
        // Arrange
        $password_plano = 'MiClave$egura123';

        // Act
        $hash    = password_hash($password_plano, PASSWORD_DEFAULT);
        $valido  = password_verify($password_plano, $hash);
        $invalido= password_verify('clave_incorrecta', $hash);

        // Assert
        $this->assertTrue($valido,   "password_verify debe retornar true con la clave correcta");
        $this->assertFalse($invalido,"password_verify debe retornar false con clave incorrecta");
    }
}
