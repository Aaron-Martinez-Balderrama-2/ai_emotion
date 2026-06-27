import unittest

# python -m unittest -v tests/Pruebas_unitarias/UnitTest/EmotionAnalyzerTest.py 

from python_ia.emotion_analyzer import (
    inferir_genero_por_nombre,
    calcular_demografia_por_comentarios,
    limpiar_y_filtrar,
    extraer_nube_pensamientos_y_enlaces,
    encontrar_detonantes_inteligentes
)


class EmotionAnalyzerTest(unittest.TestCase):

    # =========================
    # GÉNERO
    # =========================

    def test_detecta_genero_masculino(self):
        self.assertEqual(
            inferir_genero_por_nombre("Juan Perez"),
            "M"
        )

    def test_detecta_genero_femenino(self):
        self.assertEqual(
            inferir_genero_por_nombre("Maria Lopez"),
            "F"
        )

    def test_genero_desconocido(self):
        self.assertIsNone(
            inferir_genero_por_nombre("usuario123")
        )

    def test_genero_mayusculas(self):
        self.assertEqual(
            inferir_genero_por_nombre("JUAN PEREZ"),
            "M"
        )

    def test_genero_nombre_invalido(self):
        self.assertIsNone(
            inferir_genero_por_nombre("12345!!!@@@")
        )

    # =========================
    # LIMPIEZA DE TEXTO
    # =========================

    def test_limpiar_y_filtrar_retorna_lista(self):
        resultado = limpiar_y_filtrar(
            "Hola!!! esto es una prueba excelente."
        )
        self.assertIsInstance(resultado, list)

    def test_limpiar_y_filtrar_elimina_simbolos(self):
        texto = "Hola!!! $$$ esto,,, es una PRUEBA???"
        resultado = limpiar_y_filtrar(texto)

        self.assertIn("prueba", resultado)

    def test_limpiar_y_filtrar_texto_vacio(self):
        resultado = limpiar_y_filtrar("")
        self.assertEqual(resultado, [])

    # =========================
    # DEMOGRAFÍA
    # =========================

    def test_demografia_retorna_diccionario(self):
        comentarios = [
            {"autor": "Juan Perez"},
            {"autor": "Maria Lopez"}
        ]

        resultado = calcular_demografia_por_comentarios(comentarios)
        self.assertIsInstance(resultado, dict)

    def test_demografia_con_datos_incompletos(self):
        comentarios = [
            {"autor": "Juan Perez"},
            {},
            None,
            "Maria Lopez"
        ]

        resultado = calcular_demografia_por_comentarios(comentarios)

        self.assertIn("hombres", resultado)
        self.assertIn("mujeres", resultado)

    # =========================
    # NUBE DE PALABRAS
    # =========================

    def test_nube_retorna_nodos_y_enlaces(self):
        comentarios = [
            {"texto": "economia empleo progreso"},
            {"texto": "empleo desarrollo economia"}
        ]

        nodos, enlaces = extraer_nube_pensamientos_y_enlaces(comentarios)

        self.assertIsInstance(nodos, list)
        self.assertIsInstance(enlaces, list)

    def test_nube_detecta_repeticion(self):
        comentarios = [
            {"texto": "economia economia empleo"},
            {"texto": "empleo economia desarrollo"}
        ]

        nodos, enlaces = extraer_nube_pensamientos_y_enlaces(comentarios)

        palabras = [n["text"] for n in nodos]

        self.assertIn("economia", palabras)
        self.assertIn("empleo", palabras)

    # =========================
    # DETONANTES
    # =========================

    def test_detonantes_interseccion(self):
        transcripcion = "economia empleo futuro progreso"
        comentarios = [
            {"texto": "economia empleo"},
            {"texto": "empleo desarrollo"}
        ]

        resultado = encontrar_detonantes_inteligentes(transcripcion, comentarios)

        self.assertIn("economia", resultado)
        self.assertIn("empleo", resultado)

    # =========================
    # ROBUSTEZ
    # =========================

    def test_no_rompe_con_input_basura(self):
        try:
            limpiar_y_filtrar(None)
            limpiar_y_filtrar(12345)
            limpiar_y_filtrar(["texto", None])
        except Exception as e:
            self.fail(f"Falló con input basura: {e}")


if __name__ == "__main__":
    unittest.main()