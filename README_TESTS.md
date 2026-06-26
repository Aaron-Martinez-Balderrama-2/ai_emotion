# 🧪 Guía de Ejecución de Pruebas unitarias, de integración y E2E
### Proyecto: ai_emotion | Ingeniería de Calidad

Esta guía contiene los comandos exactos y las instrucciones paso a paso para ejecutar las pruebas del proyecto `ai_emotion` utilizando **PHPUnit**. Está estructurada para permitir la ejecución tanto de suites completas como de archivos individuales.

---

> [!IMPORTANT]
> **Prerrequisito Obligatorio:**
> Debes tener **Apache** y **MySQL** activos en tu panel de control de **XAMPP** antes de ejecutar cualquier prueba.

---

## ⚙️ Paso 1: Abrir la terminal en la raíz del proyecto
1. Abre tu terminal de comandos (CMD o PowerShell).
2. Navega al directorio del proyecto:
   ```cmd
   cd c:\xampp\htdocs\antigravity\ai_emotion
   ```

---

## 🔺 Nivel 1: Pruebas Unitarias (Unit Testing)
Prueban funciones puras del código en completo aislamiento, sin tocar la base de datos ni realizar llamadas a APIs externas.

* **Ejecutar toda la suite unitaria:**
  ```cmd
  c:\xampp\php\php.exe vendor/bin/phpunit --testsuite Unit --verbose
  ```

* **Ejecutar archivos individuales:**

  | Archivo de Prueba | Componente que Evalúa | Comando de Ejecución |
  | :--- | :--- | :--- |
  | **AuthLogicTest.php** | Lógica de seguridad y permisos | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/AuthLogicTest.php` |
  | **ProcessorTest.php** | Limpieza de textos y palabras clave | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/ProcessorTest.php` |
  | **ColossusTest.php** | Conexión PDO Singleton y sanitización | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/ColossusTest.php` |
  | **EmotionAnalyzerUnitTest.php** | Simulación de IA (Python Mock) | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/EmotionAnalyzerUnitTest.php` |

---

## 🔺🔺 Nivel 2: Pruebas de Integración (Integration Testing)
Prueban la comunicación entre los scripts PHP y la base de datos real. Utilizan de forma aislada la base de datos de pruebas `db_antigravity_test`.

* **Ejecutar toda la suite de integración:**
  ```cmd
  c:\xampp\php\php.exe vendor/bin/phpunit --testsuite Integration --verbose
  ```

* **Ejecutar archivos individuales:**

  | Archivo de Prueba | Componente que Evalúa | Comando de Ejecución |
  | :--- | :--- | :--- |
  | **DbCrudTest.php** | Operaciones CRUD en MySQL (videos, etc) | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Integration/DbCrudTest.php` |
  | **AuthIntegrationTest.php** | Flujo completo de Login con BD | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Integration/AuthIntegrationTest.php` |
  | **ApifyMockTest.php** | Extracción e ingesta de TikTok (Mock) | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Integration/ApifyMockTest.php` |
  | **AIEngineTest.php** | Integración PHP ↔ Python (Ollama Mock) | `c:\xampp\php\php.exe vendor/bin/phpunit tests/Integration/AIEngineTest.php` |

---

## 🔺🔺🔺 Nivel 3: Pruebas de Sistema y Seguridad (E2E)
Prueban vulnerabilidades de seguridad comunes (OWASP Top 10) y flujos del sistema completo de extremo a extremo.

* **Ejecutar toda la suite E2E:**
  ```cmd
  c:\xampp\php\php.exe vendor/bin/phpunit --testsuite E2E --verbose
  ```

* **Ejecutar archivos individuales:**

  | Archivo de Prueba | Componente que Evalúa | Comando de Ejecución |
  | :--- | :--- | :--- |
  | **SecurityTest.php** | Inyecciones SQL, XSS, contraseñas hash | `c:\xampp\php\php.exe vendor/bin/phpunit tests/E2E/SecurityTest.php` |
  | **VideoAnalysisFlowTest.php** | Integración real con el servicio Ollama | `c:\xampp\php\php.exe vendor/bin/phpunit tests/E2E/VideoAnalysisFlowTest.php` |

---

## 🏆 Comando Estrella: Ejecutar todas las pruebas a la vez
Para demostrar rápidamente que el software es estable, el siguiente comando ejecuta **las pruebas unitarias y de integración juntas** (43 pruebas exitosas en total):

```cmd
c:\xampp\php\php.exe vendor/bin/phpunit --testsuite Unit,Integration --verbose
```

---

## 💡 Conceptos Clave para la Defensa
* **Base de Datos Aislada:** Las pruebas **no** modifican la base de datos de producción (`db_antigravity`). El archivo `tests/bootstrap.php` inicializa automáticamente la base de datos `db_antigravity_test`. Además, cada prueba utiliza transacciones que al finalizar hacen un `rollBack` para mantener limpio el entorno.
* **Mocks y Dobles de Prueba:** En las pruebas de integración de la IA (`AIEngineTest.php`) y TikTok (`ApifyMockTest.php`), se inyecta la variable de entorno `MOCK_OLLAMA=1` y respuestas simuladas (Stubs). Esto permite validar que la comunicación PHP ↔ Python y el procesamiento de respuestas funcionan correctamente sin consumir créditos de red ni depender de que el servicio local Ollama esté sobrecargado.
