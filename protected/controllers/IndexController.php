<?php

class IndexController extends Controller {

    protected function indexAction() {
        return $this->listAction();
    }

    protected function listAction() {
        $model = new PostManager();
        //$posts = $model->getPartial($offset, $limit, $status);
        //var_dump($offset);
        //var_dump($limit);
        //var_dump($posts);
        /*
          echo "<pre>";
          print_r($_SERVER);
          echo "</pre>";
         */
        // $curURL = substr($_SERVER['REQUEST_URI'], 0, $)$_SERVER['REQUEST_URI'];
        //$route=$_SERVER['REQUEST_URI'];
        $route = AppHelper::instance()->getRequest()->getRoute(true);
        if (mb_strlen($route) <= 1) {
            $route = "/index/list";
        }
        //var_dump($route);
        $query = $_SERVER['QUERY_STRING'];

        if (!empty($query)) {
            if (mb_strpos($query, "page") !== false) {
                $query = mb_substr($query, 0, mb_strpos($query, "&"));
            }
        }
        $route .= "?" . $query;


        //$tag = AppHelper::instance()->getRequest()->properties['tag'];
        $tag = filter_input(INPUT_GET, "tag", FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_GET, "category", FILTER_DEFAULT);

        //var_dump($category);
        if ($tag) {

            $title = "Записи с тэгом '{$tag}'";
            $total = $model->countPostsByTag($tag, PostManager::PUBLISHED);
            //var_dump($posts);
        } elseif ($category) {

            $title = "Записи в категории {$category}";
            $total = $model->countPostsByCategory($category, PostManager::PUBLISHED);
            // var_dump($posts);
        } else {

            $total = $model->countTotal(PostManager::PUBLISHED);
        }

        // $total = $model->countTotal(PostManager::PUBLISHED);
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
        // $parsedown = new Parsedown();
        //var_dump($parsedown);
        //$beginingHtml = $parsedown->parse($post['begining']);
        $status = PostManager::PUBLISHED;
        $categories = $model->getCategories($status);
        
        $commentHandler = new CommentManager();
        $latest = $commentHandler->getLatest();

        if ($tag) {
            $posts = $model->hasTag($tag, $offset, $limit, PostManager::PUBLISHED);
        } elseif ($category) {
            $posts = $model->getByCategory($category, $offset, $limit, PostManager::PUBLISHED);
        } else {
            $posts = $model->getPublished($offset, $limit);
        }

        $data = array('posts' => $posts, 'categories' => $categories);
        if (isset($title)) {
            $data['title'] = $title;
        }
        $data = array_merge($data, array(
            'query'=>$query,
            'lastPage'=>$lastPage,
            'limit'=>$limit,
            'page'=>$page,
            'curURL'=>$route,
            'total'=>$total,
            'latest'=>$latest
        ));
        /*
        $data['query'] = $query;
        $data['lastPage'] = $lastPage;
        $data['limit'] = $limit;
        $data['page'] = $page;
        $data['curURL'] = $route;
        $data['total'] = $total;
        $data['latest'] = $latest;
        */
        $cloud = $model->getTagCloud();
        // var_dump($cloud);
        //exit();
        $data['cloud'] = $cloud;

        $this->render("posts.html.twig", $data);
        //'beginingHtml'=>$beginingHtml,); 
    }

    protected function loginAction() {
        $this->render('login.html.twig', array(
            'title' => 'Вход',
        ));
    }

    protected function authAction() {
        $model = new PostManager();
        $guard = new AuthManager();

        //$name = AppHelper::getRequest()->getProperty('name');
        //$password = AppHelper::getRequest()->getProperty('password');
        if (!$this->isFilled(array('name', 'password'))) {
            $_SESSION['feedback'] = "Все поля обязательны для заполенения";
            header("Location: /index/login");
            exit();
        }
        $name = $_POST['name'];
        $password = $_POST['password'];
        $user = $guard->login(array(
            'name' => $name,
            'password' => $password,
        ));
        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: /admin');
            exit();
        } else {
            $_SESSION['feedback'] = "Неверное имя пользователя или пароль!";
            header("Location: /index/login");
            exit();
        }
    }

    protected function commentAction() {
        $id = filter_input(INPUT_POST, 'postId', FILTER_VALIDATE_INT);
        if (!$this->isFilled(array('name', 'email', 'body', 'postId'))) {
            $_SESSION['feedback'] = "Поля со звёздочкой обязательны";
            header("Location: /index/view?id={$id}");
            exit();
        }
        require_once('recaptchalib.php');
        $privatekey = "6LdBU-8SAAAAAAF2Bhs95JcYDeVNTaR1fN5NbCM_";
        $resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

        if (!$resp->is_valid) {
            $_SESSION['feedback'] = "Вы неверно ввели каптчу. Попробуйте еще раз.";
            header("Location: /index/view?id={$id}");
            exit();
        }
        $input = filter_input_array(INPUT_POST, array(
            'email' => array(
                'filter' => FILTER_VALIDATE_EMAIL,
            ),
            'notify' => array(
                'filter' => FILTER_VALIDATE_BOOLEAN
            ),
            'parentId' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_NULL_ON_FAILURE,
            )
        ));
        $fields = array('name', 'body', 'email', 'notify', 'postId', 'parentId');
        $post = array();
        array_walk($_POST, function($val, $key) use (&$post, $fields) {
            if (in_array($key, $fields)) {
                $post[$key] = $val;
            }
        });

        $comment = array_merge($post, $input);
        $comment['admin'] = 0;
        if (!$input['email']) {
            $_SESSION['feedback'] = "Еmail должен быть корректным е-mail адресом";
            header("Location: /index/view?id={$id}");
            exit();
        }

        $model = new CommentManager();
        try {
            $commentId = $model->addComment($comment);
            $message = ( (bool) (string) AppHelper::instance()->getCommentRule() ) ?
                    'Спасибо за комментарий. Он будет опубликован после того, как пройдет модерацию.' :
                    'Комментарий успешно добавлен!';
            $_SESSION['feedback'] = $message;
            header("Location: /index/view?id={$id}");
            exit();
        } catch (Exception $e) {
            //do something else on production stage
            echo $e->getMessage();
            exit();
        }
    }

}
