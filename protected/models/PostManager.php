<?php

class PostManager extends Model {

    public function __construct() {
        parent::__construct();
    }

    const DRAFT = 1;
    const PUBLISHED = 2;
    const ARCHIVE = 3;

    private $allowedFields = array('title', 'body', 'status', 'tags', 'video', 'new_category');

    public function getAllowedFields() {
        return $this->allowedFields;
    }

    private static $countAllPostsWithTag = "SELECT frequency FROM tags WHERE name=:tag";
    private static $countPostsWithTagAndStatus = "SELECT count(*) FROM posts p join post_tag pt
                                 on p.id=pt.post_id join tags t on pt.tag_id=t.id 
                                 where t.name = :tag and p.status = :status";
    private static $countCategoryAll = "SELECT num_posts FROM categories WHERE name=:category";
    private static $countCategory = "SELECT count(*) FROM posts WHERE category =:category and status =:status";
    private static $findCategory = "SELECT COUNT(*) FROM categories WHERE name=:category";
    private static $createCategory = "INSERT INTO categories(name) VALUES(:category)";
    private static $incrementCategory = "UPDATE categories SET num_posts=num_posts+1 WHERE name=:category";
    private static $decrementCategory = "UPDATE categories SET num_posts=num_posts-1 WHERE name=:category AND num_posts > 0";
    private static $getCategory = "SELECT category FROM posts WHERE id=:id";
    private static $getOldValues = "SELECT category, status, publish_time FROM posts WHERE id=:id";
    private static $deleteCategory = "DELETE FROM categories WHERE name=:category AND num_posts = 0";
    private static $filterByCategoryAndStatus = "SELECT p.id, p.title, p.create_time, p.edit_time, p.publish_time, p.status, p.begining_html, GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags FROM
                              posts p JOIN post_tag p2t ON p.id=p2t.post_id JOIN tags t
                              ON p2t.tag_id=t.id WHERE p.id IN
                              (SELECT p.id FROM posts p JOIN post_tag pt
                              ON p.id=pt.post_id JOIN tags t
                              ON pt.tag_id=t.id WHERE %s = :%s AND p.status= :status)
                              GROUP BY p.id ORDER BY p.create_time desc";
    private static $getShallow = "SELECT p.id, p.title, p.category, p.create_time, p.edit_time, p.status, lookup.name, lookup.position
            FROM posts  p JOIN lookup ON p.status = lookup.code
            WHERE lookup.type = 'Post type'";
    private static $categoriesPublished = "SELECT category as name, count(distinct id) as num_posts from posts WHERE status=:status group by category";
    private static $categories = "SELECT * FROM categories";
    private static $getSelectivelyShallow = "SELECT p.id, p.title, p.category, p.create_time, p.edit_time, p.status, lookup.name, lookup.position
            FROM posts  p JOIN lookup ON p.status = lookup.code
            WHERE lookup.type = 'Post type' AND p.%s=:%s";
    private static $getByTwoParameters = "SELECT p.id, p.title, p.category, p.create_time, p.edit_time, p.status, lookup.name, lookup.position
            FROM posts  p JOIN lookup ON p.status = lookup.code
            WHERE lookup.type = 'Post type' AND p.status=:status AND p.category =:category";
    private static $addDraft = 'INSERT INTO posts
        (author, title, begining, ending, create_time, edit_time, status, begining_html, ending_html, category, video)
        VALUES(:author, :title, :begining, :ending, NOW(), NOW(), :status, :beginingHtml, :endingHtml, :category, :video)';
    private static $addPost = 'INSERT INTO posts
        (author, title, begining, ending, create_time, edit_time, publish_time, status, begining_html, ending_html, category, video)
        VALUES(:author, :title, :begining, :ending, NOW(), NOW(), NOW(), :status, :beginingHtml, :endingHtml, :category, :video)';
    private static $insertTag = "INSERT INTO tags (name, frequency) VALUES(:name, 1)";
    private static $findTag = "SELECT id FROM tags WHERE name = :name";
    private static $updateTag = "UPDATE tags SET frequency = frequency +1 WHERE id = :id";
    private static $getStatus = "SELECT status FROM posts WHERE id=:id";
    private static $update = "UPDATE posts SET
                       author = :author,
                       title = :title, begining = :begining, ending=:ending, edit_time = NOW(), publish_time=:publishTime,
                       status = :status, begining_html=:beginingHtml, ending_html=:endingHtml, category=:category, video=:video WHERE id=:id";
    private static $delete = 'DELETE FROM posts WHERE id=:id';
    private static $checkTagInPost = "SELECT * FROM post_tag WHERE post_id = :post AND tag_id = :tag";
    private static $linkTag = "INSERT INTO post_tag VALUES(:post, :tag)";
    private static $findByStatus = "SELECT p.id, p.title, p.create_time, p.edit_time, 
        p.publish_time, p.status, p.begining_html, GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags, p.comments
    FROM (SELECT p.*, pc.comments
      FROM posts p
      LEFT JOIN 
      (
       SELECT post_id, COUNT(*) as comments
       FROM comments
       GROUP BY post_id
       ) pc 
       ON pc.post_id = p.id) as p
       JOIN post_tag pt
       ON p.id=pt.post_id JOIN tags t
       ON pt.tag_id = t.id WHERE p.status=:status
       GROUP BY p.id ORDER BY create_time desc";
    private static $getAll = "SELECT p2l.id, p2l.title, p2l.create_time, p2l.edit_time, p2l.publish_time, p2l.status, p2l.begining_html, p2l.name, GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags, p2l.comments
FROM (SELECT p.*, pc.comments, lookup.name
      FROM posts p
      LEFT JOIN 
      (
       SELECT post_id, COUNT(*) as comments
       FROM comments
       GROUP BY post_id
       ) pc 
       ON pc.post_id = p.id
       JOIN lookup ON p.status = lookup.code
       WHERE lookup.type = 'Post type') as p2l 
                                            JOIN post_tag pt
                                            ON p2l.id=pt.post_id JOIN tags t
                                            ON pt.tag_id = t.id
                                            GROUP BY p2l.id ORDER BY p2l.status, p2l.create_time desc";
    private static $findPostsByTag = "SELECT p2l.id, p2l.title, p2l.create_time, p2l.edit_time, p2l.publish_time, p2l.status, 
                                     p2l.begining_html, p2l.name, GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags FROM
                                      (SELECT posts.*, lookup.name, lookup.position
                                            FROM posts JOIN lookup ON posts.status = lookup.code
                                                        WHERE lookup.type = 'Post type') as p2l
                                            JOIN post_tag p2t ON p2l.id=p2t.post_id JOIN tags t 
                                      ON p2t.tag_id=t.id WHERE p2l.id IN 
                                      (SELECT p.id FROM posts p JOIN post_tag pt 
                                      ON p.id=pt.post_id JOIN tags t 
                                      ON pt.tag_id=t.id WHERE %s = :%s) GROUP BY p2l.id ORDER BY p2l.status asc, p2l.create_time desc";
    private static $getRaw = "SELECT p.id, p.title, p.begining, p.ending, p.status, p.category, p.video, 
                                  l.name as name FROM posts p JOIN lookup l ON
                                  p.status = l.code WHERE l.type = 'Post type'  
                                  AND p.id=:id";
    private static $getPretty = "SELECT p.id, p.title, p.begining_html,
                                 p.ending_html, p.status, p.child_comments, p.edit_time, p.create_time, p.publish_time,
                                 p.category, 
                                 l.name as name FROM posts p JOIN lookup l ON
                                  p.status = l.code WHERE l.type = 'Post type'  
                                  AND p.id=:id";
    private static $getTags = "SELECT t.name FROM tags t
                               JOIN post_tag p2t ON t.id=p2t.tag_id
                               JOIN posts p ON p2t.post_id=p.id WHERE p.id=:id";
    private static $countTotal = "SELECT COUNT(*) as total FROM posts";
    private static $checkTags = "SELECT COUNT(*) as num FROM post_tag WHERE post_id = :id";
    private static $unlinkTags = "DELETE FROM post_tag WHERE post_id=:id";
    private static $deleteTag = "DELETE FROM tags WHERE id IN (SELECT tag_id FROM post_tag WHERE post_id = :id)
                                 AND tags.frequency = 1";
    private static $updateFreq = "UPDATE tags SET frequency = frequency-1
                                  WHERE id IN (SELECT tag_id FROM post_tag WHERE post_id = :id)
                                  AND frequency > 1";
    private static $getPopularTags = "select t.name, count(tag_id) as frequency from
                                     tags t join post_tag pt on t.id = pt.tag_id join posts p on pt.post_id = p.id where 
                                     status =:status group by tag_id order by frequency desc";
    private static $countTagPublished = "select tp.frequency from
                                        (select t.name, count(tag_id) as frequency from 
                                        tags t join post_tag pt on t.id = pt.tag_id join posts p on pt.post_id = p.id 
                                        where status = :status group by tag_id) as tp where tp.name = :name";
    private static $countTagAll = "select frequency from tags where name=:name";

    private function explodeTags(Array $posts) {
        foreach ($posts as $key => $val) {
            $tags = explode(", ", $val['tags']);
            $posts[$key]['tags'] = $tags;
        }
        return $posts;
    }

    public function countPostsByTag($tag, $status = null) {
        if (!$status) {
            $total = $this->doStatement(self::$countAllPostsWithTag, array('tag' => $tag))->fetchColumn();
        } else {
            $total = $this->doStatement(self::$countPostsWithTagAndStatus, array(
                        'tag' => $tag,
                        'status' => $status,
                    ))->fetchColumn();
        }
        return (int) $total;
    }

    public function countPostsByCategory($category, $status = null) {
        if (!$status) {
            $total = $this->doStatement(self::$countCategoryAll, array('category' => $category))->fetchColumn();
        } else {
            $total = $this->doStatement(self::$countCategory, array(
                        'category' => $category,
                        'status' => $status,
                    ))->fetchColumn();
        }
        return (int) $total;
    }

    public function hasTag($tag, $offset, $limit, $status = null) {
        $limitClause = " LIMIT {$offset}, {$limit}";
        list($column, $placeholder) = array('t.name', 'tag');
        if ($status) {
            $query = sprintf(self::$filterByCategoryAndStatus, $column, $placeholder);
            $query .= $limitClause;
            //$sth = $this->doStatement(self::$findPostsByTagAndStatus, array(
            $sth = $this->doStatement($query, array(
                'tag' => $tag,
                'status' => $status,
            ));
        } else {
            $query = sprintf(self::$findPostsByTag, $column, $placeholder);
            $query .= $limitClause;
            $sth = $this->doStatement($query, array(
                'tag' => $tag
            ));
        }
        $related = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $this->explodeTags($related);
    }

    private function doesCategoryExist($category) {
        $exists = $this->doStatement(self::$findCategory, array('category' => $category))->fetchColumn();
        return $exists;
    }

    public function getAllPosts($offset, $limit) {
        $limitClause = " LIMIT {$offset}, {$limit}";
        $query = self::$getAll . $limitClause;
        //$ret = array();
        $sth = $this->doStatement($query);
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $this->explodeTags($posts);
    }

    public function getPublished($offset, $limit) {
        $limitClause = " LIMIT {$offset}, {$limit}";
        $query = self::$findByStatus . $limitClause;
        $ret = array();
        $sth = $this->doStatement($query, array(
            'status' => self::PUBLISHED
        ));
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $this->explodeTags($posts);
    }

    private function cutBody($body) {
        $cutTag = AppHelper::instance()->getCutTag();
        if ($cut = mb_strpos($body, $cutTag)) {
            list($begining, $ending) = explode($cutTag, $body);
        } else {
            $begining = $body;
            $ending = "";
        }
        return array(
            'begining' => $begining,
            'ending' => $ending,
        );
    }

    private function createCategory($category) {
        if (!$success = $this->doStatement(self::$createCategory, array('category' => $category))->rowCount()) {
            throw new Exception("Не удалось создать категорию");
        }
        return $success;
    }

    private function incrementCategoryCounter($category) {
        if (!$success = $this->doStatement(self::$incrementCategory, array('category' => $category))->rowCount()) {
            throw new Exception("Не удалось обновить счетчик категории");
        }
        return $success;
    }

    private function decrementCategoryCounter($category) {
        if (!$success = $this->doStatement(self::$decrementCategory, array('category' => $category))->rowCount()) {
            throw new Exception("Не удалось обновить счетчик категории");
        }
        return $success;
    }

    private function getCategory($id) {
        if (!$curCategory = $this->doStatement(self::$getCategory, array('id' => $id))->fetchColumn()) {
            throw new Exception("Не удалось получить текущую категорию");
        }
        return $curCategory;
    }

    public function addPost($input) {
        extract($input);
        try {
            $this->dbh->beginTransaction();

            if (!$this->doesCategoryExist($category)) {
                //begin transaction here?
                $this->createCategory($category);
            }
            $this->incrementCategoryCounter($category);


            $body = $this->cutBody($body);

            $parsedown = new Parsedown();
            //var_dump($parsedown);


            $beginingHtml = $parsedown->parse($body['begining']);
            $endingHtml = $parsedown->parse($body['ending']);
            if ($video) {
                $videoTag = AppHelper::instance()->getVideoTag();
                //$embed = "<embed width='420' height='345' src='{$video}' type='application/x-shockwave-flash'></embed>";
                $iframe = "<iframe width='420' height='345' src='{$video}'></iframe>";
                $body['beginingHtml'] = str_replace($videoTag, $iframe, $beginingHtml, $count);
                $body['endingHtml'] = ($count) ? $endingHtml : str_replace($videoTag, $iframe, $endingHtml);
            } else {
                $body['beginingHtml'] = $beginingHtml;
                $body['endingHtml'] = $endingHtml;
            }

            $data = array_merge(array(
                'category' => $category,
                'status' => $status,
                'author' => $user,
                'title' => $title,
                'video' => $video
                    ), $body);
            //try {
            //$this->dbh->beginTransaction();
            if ($status == self::DRAFT) {
                $sth = $this->doStatement(self::$addDraft, $data);
            } else {
                $sth = $this->doStatement(self::$addPost, $data);
            }
            $postId = $this->dbh->lastInsertId();
            if (!empty($tags)) {
                $this->bindTags($postId, $tags);
            }
            $uploader = new ImageManager();
            $imagesUploaded = $uploader->storeImages($postId);
            $this->dbh->commit();
            return $postId;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            throw new Exception("{$this->error}");
            return false;
        }
    }

    public function getPost($id, $raw = false) {
        if (!$raw) {
            $sth = $this->doStatement(self::$getPretty, array('id' => $id));
        } else {
            $sth = $this->doStatement(self::$getRaw, array('id' => $id));
        }
        //$sth=$this->doStatement(self::$findPost, array('id'=>$id));
        if ($found = $sth->fetch(PDO::FETCH_ASSOC)) {
            $sth = $this->doStatement(self::$getTags, array('id' => $found['id']));
            $tags = $sth->fetchAll(PDO::FETCH_COLUMN);
            if ($tags) {
                $found['tags'] = $tags;
            }
            return $found;
        } else {
            throw new Exception("Нет такого поста");
        }
    }

    public function countTotal($status = null, $category = null, $string = null) {

        if ($string) {
            $likeClause = "";
            $likeClause .= ($status || $category) ? " AND " : " WHERE ";
            $likeClause .= "title LIKE '%{$string}%'";
        } else {
            $likeClause = "";
        }
        if ((!$status) && (!$category)) {
            $query = self::$countTotal . $likeClause;
            $sth = $this->doStatement($query);
        } elseif ((!$status ) || (!$category)) {
            $where = " WHERE %s=:%s";
            $clause = ($category) ? 'category' : 'status';
            $query = self::$countTotal . $where;
            $query = sprintf($query, $clause, $clause);
            $query .= $likeClause;
            if ($status) {
                $sth = $this->doStatement($query, array('status' => $status));
            } else {
                $sth = $this->doStatement($query, array('category' => $category));
            }
        } else {
            $where = ' WHERE %1$s=:%1$s AND %2$s=:%2$s';
            $query = self::$countTotal . $where;
            $query = sprintf($query, 'status', 'category');
            $query .= $likeClause;
            $sth = $this->doStatement($query, array(
                'status' => $status,
                'category' => $category,
            ));
        }
        if (!$sth) {
            throw new Exception("Не удалось посчитать количество постов");
        }
        return $sth->fetchColumn();
    }

    public function getPartial($offset, $limit, $like = null, $status = null, $category = null, $orderby = null, $mode = null) {
        $fields = array('title', 'status', 'create_time', 'edit_time', 'category');
        if (!in_array($orderby, $fields)) {
            $orderby = " ORDER BY p.create_time";
            //$orderby = " create_time";
        } else {
            $orderby = " ORDER BY p." . $orderby;
            //$orderby = " " . $orderby;
        }
        //var_dump($orderby);

        switch ($mode) {
            case 1:
                $dir = " DESC";
                break;
            default:
                $dir = " ASC";
        }
        $limitClause = " LIMIT {$offset}, {$limit}";

        //$escaped = addslashes($like);
        if ($like) {
            $likeClause = " AND p.title LIKE '%{$like}%'";
        } else {
            $likeClause = "";
        }

        if ((!$status) && (!$category )) {
            $stmt = self::$getShallow . $likeClause . $orderby . $dir . $limitClause;

            $sth = $this->doStatement($stmt);
        } elseif ((!$status) || (!$category)) {
            $clause = ($status) ? "status" : "category";
            $query = sprintf(self::$getSelectivelyShallow, $clause, $clause);
            $stmt = $query . $likeClause . $orderby . $dir . $limitClause;
            if ($status) {
                $sth = $this->doStatement($stmt, array(
                    "status" => $status));
            } else {
                $sth = $this->doStatement($stmt, array(
                    "category" => $category));
            }
        } else {
            $stmt = self::$getByTwoParameters . $likeClause . $orderby . $dir . $limitClause;
            $sth = $this->doStatement($stmt, array(
                'status' => $status,
                'category' => $category,
            ));
        }

        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);
        if ($posts === false) {
            throw new Exception("fdsafsadfsa");
        }
        return $posts;
    }

    private function removeTags($postId) {
        //find all tags linked to this post and check frequincy for all of them
        //if frequency is 1 -> remove tag else decrement frequency
        //remove all tags linked to this post in post_tage table
        //если нет тэгов, то ничего не делаем
        $hasTags = $this->doStatement(self::$checkTags, array('id' => $postId))->fetchColumn();
        if (!$hasTags) {
            return;
        }
        $this->doStatement(self::$deleteTag, array('id' => $postId));
        $this->doStatement(self::$updateFreq, array('id' => $postId));
        $this->doStatement(self::$unlinkTags, array('id' => $postId));
    }

    public function editPost($input) {
        extract($input);

        $body = $this->cutBody($body);

        $parsedown = new Parsedown();
        //var_dump($parsedown);
        //$beginingHtml = $parsedown->parse(htmlspecialchars($body['begining'], ENT_NOQUOTES));
        //$endingHtml = $parsedown->parse(htmlspecialchars($body['ending']), ENT_NOQUOTES);

        $beginingHtml = $parsedown->parse($body['begining']);
        $endingHtml = $parsedown->parse($body['ending']);

        if ($video) {
            $videoTag = AppHelper::instance()->getVideoTag();
            //$embed = "<embed width='420' height='345' src='{$video}' type='application/x-shockwave-flash'></embed>";
            $iframe = "<iframe width='420' height='345' src='{$video}'></iframe>";
            $body['beginingHtml'] = str_replace($videoTag, $iframe, $beginingHtml, $count);
            $body['endingHtml'] = ($count) ? $endingHtml : str_replace($videoTag, $iframe, $endingHtml);
        } else {
            $body['beginingHtml'] = $beginingHtml;
            $body['endingHtml'] = $endingHtml;
        }
        $data = array_merge(array(
            'title' => $title,
            'author' => $user,
            'status' => $status,
            'id' => $id,
            'category' => $category,
            'video' => $video
                ), $body);

        $this->dbh->beginTransaction();
        try {
            /*
              $curCategory = $this->getCategory($id);
              if (!$this->doesCategoryExist($category)) {
              $this->createCategory($category);
              } */
            $oldValues = $this->doStatement(self::$getOldValues, array('id' => $id))->fetch(PDO::FETCH_ASSOC);
            $curCategory = $oldValues['category'];
            $curStatus = $oldValues['status'];
            $curPublishTime = $oldValues['publish_time'];
            /*
              echo "<pre>";
              var_dump($oldValues);
              echo "</pre>";
              exit();
             * 
             */
            if ($curCategory != $category) {
                $this->decrementCategoryCounter($curCategory);
                $this->incrementCategoryCounter($category);
            }

            //$curStatus = $this->doStatement(self::$getStatus, array('id'=>$id))->fetchColumn();

            if ($status == self::PUBLISHED) {
                $now = new DateTime();
                $publishTime = ($curPublishTime) ? $curPublishTime : $now->format('Y-m-d H:i:s');
            } else {

                $publishTime = ($curPublishTime) ? $curPublishTime : null;
                //var_dump($publishTime);
                //die();
            }


            //$updStmt = sprintf(self::$update, $publishTime);
            /*
              var_dump($curPublishTime);
              var_dump($updStmt);
              die();
             */
            $sth = $this->doStatement(self::$update, array_merge($data, array('publishTime' => $publishTime)));
            $updated = $sth->rowCount();
            if (!$updated) {
                throw new Exception("Не удалось обновить запись");
            }
            $this->removeTags($id);

            if (!empty($tags)) {
                $this->bindTags($id, $tags);
            }

            $uploader = new ImageManager();
            $imagesUploaded = $uploader->storeImages($id);

            $this->dbh->commit();
            return $id;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function bindTags($postId, Array $tags) {
        //var_dump($tags);
        //die();
        foreach ($tags as $name) {
            $sth = $this->doStatement(self::$findTag, array(
                'name' => $name));
            if ($id = $sth->fetchColumn()) {
                $sth = $this->doStatement(self::$updateTag, array(
                    'id' => $id,
                ));
            } else {
                $sth = $this->doStatement(self::$insertTag, array(
                    'name' => $name
                ));

                if (!$id = $this->dbh->lastInsertId()) {
                    throw new Exception("Не удалось добавить тэг {$name}");
                }
            }
            $sth = $this->doStatement(self::$linkTag, array(
                'post' => $postId,
                'tag' => $id,
            ));
        }
    }

    public function removePost($id) {
        $this->dbh->beginTransaction();
        try {
            $this->removeTags($id);
            $category = $this->getCategory($id);
            $this->decrementCategoryCounter($category);

            $imageHandler = new ImageManager();
            $imageHandler->deleteAssociatedImages($id);

            $this->doStatement(self::$delete, array('id' => $id));
            $this->dbh->commit();
            return true;
        } catch (Exception $e) {
            $this->dbh->rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function getCategories($status = null) {
        if ($status) {
            $categories = $this->doStatement(self::$categoriesPublished, array(
                        'status' => $status))->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $categories = $this->doStatement(self::$categories)->fetchAll(PDO::FETCH_ASSOC);
        }
        return $categories;
    }

    public function getByCategory($category, $offset, $limit, $status = null) {
        $limitClause = " LIMIT {$offset}, {$limit}";
        if ($status) {
            $query = sprintf(self::$filterByCategoryAndStatus, "p.category", 'category');
            $query .= $limitClause;
            $posts = $this->doStatement($query, array(
                        'category' => $category,
                        'status' => $status,
                    ))->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $query = sprintf(self::$findPostsByTag, "p.category", 'category');
            $query .= $limitClause;
            $posts = $this->doStatement($query, array(
                        'category' => $category
                    ))->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->explodeTags($posts);
    }

    public function deleteCategory($category) {
        $success = $this->doStatement(self::$deleteCategory, array('category' => $category))->rowCount();
        return $success;
    }

    public function getTagCloud($limit = 20) {
        $limitClause = " LIMIT {$limit}";
        $query = self::$getPopularTags . $limitClause;

        $tags = $this->doStatement($query, array('status' => self::PUBLISHED))->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;
        foreach ($tags as $item) {
            $total+=$item['frequency'];
        }
        $weights = array();
        if ($total > 0) {
            foreach ($tags as $item) {
                $name = $item['name'];
                $weights[$name] = 8 + (int) (128 * $item['frequency'] / ($total + 12));
            }
            ksort($weights);
        }
        return $weights;
    }

    public function countTag($name, $status = null) {
        if (!$status) {
            $num = $this->doStatement(self::$countTagAll, array('name' => $name))->fetchColumn();
        } else {
            $num = $this->doStatement(self::$countTagPublished, array('name' => $name, 'status' => self::PUBLISHED))
                    ->fetchColumn();
        }
        return $num;
    }

    public function getSimilar($term) {
        $sql = "SELECT name from tags where name LIKE '{$term}%'";
        $tags = $this->doStatement($sql)->fetchAll(PDO::FETCH_COLUMN, 0);
        return $tags;
    }

}
