<?php

class CommentManager extends Transaction {

    const PENDING = 1;
    const APPROVED = 2;

    private static $add = 'INSERT INTO comments 
        (post_id, parent_id, email, name, body, path, notify_reply, time, status, admin)
        VALUES(:postId, :parentId, :email, :name, :body, :path, :notify, NOW(), :status, :admin)';
    private static $countOrphans = "SELECT child_comments FROM posts WHERE id = :post";
    private static $countSiblings = "SELECT children FROM comments WHERE id=:id";
    private static $getPath = "SELECT path FROM comments WHERE id=:id";
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
    private static $selectLatest = "SELECT comments.name, comments.body, comments.post_id, posts.title
            FROM comments JOIN posts ON comments.post_id = posts.id WHERE comments.status = :status
            ORDER BY comments.time DESC";
    
    private static $getNotify = "SELECT notify_reply from comments WHERE id=:id";
    private static $getContactInfo = "SELECT name, email FROM comments WHERE id=:id";
    
    private function isNotifyRequired($id) {
        $required = $this->doStatement(self::$getNotify, array('id'=>$id))->fetchColumn();
        return $required;
    }

    private function getSiblingsNum($postId, $parentId = null) {
        if (!$parentId) {
            $num = $this->doStatement(self::$countOrphans, array('post' => $postId))
                    ->fetchColumn();
        } else {
            $num = $this->doStatement(self::$countSiblings, array(
                        'id' => $parentId,
                    ))->fetchColumn();
        }
        return $num;
    }

    public function countAll() {
        return $total = $this->doStatement(self::$countTotal)->fetchColumn();
    }

    public function getLatest($limit = 5) {
        $query = self::$selectLatest . " LIMIT {$limit}";
        if (!$latest = $this->doStatement($query, array(
                    'status' => self::APPROVED,
                ))->fetchAll(PDO::FETCH_ASSOC)) {
            throw new Exception("Не удалось получить последние комментарии");
        }
        return $latest;
    }

    public function getPartial($offset, $limit) {
        $limitClause = " LIMIT {$offset}, {$limit}";
        $stmt = self::$getPartial . $limitClause;
        $comments = $this->doStatement($stmt)->fetchAll(PDO::FETCH_ASSOC);
        return $comments;
    }

    public function addComment($comment) {
        extract($comment);

        if (!$parentId) {
            $numSiblings = $this->getSiblingsNum($postId);
            $path = $postId . "." . ($numSiblings + 1);
        } else {
            $numSiblings = $this->getSiblingsNum($postId, $parentId);
            $parentPath = $this->doStatement(self::$getPath, array(
                        'id' => $parentId
                    ))->fetchColumn();
            $path = $parentPath . "." . ($numSiblings + 1);
            
            $sendEmail = $this->isNotifyRequired($parentId);
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

        if (is_null($comment['notify'])) {
            $comment['notify'] = 0;
        }

        if (isset($_SESSION['user'])) {
            $comment['status'] = self::APPROVED;
        }

        $this->dbh->beginTransaction();
        try {
            $sth = $this->doStatement(self::$add, $comment);
            if (!$id = $this->dbh->lastInsertId()) {
                throw new Exception('Не удалось добавить комментарий');
            }
            if (!$parentId) {
                $this->doStatement(self::$addChildToPost, array('id' => $postId));
            } else {
                $this->doStatement(self::$addChildToComment, array('id' => $parentId));
            }
            if (isset($sendEmail) && $sendEmail) {
                $subject = "Уведомление";
                $contacts = $this->doStatement(self::$getContactInfo, array('id'=>$parentId))->fetch(PDO::FETCH_ASSOC);
                $body = wordwrap($body, 70);
                $message = "Внимание Уважаемый {$contacts['name']}, на ваш комментарий ответили: \n '{$body}' \n "
                . "Чтобы просмотреть все комментарии или добавить новый, перейдите по ссылке http://" . AppHelper::instance()->getDomainName()
                . "/index/view?id={$postId} \n C уважением, " . AppHelper::instance()->getDomainName();
                //$message = wordwrap($message, 70);
                $headers = 'From: ' . AppHelper::instance()->getUserEmail('pusya') . "\r\n";
                $to = $contacts['email'];
                mail($to, $subject, $message, $headers);
            }
            $this->dbh->commit();
            return $id;
        } catch (Exception $ex) {
            $this->dbh->rollBack();
            return false;
        }
    }

    public function getAllComments($postId) {
        $comments = $this->doStatement(self::$findByPost, array(
                    'postId' => $postId,
                    'status' => self::APPROVED,
                ))->fetchAll(PDO::FETCH_ASSOC);

        return $comments;
    }

    public function countPending() {
        $num = $this->doStatement(self::$countPending, array(
                    'status' => self::PENDING
                ))->fetchColumn();
        return $num;
    }

    public function approveComment($id) {
        $success = $this->doStatement(self::$updateStatus, array(
                    'status' => self::APPROVED,
                    'id' => $id
                ))->rowCount();
        return $success;
    }

    public function deleteComment($id) {
        $success = $this->doStatement(self::$delete, array('id' => $id))->rowCount();
        return $success;
    }

}
