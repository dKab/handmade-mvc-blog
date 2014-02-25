<?php

class AdminController extends Controller
{
    protected function indexAction()
    {
        return $this->listAction();
    }
    
    protected function listAction()
    {
        //$tag = AppHelper::instance()->getRequest()->properties['tag'];
        $tag = filter_input(INPUT_GET, "tag", FILTER_SANITIZE_STRING);
        $model = new PostManager();
        if ( $tag ) {  
            $posts = $model->hasTag($tag);
            $title = "Записи с тэгом '{$tag}'";
        } else {
            $posts = $model->getAllPosts();
            $title = "Все записи";
        }
        $this->render("posts.html.twig", array(
            'posts'=>$posts,
            'title'=>$title,
        )); 
    }
    
    protected function getFeedback() {
        $this->data['user'] = $_SESSION['user'];
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
        $this->render('add_post.html.twig', array(
            'title'=>'Новая запись',
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
                )));
         $input = array_merge($_POST, $trusty);
         $input['user'] = $_SESSION['user'];
         $model = new PostManager();
         if ( isset($id) ) {
             $input = array_merge($input, array('id'=>$id));
             $success = $model->editPost($id, $input);
             if ( ! $success ) {
                 $_SESSION['feedback'] = $model->getError();
                 header("Location: /admin/edit?id={$id}");
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
        $limit = AppHelper::instance()->ItemsPerPage();
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
        $post = $model->getPost($id);
        if ( ! $post ) {
           throw new NotFoundException("couldn't find requested post" . $id);
        }
        
        $this->render('edit.html.twig', array(
           'post'=>$post,
            'title'=>'Редактировать запись',
        ));
    }
    
    
}
