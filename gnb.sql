-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-11-2026 a las 12:16:11
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gnb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

CREATE TABLE `personal` (
  `id` int(11) NOT NULL,
  `numero_carnet` varchar(50) DEFAULT NULL,
  `unidad_brigada` varchar(150) DEFAULT 'LABORATORIO CRIMINALISTICO NRO. 11',
  `destacamento_batallon` varchar(100) DEFAULT 'SLCCT',
  `componente` varchar(100) DEFAULT 'GNB',
  `grado` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `cedula` varchar(30) NOT NULL,
  `sexo` char(1) DEFAULT 'M',
  `antecedentes_observaciones` text DEFAULT NULL,
  `lugar_nacimiento` varchar(150) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `promocion` varchar(150) DEFAULT NULL,
  `escuela_graduacion` varchar(150) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `ano_graduacion` int(11) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT 'Soltero/a',
  `edad` int(11) DEFAULT NULL,
  `grupo_sang` varchar(10) DEFAULT NULL,
  `anos_servicio` int(11) DEFAULT 0,
  `meses_servicio` int(11) DEFAULT 0,
  `talla_zapato` varchar(10) DEFAULT NULL,
  `talla_camisa` varchar(10) DEFAULT NULL,
  `talla_pantalon` varchar(10) DEFAULT NULL,
  `talla_gorra` varchar(10) DEFAULT NULL,
  `talla_patriota` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `direccion_emergencia` text DEFAULT NULL,
  `telefono_celular` varchar(30) DEFAULT NULL,
  `telefono_habitacion` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `contacto_emergencia_telefono` varchar(30) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal`
--

INSERT INTO `personal` (`id`, `numero_carnet`, `unidad_brigada`, `destacamento_batallon`, `componente`, `grado`, `apellidos`, `nombres`, `cedula`, `sexo`, `antecedentes_observaciones`, `lugar_nacimiento`, `fecha_nacimiento`, `promocion`, `escuela_graduacion`, `fecha_ingreso`, `categoria`, `especialidad`, `ano_graduacion`, `estado_civil`, `edad`, `grupo_sang`, `anos_servicio`, `meses_servicio`, `talla_zapato`, `talla_camisa`, `talla_pantalon`, `talla_gorra`, `talla_patriota`, `direccion`, `direccion_emergencia`, `telefono_celular`, `telefono_habitacion`, `correo`, `contacto_emergencia_telefono`, `creado_en`) VALUES
(1, '', 'LABORATORIO CRIMINALISTICO NRO. 11', 'SLCCT', 'GNB', 'mayor', 'leon wilhelm', 'Ernesto javier', 'V-30748731', 'M', '', '', NULL, '', '', NULL, '', '', NULL, 'Soltero/a', NULL, '', NULL, NULL, '', '', '', '', '', '', '', '', '', 'leonernesto609@gmail.com', '', '2026-11-10 01:43:09'),
(2, '', 'LABORATORIO CRIMINALISTICO NRO. 11', 'SLCCT', 'GNB', '', 'leon', 'hanye luzardo', 'V-1212', 'M', '', '', NULL, '', '', NULL, '', '', NULL, 'Soltero/a', NULL, '', NULL, NULL, '', '', '', '', '', '', '', '', '', 'Hanyelisluzardo843@gmail.com', '', '2026-11-10 03:18:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_cursos`
--

CREATE TABLE `personal_cursos` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT 'Curso',
  `nombre` varchar(150) NOT NULL,
  `institucion` varchar(150) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `soporte` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_cursos`
--

INSERT INTO `personal_cursos` (`id`, `personal_id`, `tipo`, `nombre`, `institucion`, `ano`, `soporte`) VALUES
(4, 1, 'Taller', 'dsd', 'sd', 2004, 'uploads/documentos/formacion_1_V-30748731_1794274989.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_familiares`
--

CREATE TABLE `personal_familiares` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `nombre_conyuge` varchar(150) DEFAULT NULL,
  `cedula_conyuge` varchar(30) DEFAULT NULL,
  `telefono_conyuge` varchar(30) DEFAULT NULL,
  `foto_cedula_conyuge` varchar(255) DEFAULT NULL,
  `nombre_padre` varchar(150) DEFAULT NULL,
  `cedula_padre` varchar(30) DEFAULT NULL,
  `padre_vive` varchar(10) DEFAULT 'Sí',
  `telefono_padre` varchar(30) DEFAULT NULL,
  `direccion_padre` text DEFAULT NULL,
  `foto_cedula_padre` varchar(255) DEFAULT NULL,
  `nombre_madre` varchar(150) DEFAULT NULL,
  `cedula_madre` varchar(30) DEFAULT NULL,
  `madre_vive` varchar(10) DEFAULT 'Sí',
  `telefono_madre` varchar(30) DEFAULT NULL,
  `direccion_madre` text DEFAULT NULL,
  `foto_cedula_madre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_familiares`
--

INSERT INTO `personal_familiares` (`id`, `personal_id`, `nombre_conyuge`, `cedula_conyuge`, `telefono_conyuge`, `foto_cedula_conyuge`, `nombre_padre`, `cedula_padre`, `padre_vive`, `telefono_padre`, `direccion_padre`, `foto_cedula_padre`, `nombre_madre`, `cedula_madre`, `madre_vive`, `telefono_madre`, `direccion_madre`, `foto_cedula_madre`) VALUES
(1, 1, 'dfdf', 'V-34343', '4334-34', 'uploads/documentos/cedula_conyuge_V-30748731_1794274989.jpg', 'dfd', 'V-2323', 'Sí', NULL, NULL, 'uploads/documentos/cedula_padre_V-30748731_1794274989.jpg', 'dsd', 'V-2332', 'Sí', NULL, NULL, 'uploads/documentos/cedula_madre_V-30748731_1794274989.jpg'),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL, 'Sí', NULL, NULL, NULL, NULL, NULL, 'Sí', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_fotos`
--

CREATE TABLE `personal_fotos` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `foto_cedula` varchar(255) DEFAULT NULL,
  `foto_carnet_frente` varchar(255) DEFAULT NULL,
  `foto_carnet_reverso` varchar(255) DEFAULT NULL,
  `foto_partida_frente` varchar(255) DEFAULT NULL,
  `foto_partida_reverso` varchar(255) DEFAULT NULL,
  `foto_gala` varchar(255) DEFAULT NULL,
  `foto_patriota_tiuna` varchar(255) DEFAULT NULL,
  `foto_interior_cuartel` varchar(255) DEFAULT NULL,
  `foto_civil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_fotos`
--

INSERT INTO `personal_fotos` (`id`, `personal_id`, `foto_cedula`, `foto_carnet_frente`, `foto_carnet_reverso`, `foto_partida_frente`, `foto_partida_reverso`, `foto_gala`, `foto_patriota_tiuna`, `foto_interior_cuartel`, `foto_civil`) VALUES
(1, 1, 'uploads/documentos/cedula_V-30748731_1794275283.jpg', 'uploads/documentos/carnet_frente_V-30748731_1794274989.jpg', 'uploads/documentos/carnet_reverso_V-30748731_1794274989.jpg', 'uploads/documentos/partida_frente_V-30748731_1794274989.jpg', 'uploads/documentos/partida_reverso_V-30748731_1794274989.jpg', 'uploads/documentos/foto_gala_V-30748731_1794274989.jpg', 'uploads/documentos/foto_patriota_tiuna_V-30748731_1794274989.jpg', 'uploads/documentos/foto_interior_cuartel_V-30748731_1794274989.jpg', 'uploads/documentos/foto_civil_V-30748731_1794274989.jpg'),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_hijos`
--

CREATE TABLE `personal_hijos` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `sexo` varchar(10) DEFAULT 'M',
  `fecha_nac` date DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `cedula_doc` varchar(255) DEFAULT NULL,
  `partida_frente` varchar(255) DEFAULT NULL,
  `partida_reverso` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_hijos`
--

INSERT INTO `personal_hijos` (`id`, `personal_id`, `nombres`, `apellidos`, `sexo`, `fecha_nac`, `edad`, `cedula_doc`, `partida_frente`, `partida_reverso`) VALUES
(4, 1, 'fgfg', 'gfg', 'F', '2020-11-25', 5, NULL, 'uploads/documentos/hijo_1_partida_frente_V-30748731_1794274989.jpg', 'uploads/documentos/hijo_1_partida_reverso_V-30748731_1794274989.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_medico`
--

CREATE TABLE `personal_medico` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `hipertenso` varchar(10) DEFAULT 'No',
  `cardiologicos` varchar(10) DEFAULT 'No',
  `renales` varchar(10) DEFAULT 'No',
  `testiculares` varchar(10) DEFAULT 'No',
  `pelvicos` varchar(10) DEFAULT 'No',
  `infeccion_respiratoria` varchar(10) DEFAULT 'No',
  `sinusitis` varchar(10) DEFAULT 'No',
  `bronquitis` varchar(10) DEFAULT 'No',
  `neumonia` varchar(10) DEFAULT 'No',
  `asma` varchar(10) DEFAULT 'No',
  `tratamiento_asignado` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_medico`
--

INSERT INTO `personal_medico` (`id`, `personal_id`, `hipertenso`, `cardiologicos`, `renales`, `testiculares`, `pelvicos`, `infeccion_respiratoria`, `sinusitis`, `bronquitis`, `neumonia`, `asma`, `tratamiento_asignado`) VALUES
(1, 1, 'Sí', 'No', 'No', 'No', 'No', 'No', 'Sí', 'No', 'No', 'No', NULL),
(2, 2, 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `primer_nombre` varchar(100) DEFAULT NULL,
  `segundo_nombre` varchar(100) DEFAULT NULL,
  `primer_apellido` varchar(100) DEFAULT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `cedula` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(50) DEFAULT 'Admin',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `nombre`, `apellido`, `cedula`, `email`, `password`, `rol`, `creado_en`, `token_recuperacion`, `token_expiracion`, `estado`) VALUES
(1, 'Ernesto', 'Javier', 'leon', 'wilhelm', 'Ernesto', 'Leon', 'V-30748731', 'leonernesto609@gmail.com', '$2y$10$X/cw4umEyKu0HSrBi44Nwu7tVfwFoATmR7GUT8XIkV1t83b/n6GD2', 'Admin', '2026-10-17 23:41:38', '5989e0db2a4e377177e5709e47e09dc20e5e80e79afbab3980330003d50b03ae', '2026-10-25 20:59:48', 'activo'),
(2, 'prueba', '', 'p', '', 'prueba', 'p', 'V-30748732', 'prueba@gmail.com', '$2y$12$ugqxQ7mpcG0ctvN4cTyUvuN8tGdAxdoCbbrQDtZGw94LU.Fmn.fGu', 'Usuario', '2026-10-24 22:39:43', '8c4d5bd5c1653304bea2e4ac4213f821a42aa2ef67aa27f28f7767166614f9b3', '2026-10-25 01:40:00', 'suspendido');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `personal_cursos`
--
ALTER TABLE `personal_cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `personal_familiares`
--
ALTER TABLE `personal_familiares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `personal_fotos`
--
ALTER TABLE `personal_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `personal_hijos`
--
ALTER TABLE `personal_hijos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `personal_medico`
--
ALTER TABLE `personal_medico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `personal_cursos`
--
ALTER TABLE `personal_cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `personal_familiares`
--
ALTER TABLE `personal_familiares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `personal_fotos`
--
ALTER TABLE `personal_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `personal_hijos`
--
ALTER TABLE `personal_hijos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `personal_medico`
--
ALTER TABLE `personal_medico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `personal_cursos`
--
ALTER TABLE `personal_cursos`
  ADD CONSTRAINT `fk_personal_cursos` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal_familiares`
--
ALTER TABLE `personal_familiares`
  ADD CONSTRAINT `fk_personal_familiares` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal_fotos`
--
ALTER TABLE `personal_fotos`
  ADD CONSTRAINT `fk_personal_fotos` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal_hijos`
--
ALTER TABLE `personal_hijos`
  ADD CONSTRAINT `fk_personal_hijos` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal_medico`
--
ALTER TABLE `personal_medico`
  ADD CONSTRAINT `fk_personal_medico` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
