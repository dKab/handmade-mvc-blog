<?php
class IndexController extends Controller
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
        $data = $this->getFeedback();
        $data['title'] = 'Вход';
        echo AppHelper::twig()->render('login.html.twig', $data);
    }
    
    protected function authAction()
    {
        session_start();
        $model= new PostManager();
        $guard = new AuthManager();
        
        //$name = AppHelper::getRequest()->getProperty('name');
        //$password = AppHelper::getRequest()->getProperty('password');
        if ( ! $this->isFilled(array('name', 'password')) ) {
            $_SESSION['feedback'] = "Все поля обязательны для заполенения";
            header("Location: /index/login");
            exit();
        }
        //echo "<pre>";
        //var_dump($_REQUEST);
       // echo "</pre>";
        $name = $_POST['name'];
        $password = $_POST['password'];
        $user = $guard->login(array(
            'name'=>$name,
            'password'=>$password,
                ));
        if ( $user ) {
            $_SESSION['user'] = $user;
            header('Location: /admin');
            exit();
        } else {
            $_SESSION['feedback'] = "Неверное имя пользователя или пароль!";
            header("Location: /index/login");
            exit();
        }
    }
}
