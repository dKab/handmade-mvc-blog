<?php
class IndexController extends Controller
{
    protected function indexAction()
    {
        return $this->listAction();
    }
    
    protected function listAction()
    {
        $params = array();
        //$tag = AppHelper::instance()->getRequest()->properties['tag'];
        $tag = filter_input(INPUT_GET, "tag", FILTER_SANITIZE_STRING);
        $model = new PostManager();
        if ( $tag ) {  
            $posts = $model->hasTag($tag);
            $title = "Записи с тэгом {$tag}";
            $params['header'] = $title;
        } else {
            $posts = $model->getPublished();
            $title = "Все записи";
        }
        list($params['title'], $params['posts']) = array($title, $posts);
        $this->render("posts.html.twig", $params); 
    }
    
    protected function loginAction()
    {
        $this->render('login.html.twig', array(
            'title'=>'Вход',
        ));
    }
    
    protected function authAction()
    {
        $model= new PostManager();
        $guard = new AuthManager();
        
        //$name = AppHelper::getRequest()->getProperty('name');
        //$password = AppHelper::getRequest()->getProperty('password');
        if ( ! $this->isFilled(array('name', 'password')) ) {
            $_SESSION['feedback'] = "Все поля обязательны для заполенения";
            header("Location: /index/login");
            exit();
        }
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
