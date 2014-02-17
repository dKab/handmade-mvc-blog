<?php
abstract class Controller
{
    // protected $layout="main"; uncomment this line if don't use any template engine 
            
    final public function __construct() { }
    
    public function execute($action=null)
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
    
    abstract protected function indexAction();
    
    final protected function getFeedback()
    {
        //session_start();
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
        $empty = array();
        foreach($required as $field) {
          if ( empty($_REQUEST[$field]) ) {
            $empty[]=$field;
          }
        }
        if ( ! empty($empty) ) {
          return false;
        } else {
            return true;
        }
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

