-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Мар 21 2014 г., 10:04
-- Версия сервера: 5.6.12-log
-- Версия PHP: 5.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `alenablog`
--
CREATE DATABASE IF NOT EXISTS `alenablog` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `alenablog`;

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `name` char(30) NOT NULL,
  `num_posts` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `email` char(254) NOT NULL,
  `name` char(100) NOT NULL,
  `body` text NOT NULL,
  `path` varchar(100) NOT NULL,
  `notify_reply` tinyint(1) NOT NULL DEFAULT '1',
  `time` datetime NOT NULL,
  `status` tinyint(2) unsigned NOT NULL,
  `children` int(10) unsigned DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `parent_id` (`parent_id`),
  KEY `pth_ind` (`path`),
  KEY `status_index` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `name` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`name`,`path`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Структура таблицы `lookup`
--

CREATE TABLE IF NOT EXISTS `lookup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `code` int(11) NOT NULL,
  `type` varchar(128) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `lookup`
--

INSERT INTO `lookup` (`id`, `name`, `code`, `type`, `position`) VALUES
(1, 'Черновик', 1, 'Post type', 1),
(2, 'Активный', 2, 'Post type', 2),
(3, 'Устарел', 3, 'Post type', 3),
(4, 'Ожидает утверждения', 1, 'Comment type', 1),
(5, 'Утвержден', 2, 'Comment type', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author` char(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `create_time` datetime NOT NULL,
  `edit_time` datetime NOT NULL,
  `publish_time` datetime DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `begining` text NOT NULL,
  `ending` text NOT NULL,
  `child_comments` int(10) unsigned DEFAULT '0',
  `begining_html` text NOT NULL,
  `ending_html` text NOT NULL,
  `category` char(30) NOT NULL,
  `video` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author` (`author`),
  KEY `category` (`category`),
  KEY `title_index` (`title`(100)),
  KEY `status_index` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `post_tag`
--

CREATE TABLE IF NOT EXISTS `post_tag` (
  `post_id` int(10) unsigned NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  KEY `post_id_key` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Структура таблицы `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(20) NOT NULL,
  `frequency` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Структура таблицы `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `name` char(10) NOT NULL,
  `password` char(32) NOT NULL,
  `email` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`author`) REFERENCES `user` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category`) REFERENCES `categories` (`name`);

--
-- Ограничения внешнего ключа таблицы `post_tag`
--
ALTER TABLE `post_tag`
  ADD CONSTRAINT `post_tag_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `post_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
