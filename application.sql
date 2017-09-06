-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 06-09-2017 a las 21:10:01
-- Versión del servidor: 10.1.26-MariaDB
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
-- Base de datos: `application`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `a_answer`
--

CREATE TABLE `a_answer` (
  `a_key` int(11) NOT NULL,
  `a_stars` tinyint(4) NOT NULL DEFAULT '0',
  `a_comment` varchar(1000) NOT NULL DEFAULT '',
  `a_poll_key` int(11) NOT NULL,
  `a_user_key` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `a_poll`
--

CREATE TABLE `a_poll` (
  `p_key` int(11) NOT NULL,
  `p_title` varchar(250) NOT NULL,
  `p_description` varchar(500) NOT NULL DEFAULT '',
  `p_position` tinyint(4) NOT NULL,
  `p_user_key` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `a_user`
--

CREATE TABLE `a_user` (
  `u_key` int(11) NOT NULL,
  `u_email` varchar(50) NOT NULL,
  `u_password` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `a_user_profile`
--

CREATE TABLE `a_user_profile` (
  `u_p_key` int(11) NOT NULL,
  `u_p_user` varchar(25) NOT NULL,
  `u_p_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `a_answer`
--
ALTER TABLE `a_answer`
  ADD PRIMARY KEY (`a_key`),
  ADD KEY `a_poll_key` (`a_poll_key`),
  ADD KEY `a_user_key` (`a_user_key`);

--
-- Indices de la tabla `a_poll`
--
ALTER TABLE `a_poll`
  ADD PRIMARY KEY (`p_key`),
  ADD KEY `p_user_key` (`p_user_key`);

--
-- Indices de la tabla `a_user`
--
ALTER TABLE `a_user`
  ADD PRIMARY KEY (`u_key`);

--
-- Indices de la tabla `a_user_profile`
--
ALTER TABLE `a_user_profile`
  ADD PRIMARY KEY (`u_p_key`),
  ADD UNIQUE KEY `u_p_user` (`u_p_user`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `a_answer`
--
ALTER TABLE `a_answer`
  MODIFY `a_key` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `a_poll`
--
ALTER TABLE `a_poll`
  MODIFY `p_key` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `a_user`
--
ALTER TABLE `a_user`
  MODIFY `u_key` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `a_answer`
--
ALTER TABLE `a_answer`
  ADD CONSTRAINT `a_answer_ibfk_1` FOREIGN KEY (`a_poll_key`) REFERENCES `a_poll` (`p_key`),
  ADD CONSTRAINT `a_answer_ibfk_2` FOREIGN KEY (`a_user_key`) REFERENCES `a_user_profile` (`u_p_key`);

--
-- Filtros para la tabla `a_poll`
--
ALTER TABLE `a_poll`
  ADD CONSTRAINT `a_poll_ibfk_1` FOREIGN KEY (`p_user_key`) REFERENCES `a_user_profile` (`u_p_key`);

--
-- Filtros para la tabla `a_user_profile`
--
ALTER TABLE `a_user_profile`
  ADD CONSTRAINT `a_user_profile_ibfk_1` FOREIGN KEY (`u_p_key`) REFERENCES `a_user` (`u_key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
