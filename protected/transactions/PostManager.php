<?php

class PostManager extends Transaction {
    
    public function __construct()
    {
        parent::__construct();
    }
    
    const DRAFT = 1;
    const PUBLISHED = 2;
    const ARCHIVE = 3;
    
    
    /*
    private static $findByStatus = "SELECT * FROM posts
           WHERE status =
           :status
           ORDER BY create_time DESC";
    */
 
    private static $getShallow = "SELECT p.id, p.title, p.create_time, p.edit_time, p.status, lookup.name, lookup.position
            FROM posts  p JOIN lookup ON p.status = lookup.code
            WHERE lookup.type = 'Post type'
            ORDER BY lookup.position asc, p.create_time desc";
    
    private static $getSelectivelyShallow = "SELECT p.id, p.title, p.create_time, p.edit_time, p.status, lookup.name, lookup.position
            FROM posts  p JOIN lookup ON p.status = lookup.code
            WHERE lookup.type = 'Post type' AND p.status=:status
            ORDER BY lookup.position asc, p.create_time desc";
   
    private static $addPost = 'INSERT INTO posts
        (author, title, begining, ending, create_time, edit_time, status)
        VALUES(:author, :title, :begining, :ending, NOW(), NOW(), :status)';
    
    private static $insertTag = "INSERT INTO tags (name, frequency) VALUES(:name, 1)";
    
    private static $findTag = "SELECT id FROM tags WHERE name = :name";
    
    private static $updateTag = "UPDATE tags SET frequency = frequency +1 WHERE id = :id";
    
    private static $update = "UPDATE posts SET
                       author = :author,
                       title = :title, begining = :begining, ending=:ending, edit_time = NOW(),
                       status = :status WHERE id=:id";
    
    private static $delete = 'DELETE FROM posts WHERE id=:id';
    
    private static $find = "SELECT * FROM posts WHERE id=:id";
    
    private static $checkTagInPost = "SELECT * FROM post_tag WHERE post_id = :post AND tag_id = :tag";
    
    private static $linkTag = "INSERT INTO post_tag VALUES(:post, :tag)";
    
    private static $findByStatus = "SELECT p.id, p.title, p.create_time, p.edit_time, p.status, p.begining,
                                            GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags
                                            FROM posts p JOIN post_tag pt
                                            ON p.id=pt.post_id JOIN tags t
                                            ON pt.tag_id = t.id WHERE p.status=:status
                                            GROUP BY p.id ORDER BY create_time desc";
    
    private static $getAll = "SELECT p2l.id, p2l.title, p2l.create_time, p2l.edit_time, p2l.status, p2l.begining, p2l.name,  GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags
                                            FROM (SELECT posts.*, lookup.name, lookup.position
                                            FROM posts JOIN lookup ON posts.status = lookup.code
                                                        WHERE lookup.type = 'Post type') as p2l 
                                            JOIN post_tag pt
                                            ON p2l.id=pt.post_id JOIN tags t
                                            ON pt.tag_id = t.id
                                            GROUP BY p2l.id ORDER BY p2l.status, p2l.create_time desc";
    
    private static $findPostsByTagAndStatus = "
SELECT p.id, p.title, p.create_time, p.edit_time, p.status, p.begining, GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags FROM
posts p JOIN post_tag p2t ON p.id=p2t.post_id JOIN tags t
ON p2t.tag_id=t.id WHERE p.id IN
(SELECT p.id FROM posts p JOIN post_tag pt
ON p.id=pt.post_id JOIN tags t
ON pt.tag_id=t.id WHERE t.name = :tag AND p.status= :status) GROUP BY p.id ORDER BY p.create_time desc";
    
   private static $findPostsByTag = "SELECT p2l.id, p2l.title, p2l.create_time, p2l.edit_time, p2l.status, 
                                     p2l.begining, p2l.name, GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags FROM
                                      (SELECT posts.*, lookup.name, lookup.position
                                            FROM posts JOIN lookup ON posts.status = lookup.code
                                                        WHERE lookup.type = 'Post type') as p2l
                                            JOIN post_tag p2t ON p2l.id=p2t.post_id JOIN tags t 
                                      ON p2t.tag_id=t.id WHERE p2l.id IN 
                                      (SELECT p.id FROM posts p JOIN post_tag pt 
                                      ON p.id=pt.post_id JOIN tags t 
                                      ON pt.tag_id=t.id WHERE t.name = :tag) GROUP BY p2l.id ORDER BY p2l.status asc, p2l.create_time desc";
    
    private static $findPost = "SELECT p.*, l.name as name FROM posts p JOIN lookup l ON
                                  p.status = l.code WHERE l.type = 'Post type'  
                                  AND p.id=:id";
    
    private static $getTags = "SELECT t.name FROM tags t
                               JOIN post_tag p2t ON t.id=p2t.tag_id
                               JOIN posts p ON p2t.post_id=p.id WHERE p.id=:id";
    private static $getComments = "";
    
    private static $countTotal = "SELECT COUNT(*) as total FROM posts";
    private static $countOnly ="SELECT COUNT(*) as total FROM posts WHERE status = :status";
    
    private static $checkTags = "SELECT COUNT(*) as num FROM post_tag WHERE post_id = :id";
    
    private static $unlinkTags = "DELETE FROM post_tag WHERE post_id=:id";
    
    private static $deleteTag = "DELETE FROM tags WHERE id IN (SELECT tag_id FROM post_tag WHERE post_id = :id)
                                 AND tags.frequency = 1";
    
    private static $updateFreq = "UPDATE tags SET frequency = frequency-1
                                  WHERE id IN (SELECT tag_id FROM post_tag WHERE post_id = :id)
                                  AND frequency > 1";
    
    private function explodeTags(Array $posts)
    {
        foreach ($posts as $key=>$val) {
            $tags = explode(", ", $val['tags']);
            $posts[$key]['tags'] = $tags;
        }
        return $posts;
    }
    
    public function hasTag($tag, $status=null)
    {
        if ( $status ) {
            $sth = $this->doStatement(self::$findPostsByTagAndStatus, array(
                'tag'=>$tag,
                'status'=>$status,
            ));
        } else {
            $sth=$this->doStatement(self::$findPostsByTag, array(
                'tag'=>$tag
                ));
        }
        $related = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $this->explodeTags($related);
    }
    
    public function getAllPosts()
    {
        $ret = array();
        $sth = $this->doStatement(self::$getAll);
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $this->explodeTags($posts);
    }
    
    public function getPublished()
    {
        $ret = array();
        $sth = $this->doStatement(self::$findByStatus, array(
            'status'=>self::PUBLISHED
                ));
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $this->explodeTags($posts);
    }
    
    public function getShallow()
    {
        $sth = $this->doStatement(self::$getShallow);
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $posts;
    }
    
    private function cutBody($body)
    {
        $cutTag = AppHelper::instance()->getCutTag();
        if ( $cut = mb_strpos($body,$cutTag) ) {
            list($begining, $ending) = explode($cutTag, $body);
        } else {
            $begining = $body;
            $ending = "";
        }
        return array(
            'begining'=>$begining,
            'ending'=>$ending,
        );
    }
    
    public function addPost($input)
    {
        extract($input);
        /*
        $cutTag = AppHelper::instance()->getCutTag();
        if ( $cut = mb_strpos($body,$cutTag) ) {
            list($begining, $ending) = explode($cutTag, $body);
        } else {
            $begining = $body;
            $ending = "";
        }
         * 
         */
        $body = $this->cutBody($body);
        $data = array_merge(array(
                'status'=>$status, 
                'author'=>$user,
                'title'=>$title,), $body);
        try {
        $this->dbh->beginTransaction();
        $sth=$this->doStatement(self::$addPost, $data);
        
        if ( ! $postId=$this->dbh->lastInsertId() ) {
            throw new Exception("Не удалось добавить пост");
        }
        if ( ! empty($tags) ) {
            /*
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
             * 
             */
            $this->bindTags($postId, $tags);
        } 
        $this->dbh->commit();
        return $postId;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            return false;
        }    
    }
    
    public function getPost($id)
    {
        $sth=$this->doStatement(self::$findPost, array('id'=>$id));
        if ( $found = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $sth=$this->doStatement(self::$getTags, array('id'=>$found['id']));
            $tags=$sth->fetchAll(PDO::FETCH_COLUMN);
            if ($tags) {
                $found['tags']=$tags;
                return $found;
            }
        } else { 
            throw new Exception("Нет такого поста");
        }
    }
    
    public function countTotal($status=null)
    {
        if ( ! $status ) {
            return $this->doStatement(self::$countTotal)->fetchColumn();
        } else {
            return $this->doStatement(self::$countOnly, array('status'=>$status))
                ->fetchColumn();
        }
    }
    
    public function getPartial($offset, $limit, $status=null)
    {
        $limitClause = " LIMIT {$offset}, {$limit}";
        if ( ! $status ) {
            $stmt=self::$getShallow . $limitClause;
            $sth = $this->doStatement($stmt); 
        } else {
            $stmt=self::$getSelectivelyShallow . $limitClause;
            $sth=$this->doStatement($stmt, array("status"=>$status));
        }
        $posts=$sth->fetchAll(PDO::FETCH_ASSOC);
        return $posts;
    }
    
    
    private function removeTags($postId)
    {
        //find all tags linked to this post and check frequincy for all of them
        //if frequency is 1 -> remove tag else decrement frequency
           //remove all tags linked to this post in post_tage table
        //если нет тэгов, то ничего не делаем
        $hasTags = $this->doStatement(self::$checkTags, array('id'=>$postId))->fetchColumn();
        if ( ! $hasTags ) {
            return;
        }
        $this->doStatement(self::$deleteTag, array('id'=>$postId));
        $this->doStatement(self::$updateFreq, array('id'=>$postId));
        $this->doStatement(self::$unlinkTags, array('id'=>$postId));
    }

    public function editPost($input)
    {
        extract($input);
        
        $body = $this->cutBody($body);
        $data = array_merge(array(
            'title'=>$title,
            'author'=>$user,
            'status'=>$status,
            'id'=>$id,
        ), $body);
        
        $this->dbh->beginTransaction();
        try {
            $sth = $this->doStatement(self::$update, $data);
      
            $this->removeTags($id);
            
            if ( ! empty($tags) ) {
                $this->bindTags($id, $tags);
            }
            $this->dbh->commit();
            return $id;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            return false;
        }        
    }
    
    private function bindTags($postId, Array $tags)
    {
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
    
    public function removePost($id)
    {
        $this->dbh->beginTransaction();
        try {
            $this->removeTags($id);
            
            $success = $this->doStatement(self::$delete, array('id'=>$id))
                ->rowCount();
            if ( ! $success ) {
                throw new Exception("Не удалось удалить пост");
            }
            $this->dbh->commit();
            return $success;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }
    
}
