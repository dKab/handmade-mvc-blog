<?php
class SiteController extends Controller
{
    protected function indexAction()
    {
        $model = new PostManager();
        $posts = $model->getPublished();
        echo AppHelper::twig()->render("posts.html.twig", array( 
            'title'=>'Все записи',
            'posts'=>$posts,
            ));
    }
    protected function loginAction()
    {
        session_start();
        $data = array();
        if ( isset($_SESSION['feedback']) ) {
            $data['feedback'] = $_SESSION['feedback'];
            unset($_SESSION['feedback']);
        }
        echo AppHelper::twig()->render('login.html.twig', $data);
    }
}
