<?php

class AdminController extends Controller {

    private $pendingNum;

    public function getPendingNum() {
        return $this->pendingNum;
    }

    public function __construct() {
        $commentHandler = new CommentManager();
        $this->pendingNum = $commentHandler->CountPending();
    }

    protected function indexAction() {
        return $this->listAction();
    }

    protected function listAction() {
        //$tag = AppHelper::instance()->getRequest()->properties['tag'];
        $model = new PostManager();
        $route = AppHelper::instance()->getRequest()->getRoute(true);
        $query = $_SERVER['QUERY_STRING'];
        if (!empty($query)) {
            if (mb_strpos($query, "page") !== false) {
                $query = mb_substr($query, 0, mb_strpos($query, "&"));
            }
        }
        $route .= "?" . $query;

        $tag = filter_input(INPUT_GET, "tag", FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_GET, "category", FILTER_DEFAULT);
        //var_dump($category);
        if ($tag) {
            $title = "Записи с тэгом '{$tag}'";
            $total = $model->countPostsByTag($tag);
            //var_dump($posts);
        } elseif ($category) {

            $title = "Записи в категории {$category}";
            $total = $model->countPostsByCategory($category);
            // var_dump($posts);
        } else {
            $title = "Все записи";
            $total = $model->countTotal();
        }

        $limit = AppHelper::instance()->postsPerPage();
        $lastPage = $pagesNum = ceil($total / $limit);
        //var_dump($lastPage);
        if (!filter_has_var(INPUT_GET, 'page')) {
            $page = 1;
        } else {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'min_range' => 1,
                'max_range' => $lastPage));
        }
        $offset = ($page - 1) * $limit;

        if ($tag) {
            $posts = $model->hasTag($tag, $offset, $limit);
        } elseif ($category) {
            $posts = $model->getByCategory($category, $offset, $limit);
        } else {
            $posts = $model->getAllPosts($offset, $limit);
        }

        $commentHandler = new CommentManager();
        $latest = $commentHandler->getLatest();
        $categories = $model->getCategories();

        $data = array('posts' => $posts, 'categories' => $categories);
        if (isset($title)) {
            $data['title'] = $title;
        }
        $data = array_merge($data, array(
            'query' => $query,
            'lastPage' => $lastPage,
            'limit' => $limit,
            'page' => $page,
            'curURL' => $route,
            'total' => $total,
            'category' => $category,
            'latest' => $latest,
        ));
        $cloud = $model->getTagCloud();
        // var_dump($cloud);
        //exit();
        $data['cloud'] = $cloud;

        //$parsedown = new Parsedown();
        //$beginingHtml = $parsedown->parse($post['begining']);

        $this->render("posts.html.twig", $data);
    }

    protected function getFeedback() {
        $this->data['user'] = $_SESSION['user'];
        $this->data['pendingNum'] = $this->pendingNum;
        return parent::getFeedback();
    }

    public function doExecute($action = null) {
        if ($this->isAdmin()) {
            $this->data['active'] = $action;
            parent::doExecute($action);
        } else {
            header('Location: /site/login/');
            exit();
        }
    }

    private function isAdmin() {
        if (isset($_SESSION['user'])) {
            return true;
        } else {
            return false;
        }
    }

    protected function logoutAction() {
        unset($_SESSION['user']);
        header('Location: /');
        exit();
    }

    protected function addAction() {
        $model = new PostManager();
        $categories = $model->getCategories();
        $videoTag = AppHelper::instance()->getVideoTag();
        $cutTag = AppHelper::instance()->getCutTag();
        $this->render('add_post.html.twig', array(
            'title' => 'Новая запись',
            'categories' => $categories,
            'videoTag'=>$videoTag,
            'cutTag'=>$cutTag,
        ));
    }

    protected function storeAction() {
        if (!$this->isFilled(array('title', 'body', 'status', 'tags'))) {
            $message = "Поля, помеченные звёздочкой должны быть заполнены";
            $this->setFeedback($message, true);
            if (filter_has_var(INPUT_POST, 'id')) {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                header("Location: /admin/edit?id={$id}");
            } else {
                header("Location: /admin/add/");
            }
            exit();
        }

        $defaultCategory = AppHelper::instance()->getDefaultCategory();

        if (isset($_POST['category_type']) && ($_POST['category_type'] == "new_category")) {
            $category = (isset($_POST['new_category']) && (!empty($_POST['new_category']) ) ) ?
                    $_POST['new_category'] : $defaultCategory;
        } elseif (isset($_POST['category_type']) && ($_POST['category_type'] == "categories")) {
            $category = (isset($_POST['new_category']) && (!empty($_POST['categories']) ) ) ?
                    $_POST['categories'] : $defaultCategory;
        } elseif (isset($_POST['new_category']) && (!empty($_POST['new_category']) )) {
            $category = $_POST['new_category'];
        } else {
            $category = $defaultCategory;
        }


        $trusty = filter_input_array(INPUT_POST, array(
            'status' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array('min_range' => 1, 'max_range' => 3, 'default' => 2)
            ),
            'tags' => array(
                'filter' => FILTER_CALLBACK,
                'options' => function($value) {
            $tags = explode(",", $value);
            $valid = array();
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (empty($tag)) {
                    continue;
                } else {
                    $valid[] = $tag;
                }
            }
            return array_unique($valid);
        },
            ),
            'id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array('min_range' => 1)
            ),
            'video' => array(
                'filter' => FILTER_DEFAULT
        )));

        $fields = array('title', 'body', 'status', 'tags', 'video');
        $post = array();
        array_walk($_POST, function($val, $key) use (&$post, $fields) {
            if (in_array($key, $fields)) {
                $post[$key] = $val;
            }
        });
        $input = array_merge($post, $trusty);

        // $input = array_merge($_POST, $trusty);
        $input['user'] = $_SESSION['user'];

        $input['category'] = $category;
        $model = new PostManager();

        if ($input['id']) {
            $message = 'Изменения успешно сохранены!';
            $this->setFeedback($message);
            $success = $model->editPost($input);
            if (!$success) {
                $message = $model->getError();
                $error = true;
                $this->setFeedback($message, $error);
                header("Location: /admin/edit?id={$input['id']}");
                exit();
            }
        } else {
            //var_dump($input);
            $success = $model->addPost($input);
            $this->setFeedback("Запись успешно добавлена!");
            if (!$success) {
                $message = $model->getError();
                $error = true;
                $this->setFeedback($message, $error);
                header("Location: /admin/add/");
                exit();
            }
        }
        header("Location: /admin/view?id={$success}");
        exit();
    }

    protected function manageAction() {
        $model = new PostManager();
        $args = array(
            'status' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1,
                    'max_range' => 3,
                ),
                'flags' => FILTER_NULL_ON_FAILURE),
            'category' => array(
                'filter' => FILTER_DEFAULT,
                'flags' => FILTER_NULL_ON_FAILURE,),
            'title_search' => array(
                'filter' => FILTER_SANITIZE_MAGIC_QUOTES,
                'flags' => FILTER_NULL_ON_FAILURE,
            ),);
        $safeInput = filter_input_array(INPUT_GET, $args, true);

        if (is_array($safeInput)) {
            extract($safeInput);
        } else {
            $status = null;
            $category = null;
            $title_search = null;
        }
        if (empty($title_search)) {
            $title_search = null;
        }
        $string = $title_search;

        $categories = $model->getCategories(PostManager::PUBLISHED);
        try {
            $total = $model->countTotal($status, $category, $string);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        //var_dump($total);
        $limit = AppHelper::instance()->postsPerPage();
        $lastPage = $pagesNum = ceil($total / $limit);
        //var_dump($lastPage);
        if (!filter_has_var(INPUT_GET, 'page')) {
            $page = 1;
        } else {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'min_range' => 1,
                'max_range' => $lastPage));
        }
        $offset = ($page - 1) * $limit;
        $orderby = filter_input(INPUT_GET, 'c', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
        $dir = filter_input(INPUT_GET, 'd', FILTER_VALIDATE_INT, array(
            'min_range' => 0,
            'max_range' => 1));
        try {
            $posts = $model->getPartial($offset, $limit, $string, $status, $category, $orderby, $dir);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $route = AppHelper::instance()->getRequest()->getRoute(true);
        $query = $_SERVER['QUERY_STRING'];

        if (!empty($query)) {
            if (mb_strpos($query, "page") !== false) {
                $query = explode("&", $query);
                $pageClause = array_pop($query);
                $query = join("&", $query);
            }
        }
        $route .= "?" . $query;

        $this->render('manage.html.twig', array(
            'title' => 'Страница управления',
            'lastPage' => $lastPage,
            'limit' => $limit,
            'page' => $page,
            'curURL' => $route,
            'posts' => $posts,
            'query' => $query,
            'status' => $status,
            'category' => $category,
            'categories' => $categories,
            'column' => $orderby,
            'dir' => $dir,
            'array' => $safeInput,
            'string' => $string
        ));
    }

    protected function editAction() {
        $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
        $model = new PostManager();
        $categories = $model->getCategories();
        $post = $model->getPost($id, true);
        if (!$post) {
            throw new NotFoundException("couldn't find requested post" . $id);
        }
        $videoTag = AppHelper::instance()->getVideoTag();
        $cutTag = AppHelper::instance()->getCutTag();
        $this->render('edit.html.twig', array(
            'post' => $post,
            'title' => 'Редактировать запись',
            'categories' => $categories,
            'videoTag'=>$videoTag,
            'cutTag'=>$cutTag,
        ));
    }

    protected function deleteAction() {
        $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
        if (!$id) {
            throw new NotFoundException("couldn't find requested post" . $id);
        }
        $model = new PostManager();
        $success = $model->removePost($id);
        if (!$success) {
            $error = 1;
            $this->setFeedback($model->getError(), $error);
        } else {
            $this->setFeedback("Пост успешно удалён!");
        }
        header('Location: /admin/manage');
        exit();
    }

    protected function approveCommentsAction() {
        $commentsHandler = new CommentManager();

        $total = $commentsHandler->countAll();

        $limit = AppHelper::instance()->commentsPerPage();
        $lastPage = $pagesNum = ceil($total / $limit);

        if (!filter_has_var(INPUT_GET, 'page')) {
            $page = 1;
        } else {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'min_range' => 1,
                'max_range' => $lastPage));
        }
        $offset = ($page - 1) * $limit;

        $comments = $commentsHandler->getPartial($offset, $limit);

        $route = AppHelper::instance()->getRequest()->getRoute(true);
        $query = $_SERVER['QUERY_STRING'];

        if (!empty($query)) {
            if (mb_strpos($query, "page") !== false) {
                $query = mb_substr($query, 0, mb_strpos($query, "&"));
            }
        }
        $route .= "?" . $query;

        $this->render('comments-approve.html.twig', array(
            'title' => 'Управление коомментариями',
            'comments' => $comments,
            'page' => $page,
            'lastPage' => $lastPage,
            'curURL' => $route,
            'query' => $query,
        ));
    }

    protected function approveAction() {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => 'FILTER_NULL_ON_FAILURE'));
        if (!$id) {
            $this->setFeedback("комментарий не найден", 1);
            header('Location: /admin/approveComments');
            exit();
        }
        $commentHandler = new CommentManager();
        $success = $commentHandler->approveComment($id);
        if (!$success) {
            //TODO something
        }
        header('Location: /admin/approveComments');
        exit();
    }

    protected function deleteCommentAction() {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => 'FILTER_NULL_ON_FAILURE'));
        if (!$id) {
            $this->setFeedback("комментарий не найден", 1);
            header('Location: /admin/approveComments');
            exit();
        }
        $commentHandler = new CommentManager();
        $success = $commentHandler->deleteComment($id);
        if (!$success) {
            //TODO something
        }
        header('Location: /admin/approveComments');
        exit();
    }

    protected function deleteCategoryAction() {
        $category = filter_input(INPUT_GET, 'name', FILTER_DEFAULT);
        $model = new PostManager();
        $success = $model->deleteCategory($category);
        if (!$success) {
            $this->setFeedback("Не удалось удалить категорию", 1);
            header("Location: /admin/list?category={$category}");
        }
        $this->setFeedback("Категория успешно удалена!");
        header("Location: /admin/list");
    }

    protected function commentAction() {
        $input = filter_input_array(INPUT_POST, array(
            'parentId' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_NULL_ON_FAILURE,
            ),
            'postId' => array(INPUT_POST, array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_NULL_ON_FAILURE,
                ))
        ));
        //$id = filter_input(INPUT_POST, 'postId', FILTER_VALIDATE_INT);
        if (!$this->isFilled(array('body', 'postId'))) {
            $this->setFeedback("Поля со звёздочкой обязательны", 1);
            header("Location: /admin/view?id={$input['postId']}");
            exit();
        }
        require_once('recaptchalib.php');
        $privatekey = "6LdBU-8SAAAAAAF2Bhs95JcYDeVNTaR1fN5NbCM_";
        $resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

        if (!$resp->is_valid) {
            $this->setFeedback("Вы неверно ввели каптчу. Попробуйте еще раз", 1);
            header("Location: /admin/view?id={$input['postId']}");
            exit();
        }

        $fields = array('name', 'body', 'postId', 'parentId');
        $post = array();
        array_walk($_POST, function($val, $key) use (&$post, $fields) {
            if (in_array($key, $fields)) {
                $post[$key] = $val;
            }
        });
        $post['name'] = AppHelper::instance()->getUserSign($_SESSION['user']);
        $post['email'] = AppHelper::instance()->getUserEmail($_SESSION['user']);
        $post['notify'] = 0;
        $comment = array_merge($post, $input);
        $comment['admin'] = 1;
        $model = new CommentManager();
        try {
            $commentId = $model->addComment($comment);
            $message = 'Комментарий успешно добавлен!';
            $this->setFeedback($message);
            header("Location: /admin/view?id={$input['postId']}");
            exit();
        } catch (Exception $e) {
            //do something else on production stage
            echo $e->getMessage();
            exit();
        }
    }
    
    protected function countTagAction() {
      $tag = filter_input(INPUT_GET, 'tag', FILTER_DEFAULT);
      $model = new PostManager();
      $num = $model->countTag($tag);
      if ($num) {
          echo $num;
      } else {
          return false;
      }
    }

}
