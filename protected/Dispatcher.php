<?php
class Dispatcher
{
    private static $defaultController;
    
    public function __construct()
    {
        self::$defaultController = new IndexController();
    }
    
    public function getController(Request $request)
    {
        $route = $request->getRoute();
        if ( ! empty($route) ) {
           $controller = $route[0];
        }
        $sep = DIRECTORY_SEPARATOR;
        if ( ! isset($controller) ) {
            return self::$defaultController;
        }
        $classname=  ucfirst($controller) . "Controller";
        $filepath="protected{$sep}controllers{$sep}" . $classname . ".php";
        if (file_exists($filepath)) {
            require_once $filepath;
            if ( class_exists($classname) ) {
                $controller = new $classname();
                if ($controller instanceof Controller ) {
                    return $controller;
                } else {
                    throw new Exception("$classname isn't instance of Controller");
                }  
            } else {
                //return false;
                throw new Exception("couldn't find $classname class");
                //TODO redirect to 404 page
            }
        }
        throw new NotFoundException("could found requested controller");
    }
    
    public function getAction(Request $request)
    {
        $route = $request->getRoute();
        if ( count($route) > 1 ) {
               $action = $route[1];
        } else {
            $action = 'index';
        }
        $action .= "Action";
        return $action;
    }
}

