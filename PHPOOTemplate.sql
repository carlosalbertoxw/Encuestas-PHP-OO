-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 24-08-2017 a las 18:54:45
-- Versión del servidor: 10.1.25-MariaDB
-- Versión de PHP: 7.1.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `PHPOOTemplate`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `p_user`
--

CREATE TABLE `p_user` (
  `u_key` int(11) NOT NULL,
  `u_email` varchar(50) NOT NULL,
  `u_password` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `p_user`
--

INSERT INTO `p_user` (`u_key`, `u_email`, `u_password`) VALUES
(1, 'utng@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b'),
(2, 'alberto@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `p_user_profile`
--

CREATE TABLE `p_user_profile` (
  `u_p_key` int(11) NOT NULL,
  `u_p_user` varchar(25) NOT NULL,
  `u_p_name` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `p_user_profile`
--

INSERT INTO `p_user_profile` (`u_p_key`, `u_p_user`, `u_p_name`) VALUES
(1, 'carlos', 'Carlos'),
(2, 'usuario2', 'Usuario2');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `p_user`
--
ALTER TABLE `p_user`
  ADD PRIMARY KEY (`u_key`);

--
-- Indices de la tabla `p_user_profile`
--
ALTER TABLE `p_user_profile`
  ADD PRIMARY KEY (`u_p_key`),
  ADD UNIQUE KEY `u_p_user` (`u_p_user`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `p_user`
--
ALTER TABLE `p_user`
  MODIFY `u_key` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `p_user_profile`
--
ALTER TABLE `p_user_profile`
  ADD CONSTRAINT `p_user_profile_ibfk_1` FOREIGN KEY (`u_p_key`) REFERENCES `p_user` (`u_key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
