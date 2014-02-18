<?php
abstract class Controller
{
    // protected $layout="main"; uncomment this line if don't use any template engine 
            
    final public function __construct() { }
    
    protected function doExecute($action=null)
    {
        if ( $action ) {
            if ( method_exists($this, $action) ) {
                return $this->$action();
            } else {
                throw new NotFoundException("couldn't found requested action" . $action);
            }
            
        } else {
            return $this->indexAction();
        }
    }
    
    protected function prepare()
    {
        session_start();
        return $this;
    }
    
    public function execute($action)
    {
        $this->prepare()->doExecute($action);
    }
    
    abstract protected function indexAction();
    
    final protected function getFeedback()
    {
        $data = array();
        if ($this instanceof AdminController) {
           $data['user'] = $_SESSION['user'];
        }
        if ( isset($_SESSION['feedback']) ) {
            $data['feedback'] = $_SESSION['feedback'];
            unset($_SESSION['feedback']);
        }
        return $data;
    }
    
    final protected function isFilled(Array $required)
    {
        foreach($required as $field) {
            $val = trim($_REQUEST[$field]);
          if ( empty($val) ) {
           return false;
          }
        }
        return true;
    }
    
    protected function render($template, $data=null)
    {
        $essential = $this->getFeedback();
        if ( is_array($data) ) {
            $data = array_merge($data, $essential);
        } elseif ( is_null($data) ) {
            $data = $essential;
        } else {
            throw new Exception("Argument 2 passed to method" . __CLASS__ . "::render() isn't array! Array expected");
        }
        echo AppHelper::twig()->render($template, $data);
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

