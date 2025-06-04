-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04-Jun-2025 às 19:13
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
-- Estrutura da tabela `comment`
--

CREATE TABLE `comment` (
  `commentid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `responseid` int(11) NOT NULL,
  `responsetype` enum('post','comment') NOT NULL,
  `commenttext` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `likes`
--

CREATE TABLE `likes` (
  `userid` int(11) NOT NULL,
  `postid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `photos`
--

CREATE TABLE `photos` (
  `photoid` int(11) NOT NULL,
  `filepath` text NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `filetype` varchar(4) NOT NULL,
  `uploaddate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `post`
--

CREATE TABLE `post` (
  `postid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `text` varchar(350) NOT NULL,
  `date` date NOT NULL,
  `photoid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `user_type` enum('adm','user') NOT NULL,
  `username` varchar(40) NOT NULL,
  `password` varchar(300) NOT NULL,
  `AdmPin` int(6) DEFAULT NULL,
  `profilepic` text NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(60) NOT NULL,
  `birthday` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`userid`, `user_type`, `username`, `password`, `AdmPin`, `profilepic`, `name`, `email`, `birthday`) VALUES
(1, 'user', 'Aba', '$2y$10$eOAfAKfoSPeR9ldQ8pKyWOFGpeWylb3gWQmsJ3v00WU7aE1EqlfRq', NULL, 'photos/profile_pictures/Default_pfp.jpg', 'maria', 'maria@gmail.com', '2004-05-26'),
(2, 'adm', 'maria', '$2y$10$u324UtiLqhhlA9CNP4JmGuXq3i8F9uS6pxGDHUro48DMrtmyvPyuO', 211205, 'photos/profile_pictures/Default_pfp.jpg', 'maria', 'mariarita@gmail.com', '2003-06-09');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usertoken`
--

CREATE TABLE `usertoken` (
  `userid` int(11) NOT NULL,
  `token` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usertoken`
--

INSERT INTO `usertoken` (`userid`, `token`) VALUES
(1, 'edafef79f726fe9f07c7b892b7e8ee835b34f90a27aaf8fec4a407aa77143103'),
(2, 'fa0aae91592777bbddf96918352fd0a018607e5aec074cd2557686480c61aa6a');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`commentid`) USING BTREE,
  ADD KEY `responseid` (`responseid`,`responsetype`),
  ADD KEY `commentsuser` (`userid`);

--
-- Índices para tabela `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`userid`,`postid`),
  ADD KEY `photoid` (`postid`);

--
-- Índices para tabela `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`photoid`) USING BTREE;

--
-- Índices para tabela `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`postid`),
  ADD KEY `fk_user` (`userid`),
  ADD KEY `photoId` (`photoid`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`) USING BTREE,
  ADD UNIQUE KEY `username` (`username`);

--
-- Índices para tabela `usertoken`
--
ALTER TABLE `usertoken`
  ADD PRIMARY KEY (`userid`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `comment`
--
ALTER TABLE `comment`
  MODIFY `commentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `photos`
--
ALTER TABLE `photos`
  MODIFY `photoid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `post`
--
ALTER TABLE `post`
  MODIFY `postid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `responsecomment` FOREIGN KEY (`responseid`) REFERENCES `comment` (`commentid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `responsepost` FOREIGN KEY (`responseid`) REFERENCES `post` (`postid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usercomment` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `postid` FOREIGN KEY (`postid`) REFERENCES `post` (`postid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `userlike` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `photoId` FOREIGN KEY (`photoid`) REFERENCES `photos` (`photoid`);

--
-- Limitadores para a tabela `usertoken`
--
ALTER TABLE `usertoken`
  ADD CONSTRAINT `user` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
