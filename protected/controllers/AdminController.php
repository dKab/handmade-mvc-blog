<?php

class AdminController extends Controller
{
    private $pendingNum;
    
    public function getPendingNum()
    {
        return $this->pendingNum;
    }
    
    public function __construct()
    {
        $commentHandler = new CommentManager();
        $this->pendingNum = $commentHandler->CountPending();
    }
    
    protected function indexAction()
    {
        return $this->listAction();
    }
    
    protected function listAction()
    {
        //$tag = AppHelper::instance()->getRequest()->properties['tag'];
        $model = new PostManager();
        $route = AppHelper::instance()->getRequest()->getRoute(true);
        $query=$_SERVER['QUERY_STRING'];
        
        if (! empty($query) ) {
            if ( mb_strpos($query, "page") !== false ) {
                $query = mb_substr($query, 0, mb_strpos($query, "&"));
            }
        }
        $route .= "?" . $query;
        
        $tag = filter_input(INPUT_GET, "tag", FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_GET, "category", FILTER_DEFAULT);
        //var_dump($category);
        if ( $tag ) {       
            $title = "Записи с тэгом '{$tag}'";
            $total = $model->countPostsByTag($tag);
            //var_dump($posts);
        } elseif ($category) {
            
            $title="Записи в категории {$category}";
            $total = $model->countPostsByCategory($category);
                       // var_dump($posts);
        } else {
            $total = $model->countTotal();
        }
        
        $limit = AppHelper::instance()->postsPerPage();
        $lastPage = $pagesNum = ceil($total/$limit);
        //var_dump($lastPage);
        if ( ! filter_has_var(INPUT_GET, 'page') ) {
            $page = 1;
        } else {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'min_range'=>1,
                'max_range'=>$lastPage));
        }
        $offset = ($page-1) * $limit;
        
        if ( $tag ) {
             $posts = $model->hasTag($tag, $offset, $limit);
        } elseif ( $category ) {
            $posts = $model->getByCategory($category, $offset, $limit);
        } else {
            $posts = $model->getAllPosts($offset, $limit);
        }
        
        
        
        
        /*
        $tag = filter_input(INPUT_GET, "tag", FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_GET, 'category', FILTER_DEFAULT);
        $model = new PostManager();
        if ( $tag ) {  
            $posts = $model->hasTag($tag);
            $title = "Записи с тэгом '{$tag}'";
        } elseif ($category) {
            $posts = $model->getByCategory($category);
            $title="Записи в категории {$category}";
        } else {
            $posts = $model->getAllPosts();
            $title = "Все записи";
        }
        */
        
        
        $categories = $model->getCategories();
        
       $data = array('posts'=>$posts, 'categories'=>$categories);
        if (isset($title)) {
            $data['title'] = $title;
        }
        /*
        $data['query']=$query;
            $data['lastPage']=$lastPage;
            $data['limit']=$limit;
            $data['page']=$page;
            $data['curURL']=$route;
            $data['total']=$total;
        */
        $data = array_merge($data, array(
            'query'=>$query,
            'lastPage'=>$lastPage,
            'limit'=>$limit,
            'page'=>$page,
            'curURL'=>$route,
            'total'=>$total,
            'category'=>$category,
        ));
        
        //$parsedown = new Parsedown();
        //$beginingHtml = $parsedown->parse($post['begining']);
        
        $this->render("posts.html.twig", $data); 
    }
    
    protected function getFeedback() {
        $this->data['user'] = $_SESSION['user'];
        $this->data['pendingNum'] = $this->pendingNum;
        return parent::getFeedback();
    }
    
    public function doExecute($action=null)
    {
        if ( $this->isAdmin() ) {
            parent::doExecute($action);
        } else {
            header('Location: /site/login/');
            exit();
        }
    }
    
    private function isAdmin()
    {
        if ( isset($_SESSION['user']) ) {
            return true;    
        } else { return false; }
    }
    
    protected function logoutAction()
    {
        unset($_SESSION['user']);
        header('Location: /');
        exit();
    }
    
    protected function addAction()
    {
        $model = new PostManager();
        $categories = $model->getCategories();
        $this->render('add_post.html.twig', array(
            'title'=>'Новая запись',
            'categories'=>$categories,
        ));
    }
    
    protected function storeAction()
    {   
        if ( ! $this->isFilled( array('title', 'body', 'status') ) ) {
                $_SESSION['feedback'] = "Поля, помеченные звёздочкой должны быть заполнены";
                if ( filter_has_var(INPUT_POST, 'id') ) {
                    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                    header("Location: /admin/edit?id={$id}");
                } else {
                    header("Location: /admin/add/");
                }
                exit();
        }
        
        $defaultCategory = AppHelper::instance()->getDefaultCategory();
        
        if ( isset($_POST['category_type']) && ($_POST['category_type'] =="new_category") ) {
            $category = (isset($_POST['new_category']) && ( ! empty($_POST['new_category']) ) ) ? 
                    $_POST['new_category'] : $defaultCategory; 
        } elseif (isset($_POST['category_type']) && ($_POST['category_type'] =="categories") ) {
            $category = (isset($_POST['new_category']) && ( ! empty($_POST['categories']) ) ) ?
                    $_POST['categories'] : $defaultCategory;
        } elseif ( isset($_POST['new_category']) && (! empty($_POST['new_category']) ) ) {
            $category = $_POST['new_category'];
        } else {
            $category = $defaultCategory;
        }
        
                
        $trusty = filter_input_array(INPUT_POST , array(
                'status'=>array(
                    'filter'=>FILTER_VALIDATE_INT,
                    'options'=>array('min_range' => 1, 'max_range' => 3, 'default'=>2)
                ),
                'tags'=>array(
                    'filter'=>FILTER_CALLBACK,
                    'options'=>function($value) {
                           $tags = explode(",", $value);
                           $valid = array();
                           foreach( $tags as $tag) {
                               $tag = trim($tag);
                               if ( empty($tag) ) { continue; }
                               else {
                                   $valid[] = $tag;
                               }
                           }    
                           return array_unique($valid);
                       },
                ),
                'id'=>array(
                    'filter'=>FILTER_VALIDATE_INT,
                    'options'=>array('min_range'=>1)
                )));
                       
         $fields = array('title', 'body', 'status', 'tags');
         $post = array();
         array_walk($_POST, function($val, $key) use (&$post, $fields) {
            if ( in_array($key, $fields)) {
                $post[$key]=$val;
            }
        });
        $input= array_merge($post, $trusty);               
                       
        // $input = array_merge($_POST, $trusty);
         $input['user'] = $_SESSION['user'];
         
         $input['category'] = $category;
         /*
         echo "<pre>";
         print_r($input);
         echo "</pre>";
         exit();
          * 
          */
         
         $model = new PostManager();
         if ( $input['id'] ) {
             $_SESSION['feedback'] = 'Изменения успешно сохранены!';
             $success = $model->editPost($input);
             if ( ! $success ) {
                 $_SESSION['feedback'] = $model->getError();
                 header("Location: /admin/edit?id={$input['id']}");
                 exit();
             } 
         } else {
         //var_dump($input);
             $success = $model->addPost($input);
             if ( ! $success ) {
                 $_SESSION['feedback'] = $model->getError();
                 header("Location: /admin/add/");
                 exit();
             }
         }
         header("Location: /admin/view?id={$success}");
         exit();
    }
    
    protected function manageAction()
    {
        $model = new PostManager();
            $status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT, array(
                'min_range'=>1,
                'max_range'=>3,
            ));
        $total = $model->countTotal($status);
        //var_dump($total);
        $limit = AppHelper::instance()->postsPerPage();
        $lastPage = $pagesNum = ceil($total/$limit);
        //var_dump($lastPage);
        if ( ! filter_has_var(INPUT_GET, 'page') ) {
            $page = 1;
        } else {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'min_range'=>1,
                'max_range'=>$lastPage));
        }
        $offset = ($page-1) * $limit; 
        $posts = $model->getPartial($offset, $limit, $status);
        
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
        $query=$_SERVER['QUERY_STRING'];
        var_dump($query);
        if (! empty($query) ) {
            if ( mb_strpos($query, "page") !== false ) {
                $query = mb_substr($query, 0, mb_strpos($query, "&"));
            }
        }
        $route .= "?" . $query;
        $this->render('manage.html.twig', array(
            'title'=>'Страница управления',
            'lastPage'=>$lastPage,
            'limit'=>$limit,
            'page'=>$page,
            'curURL'=>$route,
            'posts'=>$posts,
            'query'=>$query,
            'status'=>$status,
        ));
        
        /*
        $model = new PostManager();
        $posts = $model->getShallow();
        $this->render("manage.html.twig", array(
            'posts'=>$posts,
            'title'=>'Управление записями'
        ));
         * 
         */
    }
    
    protected function editAction()
    {
        $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
        $model = new PostManager();
        $categories = $model->getCategories();
        $post = $model->getPost($id, true);
        if ( ! $post ) {
           throw new NotFoundException("couldn't find requested post" . $id);
        }
        
        $this->render('edit.html.twig', array(
           'post'=>$post,
            'title'=>'Редактировать запись',
            'categories'=>$categories,
        ));
    }
    
    protected function deleteAction()
    {
        $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
           if ( ! $id ) {
               throw new NotFoundException("couldn't find requested post" . $id);
           }
        $model = new PostManager();
        $success = $model->removePost($id);
        if ( ! $success ) {
            $_SESSION['feedback'] = $model->getError();
        } else {
            $_SESSION['feedback'] = "Пост успешно удалён!";
        }
       header('Location: /admin/manage');
       exit();
                
    }
    
    protected function approveCommentsAction()
    {
        $commentsHandler = new CommentManager();
        
        $total = $commentsHandler->countAll();
 
        $limit = AppHelper::instance()->commentsPerPage();
        $lastPage = $pagesNum = ceil($total/$limit);

        if ( ! filter_has_var(INPUT_GET, 'page') ) {
            $page = 1;
        } else {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'min_range'=>1,
                'max_range'=>$lastPage));
        }
        $offset = ($page-1) * $limit; 
        
        $comments = $commentsHandler->getPartial($offset, $limit);
        
        $route = AppHelper::instance()->getRequest()->getRoute(true);
        $query=$_SERVER['QUERY_STRING'];
        
        if (! empty($query) ) {
            if ( mb_strpos($query, "page") !== false ) {
                $query = mb_substr($query, 0, mb_strpos($query, "&"));
            }
        }
        $route .= "?" . $query;
        
        $this->render('comments-approve.html.twig', array(
            'title'=>'Управление коомментариями',
            'comments'=>$comments,
            'page'=>$page,
            'lastPage'=>$lastPage,
            'curURL'=>$route,
            'query'=>$query,
        ));
    }
    
    protected function approveAction()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => 'FILTER_NULL_ON_FAILURE'));
        if ( ! $id ) {
            $_SESSION['feedback']="комментарий не найден";
            header('Location: /admin/approveComments');
            exit();
        }
        $commentHandler = new CommentManager();
        $success = $commentHandler->approveComment($id);
        if ( ! $success ) {
            //TODO something
        }
        header('Location: /admin/approveComments');
        exit();
    }
    
    protected function deleteCommentAction()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => 'FILTER_NULL_ON_FAILURE'));
        if ( ! $id ) {
            $_SESSION['feedback']="комментарий не найден";
            header('Location: /admin/approveComments');
            exit();
        }
        $commentHandler = new CommentManager();
        $success = $commentHandler->deleteComment($id);
        if ( ! $success ) {
            //TODO something
        }
        header('Location: /admin/approveComments');
        exit();
    }
    
    protected function deleteCategoryAction()
    {
        $category =  filter_input(INPUT_GET, 'name', FILTER_DEFAULT);
        $model=new PostManager();
        $success = $model->deleteCategory($category);
        if ( ! $success) {
            $_SESSION['feedback'] = "Не удалось удалить категорию";
            header("Location: /admin/list?category={$category}");
        }
        $_SESSION['feedback'] = "Категория успешно удалена!";
        header("Location: /admin/list");
    }
    
    protected function commentAction()
    {
                $input = filter_input_array(INPUT_POST, array(
                  'parentId'=>array(
                      'filter'=>FILTER_VALIDATE_INT,
                      'flags'=>FILTER_NULL_ON_FAILURE,
                  ),
                  'postId'=>array(INPUT_POST, array(
                      'filter'=>FILTER_VALIDATE_INT,
                      'flags'=>FILTER_NULL_ON_FAILURE,
                  ))
            ));
        //$id = filter_input(INPUT_POST, 'postId', FILTER_VALIDATE_INT);
        if ( ! $this->isFilled(array('body', 'postId') ) ) {
           $_SESSION['feedback'] = "Поля со звёздочкой обязательны";
            header("Location: /admin/view?id={$input['postId']}");
            exit();
        }
        require_once('recaptchalib.php');
        $privatekey = "6LdBU-8SAAAAAAF2Bhs95JcYDeVNTaR1fN5NbCM_";
        $resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

        if (!$resp->is_valid) {
            $_SESSION['feedback'] = "Вы неверно ввели каптчу. Попробуйте еще раз.";
            header("Location: /admin/view?id={$input['postId']}");
            exit();
        }
        
        $fields = array('name', 'body', 'postId', 'parentId');
        $post = array();
         array_walk($_POST, function($val, $key) use (&$post, $fields) {
            if ( in_array($key, $fields)) {
                $post[$key]=$val;
            }
        });
        $post['name'] = AppHelper::instance()->getUserSign($_SESSION['user']);
        $post['email'] = AppHelper::instance()->getUserEmail($_SESSION['user']);
        $post['notify'] = 0;
        $comment= array_merge($post, $input);
        $comment['admin'] = 1;
        $model = new CommentManager();
        try {
            $commentId = $model->addComment($comment);
            $message = 
                    'Комментарий успешно добавлен!';
            $_SESSION['feedback'] = $message;
            header("Location: /admin/view?id={$input['postId']}");
            exit();
        } catch (Exception $e) {
            //do something else on production stage
            echo $e->getMessage();
            exit();
        }
        
    }
    
}
