-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-11-2026 a las 20:06:40
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

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
-- Estructura de tabla para la tabla `comunicados_enviados`
--

CREATE TABLE `comunicados_enviados` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `cuerpo` text NOT NULL,
  `total_destinatarios` int(11) NOT NULL,
  `incluye_adjunto` tinyint(1) DEFAULT 0,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comunicados_enviados`
--

INSERT INTO `comunicados_enviados` (`id`, `usuario_id`, `asunto`, `cuerpo`, `total_destinatarios`, `incluye_adjunto`, `fecha_envio`) VALUES
(1, 1, 'gf', 'fg', 1, 0, '2026-11-10 18:38:13'),
(2, 1, 'gf', 'fg', 1, 0, '2026-11-10 18:38:16'),
(3, 1, 'd', 'd', 1, 0, '2026-11-10 18:59:20');

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
(3, '00392434', 'LABORATORIO CRIMINALISTICO NRO. 11', 'SLCCT', 'GNB', 'MAYOR', 'leon wilhelm', 'Ernesto javier', 'V-30748731', 'M', 'aaaaaaaaaaaaaaaaaaa', 'MARACAIBO', '2004-11-25', 'III CURSO DE ASIMILACIÒN DE LA GNB 2010', 'ESCUELA DE ORDEN INTERNO DE LA GNB', '2020-11-25', 'ASIMILADO', 'QUÌMICO', 2020, 'Soltero/a', 21, 'A+', 5, 11, '40', 'M', '32', 'M', 'MS', '19E', '19E', '0412-4252118', '0263-4462521', 'leonernesto609@gmail.com', '0412-0464376', '2026-11-10 13:10:34'),
(5, '', 'LABORATORIO CRIMINALISTICO NRO. 11', 'SLCCT', 'GNB', 'Mayor', 'd criminalistico', 'prueba laboratorio', 'V-18522809', 'M', '', '', NULL, '', '', '2025-11-25', '', '', NULL, 'Soltero/a', NULL, '', NULL, 11, '', '', '', '', '', '19E', '19E', '', '', 'laboratoriocriminalistico11@gmail.com', '', '2026-11-10 14:45:30'),
(6, '', 'LABORATORIO CRIMINALISTICO NRO. 11', 'SLCCT', 'GNB', 'Teniente Coronel', 'fg fg', 'prueba gf', 'V-2540745', 'M', '', '', NULL, '', '', '2026-11-25', '', '', NULL, 'Soltero/a', NULL, '', NULL, 11, '', '', '', '', '', '', '', '', '', '', '', '2026-11-10 14:55:14');

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
(6, 3, 'Titulo', 'Licenciado en Quimica', 'La universidad del Zulia (LUZ)', 2020, 'uploads/documentos/formacion_1_V-30748731_1794316234.jpg');

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
(3, 3, 'hghg', 'V-88', '0424-6521646', 'uploads/documentos/cedula_conyuge_V-30748731_1794316234.jpg', 'Jesus Alberto Fernandez', 'V-686546', 'Sí', '0412-1212', '19E', 'uploads/documentos/cedula_padre_V-30748731_1794316234.jpg', NULL, NULL, 'No', NULL, NULL, NULL),
(5, 5, NULL, NULL, NULL, NULL, NULL, NULL, 'Sí', NULL, NULL, NULL, NULL, NULL, 'Sí', NULL, NULL, NULL),
(6, 6, NULL, NULL, NULL, NULL, NULL, NULL, 'Sí', NULL, NULL, NULL, NULL, NULL, 'Sí', NULL, NULL, NULL);

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
(3, 3, 'uploads/documentos/cedula_V-30748731_1794316234.jpg', 'uploads/documentos/carnet_frente_V-30748731_1794316234.jpg', 'uploads/documentos/carnet_reverso_V-30748731_1794316234.jpg', 'uploads/documentos/partida_frente_V-30748731_1794316234.jpg', 'uploads/documentos/partida_reverso_V-30748731_1794316234.jpg', 'uploads/documentos/foto_gala_V-30748731_1794316234.jpg', 'uploads/documentos/foto_patriota_tiuna_V-30748731_1794316234.jpg', 'uploads/documentos/foto_interior_cuartel_V-30748731_1794316234.jpg', 'uploads/documentos/foto_civil_V-30748731_1794316234.jpg'),
(5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
(7, 3, 'laboratorio', 'criminalistico', 'M', '2020-11-25', 5, NULL, 'uploads/documentos/hijo_1_partida_frente_V-30748731_1794316234.jpg', 'uploads/documentos/hijo_1_partida_reverso_V-30748731_1794316234.jpg'),
(8, 3, 'laboratorio', 'criminalistico', 'M', '2016-11-25', 9, 'uploads/documentos/hijo_2_cedula_V-30748731_1794316234.jpg', NULL, NULL);

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
(3, 3, 'Sí', 'No', 'No', 'No', 'Sí', 'No', 'No', 'No', 'No', 'Sí', 'mmmmmmmmm'),
(5, 5, 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL),
(6, 6, 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL);

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
(1, 'Ernesto', 'Javier', 'leon', 'wilhelm', 'Ernesto', 'Leon', 'V-30748731', 'leonernesto609@gmail.com', '$2y$10$X/cw4umEyKu0HSrBi44Nwu7tVfwFoATmR7GUT8XIkV1t83b/n6GD2', 'Admin', '2026-10-17 23:41:38', 'e207049211654ab01a71cd4ea8be7ce8d94dfb21790ee4f4f1c185f85f81a9ca', '2026-11-10 20:12:27', 'activo'),
(2, 'prueba', '', 'p', '', 'prueba', 'p', 'V-30748732', 'prueba@gmail.com', '$2y$12$ugqxQ7mpcG0ctvN4cTyUvuN8tGdAxdoCbbrQDtZGw94LU.Fmn.fGu', 'Usuario', '2026-10-24 22:39:43', '8c4d5bd5c1653304bea2e4ac4213f821a42aa2ef67aa27f28f7767166614f9b3', '2026-10-25 01:40:00', 'suspendido');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `comunicados_enviados`
--
ALTER TABLE `comunicados_enviados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comunicado_usuario` (`usuario_id`);

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
-- AUTO_INCREMENT de la tabla `comunicados_enviados`
--
ALTER TABLE `comunicados_enviados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `personal_cursos`
--
ALTER TABLE `personal_cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `personal_familiares`
--
ALTER TABLE `personal_familiares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `personal_fotos`
--
ALTER TABLE `personal_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `personal_hijos`
--
ALTER TABLE `personal_hijos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `personal_medico`
--
ALTER TABLE `personal_medico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comunicados_enviados`
--
ALTER TABLE `comunicados_enviados`
  ADD CONSTRAINT `fk_comunicado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

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
