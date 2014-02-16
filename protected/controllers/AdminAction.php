<?php
class AdminAction extends Action
{
    public function execute()
    {
        session_start();
        $model= new PostManager();
        $guard = new AuthManager();
        
        $name = AppHelper::getRequest()->getProperty('name');
        $password = AppHelper::getRequest()->getProperty('password');
        $user = $guard->login(array(
            'name'=>$name,
            'password'=>$password,
                ));
        if ( $user ) {
            $_SESSION['user'] = $user;
            $title = "Управление записями";
            $posts = $model->getAllPosts();
            echo AppHelper::twig()->render('posts.html.twig', array(
                'title'=>$title,
                'user'=>$user,
                'posts'=>$posts,
            ));
        } else {
            $_SESSION['feedback'] = "Неверное имя пользователя или пароль!";
            header("Location: login");
        }
    }
}
