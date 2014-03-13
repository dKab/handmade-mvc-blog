 CREATE TABLE `posts` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `author` char(10) NOT NULL,
 `title` varchar(255) NOT NULL,
 `create_time` datetime NOT NULL,
 `edit_time` datetime NOT NULL,
 `status` tinyint(3) unsigned NOT NULL,
 `begining` text NOT NULL,
 `ending` text NOT NULL,
 `child_comments` int(10) unsigned DEFAULT '0',
 `begining_html` text NOT NULL,
 `ending_html` text NOT NULL,
 `category` char(30) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `author` (`author`),
 KEY `category` (`category`),
 KEY `title_index` (`title`(100)),
 KEY `status_index` (`status`),
 CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`author`) REFERENCES `user` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category`) REFERENCES `categories` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `comments` (
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
 KEY `status_index` (`status`),
 CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE images(
name varchar( 100 ) NOT NULL ,
path varchar( 255 ) NOT NULL ,
post_id int( 10 ) unsigned NOT NULL ,
PRIMARY KEY ( name, path ) ,
FOREIGN KEY ( post_id ) REFERENCES posts( id ) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE `categories` (
 `name` char(30) NOT NULL,
 `num_posts` int(10) unsigned DEFAULT '0',
 PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tags` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` char(20) NOT NULL,
 `frequency` int(10) unsigned NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `name_index` (`name`(5))
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8;

CREATE TABLE `post_tag` (
 `post_id` int(10) unsigned NOT NULL,
 `tag_id` int(11) NOT NULL,
 PRIMARY KEY (`post_id`,`tag_id`),
 KEY `tag_id` (`tag_id`),
 CONSTRAINT `post_tag_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `post_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `lookup` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(128) NOT NULL,
 `code` int(11) NOT NULL,
 `type` varchar(128) NOT NULL,
 `position` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

CREATE TABLE `user` (
 `name` char(10) NOT NULL,
 `password` char(32) NOT NULL,
 `email` varchar(30) DEFAULT NULL,
 PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
