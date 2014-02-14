<?php

class PostManager extends Transaction {
    
    public function __construct($dbh)
    {
        parent::__construct($dbh);
    }
    
    const DRAFT = 1;
    const PUBLISHED = 2;
    const ARCHIVE = 3;
    
    private static $getPublished = "SELECT * FROM posts
           WHERE status =
           self::PUBLISHED
           ORDER BY create_time DESC";
    
    private static $getAll = "SELECT posts.*, lookup.name, lookup.position
            FROM posts JOIN lookup ON posts.status = lookup.code
            WHERE lookup.type = 'Post type'
            ORDER BY lookup.position asc, posts.create_time desc";
    
    private static $add = 'INSERT INTO posts
        (author, title, body, create_time, edit_time, tags, status)
        VALUES(:author, :title, :body, NOW(), NOW(), :tags, :status)';
    
    private static $update = "UPDATE posts SET
                       author = :author,
                       title = :title, body = :body, edit_time = NOW(),
                       tags = :tags, status = :status WHERE id=?";
    
    private static $delete = 'DELETE FROM posts WHERE id=?';
    
    private static $find = "SELECT * FROM posts WHERE id=?";
    
    public function getAllPosts()
    {
        $ret = array();
        $sth = $this->doStatement(self::$getAll);
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $posts;
    }
    
}
