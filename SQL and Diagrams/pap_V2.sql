-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 05-Mar-2025 às 18:19
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `pap`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `admins`
--

CREATE TABLE `admins` (
  `admid` int(11) NOT NULL,
  `admusername` varchar(40) NOT NULL,
  `password` varchar(100) NOT NULL,
  `password2` varchar(100) NOT NULL,
  `profilepic` text NOT NULL,
  `nome` int(40) NOT NULL,
  `birthday` date NOT NULL,
  `Email` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comment`
--

CREATE TABLE `comment` (
  `commentid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `user_type` enum('adm','user') NOT NULL,
  `responseid` int(11) NOT NULL,
  `responsetype` varchar(1) NOT NULL,
  `commenttext` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `likes`
--

CREATE TABLE `likes` (
  `userid` int(11) NOT NULL,
  `likeid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `photos`
--

CREATE TABLE `photos` (
  `photoid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `user_type` enum('adm','user') NOT NULL,
  `filepath` text NOT NULL,
  `filetype` varchar(4) NOT NULL,
  `filesize` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `uploaddate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `username` varchar(40) NOT NULL,
  `password` varchar(100) NOT NULL,
  `profilepic` text NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(60) NOT NULL,
  `birthday` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admid`) USING BTREE,
  ADD UNIQUE KEY `admusername` (`admusername`);

--
-- Índices para tabela `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`commentid`,`userid`,`user_type`),
  ADD UNIQUE KEY `commentid` (`commentid`),
  ADD KEY `responsecomment` (`responseid`);

--
-- Índices para tabela `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`userid`,`likeid`),
  ADD KEY `likeid` (`likeid`);

--
-- Índices para tabela `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`photoid`,`userid`,`user_type`),
  ADD UNIQUE KEY `photoid` (`photoid`),
  ADD KEY `fk_user` (`userid`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`) USING BTREE,
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `admid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comment`
--
ALTER TABLE `comment`
  MODIFY `commentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `photos`
--
ALTER TABLE `photos`
  MODIFY `photoid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `admid` FOREIGN KEY (`userid`) REFERENCES `admins` (`admid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `responsecomment` FOREIGN KEY (`responseid`) REFERENCES `comment` (`commentid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `responsephoto` FOREIGN KEY (`responseid`) REFERENCES `photos` (`photoid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `admlike` FOREIGN KEY (`userid`) REFERENCES `admins` (`admid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `likeid` FOREIGN KEY (`likeid`) REFERENCES `photos` (`photoid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `userlike` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `adm` FOREIGN KEY (`userid`) REFERENCES `admins` (`admid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
