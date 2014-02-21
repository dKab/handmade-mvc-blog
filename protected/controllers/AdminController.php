<?php

class AdminController extends Controller
{
    protected function indexAction()
    {
        return $this->listAction();
    }
    
    protected function listAction()
    {
        
        $model = new PostManager();
        $posts = $model->getAllPosts();
        $this->render("posts.html.twig", array( 
            'title'=>'Все записи',
            'posts'=>$posts,
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
                header("Location: /admin/add/");
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
         //var_dump($input);
         $model = new PostManager();
         $id = $model->addPost($input);
         if ( ! $id ) {
             $_SESSION['feedback'] = $model->getError();
             header("Location: /admin/add/");
             exit();
         } else {
             //render
             //echo "success!!";
             header("Location: /admin/view?id={$id}");
         }
    }
    
    
}
