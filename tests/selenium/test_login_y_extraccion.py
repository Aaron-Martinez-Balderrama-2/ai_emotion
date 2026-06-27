import unittest
import time

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager


class TestExtraccionTikTok(unittest.TestCase):

    def setUp(self):
        self.driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()))
        self.driver.maximize_window()

    def test_flujo_extraccion_video(self):
        driver = self.driver

        # -------------------------
        # 1. LOGIN
        # -------------------------
        driver.get("http://localhost/antigravity/ai_emotion/app/login/")

        driver.find_element(By.ID, "email").send_keys("admin@gmail.com")
        driver.find_element(By.ID, "password").send_keys("123456")

        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        time.sleep(3)


        # -------------------------
        # 2. IR A MÓDULO VIDEOS
        # -------------------------
        driver.get("http://localhost/antigravity/ai_emotion/app/videos/")

        time.sleep(2)

        # 3. Abrir formulario "Nuevo Análisis"
        driver.find_element(By.CLASS_NAME, "g_btn_nuevo").click()

        time.sleep(2)

        # 4. INGRESAR DATOS
        driver.find_element(By.NAME, "v_url").send_keys(
            "https://www.tiktok.com/@jpvelasco29/video/7653187720205896968?is_from_webapp=1&sender_device=pc&web_id=7488885870989788677"
        )

        driver.find_element(By.NAME, "v_candidato").send_keys("Candidato Test")
        driver.find_element(By.NAME, "v_partido").send_keys("Partido Test")

        # 5. Ejecutar análisis
        driver.find_element(By.NAME, "acc").click()

        # 6. Esperar procesamiento backend (yt-dlp + ffmpeg + whisper)
        time.sleep(25)

        # 7. Validación en UI (lo importante para tesis)
        page = driver.page_source.lower()

        self.assertTrue(
            "procesando" in page or
            "completado" in page or
            "progreso" in page
        )

    def tearDown(self):
        self.driver.quit()


if __name__ == "__main__":
    unittest.main()