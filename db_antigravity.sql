-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-05-2026 a las 04:36:43
-- Versión del servidor: 11.4.10-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db_antigravity`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_comentarios`
--

CREATE TABLE `tb_comentarios` (
  `cod` int(11) NOT NULL,
  `cod_video` int(11) NOT NULL,
  `comentario` mediumtext NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `sentimiento` varchar(20) DEFAULT 'neutro',
  `puntuacion` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_diccionario`
--

CREATE TABLE `tb_diccionario` (
  `cod` int(11) NOT NULL,
  `palabra` varchar(100) NOT NULL,
  `categoria` enum('buena','mala','neutra') DEFAULT 'neutra',
  `peso` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_videos`
--

CREATE TABLE `tb_videos` (
  `cod` int(11) NOT NULL,
  `url_tiktok` mediumtext NOT NULL,
  `titulo` varchar(255) DEFAULT 'Procesando...',
  `transcripcion` longtext DEFAULT NULL,
  `thumbnail` mediumtext DEFAULT NULL,
  `estado` enum('pendiente','procesando','completado','error') DEFAULT 'pendiente',
  `progreso` int(11) DEFAULT 0,
  `paso_actual` varchar(100) DEFAULT 'Pendiente',
  `analisis_ia` longtext DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tb_comentarios`
--
ALTER TABLE `tb_comentarios`
  ADD PRIMARY KEY (`cod`),
  ADD KEY `cod_video` (`cod_video`);

--
-- Indices de la tabla `tb_diccionario`
--
ALTER TABLE `tb_diccionario`
  ADD PRIMARY KEY (`cod`);

--
-- Indices de la tabla `tb_videos`
--
ALTER TABLE `tb_videos`
  ADD PRIMARY KEY (`cod`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tb_comentarios`
--
ALTER TABLE `tb_comentarios`
  MODIFY `cod` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tb_diccionario`
--
ALTER TABLE `tb_diccionario`
  MODIFY `cod` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tb_videos`
--
ALTER TABLE `tb_videos`
  MODIFY `cod` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tb_comentarios`
--
ALTER TABLE `tb_comentarios`
  ADD CONSTRAINT `tb_comentarios_ibfk_1` FOREIGN KEY (`cod_video`) REFERENCES `tb_videos` (`cod`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
