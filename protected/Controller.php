<?php
abstract class Controller
{
    // protected $layout="main"; uncomment this line if don't use any template engine 
            
    final public function __construct() { }
    
    final public function execute($action=null)
    {
        if ( $action ) {
            if ( method_exists($this, $action) ) {
                return $this->$action();
            } else {
                throw new NotFoundExceptionException("couldn't found requested action");
            }
            
        } else {
            return $this->indexAction();
        }
    }
    
    abstract protected function indexAction();
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

