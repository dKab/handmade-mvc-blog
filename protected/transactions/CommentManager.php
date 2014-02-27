<?php

class CommentManager extends Transaction 
{
    const PENDING = 1;
    const APPROVED = 2;
    
    private static $add = 'INSERT INTO comments 
        (post_id, parent_id, email, name, body, path, notify_reply, time, status)
        VALUES(:postId, :parentId, :email, :name, :body, :path, :notify, NOW(), :status)';
    
    private static $countOrphans = "SELECT COUNT(*) FROM comments WHERE post_id = :post AND parent_id IS NULL";
    private static $countSiblings = "SELECT COUNT(*) FROM comments WHERE post_id=:post AND parent_id = :parent";
    
    private static $getPath= "SELECT path FROM comments WHERE id=:id";
    
    private static $getAll = "SELECT * FROM comments WHERE post_id = :postId AND status = :status ORDER BY path ASC";
     
    private function getSiblingsNum($postId, $parentId=null)
    {
        if ( ! $parentId ) {
            $num = $this->doStatement(self::$countOrphans, array('post'=>$postId))
                    ->fetchColumn();
        } else {
            $num = $this->doStatement(self::$countSiblings, array(
                'post'=>$postId,
                'parent'=>$parentId,
                ))->fetchColumn();
        }
        return $num;
    }
    
    public function addComment($comment)
    {
        extract($comment);
        
        if ( ! $parentId ) {
            $numSiblings = $this->getSiblingsNum($postId);
            $path = $postId . "." . ($numSiblings + 1);
        } else {
            $numSiblings - $this->getSiblingsNum($postId, $parentId);
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
        
        $comment['status'] = ( AppHelper::instance()->getCommentRule() ) ? self::PENDING : self::APPROVED;
        
         if ( is_null($comment['notify']) ) {
             $comment['notify'] = 0;
         }
         
        $sth = $this->doStatement(self::$add, $comment);
       if (! $id=$this->dbh->lastInsertId() ) {
           throw new Exception('Не удалось добавить комментарий');
       }
       return $id;
    }
    
    public function getAllComments($postId)
    {
        $comments = $this->doStatement(self::$getAll, array(
            'postId'=>$postId,
            'status'=>self::APPROVED,
        ))->fetchAll(PDO::FETCH_ASSOC);
        return $comments;
    }
}
