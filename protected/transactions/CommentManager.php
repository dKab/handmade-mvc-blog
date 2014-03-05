<?php

class CommentManager extends Transaction 
{
    const PENDING = 1;
    const APPROVED = 2;
    
    private static $add = 'INSERT INTO comments 
        (post_id, parent_id, email, name, body, path, notify_reply, time, status, admin)
        VALUES(:postId, :parentId, :email, :name, :body, :path, :notify, NOW(), :status, :admin)';
    
    private static $countOrphans = "SELECT child_comments FROM posts WHERE id = :post";
    private static $countSiblings = "SELECT children FROM comments WHERE id=:id";
    
    private static $getPath= "SELECT path FROM comments WHERE id=:id";
    
    private static $findByPost = "SELECT * FROM comments WHERE post_id = :postId AND status = :status ORDER BY path ASC";
    
    private static $countPending = "SELECT COUNT(*) as num FROM comments WHERE status = :status";
    
    private static $countTotal = "SELECT COUNT(*) as num FROM comments";
    
    private static $getPartial = "SELECT c.*, l.name as fancyStatus FROM
            (SELECT c.*, p.title as postTitle FROM comments c JOIN posts p ON c.post_id = p.id ) as c
            JOIN lookup l ON c.status = l.code WHERE l.type='Comment type' ORDER BY status ASC, time DESC";
    
    private static $updateStatus = "UPDATE comments SET status = :status WHERE id=:id";
    
    private static $delete = "DELETE FROM comments WHERE id=:id";
    
    private static $addChildToPost = "UPDATE posts SET child_comments = child_comments + 1 WHERE id=:id";
    
    private static $addChildToComment = "UPDATE comments SET children = children + 1 WHERE id=:id";
    
    private function getSiblingsNum($postId, $parentId=null)
    {
        if ( ! $parentId ) {
            $num = $this->doStatement(self::$countOrphans, array('post'=>$postId))
                    ->fetchColumn();
        } else {
            $num = $this->doStatement(self::$countSiblings, array(
                'id'=>$parentId,
                ))->fetchColumn();
        }
        return $num;
    }
    
    public function countAll()
    {
       return $total = $this->doStatement(self::$countTotal)->fetchColumn();
    }
    
    public function getPartial($offset, $limit)
    {
        $limitClause = " LIMIT {$offset}, {$limit}";
        $stmt = self::$getPartial . $limitClause;
        $comments=$this->doStatement($stmt)->fetchAll(PDO::FETCH_ASSOC);
        return $comments;
    }
    
    public function addComment($comment)
    {
        extract($comment);
        
        if ( ! $parentId ) {
            $numSiblings = $this->getSiblingsNum($postId);
            $path = $postId . "." . ($numSiblings + 1);
            
        } else {
            $numSiblings = $this->getSiblingsNum($postId, $parentId);
            $parentPath = $this->doStatement(self::$getPath, array(
                'id'=>$parentId
            ))->fetchColumn();
            $path = $parentPath . "." . ($numSiblings + 1);
        }
        
        $parts = explode(".", $path);
        //var_dump($parts);
        //die();
        foreach ($parts as $key => $value) {
          while (strlen($value) < 4) {
            $value = "0" . $value;
          }
          $parts[$key] = $value;
        }
        $path = implode(".", $parts);
        
        $comment['path'] = $path;
        
        $comment['status'] = ( (bool) (string) AppHelper::instance()->getCommentRule() ) ? self::PENDING : self::APPROVED;
        
         if ( is_null($comment['notify']) ) {
             $comment['notify'] = 0;
         }
         
        if (isset($_SESSION['user'])) {
            $comment['status'] = self::APPROVED;
        }
         
         $this->dbh->beginTransaction();
         try {
             $sth = $this->doStatement(self::$add, $comment);
             if (! $id=$this->dbh->lastInsertId() ) {
                 throw new Exception('Не удалось добавить комментарий');
             }
             if ( ! $parentId ) {
                 $this->doStatement(self::$addChildToPost, array('id'=>$postId));
             } else {
                 $this->doStatement(self::$addChildToComment, array('id'=>$parentId));
             }
             $this->dbh->commit();
             return $id;
         } catch (Exception $ex) {
             $this->dbh->rollBack();
             return false;
         }
        
    }
    
    public function getAllComments($postId)
    {
            $comments = $this->doStatement(self::$findByPost, array(
                'postId'=>$postId,
                'status'=>self::APPROVED,
             ))->fetchAll(PDO::FETCH_ASSOC);
 
        return $comments;
    }
    
    public function countPending()
    {
        $num = $this->doStatement(self::$countPending, array(
            'status'=>self::PENDING
        ))->fetchColumn();
        return $num;
    }
    
    public function approveComment($id)
    {
        $success=$this->doStatement(self::$updateStatus, array(
            'status'=>self::APPROVED,
            'id'=>$id
        ))->rowCount();
        return $success;
    }
    
    public function deleteComment($id)
    {
         $success = $this->doStatement(self::$delete, array('id'=>$id))->rowCount();
         return $success;
    }
}
