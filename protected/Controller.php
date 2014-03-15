<?php

abstract class Controller {

    // protected $layout="main"; uncomment this line if don't use any template engine 

    protected $data = array();

    //public function __construct() { }

    protected function doExecute($action = null) {
        if ($action) {
            if (method_exists($this, $action)) {
                return $this->$action();
            } else {
                throw new NotFoundException("couldn't found requested action" . $action);
            }
        } else {
            return $this->indexAction();
        }
    }

    protected function prepare() {
        session_start();
        return $this;
    }

    public function execute($action) {
        $this->prepare()->doExecute($action);
    }

    abstract protected function indexAction();

    protected function getFeedback() {
        if (isset($_SESSION['feedback'])) {
            $this->data['feedback'] = array(
                'message'=>$_SESSION['feedback']['message'],
                'error'=>$_SESSION['feedback']['error']);
            unset($_SESSION['feedback']);
        }
        return $this->data;
    }
    
    protected function setFeedback($message, $error=false) {
        $_SESSION['feedback']['error'] = $error;
        $_SESSION['feedback']['message'] = $message;
    }

    final protected function isFilled(Array $required) {
        foreach ($required as $field) {
            $val = trim($_REQUEST[$field]);
            if (empty($val)) {
                return false;
            }
        }
        return true;
    }

    protected function render($template, $data = null) {
        $essential = $this->getFeedback();
        if (is_array($data)) {
            $data = array_merge($data, $essential);
        } elseif (is_null($data)) {
            $data = $essential;
        } else {
            throw new Exception("Argument 2 passed to method" . __CLASS__ . "::render isn't array! Array expected");
        }
        echo AppHelper::twig()->render($template, $data);
    }

    protected function viewAction() {
        $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
        //var_dump($id);
        //exit();
        $model = new PostManager();
        $post = $model->getPost($id);
        $commentHandler = new CommentManager;
        $comments = $commentHandler->getAllComments($id);
        if (!$post) {
            throw new NotFoundException("couldn't found requested post" . $id);
            //echo "not found!";
        }

        require_once('recaptchalib.php');
        $publickey = "6LdBU-8SAAAAAMcosmNtVcdNq03HBNWaO5YmHByT";
        $recaptcha = recaptcha_get_html($publickey);
        //echo recaptcha_get_html($publickey);

        $status = ($this instanceof AdminController) ? null : PostManager::PUBLISHED;
        $categories = $model->getCategories($status);

        $cloud = $model->getTagCloud();
        
        $commentHandler = new CommentManager();
        $latest = $commentHandler->getLatest();
        // var_dump($cloud);
        //exit();
        //echo $parsedown->parse('Hello _Parsedown_!'); # prints: <p>Hello <em>Parsedown</em>!</p>
        //exit();
        $this->render('post.html.twig', array(
            'post' => $post,
            'comments' => $comments,
            'categories' => $categories,
            'cloud' => $cloud,
            'latest'=>$latest,
            //'beginingHtml'=>$beginingHtml,
            //'endingHtml'=>$endingHtml,
            'recaptcha' => $recaptcha));
    }

    /*
     * don't need it if we use Twig
      protected function render($view, array $data, $layout=null)
      {
      if (is_array($data)) {
      extract($data);
      }
      if ($layout) {
      $this->layout = $layout;
      }
      $content = $view;
      $sep = DIRECTORY_SEPARATOR;
      include "views{$sep}layouts{$sep}{$this->layout}.php";
      }
     * 
     */
}
