<?php

abstract class Controller {

    protected $data = array();

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
        $model = new PostManager();
        $post = $model->getPost($id);
        $commentHandler = new CommentManager;
        $comments = $commentHandler->getAllComments($id);
        if (!$post) {
            throw new NotFoundException("couldn't found requested post" . $id);
        }

        require_once('recaptchalib.php');
        $publickey = "6LdBU-8SAAAAAMcosmNtVcdNq03HBNWaO5YmHByT";
        $recaptcha = recaptcha_get_html($publickey);

        $status = ($this instanceof AdminController) ? null : PostManager::PUBLISHED;
        $categories = $model->getCategories($status);

        $cloud = $model->getTagCloud();
        
        $commentHandler = new CommentManager();
        $latest = $commentHandler->getLatest();
        $this->render('post.html.twig', array(
            'post' => $post,
            'comments' => $comments,
            'categories' => $categories,
            'cloud' => $cloud,
            'latest'=>$latest,
            'recaptcha' => $recaptcha));
    }
}
