<?php

class AdminController extends Controller
{
    protected function indexAction()
    {
        $model = new PostManager();
        $posts = $model->getAllPosts();
        echo AppHelper::twig()->render("posts.html.twig", array( 
            'title'=>'Все записи',
            'posts'=>$posts,
            'user'=>true,
            ));
    }
    
    public function execute($action=null)
    {
        if ( $this->isAdmin() ) {
            parent::execute($action);
        } else {
            header('Location: /site/login/');
            exit();
        }
    }
    
    private function isAdmin()
    {
        session_start();
        if ( isset($_SESSION['user']) ) {
            return true;    
        } else { return false; }
    }
    
    protected function logoutAction()
    {
        //session_start();
        unset($_SESSION['user']);
        header('Location: /');
        exit();
    }
    
    protected function addAction()
    {
        $data = $this->getFeedback();
        $data['title'] = 'Новая запись';
        echo AppHelper::twig()->render('add_post.html.twig', $data);
    }
    
    protected function storeAction()
    {
        
        
    }
}
