<?php
class IndexController extends Controller
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
            $posts = $model->hasTag($tag, PostManager::PUBLISHED);
            $title = "Записи с тэгом '{$tag}'";
        } else {
            $posts = $model->getPublished();
            $title = "Все записи";
        }
       // $parsedown = new Parsedown();
        //var_dump($parsedown);
        
        //$beginingHtml = $parsedown->parse($post['begining']);
        
        $this->render("posts.html.twig", array(
            'posts'=>$posts,
            'title'=>$title,
            //'beginingHtml'=>$beginingHtml,
        )); 
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
    
    protected function commentAction()
    {
        $id = filter_input(INPUT_POST, 'postId', FILTER_VALIDATE_INT);
        if ( ! $this->isFilled(array('name', 'email', 'body', 'postId') ) ) {
           $_SESSION['feedback'] = "Поля со звёздочкой обязательны";
            header("Location: /index/view?id={$id}");
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
            header("Location: /index/view?id={$id}");
        exit();
        }
        $input = filter_input_array(INPUT_POST, array(
                'email'=>array(
                    'filter'=> FILTER_VALIDATE_EMAIL,
                 ),
                 'notify'=>array(
                     'filter'=> FILTER_VALIDATE_BOOLEAN
                     ),
                  'parentId'=>array(
                      'filter'=>FILTER_VALIDATE_INT,
                      'flags'=>FILTER_NULL_ON_FAILURE,
                  )
            ));
        $fields = array('name', 'body', 'email', 'notify', 'postId', 'parentId');
        $post = array();
         array_walk($_POST, function($val, $key) use (&$post, $fields) {
            if ( in_array($key, $fields)) {
                $post[$key]=$val;
            }
        });
        $comment= array_merge($post, $input);

        if ( ! $input['email'] ) {
            $_SESSION['feedback'] = "Еmail должен быть корректным е-mail адресом";
            header("Location: /index/view?id={$id}");
            exit();
        }
        
        $model = new CommentManager();
        try {
            $commentId = $model->addComment($comment);
            $message = ( (bool)(string) AppHelper::instance()->getCommentRule() ) ? 
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
