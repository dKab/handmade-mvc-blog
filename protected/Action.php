<?php
abstract class Action
{
    // protected $layout="main"; uncomment this line if don't use any template engine 
            
    final function __construct() { }
    
    abstract function execute();
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

