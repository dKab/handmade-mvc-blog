<?php

class PostManager extends Transaction {
    
    public function __construct()
    {
        parent::__construct();
    }
    
    const DRAFT = 1;
    const PUBLISHED = 2;
    const ARCHIVE = 3;
    
    private static $findByStatus = "SELECT * FROM posts
           WHERE status =
           :status
           ORDER BY create_time DESC";
    
    private static $getAll = "SELECT posts.*, lookup.name, lookup.position
            FROM posts JOIN lookup ON posts.status = lookup.code
            WHERE lookup.type = 'Post type'
            ORDER BY lookup.position asc, posts.create_time desc";
    
    private static $addPost = 'INSERT INTO posts
        (author, title, body, create_time, edit_time, status)
        VALUES(:author, :title, :body, NOW(), NOW(), :status)';
    
    private static $insertTag = "INSERT INTO tags (name, frequency) VALUES(:name, 1)";
    
    private static $findTag = "SELECT id FROM tags WHERE name = :name";
    
    private static $updateTag = "UPDATE tags SET frequency = frequency +1 WHERE id = :id";
    
    private static $update = "UPDATE posts SET
                       author = :author,
                       title = :title, body = :body, edit_time = NOW(),
                       tags = :tags, status = :status WHERE id=?";
    
    private static $delete = 'DELETE FROM posts WHERE id=?';
    
    private static $find = "SELECT * FROM posts WHERE id=?";
    
    private static $checkTagInPost = "SELECT * FROM post_tag WHERE post_id = :post AND tag_id = :tag";
    
    private static $linkTag = "INSERT INTO post_tag VALUES(:post, :tag)";
    
    public function getAllPosts()
    {
        $ret = array();
        $sth = $this->doStatement(self::$getAll);
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $posts;
    }
    public function getPublished()
    {
        $ret = array();
        $sth = $this->doStatement(self::$findByStatus, array(
            'status'=>self::PUBLISHED
                ));
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $posts;
    }
    
    public function addPost($input)
    {
        extract($input);
        try {
        $this->dbh->beginTransaction();
        $sth=$this->doStatement(self::$addPost, array(
            'author'=>$user,
            'title'=>$title,
            'body'=>$body,
            'status'=>$status
        ));
        if ( ! $postId=$this->dbh->lastInsertId() ) {
            throw new Exception("Не удалось добавить пост");
        }
        if ( ! empty($tags) ) {
            foreach ($tags as $name) {
                $sth=$this->doStatement(self::$findTag, array(
                    'name'=>$name));
                if ( $id = $sth->fetchColumn() ) {
                    $sth=$this->doStatement(self::$updateTag, array(
                        'id'=>$id,
                    ));
                    if ( ! $count = $sth->rowCount() ) {
                        throw new Exception("Не удалось обновить поле frequency тэга");
                    }
                } else {
                    $sth=$this->doStatement(self::$insertTag, array(
                        'name'=>$name
                    ));
                    if ( ! $id = $this->dbh->lastInsertId() ) {
                        throw new Exception("Не удалось добавить тэг");
                    }
                }
                $sth=$this->doStatement(self::$linkTag, array(
                        'post'=>$postId,
                        'tag'=>$id,
                    ));
                if ( ! $count = $sth->rowCount() ) {
                        throw new Exception("Не удалось связать тэг с постом");
                }
            }
        } 
        $this->dbh->commit();
        return $postId;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            return false;
        }    
    }
}
