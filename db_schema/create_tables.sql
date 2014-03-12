


CREATE TABLE images(
name varchar( 100 ) NOT NULL ,
path varchar( 255 ) NOT NULL ,
post_id int( 10 ) unsigned NOT NULL ,
PRIMARY KEY ( name, path ) ,
FOREIGN KEY ( post_id ) REFERENCES posts( id ) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
